<?php
include 'db.php';

$sql = "SELECT * FROM faq ORDER BY id ASC";
$result = $conn->query($sql);

if (!$result) {
    die("Error retrieving FAQs: " . $conn->error);
}
?>

<div class="faq-container">
    <?php while ($row = $result->fetch_assoc()): ?>
        <div class="faq-item">
            <!-- Display question prefixed with ">" and bold -->
            <div class="faq-question" onclick="toggleAnswer(this)">
                <strong>> <?php echo htmlspecialchars($row['question']); ?></strong>
            </div>
            <!-- Answer initially hidden -->
            <div class="faq-answer" style="display: none;">
                <p><?php echo htmlspecialchars($row['answer']); ?></p>
            </div>
            <br> <!-- Add spacing between questions -->
        </div>
    <?php endwhile; ?>
</div>

<script>
    function toggleAnswer(element) {
        const answer = element.nextElementSibling;
        // Toggle visibility
        if (answer.style.display === "none") {
            answer.style.display = "block";
        } else {
            answer.style.display = "none";
        }
    }
</script>
