<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!file_exists('vendor/autoload.php')) {
   die('Composer autoload.php not found. Please run composer install');
}

require 'vendor/autoload.php';

if (!file_exists('db.php')) {
   die('db.php not found');
}

include 'db.php';

if (!class_exists('OpenAI')) {
   die('OpenAI class not found. Please check if the package is installed correctly');
}

define('OPENAI_API_KEY', 'your-api-key-here');

function generateShortName($full_name) {
   $words = explode(' ', strtoupper($full_name));
   $shortName = '';
   
   foreach ($words as $word) {
       if (!empty($word)) {
           $shortName .= $word[0];
       }
   }
   
   if (strlen($shortName) < 3 && !empty($words[0])) {
       $shortName = substr($words[0], 0, 3);
   }
   
   return $shortName;
}

function getAlliancesFromDatabase($conn) {
   $alliances = [];
   $stmt = $conn->prepare("SELECT id, short_name, full_name, power, ranking FROM alliances");
   $stmt->execute();
   $result = $stmt->get_result();
   while ($row = $result->fetch_assoc()) {
       $alliances[strtolower(trim($row['full_name']))] = $row;
   }
   $stmt->close();
   return $alliances;
}

function mergeAndRankAlliances($extracted_data, $database_data) {
   $all_alliances = [];
   
   foreach ($extracted_data as $alliance) {
       $found = false;
       $extracted_name = strtolower(trim($alliance['full_name']));
       
       if (isset($database_data[$extracted_name])) {
           $db_alliance = $database_data[$extracted_name];
           $all_alliances[$extracted_name] = [
               'full_name' => $db_alliance['full_name'],
               'short_name' => $db_alliance['short_name'],
               'power' => $alliance['power'],
               'source' => 'update',
               'id' => $db_alliance['id']
           ];
           $found = true;
       }
       
       if (!$found) {
           foreach ($database_data as $db_name => $db_alliance) {
               if (levenshtein($extracted_name, $db_name) <= 2) {
                   $all_alliances[$db_name] = [
                       'full_name' => $db_alliance['full_name'],
                       'short_name' => $db_alliance['short_name'],
                       'power' => $alliance['power'],
                       'source' => 'update',
                       'id' => $db_alliance['id']
                   ];
                   $found = true;
                   break;
               }
           }
       }
       
       if (!$found) {
           $short_name = generateShortName($alliance['full_name']);
           $all_alliances[$extracted_name] = [
               'full_name' => $alliance['full_name'],
               'short_name' => $short_name,
               'power' => $alliance['power'],
               'source' => 'new'
           ];
       }
   }
   
   foreach ($database_data as $db_name => $alliance) {
       if (!isset($all_alliances[$db_name])) {
           $all_alliances[$db_name] = [
               'full_name' => $alliance['full_name'],
               'short_name' => $alliance['short_name'],
               'power' => $alliance['power'],
               'source' => 'existing',
               'id' => $alliance['id']
           ];
       }
   }
   
   uasort($all_alliances, function($a, $b) {
       return $b['power'] - $a['power'];
   });
   
   $rank = 1;
   $ranked_alliances = [];
   foreach ($all_alliances as $alliance) {
       $alliance['ranking'] = $rank++;
       $ranked_alliances[] = $alliance;
   }
   
   return $ranked_alliances;
}

function analyzeImageWithChatGPT($image_path) {
   try {
       if (!file_exists($image_path)) {
           throw new Exception("Image file not found at: " . $image_path);
       }
       
       $image_data = file_get_contents($image_path);
       if ($image_data === false) {
           throw new Exception("Could not read image file");
       }
       
       $base64_image = base64_encode($image_data);
       $client = OpenAI::client(OPENAI_API_KEY);
       
       $response = $client->chat()->create([
           'model' => 'gpt-4-o',
           'messages' => [[
               'role' => 'user',
               'content' => [
                   [
                       'type' => 'text',
                       'text' => 'Have a look at the image, extract the alliance names, don\'t include the short names inside [], then list them with scores highest to lowest. Format each line as "Alliance Name: Score". Only return the list, nothing else.'
                   ],
                   [
                       'type' => 'image_url',
                       'image_url' => [
                           'url' => "data:image/jpeg;base64,{$base64_image}"
                       ]
                   ]
               ]
           ]]
       ]);
       
       $content = $response->choices[0]->message->content;
       
       $log_entry = date('Y-m-d H:i:s') . " - Raw GPT Response:\n" . $content . "\n\n";
       file_put_contents('ocr.log', $log_entry, FILE_APPEND);
       
       $lines = explode("\n", $content);
       $extracted_data = [];
       
       foreach ($lines as $line) {
           if (preg_match('/(?:\d+\.\s*)?(.+):\s*([\d,]+)/', $line, $matches)) {
               $extracted_data[] = [
                   'full_name' => trim($matches[1]),
                   'power' => intval(str_replace(',', '', $matches[2]))
               ];
           }
       }

       $log_entry = date('Y-m-d H:i:s') . " - Parsed Data:\n" . print_r($extracted_data, true) . "\n\n";
       file_put_contents('ocr.log', $log_entry, FILE_APPEND);

       return $extracted_data;
       
   } catch (Exception $e) {
       $error_entry = date('Y-m-d H:i:s') . " - Error: " . $e->getMessage() . "\n" . 
                     "Stack trace:\n" . $e->getTraceAsString() . "\n\n";
       file_put_contents('ocr.log', $error_entry, FILE_APPEND);
       error_log("Error in analyzeImageWithChatGPT: " . $e->getMessage());
       return null;
   }
}
?>

<!DOCTYPE html>
<html>
<head>
   <title>Alliance Rankings OCR</title>
   <style>
       body {
           font-family: Arial, sans-serif;
           background-color: #1a1a1a;
           color: #fff;
           display: flex;
           justify-content: center;
           align-items: center;
           flex-direction: column;
           margin: 0;
       }

       .container {
           width: 80%;
           background-color: #2c2c2c;
           padding: 20px;
           border-radius: 10px;
           box-shadow: 0 0 10px rgba(255, 255, 255, 0.1);
           margin-top: 20px;
       }

       h1, h2 {
           text-align: center;
           color: #FFD700;
       }

       table {
           width: 100%;
           border-collapse: collapse;
           margin-bottom: 20px;
           background-color: #333333;
       }

       table, th, td {
           border: 1px solid #444;
       }

       th {
           background-color: #333333;
           color: #FFD700;
           padding: 10px;
       }

       td {
           padding: 10px;
           color: #fff;
           text-align: center;
       }

       .file-upload-wrapper {
           position: relative;
           display: inline-block;
           margin-right: 10px;
       }

       .file-upload-wrapper input[type="file"] {
           display: none;
       }

       .file-upload-button {
           background-color: #FFD700;
           color: #1a1a1a;
           padding: 10px 20px;
           border: none;
           border-radius: 5px;
           font-size: 18px;
           cursor: pointer;
           transition: background-color 0.3s ease;
       }

       .file-upload-button:hover {
           background-color: #FFC107;
       }

       .submit-button {
           background-color: #FFD700;
           color: #1a1a1a;
           padding: 10px 20px;
           border: none;
           border-radius: 5px;
           font-size: 18px;
           cursor: pointer;
           transition: background-color 0.3s ease;
       }

       .submit-button:hover {
           background-color: #FFC107;
       }

       tr.new-entry {
           background-color: #0d280d;
       }

       tr.update-entry {
           background-color: #28281e;
       }

       tr.existing-entry {
           background-color: #333333;
       }

       td:last-child {
           font-weight: bold;
       }

       tr.new-entry td:last-child {
           color: #90EE90;
       }

       tr.update-entry td:last-child {
           color: #FFD700;
       }

       tr.existing-entry td:last-child {
           color: #808080;
       }
   </style>
</head>
<body>
   <div class="container">
       <h1>Alliance Rankings OCR</h1>
       <div class="upload-form">
           <form method="post" enctype="multipart/form-data">
               <div class="file-upload-wrapper">
                   <label class="file-upload-button">
                       <input type="file" name="image" accept="image/*" required onchange="updateFileName(this)">
                       Choose File
                   </label>
                   <span id="file-name"></span>
               </div>
               <input type="submit" value="Upload and Extract Data" class="submit-button">
           </form>
       </div>
       
       <?php
       if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['image'])) {
           $target_dir = "uploads/";
           if (!is_dir($target_dir)) {
               mkdir($target_dir, 0777, true);
           }
           
           $target_file = $target_dir . basename($_FILES["image"]["name"]);
           
           if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
               $extracted_data = analyzeImageWithChatGPT($target_file);
               
               if ($extracted_data) {
                   $database_data = getAlliancesFromDatabase($conn);
                   $ranked_alliances = mergeAndRankAlliances($extracted_data, $database_data);
                   
                   if (!empty($ranked_alliances)) {
                       echo "<h2>Complete Alliance Rankings</h2>";
                       echo "<table>
                               <tr>
                                   <th>Rank</th>
                                   <th>Short Name</th>
                                   <th>Alliance Name</th>
                                   <th>Power</th>
                                   <th>Action</th>
                               </tr>";
                       
                       foreach ($ranked_alliances as $alliance) {
                           $rowClass = $alliance['source'] == 'new' ? 'new-entry' : 
                                     ($alliance['source'] == 'update' ? 'update-entry' : 'existing-entry');
                           $action = $alliance['source'] == 'new' ? 'Create New Entry' : 
                                    ($alliance['source'] == 'update' ? 'Update' : 'No Change');
                           
                           echo "<tr class='{$rowClass}'>
                                   <td>{$alliance['ranking']}</td>
                                   <td>{$alliance['short_name']}</td>
                                   <td>{$alliance['full_name']}</td>
                                   <td>" . number_format($alliance['power']) . "</td>
                                   <td>{$action}</td>
                                 </tr>";
                       }
                       echo "</table>";

                       // Properly encode the data for transport
                       $json_data = json_encode($ranked_alliances, JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_UNESCAPED_UNICODE);
                       
                       if (json_last_error() !== JSON_ERROR_NONE) {
                           error_log("JSON encode error: " . json_last_error_msg());
                           echo "Error preparing data for submission";
                       } else {
                           echo "<form method='post' action='insert_data.php'>";
                           echo "<input type='hidden' name='data' value='" . htmlspecialchars($json_data, ENT_QUOTES, 'UTF-8') . "'>";
                           echo "<input type='submit' value='Confirm and Update Rankings' class='submit-button'>";
                           echo "</form>";
                       }
                   }
               } else {
                   echo "<div style='color: #FF4500; text-align: center; margin-top: 20px;'>Failed to extract data from image</div>";
               }
           }
       }
       ?>
   </div>
   <script>
       function updateFileName(input) {
           const fileName = input.files[0]?.name || 'No file chosen';
           document.getElementById('file-name').textContent = fileName;
       }
   </script>
</body>
</html>
