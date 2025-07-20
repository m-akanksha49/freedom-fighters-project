<?php
session_start();
require 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

// Initialize variables with default values
$user_id = $_SESSION['user_id'] ?? 0;
$score = $_SESSION['score'] ?? 0;
$percentage = ($score > 0) ? ($score / 10) * 100 : 0;
$username = $_SESSION['username'] ?? 'Guest';
$error_message = null;
$result = null;
$user_answers = [];
$history = [];
$avg_score = 0;

try {
    // Get the most recent result for this quiz attempt
    $stmt = $pdo->prepare("SELECT * FROM results WHERE user_id = ? ORDER BY attempted_at DESC LIMIT 1");
    $stmt->execute([$user_id]);
    $result = $stmt->fetch();

    if ($result) {
        // Get all answers for this result
        $stmt = $pdo->prepare("
            SELECT ua.*, q.question, q.option1, q.option2, q.option3, q.option4, q.correct_answer 
            FROM user_answers ua
            JOIN questions q ON ua.question_id = q.id
            WHERE ua.result_id = ?
        ");
        $stmt->execute([$result['id']]);
        $user_answers = $stmt->fetchAll();

        // Get user's historical performance (last 5 attempts)
        $stmt = $pdo->prepare("SELECT score, attempted_at FROM results WHERE user_id = ? ORDER BY attempted_at DESC LIMIT 5");
        $stmt->execute([$user_id]);
        $history = $stmt->fetchAll();

        // Calculate average score
        $stmt = $pdo->prepare("SELECT AVG(score) as avg_score FROM results WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $avg_data = $stmt->fetch();
        $avg_score = $avg_data['avg_score'] ?? 0;
    }
} catch (PDOException $e) {
    // Log error and show user-friendly message
    error_log("Database error: " . $e->getMessage());
    $error_message = "We encountered an issue retrieving your results. Please try again later.";
}

// Clear session data
session_unset();
session_destroy();
?>

<?php
session_start();
require 'config.php';

// [Previous PHP code remains exactly the same]
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz Results - Indian Freedom Fighters</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Patua+One&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.css">
    <style>
        :root {
            --saffron: #FF9933;
            --white: #FFFFFF;
            --green: #138808;
            --blue: #000080;
            --light-blue: #4169E1;
            --dark-blue: #00004d;
            --red: #FF0000;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }
        
        body {
            background: 
                linear-gradient(135deg, 
                    var(--saffron) 0%, 
                    var(--saffron) 33%, 
                    var(--white) 33%, 
                    var(--white) 66%, 
                    var(--green) 66%, 
                    var(--green) 100%),
                url('https://hinduinfopedia.in/wp-content/uploads/2024/02/DALL%C2%B7E-2024-02-27-21.59.21_February_27_Freedom_Struggle_India.webp') no-repeat center center fixed;
            background-size: cover;
            background-blend-mode: overlay;
            color: #333;
            min-height: 100vh;
            position: relative;
        }
        
        .national-pattern {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url("data:image/svg+xml,%3Csvg width='100' height='100' viewBox='0 0 100 100' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M11 18c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm48 25c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm-43-7c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm63 31c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM34 90c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm56-76c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM12 86c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm28-65c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm23-11c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-6 60c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm29 22c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zM32 63c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm57-13c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-9-21c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM60 91c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM35 41c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM12 60c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2z' fill='%23000080' fill-opacity='0.05' fill-rule='evenodd'/%3E%3C/svg%3E");
            opacity: 0.3;
            z-index: -1;
        }
        
        .results-container {
            max-width: 1000px;
            margin: 2rem auto;
            padding: 2rem;
            background-color: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            border: 3px solid var(--blue);
            position: relative;
            overflow: hidden;
        }
        
        .results-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 10px;
            background: linear-gradient(90deg, var(--saffron), var(--white), var(--green));
        }
        
        .results-header {
            text-align: center;
            margin-bottom: 2rem;
            position: relative;
        }
        
        .results-header h1 {
            color: var(--blue);
            font-family: 'Patua One', cursive;
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
            text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.1);
        }
        
        .results-header i {
            color: var(--saffron);
            margin-right: 10px;
        }
        
        .dashboard {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .card {
            background-color: var(--white);
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.15);
        }
        
        .score-card {
            position: relative;
            overflow: hidden;
        }
        
        .score-card::after {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 100px;
            height: 100px;
            background-color: var(--saffron);
            opacity: 0.1;
            border-radius: 50%;
            transform: translate(50px, -50px);
        }
        
        .card-title {
            color: var(--blue);
            font-size: 1.3rem;
            margin-bottom: 1rem;
            font-weight: 600;
            display: flex;
            align-items: center;
        }
        
        .card-title i {
            margin-right: 10px;
            color: var(--saffron);
        }
        
        .percentage-circle {
            width: 150px;
            height: 150px;
            margin: 0 auto 1.5rem;
            position: relative;
            background: conic-gradient(
                var(--green) <?php echo $percentage; ?>%,
                rgba(0, 0, 0, 0.1) <?php echo $percentage; ?>% 100%
            );
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .percentage-circle::before {
            content: '';
            position: absolute;
            width: 120px;
            height: 120px;
            background-color: var(--white);
            border-radius: 50%;
            box-shadow: inset 0 0 10px rgba(0, 0, 0, 0.1);
        }
        
        .percentage-inner {
            position: relative;
            z-index: 1;
            text-align: center;
        }
        
        .percentage-value {
            display: block;
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--blue);
            line-height: 1;
            margin-bottom: 5px;
        }
        
        .percentage-label {
            display: block;
            font-size: 1rem;
            color: var(--dark-blue);
            font-weight: 500;
        }
        
        .score-details {
            text-align: center;
            font-size: 1.1rem;
            margin-bottom: 1.5rem;
            color: var(--dark-blue);
        }
        
        .progress-container {
            width: 100%;
            height: 10px;
            background-color: #f1f1f1;
            border-radius: 5px;
            margin-bottom: 1rem;
            overflow: hidden;
        }
        
        .progress-bar {
            height: 100%;
            background: linear-gradient(90deg, var(--saffron), var(--green));
            width: <?php echo $percentage; ?>%;
            border-radius: 5px;
            transition: width 1s ease;
        }
        
        .progress-label {
            text-align: center;
            font-size: 0.9rem;
            color: var(--dark-blue);
            margin-bottom: 1.5rem;
        }
        
        .performance-message {
            padding: 1rem;
            border-radius: 8px;
            background-color: rgba(19, 136, 8, 0.1);
            text-align: center;
            font-weight: 500;
            color: var(--dark-blue);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .performance-message i {
            font-size: 1.5rem;
            color: 
            <?php 
            if ($percentage >= 80) {
                echo 'var(--saffron)';
            } elseif ($percentage >= 50) {
                echo 'var(--green)';
            } else {
                echo 'var(--blue)';
            }
            ?>;
        }
        
        .stat-item {
            display: flex;
            justify-content: space-between;
            padding: 0.8rem 0;
            border-bottom: 1px dashed rgba(0, 0, 0, 0.1);
        }
        
        .stat-item:last-child {
            border-bottom: none;
        }
        
        .stat-label {
            color: var(--dark-blue);
            font-weight: 500;
        }
        
        .stat-value {
            color: var(--blue);
            font-weight: 600;
        }
        
        .history-card {
            grid-column: span 2;
        }
        
        .chart-container {
            height: 300px;
            margin: 1rem 0;
            position: relative;
            background-color: white;
            border-radius: 8px;
            padding: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            border: 1px solid rgba(0, 0, 128, 0.1);
        }
        
        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .chart-title {
            font-size: 1.1rem;
            color: var(--blue);
            font-weight: 600;
        }
        
        .chart-legend {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .legend-item {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 0.9rem;
        }
        
        .legend-color {
            width: 12px;
            height: 12px;
            border-radius: 3px;
        }
        
        .chart-description {
            font-size: 0.8rem;
            color: #666;
            text-align: center;
            margin-top: 0.5rem;
        }
        
        .chart-description i {
            color: var(--blue);
            margin-right: 5px;
        }
        
        .questions-review {
            margin-top: 2rem;
        }
        
        .section-title {
            background-color: var(--blue);
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 8px;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
        }
        
        .section-title:hover {
            background-color: var(--dark-blue);
        }
        
        .section-title i {
            transition: transform 0.3s ease;
        }
        
        .section-title.expanded i {
            transform: rotate(180deg);
        }
        
        .review-content {
            display: none;
        }
        
        .review-content.show {
            display: block;
            animation: fadeIn 0.5s ease;
        }
        
        .question-card {
            background-color: var(--white);
            border-radius: 8px;
            margin-bottom: 1rem;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            border-left: 4px solid var(--green);
        }
        
        .question-card.incorrect {
            border-left-color: var(--red);
        }
        
        .question-text {
            padding: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: pointer;
            background-color: rgba(0, 0, 128, 0.03);
        }
        
        .question-text span:first-child {
            flex: 1;
            font-weight: 500;
        }
        
        .question-status {
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            background-color: var(--green);
            color: white;
        }
        
        .question-status.incorrect {
            background-color: var(--red);
        }
        
        .toggle-answer {
            width: 100%;
            padding: 0.5rem;
            background-color: rgba(0, 0, 0, 0.03);
            border: none;
            color: var(--blue);
            font-weight: 500;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
            transition: all 0.3s ease;
        }
        
        .toggle-answer:hover {
            background-color: rgba(0, 0, 0, 0.05);
        }
        
        .toggle-answer.active {
            background-color: rgba(0, 0, 0, 0.07);
        }
        
        .answer-section {
            padding: 0 1rem;
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.5s ease, padding 0.5s ease;
        }
        
        .answer-section.show {
            padding: 1rem;
            max-height: 300px;
        }
        
        .your-answer, .correct-answer {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 0.8rem;
            margin-bottom: 0.8rem;
            border-radius: 6px;
        }
        
        .your-answer {
            background-color: rgba(65, 105, 225, 0.1);
            border-left: 3px solid var(--light-blue);
        }
        
        .your-answer.correct {
            background-color: rgba(19, 136, 8, 0.1);
            border-left: 3px solid var(--green);
        }
        
        .correct-answer {
            background-color: rgba(19, 136, 8, 0.1);
            border-left: 3px solid var(--green);
        }
        
        .answer-icon {
            font-size: 1.2rem;
            color: var(--blue);
        }
        
        .correct-answer .answer-icon {
            color: var(--green);
        }
        
        .actions {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-top: 2rem;
            flex-wrap: wrap;
        }
        
        .action-btn {
            padding: 0.8rem 1.5rem;
            border-radius: 50px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
        }
        
        .try-again {
            background: linear-gradient(135deg, var(--saffron), var(--red));
            color: white;
        }
        
        .view-history {
            background: linear-gradient(135deg, var(--light-blue), var(--blue));
            color: white;
        }
        
        .action-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        
        .error-container {
            text-align: center;
            padding: 2rem;
            background-color: rgba(255, 0, 0, 0.05);
            border-radius: 10px;
            border: 1px dashed var(--red);
        }
        
        .error-container i {
            font-size: 3rem;
            color: var(--red);
            margin-bottom: 1rem;
        }
        
        .error-message {
            font-size: 1.2rem;
            color: var(--dark-blue);
            margin-bottom: 1.5rem;
        }
        
        .retry-btn {
            padding: 0.8rem 1.5rem;
            background-color: var(--blue);
            color: white;
            border: none;
            border-radius: 50px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }
        
        .retry-btn:hover {
            background-color: var(--dark-blue);
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }
        
        .confetti {
            position: absolute;
            width: 10px;
            height: 10px;
            background-color: var(--saffron);
            opacity: 0.7;
            animation: fall linear infinite;
            z-index: 100;
        }
        
        @keyframes fall {
            to {
                transform: translateY(100vh) rotate(360deg);
            }
        }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @media (max-width: 768px) {
            .results-container {
                margin: 1rem;
                padding: 1.5rem;
            }
            
            .results-header h1 {
                font-size: 2rem;
            }
            
            .percentage-circle {
                width: 120px;
                height: 120px;
            }
            
            .percentage-circle::before {
                width: 90px;
                height: 90px;
            }
            
            .percentage-value {
                font-size: 2rem;
            }
            
            .history-card {
                grid-column: span 1;
            }
            
            .chart-container {
                height: 250px;
                padding: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="national-pattern"></div>
    
    <div class="results-container">
        <div class="results-header">
            <h1><i class="fas fa-flag"></i> Quiz Results</h1>
            <p>Discover how well you know India's freedom fighters</p>
        </div>
        
        <div class="main-content">
            <?php if (isset($error_message)): ?>
                <div class="error-container">
                    <i class="fas fa-exclamation-triangle"></i>
                    <div class="error-message"><?php echo htmlspecialchars($error_message); ?></div>
                    <button class="retry-btn" onclick="window.location.href='index.php'">
                        <i class="fas fa-redo"></i> Try Again
                    </button>
                </div>
            <?php else: ?>
                <div class="dashboard">
                    <div class="card score-card">
                        <h3 class="card-title"><i class="fas fa-medal"></i> Your Performance</h3>
                        <div class="percentage-circle">
                            <div class="percentage-inner">
                                <span class="percentage-value"><?php echo number_format($percentage, 1); ?>%</span>
                                <span class="percentage-label">Score</span>
                            </div>
                        </div>
                        <div class="score-details">
                            You answered <?php echo $score; ?> out of 10 correctly
                        </div>
                        <div class="progress-container">
                            <div class="progress-bar"></div>
                        </div>
                        <div class="progress-label"><?php echo $percentage; ?>% correct</div>
                        <div class="performance-message">
                            <i class="fas 
                            <?php 
                            if ($percentage >= 80) {
                                echo 'fa-trophy';
                            } elseif ($percentage >= 50) {
                                echo 'fa-thumbs-up';
                            } else {
                                echo 'fa-book';
                            }
                            ?>"></i>
                            <?php 
                            if ($percentage >= 80) {
                                echo 'Excellent! You have exceptional knowledge of Indian freedom fighters!';
                            } elseif ($percentage >= 50) {
                                echo 'Good job! You have a solid understanding of our freedom struggle.';
                            } else {
                                echo 'Keep learning! Explore more about India\'s rich history.';
                            }
                            ?>
                        </div>
                    </div>
                    
                    <div class="card stats-card">
                        <h3 class="card-title"><i class="fas fa-chart-pie"></i> Quiz Statistics</h3>
                        <div class="stat-item">
                            <span class="stat-label">Date Attempted:</span>
                            <span class="stat-value"><?php echo $result ? date('M j, Y', strtotime($result['attempted_at'])) : 'N/A'; ?></span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">Your Average:</span>
                            <span class="stat-value"><?php echo number_format($avg_score, 1); ?>/10</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">Correct Answers:</span>
                            <span class="stat-value"><?php echo $score; ?>/10</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">Incorrect Answers:</span>
                            <span class="stat-value"><?php echo 10 - $score; ?>/10</span>
                        </div>
                    </div>
                    
                    <?php if (!empty($history)): ?>
                    <div class="card history-card">
                        <div class="chart-header">
                            <h3 class="card-title"><i class="fas fa-chart-line"></i> Your Performance History</h3>
                            <div class="chart-legend">
                                <div class="legend-item">
                                    <div class="legend-color" style="background-color: #FF9933;"></div>
                                    <span>Your Score</span>
                                </div>
                                <div class="legend-item">
                                    <div class="legend-color" style="background-color: #138808;"></div>
                                    <span>Average (<?php echo number_format($avg_score, 1); ?>)</span>
                                </div>
                            </div>
                        </div>
                        <div class="chart-container">
                            <canvas id="historyChart"></canvas>
                        </div>
                        <div class="chart-description">
                            <i class="fas fa-info-circle"></i> Track your progress over last 5 attempts
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="questions-review">
                    <h3 class="section-title collapsed" id="reviewToggle">
                        <span><i class="fas fa-clipboard-check"></i> Question Review</span>
                        <i class="fas fa-chevron-down"></i>
                    </h3>
                    <div class="review-content" id="reviewContent">
                        <p style="margin-bottom: 1.5rem; color: var(--dark-blue);">Click on each question to view details about your answer and the correct solution.</p>
                        
                        <?php foreach ($user_answers as $index => $answer): ?>
                            <div class="question-card <?php echo $answer['is_correct'] ? '' : 'incorrect'; ?>">
                                <div class="question-text">
                                    <span><strong>Q<?php echo $index + 1; ?>:</strong> <?php echo htmlspecialchars($answer['question']); ?></span>
                                    <span class="question-status <?php echo $answer['is_correct'] ? '' : 'incorrect'; ?>">
                                        <?php echo $answer['is_correct'] ? 'Correct' : 'Incorrect'; ?>
                                    </span>
                                </div>
                                
                                <button class="toggle-answer" onclick="toggleAnswer(this)">
                                    <i class="fas fa-chevron-down"></i> Show Details
                                </button>
                                
                                <div class="answer-section">
                                    <div class="your-answer <?php echo $answer['is_correct'] ? 'correct' : ''; ?>">
                                        <i class="fas fa-user answer-icon"></i>
                                        <div>
                                            <strong>Your answer:</strong> 
                                            <?php 
                                            $selected_option = 'option' . $answer['selected_answer'];
                                            echo htmlspecialchars($answer[$selected_option]); 
                                            ?>
                                        </div>
                                    </div>
                                    
                                    <?php if (!$answer['is_correct']): ?>
                                        <div class="correct-answer">
                                            <i class="fas fa-check-circle answer-icon"></i>
                                            <div>
                                                <strong>Correct answer:</strong> 
                                                <?php 
                                                $correct_option = 'option' . $answer['correct_answer'];
                                                echo htmlspecialchars($answer[$correct_option]); 
                                                ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="actions">
                    <a href="index.php" class="action-btn try-again">
                        <i class="fas fa-redo"></i> Try Again
                    </a>
                    <a href="history.php?user_id=<?php echo $user_id; ?>" class="action-btn view-history">
                        <i class="fas fa-history"></i> View History
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.js"></script>
    <script>
        // Toggle answer details
        function toggleAnswer(button) {
            const answerSection = button.nextElementSibling;
            const icon = button.querySelector('i');
            
            answerSection.classList.toggle('show');
            button.classList.toggle('active');
            
            if (answerSection.classList.contains('show')) {
                button.innerHTML = '<i class="fas fa-chevron-up"></i> Hide Details';
            } else {
                button.innerHTML = '<i class="fas fa-chevron-down"></i> Show Details';
            }
        }
        
        // Toggle review section
        const reviewToggle = document.getElementById('reviewToggle');
        const reviewContent = document.getElementById('reviewContent');
        
        if (reviewToggle && reviewContent) {
            reviewToggle.addEventListener('click', function() {
                this.classList.toggle('collapsed');
                this.classList.toggle('expanded');
                reviewContent.classList.toggle('show');
            });
        }
        
        // Create confetti effect for high scores
        function createConfetti() {
            if (<?php echo $percentage; ?> >= 80) {
                const container = document.querySelector('.results-container');
                const colors = ['#FF9933', '#138808', '#000080', '#FF0000', '#FFFFFF'];
                
                for (let i = 0; i < 150; i++) {
                    const confetti = document.createElement('div');
                    confetti.className = 'confetti';
                    confetti.style.left = Math.random() * 100 + '%';
                    confetti.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
                    confetti.style.width = Math.random() * 10 + 5 + 'px';
                    confetti.style.height = Math.random() * 10 + 5 + 'px';
                    confetti.style.animationDuration = Math.random() * 3 + 2 + 's';
                    confetti.style.animationDelay = Math.random() * 2 + 's';
                    container.appendChild(confetti);
                }
            }
        }
        
        // Create enhanced history chart
        function createHistoryChart() {
            const ctx = document.getElementById('historyChart').getContext('2d');
            const historyData = <?php echo json_encode(array_reverse($history)); ?>;
            const labels = historyData.map(item => {
                const date = new Date(item.attempted_at);
                return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
            });
            const scores = historyData.map(item => item.score);
            const avgScore = <?php echo $avg_score; ?>;
            
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Your Scores',
                            data: scores,
                            backgroundColor: 'rgba(255, 153, 51, 0.2)',
                            borderColor: 'rgba(255, 153, 51, 1)',
                            borderWidth: 3,
                            tension: 0.3,
                            fill: false,
                            pointBackgroundColor: 'rgba(255, 153, 51, 1)',
                            pointBorderColor: '#fff',
                            pointRadius: 6,
                            pointHoverRadius: 8,
                            pointBorderWidth: 2
                        },
                        {
                            label: 'Your Average',
                            data: Array(labels.length).fill(avgScore),
                            backgroundColor: 'rgba(19, 136, 8, 0.1)',
                            borderColor: 'rgba(19, 136, 8, 1)',
                            borderWidth: 2,
                            borderDash: [5, 5],
                            tension: 0,
                            fill: false,
                            pointRadius: 0
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 10,
                            ticks: {
                                stepSize: 2,
                                color: '#000080',
                                font: {
                                    weight: 'bold'
                                }
                            },
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            }
                        },
                        x: {
                            ticks: {
                                color: '#000080',
                                font: {
                                    weight: 'bold'
                                }
                            },
                            grid: {
                                display: false
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: '#000080',
                            titleFont: {
                                weight: 'bold',
                                size: 14
                            },
                            bodyFont: {
                                size: 12
                            },
                            callbacks: {
                                label: function(context) {
                                    let label = context.dataset.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    if (context.parsed.y !== null) {
                                        label += context.parsed.y + '/10';
                                    }
                                    return label;
                                },
                                labelColor: function(context) {
                                    return {
                                        borderColor: context.datasetIndex === 0 ? '#FF9933' : '#138808',
                                        backgroundColor: context.datasetIndex === 0 ? '#FF9933' : '#138808'
                                    };
                                }
                            }
                        }
                    }
                }
            });
        }
        
        // Initialize elements
        document.addEventListener('DOMContentLoaded', () => {
            createConfetti();
            
            <?php if (!empty($history)): ?>
                createHistoryChart();
            <?php endif; ?>
            
            // Auto-expand first incorrect answer if any
            const incorrectCards = document.querySelectorAll('.question-card.incorrect');
            if (incorrectCards.length > 0) {
                const firstIncorrect = incorrectCards[0];
                const toggleBtn = firstIncorrect.querySelector('.toggle-answer');
                toggleAnswer(toggleBtn);
                
                // Auto-expand review section if there are incorrect answers
                reviewToggle.click();
            }
            
            // Animate progress bar
            const progressBar = document.querySelector('.progress-bar');
            if (progressBar) {
                progressBar.style.width = '0';
                setTimeout(() => {
                    progressBar.style.width = '<?php echo $percentage; ?>%';
                }, 300);
            }
        });
    </script>
</body>
</html>