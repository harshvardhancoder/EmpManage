<?php
session_start();
if (!isset($_SESSION['employee_id'])) {
    header("Location: login.php");
    exit();
}

$pageTitle = "My Profile";
include 'sidebar.php';

$employee_id = $_SESSION['employee_id'];

// Default profile images
$defaultMalePic = 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcS2amYoC3Sbo7zXr6dYH5hDE2_QyzGPO7Jd1w&s';
$defaultFemalePic = 'https://cdn-icons-png.freepik.com/512/4974/4974985.png';

// Get employee details with department name
$stmt = $mysqli->prepare("
    SELECT e.*, d.name AS department_name 
    FROM employees e
    LEFT JOIN departments d ON e.department_id = d.id
    WHERE e.id = ?
");
$stmt->bind_param("i", $employee_id);
$stmt->execute();
$result = $stmt->get_result();
$employee = $result->fetch_assoc();
$stmt->close();

// Handle missing data
$employee_name = htmlspecialchars($employee['name']);
$employee_email = htmlspecialchars($employee['email']);
$employee_sex = ($employee['sex'] == 2) ? "Female" : "Male";
$employee_department = htmlspecialchars($employee['department_name']);
$employee_joining_date = date("d M Y", strtotime($employee['joining_date']));
$employee_ecode = htmlspecialchars($employee['ecode']);
$profile_img = ($employee['sex'] == 2) ? $defaultFemalePic : $defaultMalePic;
?>

<!-- Content Wrapper -->
<div class="content-wrapper">
  <section class="content-header">
    <h1>My Profile <small>Employee Details</small></h1>
    <ol class="breadcrumb">
      <li><a href="#"><i class="fa fa-user"></i> Profile</a></li>
      <li class="active">Details</li>
    </ol>
  </section>

  <section class="content">
    <div class="box box-primary">
      <div class="box-body box-profile text-center">
        <img class="profile-user-img img-responsive img-circle" src="<?= $profile_img ?>" alt="User profile picture">
        <h3 class="profile-username text-center"><?= $employee_name ?></h3>
        <p class="text-muted text-center"><?= $employee_department ?></p>
      </div>

      <div class="box-body">
        <table class="table table-bordered table-hover">
          <tr>
            <th style="width: 30%;">Employee Code</th>
            <td><?= $employee_ecode ?></td>
          </tr>
          <tr>
            <th>Name</th>
            <td><?= $employee_name ?></td>
          </tr>
          <tr>
            <th>Email</th>
            <td><?= $employee_email ?></td>
          </tr>
          <tr>
            <th>Sex</th>
            <td><?= $employee_sex ?></td>
          </tr>
          <tr>
            <th>Join Date</th>
            <td><?= $employee_joining_date ?></td>
          </tr>
          <tr>
            <th>Department</th>
            <td><?= $employee_department ?></td>
          </tr>
        </table>
      </div>
    </div>
  </section>
</div>

<?php include 'footer.php'; ?>
