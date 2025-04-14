<?php
include '../db.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['data'])) {
        die("No data received");
    }

    $json_data = $_POST['data'];
    file_put_contents('debug.log', date('Y-m-d H:i:s') . " - Received JSON data:\n" . $json_data . "\n\n", FILE_APPEND);

    $ranked_alliances = json_decode($json_data, true, 512, JSON_UNESCAPED_UNICODE);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        die("JSON decode error: " . json_last_error_msg() . "\nReceived data: " . substr($json_data, 0, 1000));
    }

    if (!is_array($ranked_alliances)) {
        die("Decoded data is not an array");
    }

    file_put_contents('debug.log', date('Y-m-d H:i:s') . " - Processing alliances:\n" . print_r($ranked_alliances, true) . "\n\n", FILE_APPEND);

    foreach ($ranked_alliances as $alliance) {
        // Rest of your existing processing code
    }
} else {
    die("Invalid request method");
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['data'])) {
    $ranked_alliances = json_decode($_POST['data'], true);
    
    foreach ($ranked_alliances as $alliance) {
        if ($alliance['source'] == 'new') {
            // Insert new record
            $stmt = $conn->prepare("INSERT INTO alliances 
                (short_name, full_name, ranking, power, points_difference, previous_ranking, previous_power, moved_up_down) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 'same')");

            $points_difference = 0;
            $previous_ranking = $alliance['ranking'];
            $previous_power = $alliance['power'];
            
            $stmt->bind_param('ssiiiis',
                $alliance['short_name'],
                $alliance['full_name'],
                $alliance['ranking'],
                $alliance['power'],
                $points_difference,
                $previous_ranking,
                $previous_power
            );
            $stmt->execute();
            $alliance_id = $conn->insert_id;
            $stmt->close();
            
            // Insert into power history for new alliance
            $stmt = $conn->prepare("INSERT INTO alliance_power_history (alliance_id, power, date_recorded) VALUES (?, ?, NOW())");
            $stmt->bind_param('ii', $alliance_id, $alliance['power']);
            $stmt->execute();
            $stmt->close();

        } elseif ($alliance['source'] == 'update') {
            // Get previous data
            $stmt = $conn->prepare("SELECT id, ranking, power FROM alliances WHERE id = ?");
            $stmt->bind_param('i', $alliance['id']);
            $stmt->execute();
            $result = $stmt->get_result();
            $previous_data = $result->fetch_assoc();
            $stmt->close();

            if ($previous_data) {
                $points_difference = $alliance['power'] - $previous_data['power'];
                $moved_up_down = ($points_difference > 0) ? 'up' : (($points_difference < 0) ? 'down' : 'same');
                
                // Update existing record
                $stmt = $conn->prepare("UPDATE alliances SET 
                    ranking = ?,
                    power = ?,
                    points_difference = ?,
                    previous_ranking = ?,
                    previous_power = ?,
                    moved_up_down = ?
                    WHERE id = ?");
                
                $prev_ranking = $previous_data['ranking'];
                $prev_power = $previous_data['power'];
                $alliance_id = $previous_data['id'];
                
                $stmt->bind_param('iiiiisi',
                    $alliance['ranking'],
                    $alliance['power'],
                    $points_difference,
                    $prev_ranking,
                    $prev_power,
                    $moved_up_down,
                    $alliance_id
                );
                $stmt->execute();
                $stmt->close();
                
                // Insert into power history
                $stmt = $conn->prepare("INSERT INTO alliance_power_history (alliance_id, power, date_recorded) VALUES (?, ?, NOW())");
                $stmt->bind_param('ii', $alliance_id, $alliance['power']);
                $stmt->execute();
                $stmt->close();
            }
        }
    }
    
    echo "Rankings updated successfully.<br>";
    echo "<a href='index.php'>Return to main page</a>";
}
?>
