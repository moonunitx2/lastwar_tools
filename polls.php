<?php
include 'db.php'; // Database connection
session_start();

// Get the user's IP address and user agent
$user_ip = $_SERVER['REMOTE_ADDR'];
$user_agent = $_SERVER['HTTP_USER_AGENT'];

// Check if a user is logged in (for `user_id` field)
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

// Get the active poll
$poll_result = $conn->query("SELECT * FROM polls WHERE end_date >= CURDATE() ORDER BY created_at DESC LIMIT 1");
$poll = $poll_result->fetch_assoc();

if ($poll) {
    $poll_id = $poll['id'];
    $options_result = $conn->query("SELECT * FROM poll_options WHERE poll_id = $poll_id");
    $options = $options_result->fetch_all(MYSQLI_ASSOC);
    
    // Fix: Use current time instead of current date for comparison
    $poll_ended = (new DateTime($poll['end_date']) < new DateTime());

    // Check if the user has already voted in this poll
    $vote_check_result = $conn->prepare("SELECT * FROM poll_votes WHERE poll_id = ? AND user_ip = ?");
    $vote_check_result->bind_param('is', $poll_id, $user_ip);
    $vote_check_result->execute();
    $has_voted = $vote_check_result->get_result()->num_rows > 0;

    // Check if the user clicked "View Results" or if the poll has ended
    if (isset($_POST['view_results']) || $poll_ended || $has_voted) {
        // Skip voting and show the results directly
        $show_results = true;
    } else {
        $show_results = false;
    }

    // Handle the vote submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$poll_ended && isset($_POST['option']) && !$has_voted) {
        $selected_option = $_POST['option']; // Get the selected option

        // Insert the vote into the poll_votes table (logging the poll_id, option_id, user_ip, user_agent, and user_id)
        $stmt = $conn->prepare("INSERT INTO poll_votes (poll_id, option_id, user_ip, user_agent, user_id) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param('iissi', $poll_id, $selected_option, $user_ip, $user_agent, $user_id);
        if ($stmt->execute()) {
            // Increment the vote count for the selected option
            $update_stmt = $conn->prepare("UPDATE poll_options SET votes = votes + 1 WHERE id = ?");
            $update_stmt->bind_param('i', $selected_option);
            $update_stmt->execute();
        } else {
            die("Error logging vote: " . $stmt->error);
        }

        $show_results = true; // After voting, show results
    }

    // Count total votes
    $total_votes = 0;
    foreach ($options as $option) {
        $total_votes += $option['votes'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <!-- ... (head content remains the same) ... -->
</head>
<body>
    <div class="container">
    <?php if ($poll): ?>
        <h2><?php echo $poll['question']; ?></h2>

        <?php if (!$show_results): ?>
            <form method="POST" action="polls.php">
                <?php foreach ($options as $option): ?>
                    <input type="radio" name="option" value="<?php echo $option['id']; ?>" required>
                    <?php echo $option['option_text']; ?><br>
                <?php endforeach; ?>
                <br>
                <button type="submit">Submit Vote</button>
            </form>
            <br>
            <!-- Button to view results without voting -->
            <form method="POST" action="polls.php">
                <button type="submit" name="view_results">View Results</button>
            </form>

        <?php else: ?>
            <h3>Poll Results <?php if ($poll_ended) { echo '(Poll Closed)'; } ?></h3>
            <ul style="list-style-type:none;">
                <?php foreach ($options as $option): ?>
                    <?php
                    $percentage = ($total_votes > 0) ? round(($option['votes'] / $total_votes) * 100, 2) : 0;
                    ?>
                    <li>
                        <?php echo $option['option_text']; ?>: <?php echo $option['votes']; ?> votes
                        (<?php echo $percentage; ?>%)
                        <div class="bar">
                            <div class="bar-fill" style="width: <?php echo $percentage; ?>%;"></div>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    <?php else: ?>
        <p>No active poll available.</p>
    <?php endif; ?>
    </div>
</body>
</html>
