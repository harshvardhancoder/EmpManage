<?php
session_start();

if (isset($_SESSION['employee_id'])) {
    // If logged in, go to dashboard
    header("Location: employee_dashboard.php");
} else {
    // If not logged in, go to login page
    header("Location: login.php");
}
exit();
