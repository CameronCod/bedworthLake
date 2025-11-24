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
    <title>Chat System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        /* ------------------------------- */
/* GENERAL LAYOUT                  */
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

/* Sidebar adjustment for mobile */
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
/* CONTENT SECTION                  */
/* ------------------------------- */
.content-section {
    background: #fff;
    padding: 25px 28px;
    border-radius: 18px;
    box-shadow: 0 4px 14px rgba(0,0,0,0.08);
    margin-bottom: 25px;
}

/* Coming Soon Styling */
.coming-soon h3 {
    font-size: 22px;
    font-weight: 700;
    color: #ea5455;
    margin-bottom: 10px;
}

.coming-soon p {
    font-size: 15px;
    line-height: 1.6;
}

.coming-soon ul {
    padding-left: 20px;
    margin-top: 10px;
}

.coming-soon ul li {
    margin-bottom: 8px;
    font-size: 15px;
}

/* Placeholder box */
.coming-soon div {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 12px;
    font-weight: 500;
    font-size: 15px;
}

/* ------------------------------- */
/* CHAT CONTAINER                  */
/* ------------------------------- */
.chat-container {
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
}

/* Users List */
.users-list {
    flex: 1;
    min-width: 200px;
    max-width: 250px;
    background: #fff;
    border-radius: 16px;
    padding: 20px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    height: fit-content;
}

.users-list h3 {
    font-size: 18px;
    margin-bottom: 15px;
    color: #1f3b73;
}

.user-item {
    padding: 10px 14px;
    margin-bottom: 10px;
    border-radius: 10px;
    cursor: pointer;
    background: #f0f2f5;
    transition: 0.2s;
}

.user-item.active {
    background: #4e8cff;
    color: #fff;
    font-weight: 600;
}

.user-item:hover {
    background: #d9e4ff;
}

/* Chat Area */
.chat-area {
    flex: 3;
    background: #fff;
    border-radius: 16px;
    padding: 20px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    display: flex;
    flex-direction: column;
    height: 500px;
}

/* Messages */
.messages {
    flex: 1;
    overflow-y: auto;
    margin-bottom: 15px;
}

.message {
    padding: 10px 15px;
    margin-bottom: 10px;
    border-radius: 12px;
    max-width: 75%;
    font-size: 14px;
    line-height: 1.5;
}

.message.received {
    background: #f1f3f6;
    align-self: flex-start;
}

.message.sent {
    background: #4e8cff;
    color: #fff;
    align-self: flex-end;
}

/* Message Input */
.message-input {
    display: flex;
    gap: 10px;
}

.message-input input {
    flex: 1;
    padding: 12px 15px;
    border-radius: 12px;
    border: 1px solid #d7dfe7;
    font-size: 15px;
}

.message-input button {
    padding: 12px 20px;
    border-radius: 12px;
    border: none;
    background: #4e8cff;
    color: #fff;
    cursor: pointer;
    font-size: 15px;
}

.message-input button:hover {
    background: #3a74d1;
}

/* ------------------------------- */
/* RESPONSIVE DESIGN               */
/* ------------------------------- */
@media (max-width: 992px) {
    .chat-container {
        flex-direction: column;
    }

    .chat-area {
        height: 400px;
    }
}

@media (max-width: 576px) {
    .header h1 {
        font-size: 22px;
    }

    .users-list {
        max-width: 100%;
    }

    .chat-area {
        height: 350px;
        padding: 15px;
    }

    .message-input input, .message-input button {
        font-size: 14px;
        padding: 10px;
    }
}

    </style>
</head>
<body>
    <div class="container">
        <?php include 'sidebar.php'; ?>
        
        <div class="main-content">
            <header>
                <h1>Chat System</h1>
                <div class="user-info">Welcome, <?php echo $_SESSION['username']; ?></div>
            </header>

            <div class="content-section">
                <div class="coming-soon">
                    <h3>🚧 Chat System Coming Soon 🚧</h3>
                    <p>We're working on implementing a real-time chat system where you can communicate with administrators and other staff members.</p>
                    <p><strong>Planned Features:</strong></p>
                    <ul style="text-align: left; display: inline-block; margin-top: 1rem;">
                        <li>Real-time messaging with admin</li>
                        <li>Staff group discussions</li>
                        <li>Quick issue reporting</li>
                        <li>Announcements from admin</li>
                        <li>Mobile notifications</li>
                    </ul>
                    <div style="margin-top: 2rem; padding: 1rem; background: #f8f9fa; border-radius: 8px;">
                        <p><strong>Expected Launch:</strong> Next Update</p>
                    </div>
                </div>
                
                <!-- Chat Interface Placeholder -->
                <div class="chat-container" style="display: none;">
                    <div class="users-list">
                        <h3>Online Users</h3>
                        <div class="user-item active">Admin</div>
                        <div class="user-item">Jane Smith (Staff)</div>
                        <div class="user-item">Mike Johnson (Staff)</div>
                    </div>
                    
                    <div class="chat-area">
                        <div class="messages">
                            <div class="message received">
                                <strong>Admin:</strong> Please check room 205, there's a maintenance request.
                            </div>
                            <div class="message sent">
                                <strong>You:</strong> On my way to check it now.
                            </div>
                            <div class="message received">
                                <strong>Admin:</strong> Thank you. Let me know what you find.
                            </div>
                        </div>
                        
                        <div class="message-input">
                            <input type="text" placeholder="Type your message...">
                            <button class="btn">Send</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>