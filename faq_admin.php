<?php
include 'db.php';
session_start();

// Simple password protection
if (!isset($_SESSION['faq_admin'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
        if ($_POST['password'] === 'SkippyIsAwesome') { // Replace with a secure password
            $_SESSION['faq_admin'] = true;
            header("Location: faq_admin.php"); // Redirect to avoid form resubmission
            exit;
        } else {
            echo "<p style='color:red;'>Invalid password!</p>";
        }
    }

    // Display login form if not authenticated
    if (!isset($_SESSION['faq_admin'])) {
        echo '<form method="post">
                <label for="password">Admin Password:</label>
                <input type="password" name="password" required>
                <button type="submit">Login</button>
              </form>';
        exit;
    }
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add') {
        $question = $conn->real_escape_string($_POST['question']);
        $answer = $conn->real_escape_string($_POST['answer']);
        $conn->query("INSERT INTO faq (question, answer) VALUES ('$question', '$answer')");
    } elseif ($_POST['action'] === 'edit') {
        $id = intval($_POST['id']);
        $question = $conn->real_escape_string($_POST['question']);
        $answer = $conn->real_escape_string($_POST['answer']);
        $conn->query("UPDATE faq SET question='$question', answer='$answer' WHERE id=$id");
    } elseif ($_POST['action'] === 'delete') {
        $id = intval($_POST['id']);
        $conn->query("DELETE FROM faq WHERE id=$id");
    }

    // Redirect to avoid form resubmission
    header("Location: faq_admin.php");
    exit;
}

// Retrieve FAQs
$faqs = $conn->query("SELECT * FROM faq ORDER BY id ASC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FAQ Admin</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #1a1a1a;
            color: #fff;
            padding: 20px;
        }

        .faq-admin-container {
            margin: auto;
            width: 80%;
        }

        .faq-form {
            margin-bottom: 20px;
        }

        .faq-form input, .faq-form textarea, .faq-form button {
            display: block;
            margin: 10px 0;
            width: 100%;
            padding: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        table, th, td {
            border: 1px solid #444;
        }

        th, td {
            padding: 10px;
        }

        th {
            background-color: #333;
        }
    </style>
</head>
<body>
    <div class="faq-admin-container">
        <h1>FAQ Admin</h1>
        <form class="faq-form" method="post">
            <input type="hidden" name="action" value="add">
            <input type="text" name="question" placeholder="Question" required>
            <textarea name="answer" placeholder="Answer" required></textarea>
            <button type="submit">Add FAQ</button>
        </form>

        <table>
            <thead>
                <tr>
                    <th>Question</th>
                    <th>Answer</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($faq = $faqs->fetch_assoc()): ?>
                <tr>
                    <form method="post">
                        <td><input type="text" name="question" value="<?php echo htmlspecialchars($faq['question']); ?>"></td>
                        <td><textarea name="answer"><?php echo htmlspecialchars($faq['answer']); ?></textarea></td>
                        <td>
                            <input type="hidden" name="id" value="<?php echo $faq['id']; ?>">
                            <button type="submit" name="action" value="edit">Save</button>
                            <button type="submit" name="action" value="delete">Delete</button>
                        </td>
                    </form>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
