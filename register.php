<?php
include('db.php');

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nickname = trim($_POST['nickname']);
    $discord = trim($_POST['discord']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    
    // Validate form inputs
    if (empty($nickname) || empty($discord) || empty($email) || empty($password) || empty($confirm_password)) {
        $errors[] = "All fields are required.";
    } elseif ($password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    } else {
        // Hash the password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Check if the nickname or email already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $nickname, $email);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows > 0) {
            $errors[] = "Nickname or email already exists.";
        } else {
            // Insert the new user into the database
            $stmt = $conn->prepare("INSERT INTO users (username, discord, email, password) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("ssss", $nickname, $discord, $email, $hashed_password);
            if ($stmt->execute()) {
                $success = "User created successfully.";
                // Redirect back to index.php after success
                echo "<script>
                        alert('User created successfully. You can now login.');
                        window.location.href = 'index.php';
                      </script>";
            } else {
                $errors[] = "There was an error creating your account. Please try again.";
            }
        }
        
        $stmt->close();
    }
}

?>

<?php if ($errors): ?>
    <div class="alert alert-danger">
        <ul>
            <?php foreach ($errors as $error): ?>
                <li><?= $error; ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form method="POST" action="register.php">
    <table class="table table-borderless">
        <tr>
            <td>In-Game Nick:</td>
            <td><input type="text" name="nickname" class="form-control transparent-input" required></td>
        </tr>
        <tr>
            <td>Discord ID:</td>
            <td><input type="text" name="discord" class="form-control transparent-input" required></td>
        </tr>
        <tr>
            <td>E-mail:</td>
            <td><input type="email" name="email" class="form-control transparent-input" required></td>
        </tr>
        <tr>
            <td>Password:</td>
            <td><input type="password" name="password" class="form-control transparent-input" required></td>
        </tr>
        <tr>
            <td>Confirm Password:</td>
            <td><input type="password" name="confirm_password" class="form-control transparent-input" required></td>
        </tr>
        <tr>
            <td colspan="2" class="text-center">
                <button type="submit" class="btn btn-warning">Register</button>
            </td>
        </tr>
    </table>
</form>
