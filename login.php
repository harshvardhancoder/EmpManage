<?php
session_start();
if (isset($_SESSION['employee_id'])) {
    header("Location: employee_dashboard.php");
    exit();
}

$pageTitle = "Employee Login";
include 'header.php';
include 'db.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Generate CAPTCHA
    $number1 = rand(1, 9);
    $number2 = rand(1, 9);
    $operators = ['+', '-'];
    $operator = $operators[array_rand($operators)];
    $_SESSION['captcha_question'] = "$number1 $operator $number2";

    $_SESSION['captcha_result'] = ($operator === '+') ? ($number1 + $number2) : ($number1 - $number2);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $captcha_input = $_POST['captcha'];

    if (empty($email) || empty($password) || $captcha_input === '') {
        $error = "All fields are required.";
    } elseif ((int)$captcha_input !== $_SESSION['captcha_result']) {
        $error = "Captcha answer is incorrect.";
    } else {
        $stmt = $mysqli->prepare("SELECT id, name, email, password, status FROM employees WHERE email = ?");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $employee = $result->fetch_assoc();

            if ($employee['status'] !== 'Active') {
                $error = "Your account is inactive. Contact admin.";
            } elseif ($password === $employee['password']) {
                $_SESSION['employee_id'] = $employee['id'];
                $_SESSION['employee_name'] = $employee['name'];
                $success = "Login successful! Redirecting...";
            } else {
                $error = "Incorrect password.";
            }
        } else {
            $error = "No account found with that email.";
        }

        $stmt->close();
    }
}
?>

<body class="hold-transition login-page">
<div class="login-box">
  <div class="login-logo">
    <b>Employee</b> Login
  </div>
  <div class="login-box-body">
    <p class="login-box-msg">Sign in to start your session</p>

    <form method="post">
      <div class="form-group has-feedback">
        <input type="email" name="email" class="form-control" placeholder="Email" required>
        <span class="glyphicon glyphicon-envelope form-control-feedback"></span>
      </div>

      <div class="form-group has-feedback">
        <input type="password" name="password" class="form-control" placeholder="Password" required>
        <span class="glyphicon glyphicon-lock form-control-feedback"></span>
      </div>

      <div class="form-group">
        <label>Captcha: <?= $_SESSION['captcha_question'] ?> = ?</label>
        <input type="number" name="captcha" class="form-control" required>
      </div>

      <div class="row">
        <div class="col-xs-8"></div>
        <div class="col-xs-4">
          <button type="submit" class="btn btn-primary btn-block btn-flat">Login</button>
        </div>
      </div>
    </form>
  </div>
</div>

<?php include 'footer.php'; ?>

<?php if (!empty($error)): ?>
<script>
Swal.fire({
  icon: 'error',
  title: 'Login Failed',
  text: <?= json_encode($error) ?>,
  confirmButtonColor: '#d33'
});
</script>
<?php endif; ?>

<?php if (!empty($success)): ?>
<script>
Swal.fire({
  icon: 'success',
  title: 'Success',
  text: <?= json_encode($success) ?>,
  timer: 1500,
  showConfirmButton: false
}).then(() => {
  window.location.href = "employee_dashboard.php";
});
</script>
<?php endif; ?>
