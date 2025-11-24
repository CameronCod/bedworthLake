<?php
include '../config.php';
redirectIfNotLoggedIn();
if (!isStaff()) header('Location: ../admin/dashboard.php');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Dashboard</title>
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
    margin-left: 250px; /* Adjust based on sidebar width */
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
/* STATS GRID                        */
/* ------------------------------- */
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 25px;
    margin-top: 20px;
}

/* STAT CARD */
.stat-card {
    background: #fff;
    padding: 25px 28px;
    border-radius: 18px;
    text-align: left;
    box-shadow: 0 4px 14px rgba(0,0,0,0.08);
    transition: 0.25s ease-in-out;
    border-left: 6px solid #4e8cff;
}

.stat-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.12);
}

.stat-card h3 {
    margin: 0;
    font-size: 17px;
    color: #4e4e4e;
    font-weight: 600;
}

.stat-card p {
    font-size: 34px;
    font-weight: 700;
    color: #1f3b73;
    margin-top: 10px;
    margin-bottom: 0;
}

/* Optional: color coding for each stat card */
.stat-card:nth-child(1) { border-left-color: #4e8cff; }   /* Total Rooms */
.stat-card:nth-child(2) { border-left-color: #28c76f; }   /* Available */
.stat-card:nth-child(3) { border-left-color: #ea5455; }   /* Occupied */
.stat-card:nth-child(4) { border-left-color: #ff9f43; }   /* Partially Occupied */

/* ------------------------------- */
/* RESPONSIVE DESIGN                */
/* ------------------------------- */
@media (max-width: 576px) {
    header h1 {
        font-size: 22px;
        margin-bottom: 10px;
    }

    .user-info {
        font-size: 14px;
        padding: 8px 14px;
        margin-top: 10px;
    }

    .stat-card p {
        font-size: 28px;
    }
}

    </style>
</head>
<body>
    <div class="container">
        <?php include 'sidebar.php'; ?>
        
        <div class="main-content">
            <header>
                <h1>Staff Dashboard</h1>
                <div class="user-info">Welcome, <?php echo $_SESSION['username']; ?></div>
            </header>
            
            <div class="stats-grid">
                <?php
                $total_rooms = $pdo->query("SELECT COUNT(*) FROM rooms")->fetchColumn();
                $available_rooms = $pdo->query("SELECT COUNT(*) FROM rooms WHERE status = 'available'")->fetchColumn();
                $occupied_rooms = $pdo->query("SELECT COUNT(*) FROM rooms WHERE status = 'occupied'")->fetchColumn();
                $partial_rooms = $pdo->query("SELECT COUNT(*) FROM rooms WHERE status = 'partially_occupied'")->fetchColumn();
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
            </div>
        </div>
    </div>
</body>
</html>