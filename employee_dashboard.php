<?php
session_start();
if (!isset($_SESSION['employee_id'])) {
    header("Location: login.php");
    exit();
}
if (!isset($_SESSION['welcome_shown'])) {
    $_SESSION['welcome_shown'] = true;
    $showWelcome = true;
} else {
    $showWelcome = false;
}

$pageTitle = "Employee Dashboard";
include 'sidebar.php';

$employee_id = $_SESSION['employee_id'];

// Task counts for pie chart and widgets
function get_task_count($mysqli, $employee_id, $status = null) {
    $query = "SELECT COUNT(*) as total FROM task_assignments ta
              JOIN tasks t ON ta.task_id = t.id
              WHERE ta.employee_id = ?";
    if ($status) {
        $query .= " AND t.status = ?";
        $stmt = $mysqli->prepare($query);
        $stmt->bind_param("is", $employee_id, $status);
    } else {
        $stmt = $mysqli->prepare($query);
        $stmt->bind_param("i", $employee_id);
    }
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $result['total'] ?? 0;
}

$total_tasks = get_task_count($mysqli, $employee_id);
$completed_tasks = get_task_count($mysqli, $employee_id, 'Completed');
$pending_tasks = get_task_count($mysqli, $employee_id, 'Pending');
$in_progress_tasks = get_task_count($mysqli, $employee_id, 'In Progress');

// Calendar events
$calendar_events = [];
$stmt = $mysqli->prepare("
    SELECT t.title, t.due_date 
    FROM tasks t
    JOIN task_assignments ta ON t.id = ta.task_id
    WHERE ta.employee_id = ? AND t.due_date IS NOT NULL
");
$stmt->bind_param("i", $employee_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $calendar_events[] = [
        'title' => $row['title'],
        'start' => date('Y-m-d', strtotime($row['due_date']))
    ];
}
$stmt->close();
$calendar_json = json_encode($calendar_events);

// Notifications
$notifications = [];
$stmt = $mysqli->prepare("SELECT * FROM notifications WHERE employee_id = ? ORDER BY created_at DESC LIMIT 5");
$stmt->bind_param("i", $employee_id);
$stmt->execute();
$res = $stmt->get_result();
while ($n = $res->fetch_assoc()) {
    $notifications[] = $n;
}
$stmt->close();
?>

<div class="content-wrapper">
  <section class="content-header">
    <h1>Welcome <?= htmlspecialchars($_SESSION['employee_name']) ?>
      <small>Here’s your task overview</small>
    </h1>
  </section>

  <section class="content">
    <?php if ($showWelcome): ?>
  <div class="alert alert-success alert-dismissible">
    <button type="button" class="close" data-dismiss="alert">&times;</button>
    <h4><i class="icon fa fa-check"></i> Welcome!</h4>
    You’ve successfully logged in, <?= htmlspecialchars($_SESSION['employee_name']) ?>.
  </div>
<?php endif; ?>

    <div class="row">
      <!-- Notifications -->
      <div class="col-md-12">
        <?php foreach ($notifications as $note): ?>
          <div class="alert alert-<?= $note['type'] ?> alert-dismissible">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <?= htmlspecialchars($note['message']) ?>
          </div>
        <?php endforeach; ?>
      </div>

      <!-- Task Widgets -->
      <?php
      $widgets = [
        ['color' => 'aqua', 'icon' => 'tasks', 'label' => 'Total Tasks', 'count' => $total_tasks],
        ['color' => 'green', 'icon' => 'check-circle', 'label' => 'Completed', 'count' => $completed_tasks],
        ['color' => 'yellow', 'icon' => 'clock-o', 'label' => 'Pending', 'count' => $pending_tasks],
        ['color' => 'orange', 'icon' => 'spinner fa-spin', 'label' => 'In Progress', 'count' => $in_progress_tasks],
      ];
      foreach ($widgets as $w): ?>
        <div class="col-md-3 col-sm-6 col-xs-12">
          <div class="info-box bg-<?= $w['color'] ?>">
            <span class="info-box-icon"><i class="fa fa-<?= $w['icon'] ?>"></i></span>
            <div class="info-box-content">
              <span class="info-box-text"><?= $w['label'] ?></span>
              <span class="info-box-number"><?= $w['count'] ?></span>
              <div class="progress"><div class="progress-bar" style="width:100%"></div></div>
              <span class="progress-description">Updated </span>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- Pie Chart -->
    <div class="row">
      <div class="col-md-6">
        <div class="box box-success">
          <div class="box-header with-border"><h3 class="box-title">Task Distribution</h3></div>
          <div class="box-body">
            <canvas id="taskPieChart"></canvas>
          </div>
        </div>
      </div>

      <!-- Calendar -->
      <div class="col-md-6">
        <div class="box box-primary">
          <div class="box-header with-border"><h3 class="box-title">Due Date Calendar</h3></div>
          <div class="box-body"><div id="calendar"></div></div>
        </div>
      </div>
    </div>

    <!-- Task Table -->
    <div class="box box-danger">
      <div class="box-header with-border"><h3 class="box-title">Task Summary</h3></div>
      <div class="box-body table-responsive">
        <table class="table table-bordered table-striped">
          <thead>
            <tr>
              <th>Title</th>
              <th>Status</th>
              <th>Due Date</th>
              <th>Priority</th>
              <th>Client</th>
              <th>Department</th>
            </tr>
          </thead>
          <tbody>
            <?php
            $stmt = $mysqli->prepare("
              SELECT t.title, t.status, t.due_date, t.priority, t.client_name, d.name as department_name
              FROM tasks t
              JOIN task_assignments ta ON t.id = ta.task_id
              LEFT JOIN departments d ON t.department_id = d.id
              WHERE ta.employee_id = ?
            ");
            $stmt->bind_param("i", $employee_id);
            $stmt->execute();
            $res = $stmt->get_result();
            while ($row = $res->fetch_assoc()): ?>
              <tr>
                <td><?= htmlspecialchars($row['title']) ?></td>
                <td><?= htmlspecialchars($row['status']) ?></td>
                <td><?= htmlspecialchars($row['due_date']) ?></td>
                <td><?= htmlspecialchars($row['priority']) ?></td>
                <td><?= htmlspecialchars($row['client_name']) ?></td>
                <td><?= htmlspecialchars($row['department_name']) ?></td>
              </tr>
            <?php endwhile; ?>
            <?php $stmt->close(); ?>
          </tbody>
        </table>
      </div>
    </div>
  </section>
</div>

<?php include 'footer.php'; ?>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
$(document).ready(function() {
  // FullCalendar
  $('#calendar').fullCalendar({
    height: 450,
    header: {
      left: 'prev,next today',
      center: 'title',
      right: 'month,agendaWeek'
    },
    events: <?= $calendar_json ?>
  });

  // Pie Chart with labels
  var ctx = document.getElementById('taskPieChart').getContext('2d');
  new Chart(ctx, {
    type: 'pie',
    data: {
      labels: ['Completed Tasks (<?= $completed_tasks ?>)', 'Pending Tasks (<?= $pending_tasks ?>)', 'In Progress Tasks (<?= $in_progress_tasks ?>)'],
      datasets: [{
        data: [<?= $completed_tasks ?>, <?= $pending_tasks ?>, <?= $in_progress_tasks ?>],
        backgroundColor: ['#00a65a', '#f39c12', '#f56954']
      }]
    },
    options: {
      responsive: true,
      plugins: {
        legend: {
          display: true,
          position: 'bottom',
          labels: {
            font: {
              size: 14
            }
          }
        },
        tooltip: {
          callbacks: {
            label: function(context) {
              const label = context.label || '';
              const value = context.raw || 0;
              return label + ': ' + value + ' task(s)';
            }
          }
        }
      }
    }
  });
});
</script>

