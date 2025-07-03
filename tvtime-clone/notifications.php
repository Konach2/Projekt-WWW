<?php
/**
 * Strona powiadomień użytkownika
 */

require_once 'config/database.php';
require_once 'includes/session.php';
require_once 'includes/functions.php';
require_once 'includes/notifications.php';

// Wymagaj zalogowania
requireLogin();

$user_id = getCurrentUserId();
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 20;

// Pobierz powiadomienia
$notifications = getAllNotifications($user_id, $page, $per_page);
$unread_count = countUnreadNotifications($user_id);

// Obsługa akcji
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'mark_read':
                $notification_id = (int)($_POST['notification_id'] ?? 0);
                if ($notification_id && markNotificationAsRead($notification_id, $user_id)) {
                    setFlashMessage('success', 'Powiadomienie zostało oznaczone jako przeczytane.');
                }
                break;
                
            case 'mark_all_read':
                if (markAllNotificationsAsRead($user_id)) {
                    setFlashMessage('success', 'Wszystkie powiadomienia zostały oznaczone jako przeczytane.');
                }
                break;
        }
        
        header('Location: notifications.php');
        exit();
    }
}

$page_title = 'Powiadomienia - TV Time Clone';
$page_description = 'Twoje powiadomienia i aktywność w TV Time Clone';

include 'includes/header.php';
?>

<div class="notifications-page">
    <div class="notifications-header">
        <h1>
            <i class="fas fa-bell"></i>
            Powiadomienia
            <?php if ($unread_count > 0): ?>
                <span class="unread-badge"><?php echo $unread_count; ?></span>
            <?php endif; ?>
        </h1>
        
        <?php if ($unread_count > 0): ?>
            <form method="post" style="display: inline;">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                <input type="hidden" name="action" value="mark_all_read">
                <button type="submit" class="btn btn-outline btn-sm">
                    <i class="fas fa-check-double"></i>
                    Oznacz wszystkie jako przeczytane
                </button>
            </form>
        <?php endif; ?>
    </div>
    
    <div class="notifications-list">
        <?php if (empty($notifications)): ?>
            <div class="no-notifications">
                <div class="no-notifications-icon">
                    <i class="fas fa-bell-slash"></i>
                </div>
                <h3>Brak powiadomień</h3>
                <p>Nie masz jeszcze żadnych powiadomień. Gdy coś się wydarzy, znajdziesz to tutaj!</p>
            </div>
        <?php else: ?>
            <?php foreach ($notifications as $notification): ?>
                <div class="notification-item <?php echo !$notification['is_read'] ? 'unread' : ''; ?>">
                    <div class="notification-icon">
                        <?php
                        $icon_class = match($notification['type']) {
                            'new_episode' => 'fas fa-play',
                            'friend_activity' => 'fas fa-users',
                            'recommendation' => 'fas fa-star',
                            'system' => 'fas fa-cog',
                            default => 'fas fa-bell'
                        };
                        ?>
                        <i class="<?php echo $icon_class; ?>"></i>
                    </div>
                    
                    <div class="notification-content">
                        <div class="notification-title">
                            <?php echo htmlspecialchars($notification['title']); ?>
                        </div>
                        
                        <div class="notification-message">
                            <?php echo htmlspecialchars($notification['message']); ?>
                        </div>
                        
                        <div class="notification-meta">
                            <span class="notification-type">
                                <?php echo formatNotificationType($notification['type']); ?>
                            </span>
                            
                            <span class="notification-time">
                                <?php echo timeAgo($notification['created_at']); ?>
                            </span>
                            
                            <?php if ($notification['show_title']): ?>
                                <span class="notification-show">
                                    <i class="fas fa-tv"></i>
                                    <?php echo htmlspecialchars($notification['show_title']); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="notification-actions">
                        <?php if (!$notification['is_read']): ?>
                            <form method="post" style="display: inline;">
                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                <input type="hidden" name="action" value="mark_read">
                                <input type="hidden" name="notification_id" value="<?php echo $notification['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-outline" title="Oznacz jako przeczytane">
                                    <i class="fas fa-check"></i>
                                </button>
                            </form>
                        <?php endif; ?>
                        
                        <?php if ($notification['related_show_id']): ?>
                            <a href="show-details.php?id=<?php echo $notification['related_show_id']; ?>" 
                               class="btn btn-sm btn-primary">
                                <i class="fas fa-external-link-alt"></i>
                                Zobacz
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<style>
.notifications-page {
    max-width: 800px;
    margin: 0 auto;
    padding: 2rem 1rem;
}

.notifications-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 2rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #333;
}

.notifications-header h1 {
    display: flex;
    align-items: center;
    gap: 0.8rem;
    margin: 0;
    color: #fff;
}

.unread-badge {
    background: #ff4444;
    color: #fff;
    font-size: 0.8rem;
    padding: 0.2rem 0.6rem;
    border-radius: 12px;
    font-weight: 600;
}

.notifications-list {
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.notification-item {
    background: #2a2a2a;
    border-radius: 8px;
    padding: 1.5rem;
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    border-left: 3px solid transparent;
    transition: all 0.2s ease;
}

.notification-item:hover {
    background: #333;
}

.notification-item.unread {
    border-left-color: #ff9500;
    background: linear-gradient(135deg, #2a2a2a, #2d2a1f);
}

.notification-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #ff9500;
    color: #fff;
    font-size: 1.1rem;
    flex-shrink: 0;
}

.notification-content {
    flex: 1;
}

.notification-title {
    font-weight: 600;
    color: #fff;
    margin-bottom: 0.5rem;
}

.notification-message {
    color: #ccc;
    line-height: 1.4;
    margin-bottom: 0.8rem;
}

.notification-meta {
    display: flex;
    align-items: center;
    gap: 1rem;
    font-size: 0.85rem;
    color: #888;
}

.notification-type {
    background: #444;
    padding: 0.2rem 0.6rem;
    border-radius: 12px;
    text-transform: uppercase;
    font-size: 0.75rem;
    letter-spacing: 0.5px;
}

.notification-show {
    display: flex;
    align-items: center;
    gap: 0.3rem;
    color: #ff9500;
}

.notification-actions {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex-shrink: 0;
}

.no-notifications {
    text-align: center;
    padding: 4rem 2rem;
    color: #888;
}

.no-notifications-icon {
    font-size: 4rem;
    margin-bottom: 1rem;
    color: #444;
}

.no-notifications h3 {
    color: #ccc;
    margin-bottom: 0.5rem;
}

/* Mobile responsive */
@media (max-width: 768px) {
    .notifications-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }
    
    .notification-item {
        flex-direction: column;
        gap: 1rem;
    }
    
    .notification-meta {
        flex-wrap: wrap;
        gap: 0.5rem;
    }
    
    .notification-actions {
        width: 100%;
        justify-content: flex-end;
    }
}
</style>

<?php
// Dodaj funkcję pomocniczą timeAgo jeśli nie istnieje
if (!function_exists('timeAgo')) {
    function timeAgo($datetime) {
        $time = time() - strtotime($datetime);
        
        if ($time < 60) return 'przed chwilą';
        if ($time < 3600) return floor($time/60) . ' min temu';
        if ($time < 86400) return floor($time/3600) . ' godz. temu';
        if ($time < 2592000) return floor($time/86400) . ' dni temu';
        if ($time < 31536000) return floor($time/2592000) . ' mies. temu';
        
        return floor($time/31536000) . ' lat temu';
    }
}

include 'includes/footer.php';
?>
