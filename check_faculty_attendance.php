<?php
include 'db_connect.php';

// Get current time
$current_time = date("H:i:s");

// Query to check faculty who haven't logged in within 10 minutes of class start time
$query = "SELECT u.id, u.email, u.name, t.start_time, t.subject 
          FROM users u
          JOIN timetable t ON u.id = t.faculty_id
          LEFT JOIN faculty_logins fl ON u.id = fl.faculty_id
          WHERE u.role = 'faculty' 
          AND TIMESTAMPDIFF(MINUTE, t.start_time, ?) BETWEEN 10 AND 20
          AND (fl.last_login IS NULL OR fl.last_login < t.start_time)";

$stmt = $conn->prepare($query);
$stmt->bind_param("s", $current_time);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $faculty_email = $row['email'];
    $faculty_name = $row['name'];
    $subject = $row['subject'];
    $class_time = $row['start_time'];

    // Send email notification
    $to = $faculty_email;
    $subject = "Attendance Reminder: $subject Class";
    $message = "Dear $faculty_name,\n\nYou have a scheduled class for '$subject' at $class_time, but you haven't logged in yet. Please log in as soon as possible.\n\nRegards,\nAdmin Team";
    $headers = "From: admin@example.com"; // Change to your email

    mail($to, $subject, $message, $headers);
}
?>
