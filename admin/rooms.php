<?php
include '../config.php';
redirectIfNotLoggedIn();
if (!isAdmin())
    header('Location: ../staff/dashboard.php');

// Handle room actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_room'])) {
        $room_number = $_POST['room_number'];
        $capacity = $_POST['capacity'];

        $stmt = $pdo->prepare("INSERT INTO rooms (room_number, capacity) VALUES (?, ?)");
        $stmt->execute([$room_number, $capacity]);
        $success = "Room added successfully!";
    }

    if (isset($_POST['delete_room'])) {
        $room_id = $_POST['room_id'];
        $stmt = $pdo->prepare("DELETE FROM rooms WHERE id = ?");
        $stmt->execute([$room_id]);
        $success = "Room deleted successfully!";
    }

    if (isset($_POST['assign_student'])) {
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
    <title>Rooms Management</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: "Inter", sans-serif;
            background: #f0f2f5;
            color: #333;
        }

        .container {
            display: flex;
            min-height: 100vh;
        }

        .main-content {
            flex-grow: 1;
            padding: 30px;
            overflow-y: auto;
        }

        /* Header */
        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: white;
            padding: 22px 28px;
            border-radius: 16px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            margin-bottom: 25px;
        }

        header h1 {
            font-size: 26px;
            color: #1e3c72;
            margin: 0;
        }

        .user-info {
            padding: 10px 18px;
            background: #eef3ff;
            border-radius: 12px;
            color: #444;
            font-size: 15px;
            border: 1px solid #d6e2ff;
        }

        /* Alerts */
        .alert {
            padding: 15px 18px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-size: 15px;
            font-weight: 600;
        }

        .alert.success {
            background: #dff8e8;
            border-left: 5px solid #28c76f;
            color: #207f4c;
        }

        .alert.error {
            background: #ffe6e6;
            border-left: 5px solid #ea5455;
            color: #b22d2e;
        }

        /* Content Sections */
        .content-section {
            background: white;
            padding: 30px;
            border-radius: 16px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            margin-bottom: 25px;
        }

        .content-section h2 {
            margin-top: 0;
            margin-bottom: 20px;
            font-size: 22px;
            color: #1e3c72;
        }

        /* Forms */
        .form-inline {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }

        .form-inline input {
            padding: 12px 15px;
            border-radius: 10px;
            border: 1px solid #cfd6e1;
            font-size: 15px;
            flex: 1;
            transition: 0.2s;
        }

        .form-inline input:focus {
            border-color: #4e8cff;
            box-shadow: 0 0 0 2px rgba(78, 140, 255, 0.2);
        }

        .btn {
            background: linear-gradient(135deg, #4e8cff, #1e70ff);
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 10px;
            font-size: 15px;
            cursor: pointer;
            font-weight: 600;
            transition: 0.25s;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 14px rgba(0, 0, 0, 0.14);
        }

        .btn-secondary {
            background: #e1e7f5;
            color: #333;
        }

        .btn-danger {
            background: #ea5455;
        }

        .btn-small {
            padding: 8px 14px;
            font-size: 14px;
        }

        /* Table */
        .table-container {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 14px;
            overflow: hidden;
        }

        table th {
            background: #f6f8fc;
            padding: 14px;
            text-align: left;
            font-size: 15px;
            color: #555;
            font-weight: 600;
            border-bottom: 1px solid #e1e4ea;
        }

        table td {
            padding: 14px;
            border-bottom: 1px solid #f0f0f0;
            font-size: 15px;
        }

        /* Status badges */
        .status-available {
            background: #d2f6d8;
            color: #1a7f37;
            padding: 6px 12px;
            border-radius: 8px;
            font-weight: 600;
        }

        .status-occupied {
            background: #ffe0e0;
            color: #b31b1b;
            padding: 6px 12px;
            border-radius: 8px;
            font-weight: 600;
        }

        .status-partially_occupied {
            background: #fff3cd;
            color: #856404;
            padding: 6px 12px;
            border-radius: 8px;
            font-weight: 600;
        }

        /* Modals */
        .modal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.45);
            padding-top: 70px;
        }

        .modal-content {
            background: white;
            width: 600px;
            margin: auto;
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 6px 18px rgba(0, 0, 0, 0.2);
            animation: fadeIn 0.25s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: scale(0.95);
            }

            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        .close {
            float: right;
            font-size: 26px;
            cursor: pointer;
            color: #666;
        }

        .room-details {
            margin-bottom: 18px;
            font-size: 15px;
            color: #555;
        }

        /* Form grid inside modal */
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
        }

        .form-group label {
            font-size: 14px;
            font-weight: 600;
        }

        .form-group input {
            width: 100%;
            padding: 10px 12px;
            border-radius: 8px;
            border: 1px solid #cfd6e1;
            margin-top: 6px;
        }

        /* Students list */
        .students-list {
            max-height: 300px;
            overflow-y: auto;
            padding: 10px 0;
        }
    </style>
</head>

<body>
    <div class="container">
        <?php include 'sidebar.php'; ?>

        <div class="main-content">
            <header>
                <h1>Rooms Management</h1>
                <div class="user-info">Welcome, <?php echo $_SESSION['username']; ?></div>
            </header>

            <?php if (isset($success)): ?>
                <div class="alert success"><?php echo $success; ?></div>
            <?php endif; ?>
            <?php if (isset($error)): ?>
                <div class="alert error"><?php echo $error; ?></div>
            <?php endif; ?>

            <div class="content-section">
                <h2>Add New Room</h2>
                <form method="POST" class="form-inline">
                    <input type="text" name="room_number" placeholder="Room Number" required>
                    <input type="number" name="capacity" placeholder="Capacity" required min="1">
                    <button type="submit" name="add_room" class="btn">Add Room</button>
                </form>
            </div>

            <div class="content-section">
                <h2>Search Rooms</h2>
                <form method="GET" class="form-inline">
                    <input type="text" name="search" placeholder="Search by room number or status"
                        value="<?php echo htmlspecialchars($search); ?>">
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
                                        <button
                                            onclick="openAssignModal(<?php echo $room['id']; ?>, '<?php echo htmlspecialchars($room['room_number']); ?>', <?php echo $room['capacity']; ?>, <?php echo $room['current_occupancy']; ?>)"
                                            class="btn btn-small" <?php echo $room['current_occupancy'] >= $room['capacity'] ? 'disabled' : ''; ?>>
                                            Assign Student
                                        </button>
                                        <button
                                            onclick="openViewModal(<?php echo $room['id']; ?>, '<?php echo htmlspecialchars($room['room_number']); ?>')"
                                            class="btn btn-small btn-secondary">
                                            View Students
                                        </button>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="room_id" value="<?php echo $room['id']; ?>">
                                            <button type="submit" name="delete_room" class="btn btn-small btn-danger"
                                                onclick="return confirm('Are you sure? This will also unassign all students from this room.')">Delete</button>
                                        </form>
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
            fetch('get_room_students.php?room_id=' + roomId)
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
        window.onclick = function (event) {
            const assignModal = document.getElementById('assignModal');
            const viewModal = document.getElementById('viewModal');

            if (event.target == assignModal) {
                closeAssignModal();
            }
            if (event.target == viewModal) {
                closeViewModal();
            }
        }

        // Unassign student function
        function unassignStudent(studentId, roomId) {
            if (confirm('Are you sure you want to unassign this student?')) {
                const formData = new FormData();
                formData.append('student_id', studentId);
                formData.append('room_id', roomId);
                formData.append('unassign_student', 'true');

                fetch('unassign_student.php', {
                    method: 'POST',
                    body: formData
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('Student unassigned successfully!');
                            location.reload();
                        } else {
                            alert('Error: ' + data.error);
                        }
                    })
                    .catch(error => {
                        alert('Error unassigning student');
                    });
            }
        }
    </script>
</body>

</html>