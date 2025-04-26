<?php
session_start();
session_unset(); // Clear session variables
session_destroy(); // Destroy the session

// Redirect to the register page
header("Location: register.php");
exit;
?>
