<?php
session_start();
require 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username'])) {
    $username = trim($_POST['username']);
    
    if (!empty($username)) {
        try {
            // Check if user exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            
            if (!$user) {
                // Create new user
                $stmt = $pdo->prepare("INSERT INTO users (username) VALUES (?)");
                $stmt->execute([$username]);
                $user_id = $pdo->lastInsertId();
            } else {
                $user_id = $user['id'];
            }
            
            $_SESSION['user_id'] = $user_id;
            $_SESSION['username'] = $username;
            $_SESSION['score'] = 0;
            $_SESSION['current_question'] = 0;
            
            // Select 10 random questions
            $stmt = $pdo->query("SELECT id FROM questions ORDER BY RAND() LIMIT 10");
            $question_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
            $_SESSION['quiz_questions'] = $question_ids;
            
            header("Location: quiz.php");
            exit();
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    } else {
        $error = "Please enter a username";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Indian Freedom Fighters Quiz</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: url('https://vipschool.in/wp-content/uploads/2024/07/Unsung-Heroes-of-Indias-freedom-struggle.png') no-repeat center center fixed;
            background-size: cover;
            position: relative;
        }
        
        .back-button {
            position: absolute;
            top: 20px;
            left: 20px;
            background: rgba(255, 255, 255, 0.8);
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            font-size: 20px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            transition: all 0.3s ease;
        }
        
        .back-button:hover {
            background: white;
            transform: scale(1.1);
        }
        
        .container {
            background-color: rgba(255, 255, 255, 0.9);
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            padding: 30px;
            width: 400px;
            text-align: center;
            backdrop-filter: blur(2px);
            position: relative;
        }
        
        h1 {
            color: #333;
            margin-bottom: 20px;
        }
        
        form {
            display: flex;
            flex-direction: column;
        }
        
        input[type="text"] {
            padding: 12px;
            margin: 10px 0;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }
        
        button[type="submit"] {
            background-color: #4CAF50;
            color: white;
            padding: 12px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s;
        }
        
        button[type="submit"]:hover {
            background-color: #45a049;
        }
        
        .error {
            color: red;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <button class="back-button" onclick="window.location.href='dashboard.html'">←</button>
    
    <div class="container">
        <h1>Indian Freedom Fighters Quiz</h1>
        <p>Test your knowledge about the heroes of India's independence movement</p>
        
        <?php if (isset($error)): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <input type="text" name="username" placeholder="Enter your name" required>
            <button type="submit">Start Quiz</button>
        </form>
    </div>
</body>
</html>