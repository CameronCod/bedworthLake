<nav class="sidebar">
    <div class="sidebar-header">
        <h3>🏡 Bedworth Lake Admin</h3>
    </div>

    <ul class="sidebar-menu">
        <li><a href="dashboard.php" class="<?php echo $activePage=='dashboard' ? 'active':'' ?>">📊 Dashboard</a></li>
        <li><a href="rooms.php" class="<?php echo $activePage=='rooms' ? 'active':'' ?>">🏠 Rooms</a></li>
        <li><a href="students.php" class="<?php echo $activePage=='students' ? 'active':'' ?>">👨‍🎓 Students</a></li>
        <li><a href="staff.php" class="<?php echo $activePage=='staff' ? 'active':'' ?>">🧑‍💼 Staff</a></li>
        <li><a href="chat.php" class="<?php echo $activePage=='chat' ? 'active':'' ?>">💬 Chat</a></li>
        <li><a href="../logout.php" class="logout">🚪 Logout</a></li>
    </ul>
</nav>
