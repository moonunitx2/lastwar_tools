<?php
include 'db.php';

session_start();

$id = $_POST['id'];
$user_ip = $_SERVER['REMOTE_ADDR'];

// Check if the user has already liked within the last 24 hours
$result = $conn->query("SELECT * FROM likes WHERE user_ip = '$user_ip' AND alliance_id = $id AND timestamp > NOW() - INTERVAL 1 DAY");

if ($result->num_rows > 0) {
    $_SESSION['error'] = "You've already liked this in the last 24 hours. Come on, don't be a filthy monkey!";
} else {
    // Insert or update the like
    $conn->query("INSERT INTO likes (user_ip, alliance_id, timestamp) VALUES ('$user_ip', $id, NOW())");

    // Update the likes count
    $conn->query("UPDATE alliances SET likes = likes + 1 WHERE id = $id");

    $_SESSION['success'] = "Thanks for your like!";
}

header('Location: index.php');
?>
