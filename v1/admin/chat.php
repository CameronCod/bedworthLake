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
    <title>Chat System</title>
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
        height: 100vh;
    }

    /* Sidebar is already included externally */
    .main-content {
        flex-grow: 1;
        padding: 30px;
        overflow-y: auto;
    }

    header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: white;
        padding: 20px 25px;
        border-radius: 14px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        margin-bottom: 25px;
    }

    header h1 {
        font-size: 26px;
        margin: 0;
        color: #1e3c72;
    }

    .user-info {
        font-size: 16px;
        color: #555;
    }

    .content-section {
        background: white;
        padding: 30px;
        border-radius: 14px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    }

    /* Coming Soon Section */
    .coming-soon {
        text-align: center;
        padding: 30px;
    }

    .coming-soon h3 {
        font-size: 23px;
        color: #1e3c72;
        margin-bottom: 10px;
    }

    .coming-soon p {
        margin: 8px 0;
        color: #555;
        font-size: 16px;
    }

    .coming-soon ul li {
        padding: 6px 0;
        font-size: 15px;
        color: #444;
    }

    /* Chat Container (Hidden Placeholder) */
    .chat-container {
        margin-top: 40px;
        display: flex;
        height: 500px;
        border-radius: 14px;
        overflow: hidden;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }

    .users-list {
        width: 260px;
        background: #f7f9fc;
        border-right: 1px solid #e1e4e8;
        padding: 20px;
    }

    .users-list h3 {
        margin: 0 0 20px 0;
        font-size: 18px;
        color: #1e3c72;
    }

    .user-item {
        padding: 12px;
        background: white;
        margin-bottom: 12px;
        border-radius: 10px;
        transition: 0.2s;
        cursor: pointer;
        border: 1px solid #e1e4e8;
    }

    .user-item:hover {
        background: #e7f0ff;
        border-color: #1e70ff;
    }

    .user-item.active {
        background: #1e70ff;
        color: white;
        border-color: #1e70ff;
    }

    .chat-area {
        flex-grow: 1;
        display: flex;
        flex-direction: column;
        background: #fff;
    }

    .messages {
        flex-grow: 1;
        padding: 20px;
        overflow-y: auto;
    }

    .message {
        background: #e8ebf0;
        padding: 12px;
        margin-bottom: 15px;
        border-radius: 12px;
        width: fit-content;
        max-width: 70%;
    }

    .message.sent {
        background: #d0e4ff;
        margin-left: auto;
    }

    .message strong {
        display: block;
        margin-bottom: 5px;
        color: #333;
    }

    .message-input {
        display: flex;
        padding: 15px;
        border-top: 1px solid #e1e4e8;
        background: #fafbfc;
    }

    .message-input input {
        flex-grow: 1;
        padding: 12px 15px;
        border: 1px solid #d1d9e6;
        border-radius: 10px;
        outline: none;
        font-size: 15px;
        margin-right: 10px;
        transition: 0.2s;
    }

    .message-input input:focus {
        border-color: #4e8cff;
        box-shadow: 0 0 0 2px rgba(78,140,255,0.2);
    }

    .btn {
        padding: 12px 20px;
        background: linear-gradient(135deg, #4e8cff, #1e70ff);
        border: none;
        color: white;
        font-size: 15px;
        border-radius: 10px;
        cursor: pointer;
        font-weight: 600;
        transition: 0.2s;
    }

    .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 14px rgba(0,0,0,0.15);
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
                    <p>We're working on implementing a real-time chat system where you can communicate with staff members and students.</p>
                    <p><strong>Planned Features:</strong></p>
                    <ul style="text-align: left; display: inline-block; margin-top: 1rem;">
                        <li>Real-time messaging with staff</li>
                        <li>Group chats for room management</li>
                        <li>File sharing capabilities</li>
                        <li>Message history and archiving</li>
                        <li>Push notifications</li>
                    </ul>
                    <div style="margin-top: 2rem; padding: 1rem; background: #f8f9fa; border-radius: 8px;">
                        <p><strong>Expected Launch:</strong> Next Update</p>
                    </div>
                </div>
                
                <!-- Chat Interface Placeholder -->
                <div class="chat-container" style="display: none;">
                    <div class="users-list">
                        <h3>Online Users</h3>
                        <div class="user-item active">John Doe (Staff)</div>
                        <div class="user-item">Jane Smith (Staff)</div>
                        <div class="user-item">Mike Johnson (Staff)</div>
                    </div>
                    
                    <div class="chat-area">
                        <div class="messages">
                            <div class="message received">
                                <strong>John Doe:</strong> Hello, I need assistance with room 101.
                            </div>
                            <div class="message sent">
                                <strong>You:</strong> What seems to be the problem?
                            </div>
                            <div class="message received">
                                <strong>John Doe:</strong> The AC is not working properly.
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