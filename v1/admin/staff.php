<?php
include '../config.php';
redirectIfNotLoggedIn();
if (!isAdmin()) header('Location: ../staff/dashboard.php');

// Handle staff actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_staff'])) {
        $staff_id = $_POST['staff_id'];
        $name = $_POST['name'];
        $email = $_POST['email'];
        $phone = $_POST['phone'];
        $username = $_POST['username'];
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        
        try {
            $pdo->beginTransaction();
            
            // Create user account
            $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'staff')");
            $stmt->execute([$username, $password]);
            $user_id = $pdo->lastInsertId();
            
            // Create staff record
            $stmt = $pdo->prepare("INSERT INTO staff (staff_id, name, email, phone, user_id) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$staff_id, $name, $email, $phone, $user_id]);
            
            $pdo->commit();
            $success = "Staff member added successfully!";
        } catch (PDOException $e) {
            $pdo->rollBack();
            if ($e->getCode() == 23000) {
                $error = "Staff ID or Username already exists!";
            } else {
                $error = "Error adding staff: " . $e->getMessage();
            }
        }
    }
    
    if (isset($_POST['delete_staff'])) {
        $staff_id = $_POST['staff_id'];
        
        // Get user_id first
        $stmt = $pdo->prepare("SELECT user_id FROM staff WHERE id = ?");
        $stmt->execute([$staff_id]);
        $staff = $stmt->fetch();
        
        if ($staff) {
            // This will cascade delete due to foreign key constraint
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$staff['user_id']]);
            $success = "Staff member deleted successfully!";
        }
    }
    
    if (isset($_POST['assign_rooms'])) {
        $staff_id = $_POST['staff_id'];
        $rooms = $_POST['rooms'] ?? [];
        $assigned_rooms = implode(',', $rooms);
        
        $stmt = $pdo->prepare("UPDATE staff SET assigned_rooms = ? WHERE id = ?");
        $stmt->execute([$assigned_rooms, $staff_id]);
        $success = "Rooms assigned successfully!";
    }
}

// Search functionality
$search = $_GET['search'] ?? '';
$where = "WHERE 1=1";
$params = [];

if ($search) {
    $where .= " AND (s.name LIKE ? OR s.staff_id LIKE ? OR s.email LIKE ?)";
    $searchTerm = "%$search%";
    $params = [$searchTerm, $searchTerm, $searchTerm];
}

$stmt = $pdo->prepare("SELECT s.*, u.username FROM staff s JOIN users u ON s.user_id = u.id $where ORDER BY s.name");
$stmt->execute($params);
$staff_members = $stmt->fetchAll();

// Get all rooms for assignment
$rooms_stmt = $pdo->query("SELECT * FROM rooms ORDER BY room_number");
$all_rooms = $rooms_stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Management</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <?php include 'sidebar.php'; ?>
        
        <div class="main-content">
            <header>
                <h1>Staff Management</h1>
                <div class="user-info">Welcome, <?php echo $_SESSION['username']; ?></div>
            </header>

            <?php if (isset($success)): ?>
                <div class="alert success"><?php echo $success; ?></div>
            <?php endif; ?>
            <?php if (isset($error)): ?>
                <div class="alert error"><?php echo $error; ?></div>
            <?php endif; ?>

            <div class="content-section">
                <h2>Add New Staff Member</h2>
                <form method="POST" class="form-grid">
                    <div class="form-group">
                        <label>Staff ID:</label>
                        <input type="text" name="staff_id" required>
                    </div>
                    <div class="form-group">
                        <label>Full Name:</label>
                        <input type="text" name="name" required>
                    </div>
                    <div class="form-group">
                        <label>Email:</label>
                        <input type="email" name="email">
                    </div>
                    <div class="form-group">
                        <label>Phone:</label>
                        <input type="text" name="phone">
                    </div>
                    <div class="form-group">
                        <label>Username:</label>
                        <input type="text" name="username" required>
                    </div>
                    <div class="form-group">
                        <label>Password:</label>
                        <input type="password" name="password" required>
                    </div>
                    <button type="submit" name="add_staff" class="btn">Add Staff</button>
                </form>
            </div>

            <div class="content-section">
                <h2>Search Staff</h2>
                <form method="GET" class="form-inline">
                    <input type="text" name="search" placeholder="Search by name, staff ID, or email" value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit" class="btn">Search</button>
                    <a href="staff.php" class="btn btn-secondary">Clear</a>
                </form>
            </div>

            <div class="content-section">
                <h2>All Staff Members</h2>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Staff ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Username</th>
                                <th>Assigned Rooms</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($staff_members as $staff): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($staff['staff_id']); ?></td>
                                <td><?php echo htmlspecialchars($staff['name']); ?></td>
                                <td><?php echo htmlspecialchars($staff['email']); ?></td>
                                <td><?php echo htmlspecialchars($staff['phone']); ?></td>
                                <td><?php echo htmlspecialchars($staff['username']); ?></td>
                                <td>
                                    <?php 
                                    $assigned_rooms = $staff['assigned_rooms'] ? explode(',', $staff['assigned_rooms']) : [];
                                    if (!empty($assigned_rooms)) {
                                        echo implode(', ', $assigned_rooms);
                                    } else {
                                        echo '<span class="status-available">No rooms assigned</span>';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <button onclick="openAssignModal(<?php echo $staff['id']; ?>, '<?php echo htmlspecialchars($staff['name']); ?>', '<?php echo $staff['assigned_rooms']; ?>')" class="btn btn-small">Assign Rooms</button>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="staff_id" value="<?php echo $staff['id']; ?>">
                                        <button type="submit" name="delete_staff" class="btn btn-small btn-danger" onclick="return confirm('Are you sure you want to delete this staff member?')">Delete</button>
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

    <!-- Assign Rooms Modal -->
    <div id="assignModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Assign Rooms to <span id="staffName"></span></h2>
            <form method="POST" id="assignForm">
                <input type="hidden" name="staff_id" id="modalStaffId">
                <div class="form-group">
                    <label>Select Rooms:</label>
                    <div class="rooms-checkbox">
                        <?php foreach ($all_rooms as $room): ?>
                            <label class="checkbox-label">
                                <input type="checkbox" name="rooms[]" value="<?php echo $room['room_number']; ?>">
                                <?php echo $room['room_number']; ?> (<?php echo $room['status']; ?>)
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <button type="submit" name="assign_rooms" class="btn">Save Assignment</button>
            </form>
        </div>
    </div>

    <script>
        // Modal functionality
        const modal = document.getElementById('assignModal');
        const closeBtn = document.querySelector('.close');
        
        function openAssignModal(staffId, staffName, currentRooms) {
            document.getElementById('staffName').textContent = staffName;
            document.getElementById('modalStaffId').value = staffId;
            
            // Clear all checkboxes first
            document.querySelectorAll('input[name="rooms[]"]').forEach(checkbox => {
                checkbox.checked = false;
            });
            
            // Check currently assigned rooms
            if (currentRooms) {
                const assignedRooms = currentRooms.split(',');
                assignedRooms.forEach(room => {
                    document.querySelectorAll('input[name="rooms[]"]').forEach(checkbox => {
                        if (checkbox.value === room.trim()) {
                            checkbox.checked = true;
                        }
                    });
                });
            }
            
            modal.style.display = 'block';
        }
        
        closeBtn.onclick = function() {
            modal.style.display = 'none';
        }
        
        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
    </script>

    <style>
        /* ---------------------------------------------- */
/* PAGE WRAPPER & MAIN CONTENT                    */
/* ---------------------------------------------- */

.main-content {
    flex-grow: 1;
    padding: 30px;
    margin-left: 250px;
    transition: 0.3s ease;
}

/* Mobile sidebar closed = full-width content */
@media (max-width: 768px) {
    .main-content {
        margin-left: 0;
        padding: 20px;
    }
}

/* ---------------------------------------------- */
/* HEADER                                         */
/* ---------------------------------------------- */

header {
    background: #ffffff;
    padding: 22px 28px;
    border-radius: 16px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
}

header h1 {
    margin: 0;
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

/* ---------------------------------------------- */
/* CONTENT SECTIONS                               */
/* ---------------------------------------------- */

.content-section {
    background: white;
    padding: 28px 30px;
    border-radius: 18px;
    box-shadow: 0 4px 14px rgba(0, 0, 0, 0.08);
    margin-bottom: 25px;
}

.content-section h2 {
    margin-bottom: 15px;
    font-size: 22px;
    font-weight: 700;
    color: #1e3c72;
}

/* ---------------------------------------------- */
/* FORMS                                          */
/* ---------------------------------------------- */

.form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 18px;
    margin-top: 10px;
}

.form-group label {
    font-weight: 600;
    margin-bottom: 6px;
    display: block;
    color: #444;
}

.form-group input {
    width: 100%;
    padding: 12px 14px;
    border: 1px solid #d7dfe7;
    border-radius: 10px;
    font-size: 15px;
    transition: 0.25s;
}

.form-group input:focus {
    border-color: #4e8cff;
    box-shadow: 0 0 0 3px rgba(78, 140, 255, 0.18);
    outline: none;
}

/* Inline search form */
.form-inline {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
}

.form-inline input {
    flex: 1;
    padding: 12px;
}

/* ---------------------------------------------- */
/* BUTTONS                                        */
/* ---------------------------------------------- */

.btn {
    padding: 12px 20px;
    background: #4e8cff;
    color: white;
    border: none;
    border-radius: 10px;
    font-size: 15px;
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

/* ---------------------------------------------- */
/* TABLE                                          */
/* ---------------------------------------------- */

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
    color: white;
    padding: 16px;
    font-size: 15px;
    text-align: left;
}

table td {
    padding: 15px;
    border-bottom: 1px solid #ecf0f4;
}

table tr:hover {
    background: #f6f9ff;
}

/* ---------------------------------------------- */
/* STATUS                                         */
/* ---------------------------------------------- */

.status-available {
    color: #27ae60;
    font-weight: bold;
}

/* ---------------------------------------------- */
/* MODALS                                         */
/* ---------------------------------------------- */

.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.45);
    backdrop-filter: blur(4px);
    justify-content: center;
    align-items: center;
    z-index: 2000;
}

.modal-content {
    background: white;
    padding: 30px;
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

/* Checkboxes */
.rooms-checkbox {
    max-height: 250px;
    overflow-y: auto;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 12px;
}

.checkbox-label {
    display: block;
    padding: 6px 0;
    font-size: 15px;
}

/* Mobile optimizations */
@media (max-width: 600px) {
    .modal-content {
        padding: 20px;
    }
    table {
        min-width: 700px;
    }
}

    </style>
</body>
</html>