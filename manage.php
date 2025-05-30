
<?php
session_start();
include 'sidebar.php';

$employee_id = $_SESSION['employee_id'] ?? 0;
if (!$employee_id) {
    header("Location: login.php");
    exit;
}

$selected_task_id = $_POST['task_id'] ?? null;

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

$parent_task_status = null;
if ($selected_task_id) {
    foreach ($tasks as $t) {
        if ($t['id'] == $selected_task_id) {
            $parent_task_status = $t['status'];
            break;
        }
    }
}

// Prepare message script container
$swal_message = '';

if (isset($_POST['update_subtask_status'], $_POST['subtask_id'], $_POST['status'])) {
    if (!$selected_task_id) {
        $swal_message = "Swal.fire('Error', 'Please select a task first.', 'error');";
    } elseif ($parent_task_status !== 'In Progress') {
        $swal_message = "Swal.fire('Error', 'Cannot update subtask because parent task is not In Progress.', 'error');";
    } else {
        $subtask_id = $_POST['subtask_id'];
        $new_status = $_POST['status'];

        // Get subtask title for logs
        $stmt = $mysqli->prepare("SELECT title FROM subtasks WHERE id = ? AND parent_task_id = ?");
        $stmt->bind_param("ii", $subtask_id, $selected_task_id);
        $stmt->execute();
        $stmt->bind_result($subtask_title);
        $stmt->fetch();
        $stmt->close();

        $stmt = $mysqli->prepare("UPDATE subtasks SET status = ?, updated_by = ?, updated_at = NOW() WHERE id = ? AND parent_task_id = ?");
        $stmt->bind_param("siii", $new_status, $employee_id, $subtask_id, $selected_task_id);
        $stmt->execute();
        $stmt->close();

        // Insert log
        $action = "Updated subtask status to '$new_status'";
        $remarks = "Subtask: $subtask_title (ID: $subtask_id)";
        $stmt = $mysqli->prepare("INSERT INTO logs (employee_id, task_id, action, log_time, remarks) VALUES (?, ?, ?, NOW(), ?)");
        $stmt->bind_param("iiss", $employee_id, $selected_task_id, $action, $remarks);
        $stmt->execute();
        $stmt->close();

        $swal_message = "Swal.fire('Success', 'Subtask status updated successfully.', 'success');";
    }
}

if (isset($_POST['add_comment'])) {
    if (!$selected_task_id) {
        $swal_message = "Swal.fire('Error', 'Please select a task before adding a comment.', 'error');";
    } elseif ($parent_task_status !== 'In Progress') {
        $swal_message = "Swal.fire('Error', 'Cannot add comment because parent task is not In Progress.', 'error');";
    } else {
        $comment_text = trim($_POST['comment_text'] ?? '');
        if ($comment_text === '') {
            $swal_message = "Swal.fire('Error', 'Comment cannot be empty.', 'error');";
        } else {
            $stmt = $mysqli->prepare("INSERT INTO task_comments (task_id, employee_id, comment, created_at) VALUES (?, ?, ?, NOW())");
            $stmt->bind_param("iis", $selected_task_id, $employee_id, $comment_text);
            $stmt->execute();
            $stmt->close();

            // Insert log
            $action = "Added comment";
            $remarks = substr($comment_text, 0, 255);
            $stmt = $mysqli->prepare("INSERT INTO logs (employee_id, task_id, action, log_time, remarks) VALUES (?, ?, ?, NOW(), ?)");
            $stmt->bind_param("iiss", $employee_id, $selected_task_id, $action, $remarks);
            $stmt->execute();
            $stmt->close();

            $swal_message = "Swal.fire('Success', 'Comment added successfully.', 'success');";
        }
    }
}

// Fetch subtasks
$subtasks = [];
if ($selected_task_id) {
    $stmt = $mysqli->prepare("SELECT s.*, e.name as updated_by_name FROM subtasks s LEFT JOIN employees e ON s.updated_by = e.id WHERE s.parent_task_id = ? ORDER BY s.due_date ASC");
    $stmt->bind_param("i", $selected_task_id);
    $stmt->execute();
    $subtasks = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

// Fetch comments
$comments = [];
if ($selected_task_id) {
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
}

// Fetch logs
$logs = [];
if ($selected_task_id) {
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
}
?>

<div class="content-wrapper">
  <section class="content-header">
    <h1>Manage My Tasks</h1>
  </section>

  <section class="content">
    <div class="row">
      <div class="col-md-12">
        <div class="box box-primary">
          <div class="box-header with-border">
            <h3 class="box-title">Select Task</h3>
            <form method="post" style="display:inline-block; margin-left:15px;">
              <select name="task_id" class="form-control" style="width:300px; display:inline-block;" onchange="this.form.submit()">
                <option value="">-- Select Task --</option>
                <?php foreach ($tasks as $task): ?>
                  <option value="<?= $task['id'] ?>" <?= ($selected_task_id == $task['id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($task['title']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </form>
          </div>
        </div>
      </div>
    </div>

    <?php if ($selected_task_id): ?>
    <div class="row">
      <div class="col-md-12">
        <div class="box box-info">
          <div class="box-header with-border">
            <h3 class="box-title">Subtasks (Parent Task Status: <strong><?= htmlspecialchars($parent_task_status) ?></strong>)</h3>
          </div>
          <div class="box-body">
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
                    <th>Last Updated</th>
                    <th>Updated By</th>
                    <th>Change Status</th>
                    <th>Action</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($subtasks as $subtask): ?>
                    <tr>
                      <td><?= htmlspecialchars($subtask['title']) ?></td>
                      <td>
                        <?php 
                          $status = $subtask['status'];
                          $label_class = $status === 'Completed' ? 'label label-success' : ($status === 'Not Started' ? 'label label-warning' : 'label label-danger');
                        ?>
                        <span class="<?= $label_class ?>"><?= htmlspecialchars($status) ?></span>
                      </td>
                      <td><?= htmlspecialchars($subtask['due_date']) ?></td>
                      <td><?= htmlspecialchars($subtask['priority']) ?></td>
                      <td><?= $subtask['updated_at'] ?? '-' ?></td>
                      <td><?= htmlspecialchars($subtask['updated_by_name'] ?? '-') ?></td>
                      <td>
                        <?php if ($parent_task_status === 'In Progress'): ?>
                          <form method="post" class="form-inline">
                            <input type="hidden" name="task_id" value="<?= $selected_task_id ?>">
                            <input type="hidden" name="subtask_id" value="<?= $subtask['id'] ?>">
                            <select name="status" class="form-control input-sm" required>
                              <option value="Not Started" <?= $status === 'Not Started' ? 'selected' : '' ?>>Not Started</option>
                              <option value="In Progress" <?= $status === 'In Progress' ? 'selected' : '' ?>>In Progress</option>
                              <option value="Completed" <?= $status === 'Completed' ? 'selected' : '' ?>>Completed</option>
                            </select>
                      </td>
                      <td>
                            <button type="submit" name="update_subtask_status" value="1" class="btn btn-xs btn-primary">Update</button>
                          </form>
                        <?php else: ?>
                          <button type="button" class="btn btn-xs btn-default" disabled>Locked</button>
                        <?php endif; ?>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>

    <!-- Comments and Logs -->
    <div class="row">
      <div class="col-md-6">
        <div class="box box-success">
          <div class="box-header with-border"><h3 class="box-title">Comments</h3></div>
          <div class="box-body">
            <?php if ($parent_task_status === 'In Progress'): ?>
              <form method="post">
                <input type="hidden" name="task_id" value="<?= $selected_task_id ?>">
                <textarea name="comment_text" class="form-control" rows="3" required placeholder="Write your comment..."></textarea>
                <br>
                <button type="submit" name="add_comment" class="btn btn-success">Add Comment</button>
              </form><hr>
            <?php else: ?>
              <p class="text-muted">Commenting is disabled because the task is not in progress.</p><hr>
            <?php endif; ?>

            <?php foreach ($comments as $comment): ?>
              <div>
                <strong><?= htmlspecialchars($comment['employee_name']) ?></strong> 
                <small class="pull-right"><?= $comment['created_at'] ?></small><br>
                <p><?= nl2br(htmlspecialchars($comment['comment'])) ?></p><hr>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>

      <div class="col-md-6">
        <div class="box box-warning">
          <div class="box-header with-border"><h3 class="box-title">Task Logs</h3></div>
          <div class="box-body">
            <ul class="timeline">
              <?php foreach ($logs as $log): ?>
                <li>
                  <i class="fa fa-clock-o bg-blue"></i>
                  <div class="timeline-item">
                    <span class="time"><i class="fa fa-clock-o"></i> <?= date("H:i:s", strtotime($log['log_time'])) ?></span>
                    <h3 class="timeline-header"><?= htmlspecialchars($log['employee_name'] ?? 'Unknown') ?> performed <?= htmlspecialchars($log['action']) ?></h3>
                    <div class="timeline-body"><?= htmlspecialchars($log['remarks']) ?></div>
                  </div>
                </li>
              <?php endforeach; ?>
              <?php if (empty($logs)): ?>
                <li><i class="fa fa-info-circle bg-gray"></i><div class="timeline-item"><h3 class="timeline-header no-border">No logs available.</h3></div></li>
              <?php endif; ?>
              <li><i class="fa fa-clock-o bg-gray"></i></li>
            </ul>
          </div>
        </div>
      </div>
    </div>
    <?php endif; ?>
  </section>
</div>

<script>
<?php if ($swal_message): ?>
  <?= $swal_message ?>
<?php endif; ?>
</script>

<?php include 'footer.php'; ?>
