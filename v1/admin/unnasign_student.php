<?php
include '../config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['unassign_student'])) {
    $student_id = $_POST['student_id'];
    $room_id = $_POST['room_id'];
    
    try {
        $pdo->beginTransaction();
        
        // Unassign student
        $stmt = $pdo->prepare("UPDATE students SET room_id = NULL, assigned_date = NULL WHERE id = ?");
        $stmt->execute([$student_id]);
        
        // Update room occupancy
        $stmt = $pdo->prepare("UPDATE rooms SET current_occupancy = GREATEST(0, current_occupancy - 1) WHERE id = ?");
        $stmt->execute([$room_id]);
        
        // Update room status
        $stmt = $pdo->prepare("
            UPDATE rooms 
            SET status = CASE 
                WHEN current_occupancy = 0 THEN 'available'
                WHEN current_occupancy = capacity THEN 'occupied'
                ELSE 'partially_occupied'
            END 
            WHERE id = ?
        ");
        $stmt->execute([$room_id]);
        
        $pdo->commit();
        
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}
?>