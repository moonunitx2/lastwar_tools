<?php
include 'db.php';
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Function to check if the user is on a mobile device
function isMobile() {
    return preg_match('/(android|iphone|ipad|ipod|mobile|blackberry|iemobile|opera mini)/i', $_SERVER['HTTP_USER_AGENT']);
}

// Initialize variables
$biggest_mover = null;
$alliances = [];

// Determine the sorting column
$sort_column = isset($_GET['sort']) ? $_GET['sort'] : 'ranking'; // Default to sorting by ranking

// Determine the sorting direction
if (isset($_GET['direction'])) {
    $sort_direction = $_GET['direction'] === 'desc' ? 'DESC' : 'ASC';
} else {
    if ($sort_column === 'points_difference' || $sort_column === 'gift_lvl') {
        $sort_direction = 'DESC';  // Default to descending for performance-related columns
    } else {
        $sort_direction = 'ASC';  // Default to ascending for ranking
    }
}

// Validate the sorting column
$allowed_columns = ['ranking', 'points_difference', 'gift_lvl'];
if (!in_array($sort_column, $allowed_columns)) {
    $sort_column = 'ranking';
}

// First, filter the top 10 alliances based on ranking
$sql = "SELECT * FROM alliances WHERE ranking <= 10 ORDER BY $sort_column $sort_direction";
$result = $conn->query($sql);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $alliances[] = $row;
        if ($biggest_mover === null || $row['points_difference'] > $biggest_mover['points_difference']) {
            $biggest_mover = $row;
        }
    }
} else {
    die("Database query failed: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alliance Power Rankings</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

    <style>
        .like-button i {
            font-size: 1.2rem;
            color: #FFD700; /* Gold color for the icon */
        }

        .like-button i:hover {
            color: #FFC107; /* Slightly darker gold on hover */
        }

        body {
            font-family: Arial, sans-serif;
            background-color: #1a1a1a; /* Dark background */
            color: #fff;
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
            margin: 0;
        }
        .container {
            width: <?php echo isMobile() ? '100%' : '80%'; ?>;
            background-color: #2c2c2c; /* Dark container background */
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(255, 255, 255, 0.1); /* Light shadow */
            margin-top: 20px;
        }
        h2 {
            text-align: center;
            color: #FFD700; /* Gold color */
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table, th, td {
            border: 1px solid #444; /* Darker borders */
        }
        th, td {
            padding: 10px;
            text-align: center;
        }
        th {
            background-color: #333; /* Darker header background */
            color: #FFD700; /* Gold text for headers */
        }
        td {
            color: #ddd; /* Light grey for table cells for better readability */
        }
        .like-button {
            background-color: #FFD700;
            color: white;
            border: none;
            padding: 5px 10px;
            cursor: pointer;
            border-radius: 4px;
        }
        .like-button:hover {
            background-color: #FFC107; /* Darker gold on hover */
        }
        .arrow-up {
            color: #00FF00; /* Bright green */
            font-weight: bold;
        }
        .arrow-down {
            color: #FF4500; /* Bright red */
            font-weight: bold;
        }
        .difference-up {
            color: #00FF00;
            font-weight: bold;
        }
        .difference-down {
            color: #FF4500;
            font-weight: bold;
        }

        /* Styling for biggest mover star */
        .biggest-mover-star {
            color: #FFD700; /* Gold color for the star */
            font-size: 1.2rem;
        }

        /* Mobile specific styles */
        @media only screen and (max-width: 600px) {
            th, td {
                padding: 5px;
                font-size: 12px;
            }
        }

        /* Button for View History */
        .history-button {
            display: inline-block;
            padding: 10px 20px;
            background-color: #FFD700;
            color: #1a1a1a;
            text-align: center;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            margin-top: 20px;
        }
        .history-button:hover {
            background-color: #FFC107; /* Darker gold on hover */
            color: #fff;
        }

    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
</head>
<body>
    <div class="container">
        <h2>Alliance Power Rankings</h2>
        <?php if ($biggest_mover): ?>
            <p><center>The biggest mover is <strong><?php echo $biggest_mover['full_name']; ?></strong> with a difference of <strong><?php echo number_format($biggest_mover['points_difference']); ?></strong>.</center></p>
        <?php endif; ?>

        <table>
            <thead>
                <tr>
                    <th><a href="?sort=ranking&direction=<?php echo $sort_column == 'ranking' && $sort_direction == 'ASC' ? 'desc' : 'asc'; ?>" style="color: #FFD700;">Ranking</a></th>
                    <th>Alliance Name</th>
                    <th>Power</th>
                    <th>Points Dif</th>
                    <th>Moved</th>
                    <th><a href="?sort=points_difference&direction=<?php echo $sort_column == 'points_difference' && $sort_direction == 'DESC' ? 'asc' : 'desc'; ?>" style="color: #FFD700;">Difference</a></th>
                    <th><a href="?sort=gift_lvl&direction=<?php echo $sort_column == 'gift_lvl' && $sort_direction == 'DESC' ? 'asc' : 'desc'; ?>" style="color: #FFD700;">Gift Lvl</a></th>
                    <th>Likes</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($alliances as $row): ?>
                <tr>
                    <td>
                        <?php echo $row['ranking']; ?>
                        <?php if ($row['id'] == $biggest_mover['id']): ?>
                            <span class="biggest-mover-star">★</span> <!-- Add golden star for biggest mover -->
                        <?php endif; ?>
                    </td>
                    <td><?php echo $row['full_name']; ?></td>
                    <td>
                        <?php
                            $power_display = $row['power'] >= 1000000000 ? number_format($row['power'] / 1000000000, 2) . 'B' : number_format($row['power'] / 1000000, 2) . 'M';
                            echo $power_display;
                        ?>
                    </td>
                    <td>
                        <?php
                            $points_diff = $row['power'] - $alliances[0]['power'];
                            $points_diff_display = $points_diff >= 1000000000 ? number_format($points_diff / 1000000000, 2) . 'B' : number_format($points_diff / 1000000, 2) . 'M';
                            echo $points_diff_display;
                        ?>
                    </td>
                    <td>
                        <?php
                            if ($row['ranking'] < $row['previous_ranking']) {
                                echo "<span class='arrow-up'>&uarr;</span>";
                            } elseif ($row['ranking'] > $row['previous_ranking']) {
                                echo "<span class='arrow-down'>&darr;</span>";
                            } else {
                                echo "<span>&rarr;</span>";
                            }
                        ?>
                    </td>
                    <td>
                        <?php
                            $difference_display = $row['points_difference'] >= 1000000000 ? number_format($row['points_difference'] / 1000000000, 2) . 'B' : number_format($row['points_difference'] / 1000000, 2) . 'M';
                            if ($row['points_difference'] > 0) {
                                echo "<span class='difference-up'>+" . $difference_display . "</span>";
                            } elseif ($row['points_difference'] < 0) {
                                echo "<span class='difference-down'>" . $difference_display . "</span>";
                            } else {
                                echo "<span>" . $difference_display . "</span>";
                            }
                        ?>
                    </td>
                    <td><?php echo $row['gift_lvl']; ?></td>
                    <td>
                        <?php echo $row['likes']; ?>
                        <form method="post" action="like.php" style="display:inline;">
                            <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
                            <button type="submit" class="like-button" style="background:none; border:none; cursor:pointer;">
                                <i class="fas fa-thumbs-up"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <p>Last updated: <?php
            $last_update_result = $conn->query("SELECT MAX(last_updated) as last_updated FROM alliances");
            $last_updated = $last_update_result->fetch_assoc()['last_updated'];
            echo date('d.m.y \a\t H:i', strtotime($last_updated));
        ?></p>

        <!-- View History Button -->
        <a href="history.php" target="_newwindow" class="history-button">View History</a>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

    <script>
        <?php if (isset($_SESSION['error'])): ?>
            toastr.error('<?php echo $_SESSION['error']; unset($_SESSION['error']); ?>', 'Error', {
                timeOut: 5000,
                closeButton: true,
                positionClass: 'toast-bottom-right'
            });
        <?php elseif (isset($_SESSION['success'])): ?>
            toastr.success('<?php echo $_SESSION['success']; unset($_SESSION['success']); ?>', 'Success', {
                timeOut: 3000,
                closeButton: true,
                positionClass: 'toast-bottom-right'
            });
        <?php endif; ?>
    </script>
</body>
</html>
