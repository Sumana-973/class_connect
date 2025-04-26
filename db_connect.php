<?php
$servername = "localhost";  // Change if using a different host
$username = "root";         // Database username (default for XAMPP)
$password = "root";             // Database password (default is empty for XAMPP)
$database = "class_portal"; // Your database name

// Create connection
$conn = new mysqli($servername, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
