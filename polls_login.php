<?php
include('db.php');
session_start();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nickname = trim($_POST['nickname']);
    $password = trim($_POST['password']);

    if (empty($nickname) || empty($password)) {
        $errors[] = "Both fields are required.";
    } else {
        // Check user credentials
        $stmt = $conn->prepare("SELECT id, username, password, role FROM users WHERE username = ?");
        $stmt->bind_param("s", $nickname);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['is_admin'] = ($user['role'] === 'admin');

                // Redirect to index.php
                header('Location: polls_admin.php');
                exit();
            } else {
                $errors[] = "Incorrect password.";
            }
        } else {
            $errors[] = "No user found with that nickname.";
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

<form method="POST" action="polls_login.php">
    <table class="table table-borderless">
        <tr>
            <td>In-Game Nick:</td>
            <td><input type="text" name="nickname" class="form-control transparent-input" required></td>
        </tr>
        <tr>
            <td>Password:</td>
            <td><input type="password" name="password" class="form-control transparent-input" required></td>
        </tr>
        <tr>
            <td colspan="2" class="text-center">
                <button type="submit" class="btn btn-warning">Login</button>
            </td>
        </tr>
    </table>
</form>
