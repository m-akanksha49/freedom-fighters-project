<?php
require 'config.php';

if (!isset($_GET['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_GET['user_id'];

// Get user information
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    header("Location: index.php");
    exit();
}

// Get all quiz attempts for this user
$stmt = $pdo->prepare("SELECT * FROM results WHERE user_id = ? ORDER BY attempted_at DESC");
$stmt->execute([$user_id]);
$attempts = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz History - Indian Freedom Fighters</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background-color: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }
        h1 {
            color: #333;
            margin-bottom: 20px;
        }
        .user-info {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        .attempt {
            margin-bottom: 20px;
            padding: 15px;
            background-color: #f9f9f9;
            border-radius: 5px;
        }
        .attempt-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-weight: bold;
        }
        .score {
            color: #4CAF50;
        }
        .percentage {
            font-size: 18px;
        }
        .date {
            color: #666;
        }
        .btn {
            display: inline-block;
            background-color: #4CAF50;
            color: white;
            padding: 10px 15px;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
        }
        .btn:hover {
            background-color: #45a049;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Quiz History</h1>
        
        <div class="user-info">
            <h2>User: <?php echo htmlspecialchars($user['username']); ?></h2>
            <p>Member since: <?php echo date('F j, Y', strtotime($user['created_at'])); ?></p>
        </div>
        
        <h3>Quiz Attempts:</h3>
        
        <?php if (empty($attempts)): ?>
            <p>No quiz attempts found.</p>
        <?php else: ?>
            <?php foreach ($attempts as $attempt): ?>
                <div class="attempt">
                    <div class="attempt-header">
                        <span class="date"><?php echo date('F j, Y g:i a', strtotime($attempt['attempted_at'])); ?></span>
                        <span class="score">Score: <?php echo $attempt['score']; ?>/10</span>
                    </div>
                    <div class="percentage">Percentage: <?php echo number_format($attempt['percentage'], 1); ?>%</div>
                    <a href="attempt_details.php?result_id=<?php echo $attempt['id']; ?>" class="btn">View Details</a>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
        
        <a href="index.php" class="btn">Back to Home</a>
    </div>
</body>
</html>