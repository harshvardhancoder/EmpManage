<?php
session_start();
include 'sidebar.php'; // Includes DB connection and header
$employee_id = $_SESSION['employee_id'] ?? 0;

if (!$employee_id) {
    echo "<script>Swal.fire('Unauthorized', 'Please log in.', 'error');</script>";
    exit;
}

$employee_stmt = $mysqli->prepare("SELECT name FROM employees WHERE id = ?");
$employee_stmt->bind_param("i", $employee_id);
$employee_stmt->execute();
$employee = $employee_stmt->get_result()->fetch_assoc();
$employee_name = $employee['name'] ?? 'Unknown';
$employee_stmt->close();

$tasks_stmt = $mysqli->prepare("
    SELECT t.* FROM tasks t
    JOIN task_assignments ta ON ta.task_id = t.id
    WHERE ta.employee_id = ?
");
$tasks_stmt->bind_param("i", $employee_id);
$tasks_stmt->execute();
$tasks = $tasks_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$tasks_stmt->close();
?>

<div class="content-wrapper">
  <section class="content-header">
    <h1>Task Report</h1>
    <p>Employee: <strong><?= htmlspecialchars($employee_name) ?></strong></p>
  </section>

  <section class="content">

    <div class="box box-info">
      <div class="box-header with-border">
        <h3 class="box-title">Generate Report</h3>
      </div>
      <div class="box-body">
        <form method="post">
          <div class="form-group">
            <label>Select Task</label>
            <select name="task_id" class="form-control" required>
              <option value="">-- Select Task --</option>
              <?php foreach ($tasks as $t): ?>
                <option value="<?= $t['id'] ?>" <?= isset($_POST['task_id']) && $_POST['task_id'] == $t['id'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars($t['title']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <button type="submit" class="btn btn-primary">Generate Report</button>
          <button type="button" class="btn btn-default" onclick="printReport()">Print Report</button>
        </form>
      </div>
    </div>

    <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['task_id'])): 
      $task_id = intval($_POST['task_id']);

      $task_stmt = $mysqli->prepare("SELECT * FROM tasks WHERE id = ?");
      $task_stmt->bind_param("i", $task_id);
      $task_stmt->execute();
      $task = $task_stmt->get_result()->fetch_assoc();
      $task_stmt->close();

      // Subtasks
      $sub_stmt = $mysqli->prepare("SELECT * FROM subtasks WHERE parent_task_id = ?");
      $sub_stmt->bind_param("i", $task_id);
      $sub_stmt->execute();
      $subtasks = $sub_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
      $sub_stmt->close();

      // Assigned Employees
      $emp_stmt = $mysqli->prepare("SELECT e.name FROM employees e JOIN task_assignments ta ON ta.employee_id = e.id WHERE ta.task_id = ?");
      $emp_stmt->bind_param("i", $task_id);
      $emp_stmt->execute();
      $assignees = $emp_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
      $emp_stmt->close();

      // Comments
      $cmt_stmt = $mysqli->prepare("SELECT comment, created_at FROM task_comments WHERE task_id = ? AND employee_id = ? ORDER BY created_at DESC");
      $cmt_stmt->bind_param("ii", $task_id, $employee_id);
      $cmt_stmt->execute();
      $comments = $cmt_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
      $cmt_stmt->close();

      // Logs
      $log_stmt = $mysqli->prepare("SELECT action, log_time, remarks FROM logs WHERE task_id = ? AND employee_id = ? ORDER BY log_time DESC");
      $log_stmt->bind_param("ii", $task_id, $employee_id);
      $log_stmt->execute();
      $logs = $log_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
      $log_stmt->close();

      // Timesheet
      $time_stmt = $mysqli->prepare("SELECT date_worked, hours_spent, description FROM task_timesheets WHERE task_id = ? AND employee_id = ? ORDER BY date_worked");
      $time_stmt->bind_param("ii", $task_id, $employee_id);
      $time_stmt->execute();
      $timesheets = $time_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
      $time_stmt->close();
    ?>

    <div id="printSection">
      <div class="box box-primary">
        <div class="box-header with-border"><h3 class="box-title">Task Details</h3></div>
        <div class="box-body">
          <table class="table table-bordered">
            <tr><th>Title</th><td><?= htmlspecialchars($task['title']) ?></td></tr>
            <tr><th>Status</th><td><?= $task['status'] ?></td></tr>
            <tr><th>Priority</th><td><?= $task['priority'] ?></td></tr>
            <tr><th>Start Date</th><td><?= $task['start_date'] ?></td></tr>
            <tr><th>Due Date</th><td><?= $task['due_date'] ?></td></tr>
            <tr><th>Description</th><td><?= nl2br(htmlspecialchars($task['description'])) ?></td></tr>
          </table>
        </div>
      </div>

      <div class="box box-success">
        <div class="box-header with-border"><h3 class="box-title">Assigned Employees</h3></div>
        <div class="box-body">
          <ul><?php foreach ($assignees as $a): ?><li><?= htmlspecialchars($a['name']) ?></li><?php endforeach; ?></ul>
        </div>
      </div>

      <div class="box box-warning">
        <div class="box-header with-border"><h3 class="box-title">Subtasks</h3></div>
        <div class="box-body">
          <table class="table table-bordered">
            <thead>
              <tr><th>Title</th><th>Status</th><th>Due</th><th>Priority</th><th>Updated At</th><th>On Time?</th></tr>
            </thead>
            <tbody>
              <?php foreach ($subtasks as $s): ?>
              <tr>
                <td><?= $s['title'] ?></td>
                <td><?= $s['status'] ?></td>
                <td><?= $s['due_date'] ?></td>
                <td><?= $s['priority'] ?></td>
                <td><?= $s['updated_at'] ?></td>
                <td>
                  <?php
                    if ($s['status'] === 'Completed') {
                      echo (strtotime($s['updated_at']) <= strtotime($s['due_date']))
                        ? "<span class='text-success'>On Time</span>"
                        : "<span class='text-danger'>Overdue</span>";
                    } else {
                      echo "<em>Pending</em>";
                    }
                  ?>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

      <div class="box box-info">
        <div class="box-header with-border"><h3 class="box-title">Logs</h3></div>
        <div class="box-body">
          <table class="table table-bordered">
            <thead><tr><th>Time</th><th>Action</th><th>Remarks</th></tr></thead>
            <tbody>
              <?php foreach ($logs as $log): ?>
              <tr><td><?= $log['log_time'] ?></td><td><?= $log['action'] ?></td><td><?= nl2br(htmlspecialchars($log['remarks'])) ?></td></tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

      <div class="box box-default">
        <div class="box-header with-border"><h3 class="box-title">Work Summary (Timesheet)</h3></div>
        <div class="box-body">
          <table class="table table-bordered">
            <thead><tr><th>Date</th><th>Hours</th><th>Description</th></tr></thead>
            <tbody>
              <?php 
              $total_hours = 0;
              foreach ($timesheets as $ts): 
                $total_hours += $ts['hours_spent'];
              ?>
              <tr><td><?= $ts['date_worked'] ?></td><td><?= $ts['hours_spent'] ?></td><td><?= nl2br(htmlspecialchars($ts['description'])) ?></td></tr>
              <?php endforeach; ?>
            </tbody>
            <tfoot><tr><th>Total</th><th><?= $total_hours ?></th><th></th></tr></tfoot>
          </table>
        </div>
      </div>

      <div class="box box-danger">
        <div class="box-header with-border"><h3 class="box-title">Comments</h3></div>
        <div class="box-body">
          <?php if (empty($comments)): echo "<p><em>No comments made by you.</em></p>"; else: ?>
            <ul>
              <?php foreach ($comments as $c): ?>
              <li><strong><?= $c['created_at'] ?>:</strong> <?= nl2br(htmlspecialchars($c['comment'])) ?></li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <script>
      function printReport() {
        const content = document.getElementById("printSection").innerHTML;
        const win = window.open("", "", "width=1000,height=700");
        win.document.write(`
          <html><head><title>Task Report</title>
          <link rel="stylesheet" href="bootstrap.min.css">
          <style>
            body { font-family: Arial; padding: 20px; }
            table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
            table, th, td { border: 1px solid #000; padding: 5px; }
          </style>
          </head><body>
          <h2>Task Report for <?= htmlspecialchars($employee_name) ?></h2>
          <p><strong>Printed on:</strong> <?= date("Y-m-d H:i:s") ?></p>
          ${content}
          </body></html>
        `);
        win.document.close();
        win.print();
      }
    </script>

    <?php endif; ?>
  </section>
</div>

<?php include 'footer.php'; ?>
