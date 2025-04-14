<?php
session_start();
include 'db.php'; // Database connection

// Check if user is logged in and is an admin
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'admin') {
    header("Location: login_polls_admin.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $question = $_POST['question'];
    $end_date = $_POST['end_date'];
    $options = $_POST['options'];

    // Insert the poll question into the database
    $stmt = $conn->prepare("INSERT INTO polls (question, end_date) VALUES (?, ?)");
    $stmt->bind_param('ss', $question, $end_date);
    $stmt->execute();
    $poll_id = $stmt->insert_id;

    // Insert poll options into the poll_options table
    foreach ($options as $option) {
        $stmt = $conn->prepare("INSERT INTO poll_options (poll_id, option_text) VALUES (?, ?)");
        $stmt->bind_param('is', $poll_id, $option);
        $stmt->execute();
    }

    echo "Poll created successfully!";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create a New Poll</title>
<style>
        body {
            font-family: Arial, sans-serif;
            background-color: #2c2c2c;
            color: white;
        }
        label {
            color: white; /* Set label text to white */
        }
        input[type="text"], input[type="date"] {
            width: 100%;
            padding: 8px;
            margin-bottom: 10px;
            color: black;
        }
        button {
            color: black;
        }
    </style>
</head>
<body>
    <h2>Create a New Poll</h2>
    <form method="POST" action="polls_admin.php">
        <label for="question">Poll Question:</label>
        <input type="text" name="question" id="question" required><br><br>

        <label for="options">Poll Options:</label><br>
        <div id="options-container">
            <input type="text" name="options[]" required><br>
        </div>
        <button type="button" onclick="addOption()">Add another option</button><br><br>

        <label for="end_date">Poll End Date:</label>
        <input type="date" name="end_date" id="end_date" required><br><br>

        <button type="submit">Create Poll</button>
    </form>

    <script>
        function addOption() {
            var container = document.getElementById('options-container');
            var input = document.createElement('input');
            input.type = 'text';
            input.name = 'options[]';
            input.required = true;
            container.appendChild(input);
            container.appendChild(document.createElement('br'));
        }
    </script>
</body>
</html>
