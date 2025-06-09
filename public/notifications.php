<?php
require_once '../config/bootstrap.php';
require_login();

$userId = $_SESSION['user']['id'];

$db = new Database();

// Fetch notifications for current user
$notifications = $db->fetchAll("
    SELECT id, title, message, type, is_read, created_at
    FROM notifications
    WHERE user_id = ?
    ORDER BY created_at DESC
", [$userId]);

// Mark all as read (optional)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_all_read'])) {
    $db->query("UPDATE notifications SET is_read = 1 WHERE user_id = ?", [$userId]);
    header("Location: notifications.php");
    exit;
}

include APP_ROOT . 'includes/header.php';
?>

<h3>🔔 Notificările Mele</h3>

<?php if (empty($notifications)): ?>
    <p>Nu ai notificări noi.</p>
<?php else: ?>
    <form method="POST" style="margin-bottom: 20px;">
        <button type="submit" name="mark_all_read" class="btn-sm btn-outline-grey">✔️ Marchează toate ca citite</button>
    </form>

    <ul class="notif-list">
    <?php foreach ($notifications as $n): ?>
        <li class="notif-item <?= $n['is_read'] ? 'read' : 'unread' ?>">
            <div class="notif-header">
                <strong><?= htmlspecialchars($n['title']) ?></strong>
                <span class="notif-date"><?= date('Y-m-d H:i', strtotime($n['created_at'])) ?></span>
            </div>
            <div class="notif-message"><?= nl2br(htmlspecialchars($n['message'])) ?></div>

            <div class="notif-actions">
                <?php if (!$n['is_read']): ?>
                    <a href="read_notification.php?id=<?= $n['id'] ?>" class="btn-sm btn-outline-success">✔️ Marchează ca citit</a>
                <?php endif; ?>
                <a href="delete_notification.php?id=<?= $n['id'] ?>" class="btn-sm btn-outline-danger" onclick="return confirm('Ești sigur că vrei să ștergi această notificare?')">🗑️ Șterge</a>
            </div>
        </li>
    <?php endforeach; ?>
    </ul>
<?php endif; ?>

<?php include APP_ROOT . 'includes/footer.php'; ?>
