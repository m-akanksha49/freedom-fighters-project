<?php
require 'config.php';

if (!isset($_GET['result_id'])) {
    header("Location: index.php");
    exit();
}

$result_id = $_GET['result_id'];

// Get the quiz attempt information
$stmt = $pdo->prepare("
    SELECT r.*, u.username 
    FROM results r
    JOIN users u ON r.user_id = u.id
    WHERE r.id = ?
");
$stmt->execute([$result_id]);
$attempt = $stmt->fetch();

if (!$attempt) {
    header("Location: index.php");
    exit();
}

// Get all answers for this attempt
$stmt = $pdo->prepare("
    SELECT ua.*, q.question, q.option1, q.option2, q.option3, q.option4, q.correct_answer 
    FROM user_answers ua
    JOIN questions q ON ua.question_id = q.id
    WHERE ua.result_id = ?
");
$stmt->execute([$result_id]);
$answers = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attempt Details - Indian Freedom Fighters Quiz</title>
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
            margin-bottom: 5px;
        }
        .attempt-info {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        .score {
            font-size: 24px;
            color: #4CAF50;
            margin: 10px 0;
        }
        .date {
            color: #666;
        }
        .question-result {
            margin-bottom: 20px;
            padding: 15px;
            border-radius: 5px;
            background-color: #f9f9f9;
        }
        .question-text {
            font-weight: bold;
            margin-bottom: 10px;
        }
        .answer-detail {
            margin-top: 10px;
            padding: 10px;
            background-color: #fff;
            border-radius: 5px;
            border-left: 4px solid #4CAF50;
        }
        .incorrect-answer {
            border-left-color: #F44336;
        }
        .correct-answer {
            font-weight: bold;
            color: #4CAF50;
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
        <div class="attempt-info">
            <h1>Quiz Attempt Details</h1>
            <div class="username">User: <?php echo htmlspecialchars($attempt['username']); ?></div>
            <div class="score">Score: <?php echo $attempt['score']; ?> out of 10</div>
            <div class="percentage">Percentage: <?php echo number_format($attempt['percentage'], 1); ?>%</div>
            <div class="date">Attempted on: <?php echo date('F j, Y g:i a', strtotime($attempt['attempted_at'])); ?></div>
        </div>
        
        <h2>Question Details:</h2>
        
        <?php foreach ($answers as $index => $answer): ?>
            <div class="question-result">
                <div class="question-text">Q<?php echo $index + 1; ?>: <?php echo htmlspecialchars($answer['question']); ?></div>
                
                <div class="answer-detail <?php echo !$answer['is_correct'] ? 'incorrect-answer' : ''; ?>">
                    <p>Your answer: 
                        <?php 
                        $selected_option = 'option' . $answer['selected_answer'];
                        echo htmlspecialchars($answer[$selected_option]); 
                        ?>
                        <?php if (!$answer['is_correct']): ?>
                            <span style="color: #F44336;">(Incorrect)</span>
                        <?php else: ?>
                            <span style="color: #4CAF50;">(Correct)</span>
                        <?php endif; ?>
                    </p>
                    
                    <?php if (!$answer['is_correct']): ?>
                        <p class="correct-answer">Correct answer: <?php 
                            $correct_option = 'option' . $answer['correct_answer'];
                            echo htmlspecialchars($answer[$correct_option]); 
                        ?></p>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
        
        <a href="history.php?user_id=<?php echo $attempt['user_id']; ?>" class="btn">Back to History</a>
    </div>
</body>
</html>