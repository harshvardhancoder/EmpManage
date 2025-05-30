<?php
session_start();
include 'sidebar.php'; // Contains DB connection and sidebar etc.

$employee_id = $_SESSION['employee_id'] ?? 0;
if (!$employee_id) {
    header("Location: login.php");
    exit;
}

$selected_task_id = $_POST['task_id'] ?? null;
$show_form = false;
$errors = [];
$success = null;

// Fetch tasks assigned to this employee
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

if ($selected_task_id) {
    // Check if selected task belongs to user and get its status
    $task_status = null;
    foreach ($tasks as $t) {
        if ($t['id'] == $selected_task_id) {
            $task_status = $t['status'];
            break;
        }
    }

    if (!$task_status) {
        $errors[] = "Invalid task selected.";
    } else {
        // Show form only if task is "In Progress"
        if (strtolower($task_status) === 'in progress') {
            $show_form = true;
        }
    }

    // Handle timesheet submission
    if ($show_form && isset($_POST['hours_spent'], $_POST['description'])) {
        $hours_spent = floatval($_POST['hours_spent']);
        $description = trim($_POST['description']);

        // Validation
        if ($hours_spent < 0.5 || $hours_spent > 5) {
            $errors[] = "Hours spent must be between 0.5 and 5.";
        }
        // Check description has at least 10 words
        if (str_word_count($description) < 10) {
            $errors[] = "Description must be at least 10 words.";
        }

        if (empty($errors)) {
            $stmt = $mysqli->prepare("
                INSERT INTO task_timesheets (task_id, employee_id, date_worked, hours_spent, description, created_at) 
                VALUES (?, ?, CURDATE(), ?, ?, NOW())
            ");
            $stmt->bind_param("iids", $selected_task_id, $employee_id, $hours_spent, $description);
            if ($stmt->execute()) {
                $success = "Timesheet entry added successfully.";
            } else {
                $errors[] = "Failed to add timesheet entry.";
            }
            $stmt->close();
        }
    }

    // Fetch previous timesheets for this task and user
    $prev_timesheets = [];
    $stmt = $mysqli->prepare("
        SELECT date_worked, hours_spent, description, created_at 
        FROM task_timesheets 
        WHERE task_id = ? AND employee_id = ? 
        ORDER BY date_worked DESC
    ");
    $stmt->bind_param("ii", $selected_task_id, $employee_id);
    $stmt->execute();
    $prev_timesheets = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Fetch total hours worked on this task by the user
    $total_hours = 0;
    $stmt = $mysqli->prepare("
        SELECT IFNULL(SUM(hours_spent),0) AS total_hours 
        FROM task_timesheets 
        WHERE task_id = ? AND employee_id = ?
    ");
    $stmt->bind_param("ii", $selected_task_id, $employee_id);
    $stmt->execute();
    $stmt->bind_result($total_hours);
    $stmt->fetch();
    $stmt->close();
}
?>

<div class="content-wrapper">
  <section class="content-header">
    <h1>Log My Timesheet</h1>
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
                <select name="task_id" id="taskSelect" class="form-control" onchange="this.form.submit()" required>
                  <option value="">-- Select Task --</option>
                  <?php foreach ($tasks as $task): ?>
                    <option value="<?= $task['id'] ?>" <?= ($selected_task_id == $task['id']) ? 'selected' : '' ?>>
                      <?= htmlspecialchars($task['title']) ?> (<?= htmlspecialchars($task['status']) ?>)
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
              <h3 class="box-title">Timesheet Entry</h3>
            </div>
            <div class="box-body">
              <?php if ($task_status !== 'In Progress'): ?>
                <div class="alert alert-warning">Timesheet entry allowed only when task status is "In Progress". Current status: <strong><?= htmlspecialchars($task_status) ?></strong></div>
              <?php endif; ?>

              <?php if ($show_form): ?>
                <?php if ($success): ?>
                  <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
                <?php endif; ?>

                <?php if (!empty($errors)): ?>
                  <div class="alert alert-danger">
                    <ul>
                      <?php foreach ($errors as $e): ?>
                        <li><?= htmlspecialchars($e) ?></li>
                      <?php endforeach; ?>
                    </ul>
                  </div>
                <?php endif; ?>

                <form method="post" action="">
                  <input type="hidden" name="task_id" value="<?= $selected_task_id ?>">

                  <div class="form-group">
                    <label>Current Date</label>
                    <input type="text" class="form-control" value="<?= date('Y-m-d') ?>" readonly>
                  </div>

                  <div class="form-group">
                    <label>Current Time</label>
                    <input type="text" class="form-control" value="<?= date('H:i:s') ?>" readonly>
                  </div>

                  <div class="form-group">
                    <label>Hours Spent (0.5 - 5)</label>
                    <input type="number" name="hours_spent" class="form-control" step="0.25" min="0.5" max="5" required value="<?= isset($_POST['hours_spent']) ? htmlspecialchars($_POST['hours_spent']) : '' ?>">
                  </div>

                  <div class="form-group">
                    <label>Description (minimum 10 words)</label>
                    <textarea name="description" class="form-control" rows="4" required><?= isset($_POST['description']) ? htmlspecialchars($_POST['description']) : '' ?></textarea>
                  </div>

                  <button type="submit" class="btn btn-primary">Submit Timesheet</button>
                </form>
              <?php endif; ?>
            </div>
          </div>

          <div class="box box-info">
            <div class="box-header with-border">
              <h3 class="box-title">Previous Timesheet Entries</h3>
              <span class="pull-right"><strong>Total Hours Worked:</strong> <?= number_format($total_hours, 2) ?></span>
            </div>
            <div class="box-body table-responsive no-padding">
              <?php if (empty($prev_timesheets)): ?>
                <p>No previous timesheet entries for this task.</p>
              <?php else: ?>
                <table class="table table-hover">
                  <thead>
                    <tr>
                      <th>Date Worked</th>
                      <th>Hours Spent</th>
                      <th>Description</th>
                      <th>Entry Created At</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($prev_timesheets as $entry): ?>
                      <tr>
                        <td><?= htmlspecialchars($entry['date_worked']) ?></td>
                        <td><?= number_format($entry['hours_spent'], 2) ?></td>
                        <td><?= nl2br(htmlspecialchars($entry['description'])) ?></td>
                        <td><?= htmlspecialchars($entry['created_at']) ?></td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              <?php endif; ?>
            </div>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </section>
</div>

<?php include 'footer.php'; ?>
