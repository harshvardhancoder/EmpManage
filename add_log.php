<?php
session_start();
include 'sidebar.php'; // contains $mysqli connection and sidebar etc.

$employee_id = $_SESSION['employee_id'] ?? 0;
if (!$employee_id) {
    header("Location: login.php");
    exit;
}

$selected_task_id = $_POST['task_id'] ?? null;
$action = $_POST['action'] ?? '';
$remarks = trim($_POST['remarks'] ?? '');
$errors = [];
$success = null;

// Fetch tasks assigned to this employee
$tasks = [];
$stmt = $mysqli->prepare("
    SELECT t.id, t.title 
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

// Handle log submission
if ($selected_task_id && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_log'])) {
    if (!$action) {
        $errors[] = "Please select an action.";
    }
    if (strlen($remarks) < 5) {
        $errors[] = "Remarks must be at least 5 characters.";
    }

    // Validate that selected_task_id belongs to user
    $valid_task = false;
    foreach ($tasks as $t) {
        if ($t['id'] == $selected_task_id) {
            $valid_task = true;
            break;
        }
    }

    if (!$valid_task) {
        $errors[] = "Invalid task selected.";
    }

    if (empty($errors)) {
        $stmt = $mysqli->prepare("
            INSERT INTO logs (employee_id, task_id, action, log_time, remarks) 
            VALUES (?, ?, ?, NOW(), ?)
        ");
        $stmt->bind_param("iiss", $employee_id, $selected_task_id, $action, $remarks);
        if ($stmt->execute()) {
            $success = "Log entry added successfully.";
            // Clear post vars to prevent resubmission
            $action = '';
            $remarks = '';
        } else {
            $errors[] = "Failed to add log entry.";
        }
        $stmt->close();
    }
}

// Fetch previous logs for selected task
$logs = [];
if ($selected_task_id) {
    $stmt = $mysqli->prepare("
        SELECT l.*, e.name as employee_name 
        FROM logs l
        JOIN employees e ON l.employee_id = e.id
        WHERE l.task_id = ? 
        ORDER BY l.log_time DESC
    ");
    $stmt->bind_param("i", $selected_task_id);
    $stmt->execute();
    $logs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}
?>

<div class="content-wrapper">
  <section class="content-header">
    <h1>Add Task Logs</h1>
  </section>

  <section class="content">
    <div class="row">
      <div class="col-md-12">

        <div class="box box-primary">
          <div class="box-header with-border">
            <h3 class="box-title">Select Task</h3>
          </div>
          <div class="box-body">
            <form method="post" action="">
              <div class="form-group">
                <label for="taskSelect">Task</label>
                <select id="taskSelect" name="task_id" class="form-control" onchange="this.form.submit()" required>
                  <option value="">-- Select Task --</option>
                  <?php foreach ($tasks as $task): ?>
                    <option value="<?= $task['id'] ?>" <?= ($selected_task_id == $task['id']) ? 'selected' : '' ?>>
                      <?= htmlspecialchars($task['title']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>
            </form>
          </div>
        </div>

        <?php if ($selected_task_id): ?>
          <div class="box box-success">
            <div class="box-header with-border">
              <h3 class="box-title">Add Log Entry</h3>
            </div>
            <div class="box-body">
              <?php if ($success): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
              <?php endif; ?>

              <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                  <ul>
                    <?php foreach ($errors as $error): ?>
                      <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                  </ul>
                </div>
              <?php endif; ?>

              <form method="post" action="">
                <input type="hidden" name="task_id" value="<?= $selected_task_id ?>">

                <div class="form-group">
                  <label for="actionSelect">Action</label>
                  <select id="actionSelect" name="action" class="form-control" required>
                    <option value="">-- Select Action --</option>
                    <option value="Started" <?= ($action === 'Started') ? 'selected' : '' ?>>Started</option>
                    <option value="Paused" <?= ($action === 'Paused') ? 'selected' : '' ?>>Paused</option>
                    <option value="Resumed" <?= ($action === 'Resumed') ? 'selected' : '' ?>>Resumed</option>
                    <option value="Completed" <?= ($action === 'Completed') ? 'selected' : '' ?>>Completed</option>
                    <option value="Reviewed" <?= ($action === 'Reviewed') ? 'selected' : '' ?>>Reviewed</option>
                    <option value="Other" <?= ($action === 'Other') ? 'selected' : '' ?>>Other</option>
                  </select>
                </div>

                <div class="form-group">
                  <label for="remarksInput">Remarks</label>
                  <textarea id="remarksInput" name="remarks" class="form-control" rows="4" required><?= htmlspecialchars($remarks) ?></textarea>
                </div>

                <button type="submit" name="add_log" class="btn btn-primary">Add Log</button>
              </form>
            </div>
          </div>

          <div class="box box-info">
            <div class="box-header with-border">
              <h3 class="box-title">Task Logs Timeline</h3>
            </div>
            <div class="box-body">
              <?php if (empty($logs)): ?>
                <p>No logs for this task yet.</p>
              <?php else: ?>
                <ul class="timeline timeline-inverse">
                  <?php foreach ($logs as $log): ?>
                    <li>
                      <i class="fa fa-clock-o bg-blue"></i>
                      <div class="timeline-item">
                        <span class="time"><i class="fa fa-clock-o"></i> <?= htmlspecialchars($log['log_time']) ?></span>
                        <h3 class="timeline-header">
                          <a href="#"><?= htmlspecialchars($log['employee_name']) ?></a> performed action: <strong><?= htmlspecialchars($log['action']) ?></strong>
                        </h3>
                        <div class="timeline-body">
                          <?= nl2br(htmlspecialchars($log['remarks'])) ?>
                        </div>
                      </div>
                    </li>
                  <?php endforeach; ?>
                  <li><i class="fa fa-clock-o bg-gray"></i></li>
                </ul>
              <?php endif; ?>
            </div>
          </div>
        <?php endif; ?>

      </div>
    </div>
  </section>
</div>

<?php include 'footer.php'; ?>
