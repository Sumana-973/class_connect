<?php
session_start();
include 'db_connect.php';

// Ensure the user is logged in and is a student
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch Student's Section ID
$query = "SELECT section_id FROM users WHERE id = ?";
$stmt = $conn->prepare($query);
if (!$stmt) {
    die("SQL Error: " . $conn->error);
}

$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$student_section_id = $user['section_id'] ?? null;
$stmt->close();

if (!$student_section_id) {
    die("Error: Student section not found.");
}

// Fetch timetable based on student's section_id
$query = "SELECT t.subject, t.room_number, 
          (SELECT name FROM users WHERE id = t.faculty_id) AS faculty, 
          t.start_time, t.end_time 
          FROM timetable t
          WHERE t.section_id = ?";

$stmt = $conn->prepare($query);
if (!$stmt) {
    die("SQL Error: " . $conn->error);
}
$stmt->bind_param("i", $student_section_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-image: url('c3.jpg'); 
            background-size: cover;
            background-position: center;
            text-align: center;
            color: white;
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            flex-direction: column;
        }
        .container {
            background: rgba(0, 0, 0, 0.8);
            padding: 30px;
            border-radius: 10px;
            width: 80%;
            max-width: 700px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.5);
        }
        h2 {
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            color: black;
        }
        th, td {
            padding: 12px;
            border: 1px solid #ddd;
            text-align: center;
        }
        th {
            background: #28a745;
            color: white;
        }
        tr:nth-child(even) {
            background: #f2f2f2;
        }
        .logout-btn {
            margin-top: 20px;
            padding: 10px 15px;
            background: red;
            color: white;
            border: none;
            cursor: pointer;
            border-radius: 5px;
            font-size: 16px;
        }
        .logout-btn:hover {
            background: darkred;
        }
        .form-container {
            margin-top: 30px;
            background: rgba(255, 255, 255, 0.9);
            padding: 20px;
            border-radius: 10px;
            color: black;
            width: 100%;
            max-width: 500px;
        }
        input, button {
            margin: 10px 0;
            padding: 10px;
            width: 100%;
            border-radius: 5px;
        }
        button {
            background: #007bff;
            color: white;
            border: none;
        }
        button:hover {
            background: #0056b3;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Your Class Schedule (Auto-Detected)</h2>
    <table>
        <tr>
            <th>Subject</th>
            <th>Faculty</th>
            <th>Room Number</th>
            <th>Timings</th>
        </tr>
        <?php while ($row = $result->fetch_assoc()): ?>
        <tr>
            <td><?= htmlspecialchars($row['subject']) ?></td>
            <td><?= htmlspecialchars($row['faculty']) ?></td>
            <td><?= htmlspecialchars($row['room_number']) ?></td>
            <td><?= htmlspecialchars($row['start_time']) ?> - <?= htmlspecialchars($row['end_time']) ?></td>
        </tr>
        <?php endwhile; ?>
    </table>
</div>

<!-- Manual Timetable Retrieval -->
<div class="form-container">
    <h3>Retrieve Timetable Manually</h3>
    <form method="post">
        <label>Registration Number:</label>
        <input type="text" name="reg_number" required><br>

        <label>Section:</label>
        <input type="text" name="section" required><br>

        <label>Faculty Name (Optional):</label>
        <input type="text" name="faculty"><br>

        <button type="submit" name="retrieve">Retrieve Timetable</button>
    </form>

    <?php
    if (isset($_POST['retrieve'])) {
        $reg_number = $_POST['reg_number'];
        $section_name = $_POST['section'];
        $faculty_name = $_POST['faculty'];

        // Fetch section_id from the section name
        $section_query = "SELECT id FROM sections WHERE section_name = ?";
        $stmt = $conn->prepare($section_query);
        $stmt->bind_param("s", $section_name);
        $stmt->execute();
        $section_result = $stmt->get_result();
        $section_data = $section_result->fetch_assoc();
        $section_id = $section_data['id'] ?? null;
        $stmt->close();

        if (!$section_id) {
            echo "<p>Error: Invalid section.</p>";
        } else {
            // Fetch timetable based on section_id
            $sql = "SELECT t.subject, t.room_number, t.start_time, t.end_time, 
                           (SELECT name FROM users WHERE id = t.faculty_id) AS faculty
                    FROM timetable t WHERE t.section_id = ?";
            
            if (!empty($faculty_name)) {
                $sql .= " AND t.faculty_id = (SELECT id FROM users WHERE name = ?)";
            }

            $stmt = $conn->prepare($sql);
            if (!empty($faculty_name)) {
                $stmt->bind_param("is", $section_id, $faculty_name);
            } else {
                $stmt->bind_param("i", $section_id);
            }

            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                echo "<h3>Retrieved Timetable:</h3><table>";
                echo "<tr><th>Subject</th><th>Faculty</th><th>Room Number</th><th>Timings</th></tr>";
                while ($row = $result->fetch_assoc()) {
                    echo "<tr>
                            <td>{$row['subject']}</td>
                            <td>{$row['faculty']}</td>
                            <td>{$row['room_number']}</td>
                            <td>{$row['start_time']} - {$row['end_time']}</td>
                          </tr>";
                }
                echo "</table>";
            } else {
                echo "<p>No timetable found for the given details.</p>";
            }
            $stmt->close();
        }
    }
    ?>
</div>

<form action="logout.php" method="POST">
    <button type="submit" class="logout-btn">Logout</button>
</form>

</body>
</html>

<?php
$conn->close();
?>
