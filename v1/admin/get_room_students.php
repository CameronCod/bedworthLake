<?php
include '../config.php';

if (isset($_GET['room_id'])) {
    $room_id = $_GET['room_id'];
    
    $stmt = $pdo->prepare("
        SELECT s.* 
        FROM students s 
        WHERE s.room_id = ? 
        ORDER BY s.name
    ");
    $stmt->execute([$room_id]);
    $students = $stmt->fetchAll();
    
    if (empty($students)) {
        echo '<p>No students assigned to this room.</p>';
    } else {
        foreach ($students as $student) {
            echo '<div class="student-item">';
            echo '<div>';
            echo '<strong>' . htmlspecialchars($student['name']) . '</strong><br>';
            echo 'ID: ' . htmlspecialchars($student['student_id']) . ' | ';
            echo 'Email: ' . htmlspecialchars($student['email']) . ' | ';
            echo 'Phone: ' . htmlspecialchars($student['phone']);
            echo '</div>';
            if (isAdmin()) {
                echo '<button class="unassign-btn" onclick="unassignStudent(' . $student['id'] . ', ' . $room_id . ')">Unassign</button>';
            }
            echo '</div>';
        }
    }
}
?>