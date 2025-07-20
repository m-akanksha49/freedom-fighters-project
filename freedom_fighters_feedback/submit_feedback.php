<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    include 'db_config.php';

    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $message = trim($_POST['message']);

    if (!empty($name) && !empty($email) && !empty($message)) {
        $stmt = $conn->prepare("INSERT INTO contact_messages (name, email, message) VALUES (?, ?, ?)");
        if (!$stmt) {
            die("Prepare failed: " . $conn->error);
        }

        $stmt->bind_param("sss", $name, $email, $message);

        if ($stmt->execute()) {
            echo '
            <html>
            <head><title>Success</title>
            <link href="https://fonts.googleapis.com/css2?family=Poppins&display=swap" rel="stylesheet">
            <style>
              body {
                font-family: Poppins, sans-serif;
                background: #f1f8e9;
                display: flex;
                align-items: center;
                justify-content: center;
                height: 100vh;
              }
              .message {
                background: white;
                padding: 40px;
                border-radius: 12px;
                box-shadow: 0 5px 10px rgba(0,0,0,0.1);
                text-align: center;
              }
              .message h2 {
                color: #2e7d32;
              }
              .message a {
                display: inline-block;
                margin-top: 20px;
                text-decoration: none;
                background: #2e7d32;
                color: white;
                padding: 10px 20px;
                border-radius: 8px;
              }
            </style>
            </head>
            <body>
              <div class="message">
                <h2>✅ Thank you for your feedback!</h2>
                <p>We truly appreciate your contribution.</p>
                <a href="index.html">Submit More</a>
              </div>
            </body>
            </html>';
        } else {
            echo "Error: " . $stmt->error;
        }

        $stmt->close();
    } else {
        echo "Please fill in all fields.";
    }

    $conn->close();
}
?>
