<?php
session_start();
include 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

if (!isset($_SESSION['quiz_questions'])) {
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['answer'])) {
    $question_id = $_POST['question_id'];
    $selected_answer = $_POST['answer'];

    $stmt = $pdo->prepare("SELECT correct_answer FROM questions WHERE id = ?");
    $stmt->execute([$question_id]);
    $question = $stmt->fetch();

    $is_correct = false;
    if ($question && $selected_answer == $question['correct_answer']) {
        $_SESSION['score']++;
        $is_correct = true;
    }

    $_SESSION['user_answers'][] = [
        'question_id' => $question_id,
        'selected_answer' => $selected_answer,
        'is_correct' => $is_correct
    ];

    $_SESSION['current_question']++;

    if ($_SESSION['current_question'] >= 10) {
        $score = $_SESSION['score'];
        $percentage = ($score / 10) * 100;

        $stmt = $pdo->prepare("INSERT INTO results (user_id, score, percentage) VALUES (?, ?, ?)");
        $stmt->execute([$_SESSION['user_id'], $score, $percentage]);
        $result_id = $pdo->lastInsertId();

        foreach ($_SESSION['user_answers'] as $answer) {
            $stmt = $pdo->prepare("INSERT INTO user_answers (result_id, question_id, selected_answer, is_correct) VALUES (?, ?, ?, ?)");
            $stmt->execute([$result_id, $answer['question_id'], $answer['selected_answer'], $answer['is_correct']]);
        }

        $_SESSION['last_result_id'] = $result_id;
        header("Location: result.php");
        exit();
    }
}

$current_question_index = $_SESSION['current_question'];
$question_id = $_SESSION['quiz_questions'][$current_question_index];

$stmt = $pdo->prepare("SELECT * FROM questions WHERE id = ?");
$stmt->execute([$question_id]);
$question = $stmt->fetch();

if (!$question) {
    header("Location: result.php");
    exit();
}

$update = $pdo->prepare("UPDATE questions SET used_count = used_count + 1 WHERE id = ?");
$update->execute([$question['id']]);

// Progress calculation
$progress = (($current_question_index + 1) / 10) * 100;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz - Indian Freedom Fighters</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --saffron: #FF9933;
            --white: #FFFFFF;
            --green: #138808;
            --blue: #000080;
            --light-blue: #4169E1;
            --dark-blue: #000066;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            margin: 0;
            padding: 0;
            background: url('https://designethical.com/assets/images/stock/image/2024/07/29/66a74d7ae7d561722240378.jpg') no-repeat center center fixed;
            background-size: cover;
            position: relative;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        body::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
          
            z-index: -1;
        }
        
        .quiz-container {
            width: 90%;
            max-width: 800px;
            background: linear-gradient(145deg, rgba(255,255,255,0.95) 0%, rgba(255,255,255,0.98) 100%);
            border-radius: 15px;
            padding: 2.5rem;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.3);
            margin: 2rem auto;
        }
        
        .quiz-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 8px;
            background: linear-gradient(90deg, var(--saffron) 33%, white 33%, white 66%, var(--green) 66%);
        }
        
        h1 {
            text-align: center;
            color: var(--dark-blue);
            margin-bottom: 1.8rem;
            font-size: 2.2rem;
            position: relative;
            padding-bottom: 15px;
            font-weight: 700;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.1);
        }
        
        h1::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 30%;
            width: 40%;
            height: 4px;
            background: linear-gradient(90deg, var(--saffron), var(--green));
            border-radius: 2px;
        }
        
        h2 {
            color: var(--dark-blue);
            margin-bottom: 1.8rem;
            font-size: 1.4rem;
            line-height: 1.6;
            font-weight: 600;
        }
        
        .progress-container {
            width: 100%;
            background-color: #f1f1f1;
            border-radius: 10px;
            margin-bottom: 2rem;
            height: 12px;
            position: relative;
            overflow: hidden;
            box-shadow: inset 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .progress-bar {
            height: 100%;
            background: linear-gradient(90deg, var(--saffron), var(--green));
            width: <?php echo $progress; ?>%;
            border-radius: 10px;
            transition: width 0.4s ease;
            position: relative;
        }
        
        .progress-text {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--dark-blue);
            font-weight: bold;
            font-size: 0.9rem;
        }
        
        .question-counter {
            text-align: right;
            margin-bottom: 1.5rem;
            color: var(--dark-blue);
            font-weight: 600;
            font-size: 1rem;
        }
        
        .options {
            margin: 2rem 0;
        }
        
        .options label {
            display: block;
            padding: 15px 20px;
            margin-bottom: 12px;
            background-color: rgba(255, 255, 255, 0.9);
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 1.1rem;
            color: #333;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        
        .options label:hover {
            background-color: #f5f5f5;
            border-color: var(--saffron);
            transform: translateY(-2px);
            box-shadow: 0 5px 10px rgba(0,0,0,0.1);
        }
        
        .options input[type="radio"] {
            margin-right: 12px;
            transform: scale(1.2);
            accent-color: var(--green);
        }
        
        button[type="submit"] {
            display: block;
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, var(--saffron), var(--green));
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 1.2rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 1.5rem;
            letter-spacing: 0.5px;
            text-shadow: 0 1px 1px rgba(0,0,0,0.2);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
        }
        
        button[type="submit"]:hover {
            background: linear-gradient(135deg, #e68a00, #0d7000);
            transform: translateY(-3px);
            box-shadow: 0 7px 20px rgba(0, 0, 0, 0.2);
        }
        
        button[type="submit"]:active {
            transform: translateY(1px);
        }
        
        .question-image {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
            margin: 1.5rem 0;
            border: 3px solid var(--white);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
            display: block;
            margin-left: auto;
            margin-right: auto;
            max-height: 250px;
            object-fit: cover;
        }
        
        .ashoka-chakra {
            position: absolute;
            top: 20px;
            right: 20px;
            width: 50px;
            height: 50px;
            opacity: 0.1;
        }
        
        .flag-decoration {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 10px;
            background: linear-gradient(90deg, var(--saffron) 33%, white 33%, white 66%, var(--green) 66%);
        }
        
        @media (max-width: 768px) {
            .quiz-container {
                width: 95%;
                padding: 1.8rem;
            }
            
            h1 {
                font-size: 1.8rem;
                padding-bottom: 12px;
            }
            
            h2 {
                font-size: 1.2rem;
            }
            
            .options label {
                padding: 12px 15px;
                font-size: 1rem;
            }
            
            button[type="submit"] {
                padding: 12px;
                font-size: 1.1rem;
            }
        }
    </style>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="quiz-container">
        <svg class="ashoka-chakra" viewBox="0 0 24 24">
            <circle cx="12" cy="12" r="10" fill="none" stroke="var(--blue)" stroke-width="1"/>
            <circle cx="12" cy="12" r="1" fill="var(--blue)"/>
            <line x1="12" y1="2" x2="12" y2="4" stroke="var(--blue)" stroke-width="1"/>
            <line x1="12" y1="20" x2="12" y2="22" stroke="var(--blue)" stroke-width="1"/>
            <line x1="4" y1="12" x2="6" y2="12" stroke="var(--blue)" stroke-width="1"/>
            <line x1="18" y1="12" x2="20" y2="12" stroke="var(--blue)" stroke-width="1"/>
            <line x1="4.93" y1="4.93" x2="6.34" y2="6.34" stroke="var(--blue)" stroke-width="1"/>
            <line x1="17.66" y1="17.66" x2="19.07" y2="19.07" stroke="var(--blue)" stroke-width="1"/>
            <line x1="4.93" y1="19.07" x2="6.34" y2="17.66" stroke="var(--blue)" stroke-width="1"/>
            <line x1="17.66" y1="6.34" x2="19.07" y2="4.93" stroke="var(--blue)" stroke-width="1"/>
        </svg>
        
        <h1><i class="fas fa-flag"></i> Indian Freedom Fighters Quiz</h1>
        
        <div class="question-counter">
            Question <?php echo $current_question_index + 1; ?> of 10
        </div>
        
        <div class="progress-container">
            <div class="progress-bar"></div>
            <div class="progress-text"><?php echo round($progress); ?>%</div>
        </div>
        
        <form method="POST">
            <input type="hidden" name="question_id" value="<?php echo $question['id']; ?>">
            <h2><?php echo htmlspecialchars($question['question']); ?></h2>

            <?php if (!empty($question['image_path'])): ?>
                <img src="<?php echo htmlspecialchars($question['image_path']); ?>" alt="Question Image" class="question-image">
            <?php endif; ?>

            <div class="options">
                <label><input type="radio" name="answer" value="1" required> <?php echo htmlspecialchars($question['option1']); ?></label>
                <label><input type="radio" name="answer" value="2"> <?php echo htmlspecialchars($question['option2']); ?></label>
                <label><input type="radio" name="answer" value="3"> <?php echo htmlspecialchars($question['option3']); ?></label>
                <label><input type="radio" name="answer" value="4"> <?php echo htmlspecialchars($question['option4']); ?></label>
            </div>

            <button type="submit">
                <?php echo ($current_question_index < 9) ? 'Next Question <i class="fas fa-arrow-right"></i>' : 'Finish Quiz <i class="fas fa-flag-checkered"></i>'; ?>
            </button>
        </form>
        
        <div class="flag-decoration"></div>
    </div>
    
    <script>
        // Enhanced option selection animation
        document.querySelectorAll('.options input[type="radio"]').forEach(radio => {
            radio.addEventListener('change', function() {
                document.querySelectorAll('.options label').forEach(label => {
                    label.style.backgroundColor = 'rgba(255, 255, 255, 0.9)';
                    label.style.borderColor = '#e0e0e0';
                    label.style.transform = 'translateY(0)';
                });
                
                if (this.checked) {
                    this.parentElement.style.backgroundColor = 'rgba(19, 136, 8, 0.1)';
                    this.parentElement.style.borderColor = 'var(--green)';
                    this.parentElement.style.transform = 'translateY(-3px)';
                }
            });
        });
    </script>
</body>
</html>