<?php
session_start();
include 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $role = $_POST['role'];
    $faculty_id = null;
    $reg_number = null;
    $section_id = null;

    if ($role == "faculty") {
        if (!empty($_POST['faculty_id'])) {
            $faculty_id = $_POST['faculty_id'];
        }
    } else {
        if (empty($_POST['reg_number']) || empty($_POST['section'])) {
            die("<script>alert('Registration Number and Section are required for students!'); window.history.back();</script>");
        }
        $reg_number = $_POST['reg_number'];
        $section_name = $_POST['section'];

        // Check if section exists, if not insert it
        $stmt = $conn->prepare("SELECT id FROM sections WHERE section_name = ?");
        $stmt->bind_param("s", $section_name);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        if ($row) {
            $section_id = $row['id'];
        } else {
            $stmt = $conn->prepare("INSERT INTO sections (section_name) VALUES (?)");
            $stmt->bind_param("s", $section_name);
            $stmt->execute();
            $section_id = $stmt->insert_id;
        }
        $stmt->close();
    }

    // Insert user with section_id
    $stmt = $conn->prepare("INSERT INTO users (name, email, password, role, faculty_id, reg_number, section_id) VALUES (?, ?, ?, ?, ?, ?, ?)");

    if (!$stmt) {
        die("Statement preparation failed: " . $conn->error);
    }

    $stmt->bind_param("ssssssi", $name, $email, $password, $role, $faculty_id, $reg_number, $section_id);

    if ($stmt->execute()) {
        echo "<script>alert('Registration successful! Redirecting to login page...'); window.location.href='login.php';</script>";
    } else {
        echo "<script>alert('Error: " . $stmt->error . "');</script>";
    }

    $stmt->close();
    $conn->close();
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-image: url('c4.jpg');
            background-size: cover;
            background-position: center;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .container {
            background: rgba(0, 0, 0, 0.7);
            padding: 30px;
            border-radius: 10px;
            color: white;
            width: 350px;
            text-align: center;
        }
        input, select, button {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: none;
            border-radius: 5px;
            font-size: 16px;
        }
        input, select {
            background: #fff;
            color: black;
        }
        button {
            background: #ff7e5f;
            color: white;
            cursor: pointer;
            transition: 0.3s;
        }
        button:hover {
            background: #feb47b;
        }
        a {
            color: #ff7e5f;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }
    </style>
    <script>
        function toggleFields() {
            var role = document.getElementById("role").value;
            document.getElementById("studentFields").style.display = (role === "student") ? "block" : "none";
            document.getElementById("facultyFields").style.display = (role === "faculty") ? "block" : "none";
        }
    </script>
</head>
<body>

<div class="container">
    <h2>Register</h2>
    <form method="POST">
        <label>Name:</label>
        <input type="text" name="name" required>

        <label>Email:</label>
        <input type="email" name="email" required>

        <label>Password:</label>
        <input type="password" name="password" required>

        <label>Role:</label>
        <select name="role" id="role" onchange="toggleFields()" required>
            <option value="student">Student</option>
            <option value="faculty">Faculty</option>
        </select>

        <!-- Student fields -->
        <div id="studentFields">
            <label>Registration Number:</label>
            <input type="text" name="reg_number">
            
            <label>Section:</label>
            <input type="text" name="section">
        </div>

        <!-- Faculty fields -->
        <div id="facultyFields" style="display: none;">
            <label>Faculty ID (Optional):</label>
            <input type="text" name="faculty_id">
        </div>

        <button type="submit">Register</button>
    </form>
    <p>Already have an account? <a href="login.php">Login here</a></p>
</div>

</body>
</html>
