<?php
session_start();

// If user is not logged in, just redirect immediately
if (!isset($_SESSION['employee_id'])) {
    header('Location: login.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Logout</title>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
<script>
  Swal.fire({
    icon: 'info',
    title: 'Logging you out...',
    text: 'You will be logged out in 5 seconds.',
    timer: 5000,
    timerProgressBar: true,
    showConfirmButton: false,
    allowOutsideClick: false,
    allowEscapeKey: false
  });

  setTimeout(() => {
    window.location.href = 'logout.php';
  }, 5000);
</script>
</body>
</html>
