<?php
session_start();
include 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $query = "SELECT id, password, role FROM users WHERE email = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];

            // If faculty logs in, update last_login time
            if ($user['role'] === 'faculty') {
                $updateQuery = "INSERT INTO faculty_logins (faculty_id) VALUES (?) 
                                ON DUPLICATE KEY UPDATE last_login = CURRENT_TIMESTAMP";
                $updateStmt = $conn->prepare($updateQuery);
                $updateStmt->bind_param("i", $user['id']);
                $updateStmt->execute();
            }

            // Redirect based on role
            header("Location: " . ($user['role'] === 'faculty' ? "faculty_dashboard.php" : "student_dashboard.php"));
            exit();
        } else {
            $_SESSION['error'] = "Invalid email or password!";
        }
    } else {
        $_SESSION['error'] = "Invalid email or password!";
    }
    
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            text-align: center;
            background-image: url('c4.jpg'); /* Ensure the background image is available */
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            color: white;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .container {
            background: rgba(0, 0, 0, 0.6);
            padding: 30px;
            border-radius: 10px;
            width: 350px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.5);
        }
        input, button {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: none;
            border-radius: 5px;
            font-size: 16px;
        }
        input {
            background: #f9f9f9;
            color: black;
        }
        button {
            background: #28a745;
            color: white;
            cursor: pointer;
            transition: 0.3s;
        }
        button:hover {
            background: #218838;
        }
        .error {
            color: red;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Login</h2>
    
    <?php
    if (isset($_SESSION['error'])) {
        echo "<p class='error'>{$_SESSION['error']}</p>";
        unset($_SESSION['error']); // Remove error after displaying
    }
    ?>
    
    <form method="POST">
        <input type="email" name="email" placeholder="Email" required>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit">Login</button>
    </form>
</div>

</body>
</html>
