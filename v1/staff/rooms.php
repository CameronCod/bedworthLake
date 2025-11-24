<?php
include '../config.php';
redirectIfNotLoggedIn();
if (!isStaff()) header('Location: ../admin/dashboard.php');

// Handle student assignment
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['assign_student'])) {
    $room_id = $_POST['room_id'];
    $student_id = $_POST['student_id'];
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    
    try {
        $pdo->beginTransaction();
        
        // Check if student already exists
        $stmt = $pdo->prepare("SELECT id FROM students WHERE student_id = ?");
        $stmt->execute([$student_id]);
        $existing_student = $stmt->fetch();
        
        if ($existing_student) {
            // Update existing student
            $stmt = $pdo->prepare("UPDATE students SET room_id = ?, assigned_date = CURDATE() WHERE id = ?");
            $stmt->execute([$room_id, $existing_student['id']]);
        } else {
            // Create new student
            $stmt = $pdo->prepare("INSERT INTO students (student_id, name, email, phone, room_id, assigned_date) VALUES (?, ?, ?, ?, ?, CURDATE())");
            $stmt->execute([$student_id, $name, $email, $phone, $room_id]);
        }
        
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
        
        $pdo->commit();
        $success = "Student assigned to room successfully!";
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error = "Error assigning student: " . $e->getMessage();
    }
}

// Search functionality
$search = $_GET['search'] ?? '';
$where = "";
if ($search) {
    $where = "WHERE room_number LIKE ? OR status LIKE ?";
}

$stmt = $pdo->prepare("SELECT * FROM rooms $where ORDER BY room_number");
if ($search) {
    $searchTerm = "%$search%";
    $stmt->execute([$searchTerm, $searchTerm]);
} else {
    $stmt->execute();
}
$rooms = $stmt->fetchAll();

// Get students in each room
$students_stmt = $pdo->query("
    SELECT s.*, r.room_number 
    FROM students s 
    LEFT JOIN rooms r ON s.room_id = r.id 
    WHERE s.room_id IS NOT NULL
");
$room_students = $students_stmt->fetchAll(PDO::FETCH_GROUP);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Rooms</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        /* ------------------------------- */
/* GENERAL STYLING                 */
/* ------------------------------- */
body {
    margin: 0;
    font-family: "Inter", sans-serif;
    background: #f0f2f5;
    color: #333;
}

.container {
    display: flex;
}

/* ------------------------------- */
/* MAIN CONTENT                     */
/* ------------------------------- */
.main-content {
    flex-grow: 1;
    padding: 30px;
    margin-left: 250px; /* Adjust according to sidebar width */
    transition: 0.3s ease;
}

@media (max-width: 768px) {
    .main-content {
        margin-left: 0;
        padding: 20px;
    }
}

/* ------------------------------- */
/* HEADER                            */
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
/* CONTENT SECTIONS                 */
/* ------------------------------- */
.content-section {
    background: #fff;
    padding: 25px 28px;
    border-radius: 16px;
    box-shadow: 0 4px 14px rgba(0,0,0,0.08);
    margin-bottom: 25px;
}

.content-section h2 {
    font-size: 20px;
    margin-bottom: 20px;
    color: #1f3b73;
    border-bottom: 2px solid #ecf0f1;
    padding-bottom: 8px;
}

/* ------------------------------- */
/* FORMS                              */
/* ------------------------------- */
.form-inline {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    align-items: flex-end;
}

.form-inline input,
.form-inline select {
    flex: 1;
    padding: 10px 15px;
    border-radius: 12px;
    border: 1px solid #ddd;
    font-size: 14px;
}

.form-inline button,
.form-inline a.btn {
    padding: 10px 18px;
    border-radius: 12px;
    font-size: 14px;
}

/* ------------------------------- */
/* TABLES                             */
/* ------------------------------- */
.table-container {
    overflow-x: auto;
    border-radius: 16px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
}

table {
    width: 100%;
    border-collapse: collapse;
    min-width: 600px;
}

table th,
table td {
    padding: 15px 18px;
    text-align: left;
    border-bottom: 1px solid #ecf0f1;
    font-size: 14px;
}

table th {
    background: #34495e;
    color: #fff;
    font-weight: 600;
    font-size: 15px;
}

table tr:hover {
    background: #f8f9fa;
}

/* Status badges */
.status-available { color: #27ae60; font-weight: bold; }
.status-occupied { color: #e74c3c; font-weight: bold; }
.status-partially_occupied { color: #f39c12; font-weight: bold; }

/* ------------------------------- */
/* BUTTONS                            */
/* ------------------------------- */
.btn {
    background: #3498db;
    color: #fff;
    border: none;
    border-radius: 12px;
    cursor: pointer;
    font-size: 14px;
    transition: 0.25s;
}

.btn:hover {
    background: #2980b9;
}

.btn-secondary {
    background: #95a5a6;
}

.btn-secondary:hover {
    background: #7f8c8d;
}

.btn-small {
    padding: 6px 12px;
    font-size: 12px;
}

.btn-danger {
    background: #e74c3c;
}

.btn-danger:hover {
    background: #c0392b;
}

/* ------------------------------- */
/* MODALS                             */
/* ------------------------------- */
.modal {
    display: none;
    position: fixed;
    z-index: 999;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow-y: auto;
    background-color: rgba(0,0,0,0.5);
    padding: 40px 0;
}

.modal-content {
    background: #fff;
    margin: auto;
    padding: 25px 30px;
    border-radius: 16px;
    width: 90%;
    max-width: 600px;
    box-shadow: 0 8px 24px rgba(0,0,0,0.15);
    position: relative;
}

.modal-content h2 {
    margin-top: 0;
    color: #1f3b73;
    margin-bottom: 20px;
}

.modal-content .close {
    position: absolute;
    top: 18px;
    right: 20px;
    font-size: 24px;
    font-weight: bold;
    color: #aaa;
    cursor: pointer;
}

.modal-content .close:hover {
    color: #333;
}

.modal-content .room-details {
    margin-bottom: 20px;
    font-size: 14px;
}

.form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
}

@media (max-width: 576px) {
    .form-grid {
        grid-template-columns: 1fr;
    }
}

    </style>
</head>
<body>
    <div class="container">
        <?php include 'sidebar.php'; ?>
        
        <div class="main-content">
            <header>
                <h1>View Rooms</h1>
                <div class="user-info">Welcome, <?php echo $_SESSION['username']; ?></div>
            </header>

            <?php if (isset($success)): ?>
                <div class="alert success"><?php echo $success; ?></div>
            <?php endif; ?>
            <?php if (isset($error)): ?>
                <div class="alert error"><?php echo $error; ?></div>
            <?php endif; ?>

            <div class="content-section">
                <h2>Search Rooms</h2>
                <form method="GET" class="form-inline">
                    <input type="text" name="search" placeholder="Search by room number or status" value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit" class="btn">Search</button>
                    <a href="rooms.php" class="btn btn-secondary">Clear</a>
                </form>
            </div>

            <div class="content-section">
                <h2>All Rooms</h2>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Room Number</th>
                                <th>Capacity</th>
                                <th>Current Occupancy</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rooms as $room): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($room['room_number']); ?></td>
                                <td><?php echo $room['capacity']; ?></td>
                                <td><?php echo $room['current_occupancy']; ?></td>
                                <td>
                                    <span class="status-<?php echo $room['status']; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $room['status'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <button onclick="openAssignModal(<?php echo $room['id']; ?>, '<?php echo htmlspecialchars($room['room_number']); ?>', <?php echo $room['capacity']; ?>, <?php echo $room['current_occupancy']; ?>)" 
                                            class="btn btn-small" 
                                            <?php echo $room['current_occupancy'] >= $room['capacity'] ? 'disabled' : ''; ?>>
                                        Assign Student
                                    </button>
                                    <button onclick="openViewModal(<?php echo $room['id']; ?>, '<?php echo htmlspecialchars($room['room_number']); ?>')" class="btn btn-small btn-secondary">
                                        View Students
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Assign Student Modal -->
    <div id="assignModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeAssignModal()">&times;</span>
            <h2>Assign Student to Room <span id="modalRoomNumber"></span></h2>
            <div class="room-details">
                <strong>Capacity:</strong> <span id="modalCapacity"></span><br>
                <strong>Current Occupancy:</strong> <span id="modalOccupancy"></span><br>
                <strong>Available Spaces:</strong> <span id="modalAvailable"></span>
            </div>
            <form method="POST" id="assignForm">
                <input type="hidden" name="room_id" id="modalRoomId">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Student ID:*</label>
                        <input type="text" name="student_id" required>
                    </div>
                    <div class="form-group">
                        <label>Full Name:*</label>
                        <input type="text" name="name" required>
                    </div>
                    <div class="form-group">
                        <label>Email:</label>
                        <input type="email" name="email">
                    </div>
                    <div class="form-group">
                        <label>Phone Number:</label>
                        <input type="text" name="phone">
                    </div>
                </div>
                <div style="margin-top: 1rem;">
                    <button type="submit" name="assign_student" class="btn">Assign Student</button>
                    <button type="button" class="btn btn-secondary" onclick="closeAssignModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- View Students Modal -->
    <div id="viewModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeViewModal()">&times;</span>
            <h2>Students in Room <span id="viewRoomNumber"></span></h2>
            <div id="studentsList" class="students-list">
                <!-- Students will be loaded here -->
            </div>
        </div>
    </div>

    <script>
        // Assign Modal functionality
        function openAssignModal(roomId, roomNumber, capacity, occupancy) {
            document.getElementById('modalRoomId').value = roomId;
            document.getElementById('modalRoomNumber').textContent = roomNumber;
            document.getElementById('modalCapacity').textContent = capacity;
            document.getElementById('modalOccupancy').textContent = occupancy;
            document.getElementById('modalAvailable').textContent = capacity - occupancy;
            
            // Clear form
            document.getElementById('assignForm').reset();
            
            document.getElementById('assignModal').style.display = 'block';
        }
        
        function closeAssignModal() {
            document.getElementById('assignModal').style.display = 'none';
        }
        
        // View Modal functionality
        function openViewModal(roomId, roomNumber) {
            document.getElementById('viewRoomNumber').textContent = roomNumber;
            
            // Load students via AJAX
            fetch('../admin/get_room_students.php?room_id=' + roomId)
                .then(response => response.text())
                .then(data => {
                    document.getElementById('studentsList').innerHTML = data;
                })
                .catch(error => {
                    document.getElementById('studentsList').innerHTML = '<p>Error loading students</p>';
                });
            
            document.getElementById('viewModal').style.display = 'block';
        }
        
        function closeViewModal() {
            document.getElementById('viewModal').style.display = 'none';
        }
        
        // Close modals when clicking outside
        window.onclick = function(event) {
            const assignModal = document.getElementById('assignModal');
            const viewModal = document.getElementById('viewModal');
            
            if (event.target == assignModal) {
                closeAssignModal();
            }
            if (event.target == viewModal) {
                closeViewModal();
            }
        }
    </script>
</body>
</html>