<?php
session_start();
include 'sidebar.php'; // includes $mysqli and session check

$employee_id = $_SESSION['employee_id'] ?? 0;
if (!$employee_id) {
    header("Location: login.php");
    exit;
}

$selected_task_id = $_POST['task_id'] ?? ($_GET['task_id'] ?? null);
$message = $_GET['message'] ?? '';
$error = '';

// Handle "Start Task" modal submission
if (isset($_POST['start_task_confirm']) && isset($_POST['task_id_to_start'])) {
    $task_id = intval($_POST['task_id_to_start']);
    $disclaimer = $_POST['disclaimer_confirmed'] ?? '';

    if ($disclaimer === 'yes') {
        $stmt = $mysqli->prepare("UPDATE tasks SET status = 'In Progress' WHERE id = ?");
        $stmt->bind_param("i", $task_id);
        $stmt->execute();
        $stmt->close();

        $stmt = $mysqli->prepare("INSERT INTO logs (employee_id, task_id, action, log_time) VALUES (?, ?, 'Task Started', NOW())");
        $stmt->bind_param("ii", $employee_id, $task_id);
        $stmt->execute();
        $stmt->close();

        $message = "Task started successfully.";
        header("Location: " . $_SERVER['PHP_SELF'] . "?task_id=$task_id&message=" . urlencode($message));
        exit;
    } else {
        $error = "You must acknowledge the responsibility before starting the task.";
    }
}

// Handle "Complete Task" modal submission
if (isset($_POST['complete_task_confirm']) && isset($_POST['task_id_to_complete'])) {
    $task_id = intval($_POST['task_id_to_complete']);
    $confirmation = $_POST['completion_confirmed'] ?? '';

    if ($confirmation === 'yes') {
        $stmt = $mysqli->prepare("UPDATE tasks SET status = 'Completed' WHERE id = ?");
        $stmt->bind_param("i", $task_id);
        $stmt->execute();
        $stmt->close();

        $stmt = $mysqli->prepare("INSERT INTO logs (employee_id, task_id, action, log_time) VALUES (?, ?, 'Task Completed', NOW())");
        $stmt->bind_param("ii", $employee_id, $task_id);
        $stmt->execute();
        $stmt->close();

        $message = "Task marked as completed successfully.";
        header("Location: " . $_SERVER['PHP_SELF'] . "?task_id=$task_id&message=" . urlencode($message));
        exit;
    } else {
        $error = "You must confirm completion before submitting.";
    }
}

// Fetch assigned tasks
$tasks = [];
$stmt = $mysqli->prepare("
    SELECT t.id, t.title, t.status 
    FROM tasks t
    JOIN task_assignments ta ON t.id = ta.task_id
    WHERE ta.employee_id = ?
");
$stmt->bind_param("i", $employee_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $tasks[] = $row;
}
$stmt->close();

// Initialize variables for selected task
$task_details = null;
$assigned_employees = [];
$subtasks = [];
$comments = [];
$logs = [];
$total_hours_worked = 0;
$parent_task_status = null;

if ($selected_task_id) {
    $selected_task_id = intval($selected_task_id);

    $stmt = $mysqli->prepare("SELECT * FROM tasks WHERE id = ?");
    $stmt->bind_param("i", $selected_task_id);
    $stmt->execute();
    $task_details = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($task_details) {
        $parent_task_status = $task_details['status'];

        $stmt = $mysqli->prepare("
            SELECT e.id, e.name 
            FROM employees e
            INNER JOIN task_assignments ta ON ta.employee_id = e.id
            WHERE ta.task_id = ?");
        $stmt->bind_param("i", $selected_task_id);
        $stmt->execute();
        $assigned_employees = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        $stmt = $mysqli->prepare("SELECT * FROM subtasks WHERE parent_task_id = ? ORDER BY due_date ASC");
        $stmt->bind_param("i", $selected_task_id);
        $stmt->execute();
        $subtasks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        $stmt = $mysqli->prepare("
            SELECT tc.*, e.name as employee_name
            FROM task_comments tc
            JOIN employees e ON tc.employee_id = e.id
            WHERE tc.task_id = ?
            ORDER BY tc.created_at DESC
        ");
        $stmt->bind_param("i", $selected_task_id);
        $stmt->execute();
        $comments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        $stmt = $mysqli->prepare("
            SELECT l.*, e.name as employee_name
            FROM logs l
            LEFT JOIN employees e ON l.employee_id = e.id
            WHERE l.task_id = ?
            ORDER BY l.log_time DESC
        ");
        $stmt->bind_param("i", $selected_task_id);
        $stmt->execute();
        $logs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        $stmt = $mysqli->prepare("
            SELECT COALESCE(SUM(hours_spent),0) as total_hours
            FROM task_timesheets
            WHERE task_id = ?
        ");
        $stmt->bind_param("i", $selected_task_id);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $total_hours_worked = $res['total_hours'] ?? 0;
        $stmt->close();
    }
}

// Check if all subtasks are completed
$all_subtasks_completed = true;
foreach ($subtasks as $subtask) {
    if (strtolower($subtask['status']) !== 'completed') {
        $all_subtasks_completed = false;
        break;
    }
}
?>

<div class="content-wrapper">
  <section class="content-header">
    <h1>Manage My Tasks</h1>
  </section>

  <section class="content">
    <!-- Show SweetAlert Messages -->
    <script>
      <?php if (!empty($message)): ?>
        Swal.fire({
          icon: 'success',
          title: 'Success!',
          text: <?= json_encode($message) ?>,
          confirmButtonColor: '#3085d6'
        });
      <?php elseif (!empty($error)): ?>
        Swal.fire({
          icon: 'error',
          title: 'Error!',
          text: <?= json_encode($error) ?>,
          confirmButtonColor: '#d33'
        });
      <?php endif; ?>
    </script>

  <section class="content">
    <div class="row">
      <div class="col-md-12">
        <div class="box box-primary">
          <div class="box-header with-border">
            <h3 class="box-title">Select Task</h3>
            <form method="post" style="display: inline-block; margin-left: 15px;">
              <select name="task_id" class="form-control" style="width: 300px; display: inline-block;" onchange="this.form.submit()">
                <option value="">-- Select Task --</option>
                <?php foreach ($tasks as $task): ?>
                  <option value="<?= $task['id'] ?>" <?= ($selected_task_id == $task['id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($task['title']) ?> (<?= htmlspecialchars($task['status']) ?>)
                  </option>
                <?php endforeach; ?>
              </select>
            </form>
          </div>
        </div>
      </div>
    </div>

    <?php if ($selected_task_id && $task_details): ?>
      <div class="row">
        <div class="col-md-12">
          <div class="box box-info">
            <div class="box-header with-border">
              <h3 class="box-title">Task Details & Subtasks (Status: <strong><?= htmlspecialchars($parent_task_status) ?></strong>)</h3>

              <?php if ($parent_task_status === 'Not Started'): ?>
                <!-- Start Task Button -->
                <button type="button" class="btn btn-primary btn-sm pull-right" data-toggle="modal" data-target="#startTaskModal" style="margin-top:-7px;">
                  Start Task
                </button>
              <?php elseif ($parent_task_status === 'In Progress'): ?>
                <?php if ($all_subtasks_completed): ?>
                  <!-- Enabled Complete Task Button -->
                  <button type="button" class="btn btn-danger btn-sm pull-right" data-toggle="modal" data-target="#completeTaskModal" style="margin-top:-7px; margin-right: 10px;">
                    Mark as Completed
                  </button>
                <?php else: ?>
                  <!-- Disabled Complete Task Button -->
                  <button type="button" class="btn btn-danger btn-sm pull-right" style="margin-top:-7px; margin-right: 10px;" disabled title="Complete all subtasks first">
                    Mark as Completed
                  </button>
                   <p style="color: #a94442; margin-right: 10px; font-size: 0.9em;" class="pull-right">
      <i class="fa fa-info-circle"></i> Complete all subtasks first.
    </p>
    <div class="clearfix"></div>
                <?php endif; ?>
              <?php endif; ?>
            </div>

            <div class="box-body">
              <p><strong>Title:</strong> <?= htmlspecialchars($task_details['title']) ?></p>
              <p><strong>Description:</strong><br><?= nl2br(htmlspecialchars($task_details['description'])) ?></p>
              <p><strong>Total Hours Worked:</strong> <?= number_format($total_hours_worked, 2) ?></p>

              <h4>Assigned Employees</h4>
              <ul>
                <?php foreach ($assigned_employees as $emp): ?>
                  <li><?= htmlspecialchars($emp['name']) ?></li>
                <?php endforeach; ?>
              </ul>

              <h4>Subtasks</h4>
              <?php if (count($subtasks) === 0): ?>
                <p>No subtasks for this task.</p>
              <?php else: ?>
                <table class="table table-bordered table-striped">
                  <thead>
                    <tr>
                      <th>Title</th>
                      <th>Status</th>
                      <th>Due Date</th>
                      <th>Priority</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($subtasks as $subtask): ?>
                      <tr>
                        <td><?= htmlspecialchars($subtask['title']) ?></td>
                        <td>
                          <?php
                            $status = $subtask['status'];
                            $label_class = $status === 'Completed' ? 'label label-success' : ($status === 'Pending' ? 'label label-warning' : 'label label-default');
                          ?>
                          <span class="<?= $label_class ?>"><?= htmlspecialchars($status) ?></span>
                        </td>
                        <td><?= htmlspecialchars($subtask['due_date']) ?></td>
                        <td><?= htmlspecialchars($subtask['priority']) ?></td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>

      <div class="row">
        <!-- Comments box -->
        <div class="col-md-6">
  <div class="box box-success">
    <div class="box-header with-border">
      <h3 class="box-title">Comments</h3>
    </div>
    <div class="box-body">
      <ul class="products-list product-list-in-box">
        <?php if (count($comments)): ?>
          <?php foreach ($comments as $comment): ?>
            <li class="item">
              <div class="product-info">
                <a href="#" class="product-title">
                  <?= htmlspecialchars($comment['employee_name']) ?>
                  <span class="label label-info pull-right"><?= $comment['created_at'] ?></span>
                </a>
                <span class="product-description"><?= nl2br(htmlspecialchars($comment['comment'])) ?></span>
              </div>
            </li>
          <?php endforeach; ?>
        <?php else: ?>
          <li>No comments found.</li>
        <?php endif; ?>
      </ul>
    </div>
  </div>

        </div>

        <!-- Logs box -->
        <div class="col-md-6">
          <div class="box box-warning">
            <div class="box-header with-border">
              <h3 class="box-title">Task Logs</h3>
            </div>
            <div class="box-body">
              <ul class="timeline">
                <?php if (count($logs)): ?>
                  <?php 
                  $current_date = '';
                  foreach (array_reverse($logs) as $log): 
                    $log_date = date("Y-m-d", strtotime($log['log_time']));
                    if ($log_date !== $current_date): 
                      $current_date = $log_date;
                  ?>
                    <li class="time-label">
                      <span class="bg-yellow"><?= $current_date ?></span>
                    </li>
                  <?php endif; ?>
                  <li>
                    <i class="fa fa-clock-o bg-blue"></i>
                    <div class="timeline-item">
                      <span class="time"><i class="fa fa-clock-o"></i> <?= date("H:i:s", strtotime($log['log_time'])) ?></span>
                      <h3 class="timeline-header">
                        <?= htmlspecialchars($log['employee_name'] ?? 'Unknown') ?> performed <?= htmlspecialchars($log['action']) ?>
                      </h3>
                      <div class="timeline-body">
                        <?= htmlspecialchars($log['remarks'] ?? 'No remarks') ?>
                      </div>
                    </div>
                  </li>
                  <?php endforeach; ?>
                <?php else: ?>
                  <li>
                    <i class="fa fa-info-circle bg-gray"></i>
                    <div class="timeline-item">
                      <h3 class="timeline-header no-border">No logs available for this task.</h3>
                    </div>
                  </li>
                <?php endif; ?>
                <li>
                  <i class="fa fa-clock-o bg-gray"></i>
                </li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    <?php endif; ?>
  </section>
</div>

<!-- Start Task Modal -->
<div class="modal fade" id="startTaskModal" tabindex="-1" role="dialog" aria-labelledby="startTaskModalLabel">
  <div class="modal-dialog" role="document">
    <form method="post" action="">
      <input type="hidden" name="task_id_to_start" value="<?= $selected_task_id ?>">
      <div class="modal-content">
        <div class="modal-header">
          <h4 class="modal-title" id="startTaskModalLabel">Start Task Confirmation</h4>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">&times;</button>
        </div>
        <div class="modal-body">
          <h4>Task Summary</h4>
          <table class="table table-bordered">
            <tr><th>Title</th><td><?= htmlspecialchars($task_details['title']) ?></td></tr>
            <tr><th>Client</th><td><?= htmlspecialchars($task_details['client_name']) ?></td></tr>
            <tr><th>Description</th><td><?= nl2br(htmlspecialchars($task_details['description'])) ?></td></tr>
            <tr><th>Deadline</th><td><?= htmlspecialchars($task_details['due_date']) ?></td></tr>
            <tr><th>Total Hours Worked</th><td><?= number_format($total_hours_worked, 2) ?></td></tr>
            <tr><th>Assigned Employees</th>
              <td>
                <ul style="padding-left:20px;">
                  <?php foreach ($assigned_employees as $emp): ?>
                    <li><?= htmlspecialchars($emp['name']) ?></li>
                  <?php endforeach; ?>
                </ul>
              </td>
            </tr>
            <tr>
              <th>Subtasks</th>
              <td>
                <table class="table table-bordered">
                  <thead>
                    <tr><th>Title</th><th>Status</th><th>Due Date</th><th>Priority</th></tr>
                  </thead>
                  <tbody>
                    <?php foreach ($subtasks as $sub): ?>
                      <tr>
                        <td><?= htmlspecialchars($sub['title']) ?></td>
                        <td><?= htmlspecialchars($sub['status']) ?></td>
                        <td><?= htmlspecialchars($sub['due_date']) ?></td>
                        <td><?= htmlspecialchars($sub['priority']) ?></td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </td>
            </tr>
          </table>
          <div class="form-group">
            <label>
              <input type="checkbox" name="disclaimer_confirmed" value="yes" required>
              I acknowledge and accept responsibility for starting this task.
            </label>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" name="start_task_confirm" class="btn btn-primary">Start Task</button>
          <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
        </div>
      </div>
    </form>
  </div>
</div>

<!-- Complete Task Modal -->
<div class="modal fade" id="completeTaskModal" tabindex="-1" role="dialog" aria-labelledby="completeTaskModalLabel">
  <div class="modal-dialog" role="document">
    <form method="post" action="">
      <input type="hidden" name="task_id_to_complete" value="<?= $selected_task_id ?>">
      <div class="modal-content">
        <div class="modal-header">
          <h4 class="modal-title" id="completeTaskModalLabel">Complete Task Confirmation</h4>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">&times;</button>
        </div>
        <div class="modal-body">
          <h4>Task Summary</h4>
          <table class="table table-bordered">
            <tr><th>Title</th><td><?= htmlspecialchars($task_details['title']) ?></td></tr>
            <tr><th>Client</th><td><?= htmlspecialchars($task_details['client_name']) ?></td></tr>
            <tr><th>Description</th><td><?= nl2br(htmlspecialchars($task_details['description'])) ?></td></tr>
            <tr><th>Deadline</th><td><?= htmlspecialchars($task_details['due_date']) ?></td></tr>
            <tr><th>Total Hours Worked</th><td><?= number_format($total_hours_worked, 2) ?></td></tr>
            <tr><th>Assigned Employees</th>
              <td>
                <ul style="padding-left:20px;">
                  <?php foreach ($assigned_employees as $emp): ?>
                    <li><?= htmlspecialchars($emp['name']) ?></li>
                  <?php endforeach; ?>
                </ul>
              </td>
            </tr>
            <tr>
              <th>Subtasks</th>
              <td>
                <table class="table table-bordered">
                  <thead>
                    <tr><th>Title</th><th>Status</th><th>Due Date</th><th>Priority</th></tr>
                  </thead>
                  <tbody>
                    <?php foreach ($subtasks as $sub): ?>
                      <tr>
                        <td><?= htmlspecialchars($sub['title']) ?></td>
                        <td><?= htmlspecialchars($sub['status']) ?></td>
                        <td><?= htmlspecialchars($sub['due_date']) ?></td>
                        <td><?= htmlspecialchars($sub['priority']) ?></td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </td>
            </tr>
          </table>
          <div class="form-group mt-3">
            <label>
              <input type="checkbox" name="completion_confirmed" value="yes" required>
              I confirm that all subtasks are completed and this task is ready to be marked as completed.
            </label>
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" name="complete_task_confirm" class="btn btn-success">Mark as Completed</button>
          <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
        </div>
      </div>
    </form>
  </div>
</div>



<!-- jQuery and Bootstrap scripts (ensure these are included in your template or add here) -->
<?php include 'footer.php'; ?>
