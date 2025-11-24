<?php
include '../config.php';
redirectIfNotLoggedIn();
if (!isAdmin()) header('Location: ../staff/dashboard.php');

// Handle student actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['assign_room'])) {
        $student_id = $_POST['student_id'];
        $room_id = $_POST['room_id'];
        
        // Update student room assignment
        $stmt = $pdo->prepare("UPDATE students SET room_id = ?, assigned_date = CURDATE() WHERE id = ?");
        $stmt->execute([$room_id, $student_id]);
        
        // Update room occupancy
        $stmt = $pdo->prepare("UPDATE rooms SET current_occupancy = current_occupancy + 1 WHERE id = ?");
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
        
        $success = "Student assigned to room successfully!";
    }
    
    if (isset($_POST['unassign_room'])) {
        $student_id = $_POST['student_id'];
        
        // Get current room_id before unassigning
        $stmt = $pdo->prepare("SELECT room_id FROM students WHERE id = ?");
        $stmt->execute([$student_id]);
        $student = $stmt->fetch();
        $room_id = $student['room_id'];
        
        // Unassign student
        $stmt = $pdo->prepare("UPDATE students SET room_id = NULL, assigned_date = NULL WHERE id = ?");
        $stmt->execute([$student_id]);
        
        // Update room occupancy
        if ($room_id) {
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
        }
        
        $success = "Student unassigned from room successfully!";
    }
}

// Search functionality
$search = $_GET['search'] ?? '';
$where = "WHERE 1=1";
$params = [];

if ($search) {
    $where .= " AND (s.name LIKE ? OR s.student_id LIKE ? OR s.email LIKE ? OR r.room_number LIKE ?)";
    $searchTerm = "%$search%";
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
}

$stmt = $pdo->prepare("
    SELECT s.*, r.room_number, r.capacity as room_capacity 
    FROM students s 
    LEFT JOIN rooms r ON s.room_id = r.id 
    $where 
    ORDER BY s.name
");
$stmt->execute($params);
$students = $stmt->fetchAll();

// Get available rooms for assignment
$rooms_stmt = $pdo->query("SELECT * FROM rooms WHERE current_occupancy < capacity ORDER BY room_number");
$available_rooms = $rooms_stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Students Management</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        /* ------------------------------- */
/* PAGE LAYOUT                     */
/* ------------------------------- */

body {
    margin: 0;
    font-family: "Inter", sans-serif;
    background: #f0f2f5;
    color: #333;
}

.main-content {
    flex-grow: 1;
    padding: 30px;
    margin-left: 250px;
    transition: 0.3s ease;
}

/* Sidebar responsive adjustment */
@media (max-width: 768px) {
    .main-content {
        margin-left: 0;
        padding: 20px;
    }
}

/* ------------------------------- */
/* HEADER                           */
/* ------------------------------- */

header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #fff;
    padding: 22px 28px;
    border-radius: 16px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    margin-bottom: 25px;
    flex-wrap: wrap;
}

header h1 {
    font-size: 28px;
    font-weight: 700;
    color: #1f3b73;
    margin: 0;
}

.user-info {
    background: #eef3ff;
    padding: 10px 20px;
    border-radius: 12px;
    border: 1px solid #d5e1ff;
    font-size: 15px;
    font-weight: 500;
}

/* ------------------------------- */
/* ALERTS                           */
/* ------------------------------- */

.alert {
    padding: 15px 20px;
    border-radius: 12px;
    margin-bottom: 20px;
    font-weight: 500;
}

.alert.success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.alert.error {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

/* ------------------------------- */
/* CONTENT SECTIONS                 */
/* ------------------------------- */

.content-section {
    background: #fff;
    padding: 25px 28px;
    border-radius: 18px;
    box-shadow: 0 4px 14px rgba(0,0,0,0.08);
    margin-bottom: 25px;
}

.content-section h2 {
    margin-bottom: 18px;
    font-size: 22px;
    font-weight: 700;
    color: #1e3c72;
    border-bottom: 2px solid #ecf0f4;
    padding-bottom: 8px;
}

/* ------------------------------- */
/* FORMS                             */
/* ------------------------------- */

/* Inline / Add Student Form */
.form-inline {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    margin-top: 10px;
}

.form-inline input,
.form-inline select {
    padding: 12px 14px;
    border: 1px solid #d7dfe7;
    border-radius: 10px;
    font-size: 15px;
    flex: 1;
}

.form-inline button,
.form-inline a {
    padding: 12px 18px;
    border-radius: 10px;
    font-size: 15px;
}

/* ------------------------------- */
/* TABLES                            */
/* ------------------------------- */

.table-container {
    overflow-x: auto;
    border-radius: 14px;
    box-shadow: 0 4px 14px rgba(0,0,0,0.1);
}

table {
    width: 100%;
    border-collapse: collapse;
    min-width: 900px;
}

table th {
    background: #1e3c72;
    color: #fff;
    padding: 16px;
    font-size: 15px;
    text-align: left;
}

table td {
    padding: 14px 16px;
    border-bottom: 1px solid #ecf0f4;
    font-size: 14px;
}

table tr:hover {
    background: #f6f9ff;
}

/* ------------------------------- */
/* BUTTONS                           */
/* ------------------------------- */

.btn {
    background: #4e8cff;
    color: #fff;
    border: none;
    border-radius: 10px;
    cursor: pointer;
    transition: 0.25s;
}

.btn:hover {
    background: #3a74d1;
}

.btn-secondary {
    background: #95a5a6;
}

.btn-secondary:hover {
    background: #7f8c8d;
}

.btn-danger {
    background: #e74c3c;
}

.btn-danger:hover {
    background: #c0392b;
}

.btn-small {
    padding: 8px 14px;
    font-size: 14px;
}

/* ------------------------------- */
/* STATUS LABELS                     */
/* ------------------------------- */

.status-available {
    color: #27ae60;
    font-weight: 600;
}

/* ------------------------------- */
/* MODALS                             */
/* ------------------------------- */

.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.45);
    backdrop-filter: blur(3px);
    justify-content: center;
    align-items: center;
    z-index: 2000;
}

.modal-content {
    background: white;
    padding: 28px;
    width: 95%;
    max-width: 520px;
    border-radius: 16px;
    box-shadow: 0 6px 20px rgba(0,0,0,0.2);
    animation: fadeIn 0.25s ease-out;
}

@keyframes fadeIn {
    from {opacity: 0; transform: translateY(10px);}
    to {opacity: 1; transform: translateY(0);}
}

.close {
    float: right;
    font-size: 26px;
    cursor: pointer;
    margin-top: -10px;
}

/* ------------------------------- */
/* RESPONSIVE BREAKPOINTS           */
/* ------------------------------- */

@media (max-width: 768px) {
    .form-inline {
        flex-direction: column;
    }

    table {
        min-width: 700px;
    }
}

@media (max-width: 480px) {
    header h1 {
        font-size: 22px;
    }

    .content-section {
        padding: 20px;
    }

    table th, table td {
        padding: 10px;
        font-size: 13px;
    }

    .btn, .form-inline button {
        width: 100%;
    }
}

    </style>
</head>
<body>
    <div class="container">
        <?php include 'sidebar.php'; ?>
        
        <div class="main-content">
            <header>
                <h1>Students Management</h1>
                <div class="user-info">Welcome, <?php echo $_SESSION['username']; ?></div>
            </header>

            <?php if (isset($success)): ?>
                <div class="alert success"><?php echo $success; ?></div>
            <?php endif; ?>

            <div class="content-section">
                <h2>Add New Student</h2>
                <form method="POST" action="add_student.php" class="form-inline">
                    <input type="text" name="student_id" placeholder="Student ID" required>
                    <input type="text" name="name" placeholder="Full Name" required>
                    <input type="email" name="email" placeholder="Email">
                    <input type="text" name="phone" placeholder="Phone">
                    <button type="submit" class="btn">Add Student</button>
                </form>
            </div>

            <div class="content-section">
                <h2>Search Students</h2>
                <form method="GET" class="form-inline">
                    <input type="text" name="search" placeholder="Search by name, student ID, email, or room" value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit" class="btn">Search</button>
                    <a href="students.php" class="btn btn-secondary">Clear</a>
                </form>
            </div>

            <div class="content-section">
                <h2>All Students</h2>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Student ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Assigned Room</th>
                                <th>Assigned Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $student): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($student['student_id']); ?></td>
                                <td><?php echo htmlspecialchars($student['name']); ?></td>
                                <td><?php echo htmlspecialchars($student['email']); ?></td>
                                <td><?php echo htmlspecialchars($student['phone']); ?></td>
                                <td>
                                    <?php if ($student['room_number']): ?>
                                        <?php echo htmlspecialchars($student['room_number']); ?>
                                        (Capacity: <?php echo $student['room_capacity']; ?>)
                                    <?php else: ?>
                                        <span class="status-available">Not Assigned</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $student['assigned_date'] ?: 'N/A'; ?></td>
                                <td>
                                    <?php if ($student['room_id']): ?>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="student_id" value="<?php echo $student['id']; ?>">
                                            <button type="submit" name="unassign_room" class="btn btn-small btn-danger">Unassign</button>
                                        </form>
                                    <?php else: ?>
                                        <form method="POST" style="display:inline;" class="form-inline">
                                            <input type="hidden" name="student_id" value="<?php echo $student['id']; ?>">
                                            <select name="room_id" required>
                                                <option value="">Select Room</option>
                                                <?php foreach ($available_rooms as $room): ?>
                                                    <option value="<?php echo $room['id']; ?>">
                                                        <?php echo $room['room_number']; ?> (<?php echo $room['current_occupancy']; ?>/<?php echo $room['capacity']; ?>)
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button type="submit" name="assign_room" class="btn btn-small">Assign</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>