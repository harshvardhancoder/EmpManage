<?php
session_start();
    // Header + CSS/JS includes + body class
include 'sidebar.php';   // Sidebar navigation

$employee_id = $_SESSION['employee_id'] ?? 0;
if (!$employee_id) {
    header('Location: login.php');
    exit;
}

// Function to get count of tasks by status assigned to current employee
function get_task_status_count($mysqli, $employee_id, $status) {
    $stmt = $mysqli->prepare("SELECT COUNT(*) as count FROM tasks t 
        INNER JOIN task_assignments ta ON t.id = ta.task_id
        WHERE ta.employee_id = ? AND t.status = ?");
    $stmt->bind_param("is", $employee_id, $status);
    $stmt->execute();
    $result = $stmt->get_result();
    $count = 0;
    if ($row = $result->fetch_assoc()) {
        $count = $row['count'];
    }
    $stmt->close();
    return $count;
}

// Get task counts by status
$not_started_count = get_task_status_count($mysqli, $employee_id, 'Not Started');
$in_progress_count = get_task_status_count($mysqli, $employee_id, 'In Progress');
$completed_count = get_task_status_count($mysqli, $employee_id, 'Completed');

// Get total hours worked by employee from timesheets
$stmt = $mysqli->prepare("SELECT IFNULL(SUM(hours_spent), 0) AS total_hours FROM task_timesheets WHERE employee_id = ?");
$stmt->bind_param("i", $employee_id);
$stmt->execute();
$result = $stmt->get_result();
$total_hours_worked = 0;
if ($row = $result->fetch_assoc()) {
    $total_hours_worked = $row['total_hours'];
}
$stmt->close();

// Get tasks assigned to employee
$stmt = $mysqli->prepare("SELECT t.id, t.title, t.client_name, t.priority, t.status, t.due_date 
    FROM tasks t
    INNER JOIN task_assignments ta ON t.id = ta.task_id
    WHERE ta.employee_id = ?");
$stmt->bind_param("i", $employee_id);
$stmt->execute();
$tasks_result = $stmt->get_result();

?>

<div class="content-wrapper">
  <section class="content-header">
    <h1>Assigned Tasks</h1>
  </section>

  <section class="content">
    <div class="row">
      <!-- Not Started -->
      <div class="col-md-3 col-sm-6 col-xs-12">
        <div class="info-box">
          <span class="info-box-icon bg-red"><i class="fa fa-clock-o"></i></span>
          <div class="info-box-content">
            <span class="info-box-text">Not Started</span>
            <span class="info-box-number"><?= $not_started_count ?></span>
          </div>
        </div>
      </div>
      <!-- In Progress -->
      <div class="col-md-3 col-sm-6 col-xs-12">
        <div class="info-box">
          <span class="info-box-icon bg-yellow"><i class="fa fa-spinner"></i></span>
          <div class="info-box-content">
            <span class="info-box-text">In Progress</span>
            <span class="info-box-number"><?= $in_progress_count ?></span>
          </div>
        </div>
      </div>
      <!-- Completed -->
      <div class="col-md-3 col-sm-6 col-xs-12">
        <div class="info-box">
          <span class="info-box-icon bg-green"><i class="fa fa-check"></i></span>
          <div class="info-box-content">
            <span class="info-box-text">Completed</span>
            <span class="info-box-number"><?= $completed_count ?></span>
          </div>
        </div>
      </div>
      <!-- Total Hours Worked -->
      <div class="col-md-3 col-sm-6 col-xs-12">
        <div class="info-box">
          <span class="info-box-icon bg-blue"><i class="fa fa-hourglass-half"></i></span>
          <div class="info-box-content">
            <span class="info-box-text">Total Hours Worked</span>
            <span class="info-box-number"><?= $total_hours_worked ?></span>
          </div>
        </div>
      </div>
    </div>

    <div class="box box-primary">
      <div class="box-header with-border">
        <h3 class="box-title">Tasks Assigned to You</h3>
      </div>
      <div class="box-body">
        <table id="tasksTable" class="table table-bordered table-striped">
          <thead>
            <tr>
              <th>SL No</th>
              <th>Title</th>
              <th>Client</th>
              <th>Priority</th>
              <th>Status</th>
              <th>Due Date</th>
            </tr>
          </thead>
          <tbody>
            <?php
            $sl = 1;
            while ($row = $tasks_result->fetch_assoc()):
              $priority_class = 'label-default';
              switch (strtolower($row['priority'])) {
                case 'high': $priority_class = 'label-danger'; break;
                case 'medium': $priority_class = 'label-warning'; break;
                case 'low': $priority_class = 'label-info'; break;
              }

              $status = $row['status'];
              $status_label = '';
              $status_class = '';
              if ($status === 'Not Started') {
                $status_label = 'Not Started';
                $status_class = 'label-danger';
              } elseif ($status === 'In Progress') {
                $status_label = 'In Progress';
                $status_class = 'label-warning';
              } elseif ($status === 'Completed') {
                $status_label = 'Completed';
                $status_class = 'label-success';
              } else {
                $status_label = htmlspecialchars($status);
                $status_class = 'label-default';
              }
            ?>
            <tr>
              <td><?= $sl++ ?></td>
              <td><?= htmlspecialchars($row['title']) ?></td>
              <td><?= htmlspecialchars($row['client_name']) ?></td>
              <td><span class="label <?= $priority_class ?>"><?= ucfirst($row['priority']) ?></span></td>
              <td><span class="label <?= $status_class ?>"><?= $status_label ?></span></td>
              <td><?= date('d-m-Y', strtotime($row['due_date'])) ?></td>
            </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>
  </section>
</div>

<script>
$(document).ready(function() {
  $('#tasksTable').DataTable({
    "ordering": true,
    "pageLength": 10,
    "lengthChange": false,
    "autoWidth": false,
    "columnDefs": [
      { "orderable": false, "targets": [4, 5] }
    ]
  });
});
</script>

<?php
$stmt->close();
include 'footer.php';
?>
