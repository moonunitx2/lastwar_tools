<?php
$host = "localhost";  // or the correct host for your database
$username = "your_db_user";  // Replace with your MySQL username
$password = "your_db_password";  // Replace with your MySQL password
$dbname = "your_db_name";  // Replace with your database name

$conn = new mysqli($host, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
