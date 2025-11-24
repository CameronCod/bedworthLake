<?php
include '../config.php';
redirectIfNotLoggedIn();
if (!isAdmin()) header('Location: ../staff/dashboard.php');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    
</head>
<body>
    <div class="container">
        <?php include 'sidebar.php'; ?>
        
        <div class="main-content">
            <header>
                <h1>Dashboard</h1>
                <div class="user-info">Welcome, <?php echo $_SESSION['username']; ?></div>
            </header>
            
            <div class="stats-grid">
                <?php
                $total_rooms = $pdo->query("SELECT COUNT(*) FROM rooms")->fetchColumn();
                $available_rooms = $pdo->query("SELECT COUNT(*) FROM rooms WHERE status = 'available'")->fetchColumn();
                $occupied_rooms = $pdo->query("SELECT COUNT(*) FROM rooms WHERE status = 'occupied'")->fetchColumn();
                $partial_rooms = $pdo->query("SELECT COUNT(*) FROM rooms WHERE status = 'partially_occupied'")->fetchColumn();
                $total_students = $pdo->query("SELECT COUNT(*) FROM students")->fetchColumn();
                $total_staff = $pdo->query("SELECT COUNT(*) FROM staff")->fetchColumn();
                ?>
                
                <div class="stat-card">
                    <h3>Total Rooms</h3>
                    <p><?php echo $total_rooms; ?></p>
                </div>
                <div class="stat-card">
                    <h3>Available Rooms</h3>
                    <p><?php echo $available_rooms; ?></p>
                </div>
                <div class="stat-card">
                    <h3>Occupied Rooms</h3>
                    <p><?php echo $occupied_rooms; ?></p>
                </div>
                <div class="stat-card">
                    <h3>Partially Occupied</h3>
                    <p><?php echo $partial_rooms; ?></p>
                </div>
                <div class="stat-card">
                    <h3>Total Students</h3>
                    <p><?php echo $total_students; ?></p>
                </div>
                <div class="stat-card">
                    <h3>Total Staff</h3>
                    <p><?php echo $total_staff; ?></p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>