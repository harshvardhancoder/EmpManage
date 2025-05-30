<?php
include 'header.php';
include 'db.php';

if (!isset($_SESSION['employee_id'])) {
    header("Location: login.php");
    exit();
}

$employeeId = $_SESSION['employee_id'];

// Fetch employee details from DB
$stmt = $mysqli->prepare("SELECT name, profile_photo,position, sex FROM employees WHERE id = ?");
$stmt->bind_param('i', $employeeId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $employee = $result->fetch_assoc();
    $_SESSION['employee_name'] = $employee['name'];
    $_SESSION['position'] = $employee['position'];
    $_SESSION['employee_profile_photo'] = $employee['profile_photo'] ?? '';
    $_SESSION['employee_sex'] = $employee['sex'] ?? 1; // default male
}
$stmt->close();

// Default profile images
$defaultMalePic = 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcS2amYoC3Sbo7zXr6dYH5hDE2_QyzGPO7Jd1w&s';
$defaultFemalePic = 'https://cdn-icons-png.freepik.com/512/4974/4974985.png';

// Decide which profile photo to use
$profilePhoto = $_SESSION['employee_profile_photo'];
if (empty($profilePhoto) || !file_exists($profilePhoto)) {
    $profilePhoto = ($_SESSION['employee_sex'] == 2) ? $defaultFemalePic : $defaultMalePic;
}
?>

<body class="hold-transition skin-blue sidebar-mini">
<div class="wrapper">

<!-- Main Header -->
<header class="main-header">
  <!-- Logo -->
  <a href="employee_dashboard.php" class="logo">
    <!-- mini logo for sidebar mini 50x50 pixels -->
    <span class="logo-mini"><b>EMS</b></span>
    <!-- logo for regular state and mobile devices -->
    <span class="logo-lg"><b>Employee</b>MS</span>
  </a>

  <!-- Header Navbar -->
  <nav class="navbar navbar-static-top" role="navigation">
    <!-- Sidebar toggle button-->
    <a href="#" class="sidebar-toggle" data-toggle="push-menu" role="button">
      <span class="sr-only">Toggle navigation</span>
    </a>

    <!-- Navbar Right Menu -->
    <div class="navbar-custom-menu">
      <ul class="nav navbar-nav">

        <!-- User Account Dropdown -->
        <li class="dropdown user user-menu">
          <a href="#" class="dropdown-toggle" data-toggle="dropdown">
            <img src="<?= htmlspecialchars($profilePhoto) ?>" class="user-image" alt="User Image">
            <span class="hidden-xs"><?= htmlspecialchars($_SESSION['employee_name'] ?? 'Employee') ?></span>
          </a>
          <ul class="dropdown-menu">
            <!-- User image in dropdown -->
            <li class="user-header">
              <img src="<?= htmlspecialchars($profilePhoto) ?>" class="img-circle" alt="User Image">
              <p>
                <?= htmlspecialchars($_SESSION['employee_name'] ?? 'Employee') ?>
                <small>   <?= htmlspecialchars($_SESSION['position'] ?? 'Employee') ?></small>
              </p>
            </li>
            <!-- Menu Footer-->
            <li class="user-footer">
              <div class="pull-left">
                <a href="profile.php" class="btn btn-default btn-flat">Profile</a>
              </div>
              <div class="pull-right">
                <a href="logout_Confirm.php" class="btn btn-default btn-flat">Sign out</a>
              </div>
            </li>
          </ul>
        </li>

      </ul>
    </div>
  </nav>
</header>

<!-- Left side column. contains the sidebar -->
<aside class="main-sidebar">
  <!-- sidebar: style can be found in sidebar.less -->
  <section class="sidebar">

    <!-- Sidebar user panel -->
    <div class="user-panel">
      <div class="pull-left image">
        <img src="<?= htmlspecialchars($profilePhoto) ?>" class="img-circle" alt="User Image">
      </div>
      <div class="pull-left info">
        <p><?= htmlspecialchars($_SESSION['employee_name'] ?? 'Employee') ?></p>
        <a href="#"><i class="fa fa-circle text-success"></i> Online</a>
      </div>
    </div>

    <!-- sidebar menu -->
    <?php
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<ul class="sidebar-menu" data-widget="tree">
  <li class="header">NAVIGATION</li>

  <!-- Task Management Menu -->
  

  <!-- Other items outside submenus -->
  <li class="<?= ($currentPage == 'employee_dashboard.php') ? 'active' : '' ?>">
    <a href="employee_dashboard.php"><i class="fa fa-dashboard"></i> <span>Dashboard</span></a>
  </li>

  <li class="<?= ($currentPage == 'profile.php') ? 'active' : '' ?>">
    <a href="profile.php"><i class="fa fa-user"></i> <span>Profile</span></a>
  </li>

  <li class="treeview <?= in_array($currentPage, ['assigned_tasks.php', 'timesheet_user.php', 'manage.php', 'add_log.php']) ? 'active menu-open' : '' ?>">
    <a href="#">
      <i class="fa fa-tasks"></i> <span>Task Management</span>
      <span class="pull-right-container"><i class="fa fa-angle-left pull-right"></i></span>
    </a>
    <ul class="treeview-menu" style="<?= in_array($currentPage, ['assigned_tasks.php', 'timesheet_user.php', 'manage.php', 'add_log.php']) ? 'display: block;' : 'display: none;' ?>">
      <li class="<?= ($currentPage == 'assigned_tasks.php') ? 'active' : '' ?>">
        <a href="assigned_tasks.php"><i class="fa fa-circle-o"></i> Check Assigned Task</a>
      </li>
      <li class="<?= ($currentPage == 'timesheet_user.php') ? 'active' : '' ?>">
        <a href="timesheet_user.php"><i class="fa fa-circle-o"></i> Daily Work Time Entry</a>
      </li>
      <li class="<?= ($currentPage == 'manage.php') ? 'active' : '' ?>">
        <a href="manage.php"><i class="fa fa-circle-o"></i> Subtask Status& Comment</a>
      </li>
      <li class="<?= ($currentPage == 'add_log.php') ? 'active' : '' ?>">
        <a href="add_log.php"><i class="fa fa-circle-o"></i> Add Task Logs</a>
      </li>
    </ul>
  </li>

  <!-- Report Menu -->
  <li class="treeview <?= in_array($currentPage, ['my_task_report.php', 'assigned_tasks.php']) ? 'active menu-open' : '' ?>">
    <a href="#">
      <i class="fa fa-bar-chart"></i> <span>Report</span>
      <span class="pull-right-container"><i class="fa fa-angle-left pull-right"></i></span>
    </a>
    <ul class="treeview-menu" style="<?= in_array($currentPage, ['my_task_report.php', 'assigned_tasks.php']) ? 'display: block;' : 'display: none;' ?>">
      <li class="<?= ($currentPage == 'my_task_report.php') ? 'active' : '' ?>">
        <a href="my_task_report.php"><i class="fa fa-circle-o"></i> Employee Task Report</a>
      </li>
      <li class="<?= ($currentPage == 'assigned_tasks.php') ? 'active' : '' ?>">
        <a href="assigned_tasks.php"><i class="fa fa-circle-o"></i> Assigned Task Summary</a>
      </li>
    </ul>
  </li>
  <li>
    <a href="logout_Confirm.php"><i class="fa fa-sign-out"></i> <span>Logout</span></a>
  </li>

</ul>
  </section>
  <!-- /.sidebar -->
</aside>
