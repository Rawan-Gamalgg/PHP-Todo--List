<?php
require 'config/database.php';
require 'includes/header.php';

// Initialize variables
$errors = [];
$task = '';

// Add a new task with validation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $task = trim($_POST['task'] ?? '');
    
    // Validate input
    if (empty($task)) {
        $errors['task'] = 'Task cannot be empty / لا يمكن أن تكون المهمة فارغة';
    } elseif (strlen($task) > 255) {
        $errors['task'] = 'Task is too long (max 255 characters) / المهمة طويلة جداً (255 حرف كحد أقصى)';
    } elseif (!preg_match('/^[\p{Arabic}\p{L}\p{N}\s\-_.,!]+$/u', $task)) {
        $errors['task'] = 'Invalid characters / أحرف غير مسموحة';
    }

    // If validation passes
    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO tasks (title, createdAt) VALUES (?, NOW())");
        $stmt->bind_param("s", $task);
        
        if ($stmt->execute()) {
            $_SESSION['message'] = 'Task added successfully / تمت إضافة المهمة بنجاح';
            header('Location: index.php');
            exit();
        } else {
            $errors['database'] = 'Error saving task: ' . $conn->error;
        }
        $stmt->close();
    }
}

// Get all tasks (fixed ORDER BY clause)
$query = "SELECT * FROM tasks ORDER BY status, createdAt DESC";
$result = $conn->query($query);
$tasks = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

// Mark task as completed
if (isset($_GET['complete']) && is_numeric($_GET['complete'])) {
    $task_id = (int)$_GET['complete'];
    $stmt = $conn->prepare("UPDATE tasks SET status = 1, updatedAt = NOW() WHERE id = ?");
    $stmt->bind_param("i", $task_id);
    if ($stmt->execute()) {
        $_SESSION['message'] = 'Task completed';
    }
    $stmt->close();
    header("Location: index.php");
    exit();
}

// Delete task
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $task_id = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM tasks WHERE id = ?");
    $stmt->bind_param("i", $task_id);
    if ($stmt->execute()) {
        $_SESSION['message'] = 'Task deleted / تم حذف المهمة';
    }
    $stmt->close();
    header("Location: index.php");
    exit();
}
?>

<div class="form-container">
    <!-- Display session messages -->
    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-success"><?= htmlspecialchars($_SESSION['message']) ?></div>
        <?php unset($_SESSION['message']); ?>
    <?php endif; ?>

    <!-- Display errors -->
    <?php if (!empty($errors)): ?>
        <?php foreach ($errors as $error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endforeach; ?>
    <?php endif; ?>

    <!-- Task form -->
    <form method="POST">
        <input type="text" name="task" 
               placeholder="أدخل المهمة / Enter task..." 
               value="<?= isset($task) ? htmlspecialchars($task) : '' ?>"
               required
               maxlength="255">
        <button type="submit" name="add-btn">Add Task </button>
    </form>

    <!-- Tasks list -->
    <ul class="task-list">
        <?php foreach ($tasks as $task): ?>
            <li class="task-item <?= $task['status'] ? 'completed' : '' ?>">
                <span><?= htmlspecialchars($task['title']) ?></span>
                <div class="task-actions">
                    <?php if (!$task['status']): ?>
                        <a href="index.php?complete=<?= $task['id'] ?>" class="complete-btn">
                            ✓ Complete 
                        </a>
                    <?php endif; ?>
                    <a href="index.php?delete=<?= $task['id'] ?>" class="delete-btn">
                        ✗ Delete 
                    </a>
                    <?php if ($task['status']): ?>
                        <span class="completed-date">
                            (Completed at: <?= $task['updatedAt'] ?>)
                        </span>
                    <?php endif; ?>
                </div>
            </li>
        <?php endforeach; ?>
    </ul>
</div>

<?php require 'includes/footer.php'; ?>