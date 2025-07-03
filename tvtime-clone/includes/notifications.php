<?php
/**
 * Funkcje do obsługi powiadomień użytkowników
 */

/**
 * Pobiera nieprzeczytane powiadomienia użytkownika
 * @param int $user_id ID użytkownika
 * @param int $limit Limit wyników
 * @return array
 */
function getUnreadNotifications($user_id, $limit = 10) {
    $sql = "SELECT n.*, s.title as show_title, u.username as related_username
            FROM user_notifications n
            LEFT JOIN shows s ON n.related_show_id = s.id
            LEFT JOIN users u ON n.related_user_id = u.id
            WHERE n.user_id = ? AND n.is_read = FALSE
            ORDER BY n.created_at DESC
            LIMIT ?";
    
    return fetchAll($sql, [$user_id, $limit]);
}

/**
 * Pobiera wszystkie powiadomienia użytkownika
 * @param int $user_id ID użytkownika
 * @param int $page Numer strony
 * @param int $per_page Ilość na stronę
 * @return array
 */
function getAllNotifications($user_id, $page = 1, $per_page = 20) {
    $offset = ($page - 1) * $per_page;
    
    $sql = "SELECT n.*, s.title as show_title, u.username as related_username
            FROM user_notifications n
            LEFT JOIN shows s ON n.related_show_id = s.id
            LEFT JOIN users u ON n.related_user_id = u.id
            WHERE n.user_id = ?
            ORDER BY n.created_at DESC
            LIMIT ? OFFSET ?";
    
    return fetchAll($sql, [$user_id, $per_page, $offset]);
}

/**
 * Liczy nieprzeczytane powiadomienia użytkownika
 * @param int $user_id ID użytkownika
 * @return int
 */
function countUnreadNotifications($user_id) {
    return fetchCount("SELECT COUNT(*) FROM user_notifications WHERE user_id = ? AND is_read = FALSE", [$user_id]);
}

/**
 * Oznacza powiadomienie jako przeczytane
 * @param int $notification_id ID powiadomienia
 * @param int $user_id ID użytkownika (dla bezpieczeństwa)
 * @return bool
 */
function markNotificationAsRead($notification_id, $user_id) {
    $affected = executeQuery("UPDATE user_notifications SET is_read = TRUE WHERE id = ? AND user_id = ?", 
                            [$notification_id, $user_id]);
    return $affected->rowCount() > 0;
}

/**
 * Oznacza wszystkie powiadomienia użytkownika jako przeczytane
 * @param int $user_id ID użytkownika
 * @return bool
 */
function markAllNotificationsAsRead($user_id) {
    $affected = executeQuery("UPDATE user_notifications SET is_read = TRUE WHERE user_id = ? AND is_read = FALSE", 
                            [$user_id]);
    return $affected->rowCount() > 0;
}

/**
 * Tworzy nowe powiadomienie
 * @param int $user_id ID użytkownika
 * @param string $type Typ powiadomienia
 * @param string $title Tytuł
 * @param string $message Treść
 * @param int|null $related_show_id ID powiązanego serialu
 * @param int|null $related_user_id ID powiązanego użytkownika
 * @return bool
 */
function createNotification($user_id, $type, $title, $message, $related_show_id = null, $related_user_id = null) {
    $sql = "INSERT INTO user_notifications (user_id, type, title, message, related_show_id, related_user_id, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)";
    
    try {
        executeQuery($sql, [$user_id, $type, $title, $message, $related_show_id, $related_user_id]);
        return true;
    } catch (Exception $e) {
        error_log('Error creating notification: ' . $e->getMessage());
        return false;
    }
}

/**
 * Usuwa stare powiadomienia (starsze niż 30 dni)
 * @return int Liczba usuniętych powiadomień
 */
function cleanOldNotifications() {
    $sql = "DELETE FROM user_notifications WHERE created_at < DATE_SUB(CURRENT_TIMESTAMP, INTERVAL 30 DAY)";
    $affected = executeQuery($sql);
    return $affected->rowCount();
}

/**
 * Pobiera aktywność użytkownika
 * @param int $user_id ID użytkownika
 * @param int $limit Limit wyników
 * @return array
 */
function getUserActivity($user_id, $limit = 20) {
    $sql = "SELECT a.*, s.title as show_title, e.title as episode_title
            FROM user_activity a
            LEFT JOIN shows s ON a.show_id = s.id
            LEFT JOIN episodes e ON a.episode_id = e.id
            WHERE a.user_id = ?
            ORDER BY a.created_at DESC
            LIMIT ?";
    
    return fetchAll($sql, [$user_id, $limit]);
}

/**
 * Zapisuje aktywność użytkownika
 * @param int $user_id ID użytkownika
 * @param string $activity_type Typ aktywności
 * @param int|null $show_id ID serialu
 * @param int|null $episode_id ID odcinka
 * @param array|null $data Dodatkowe dane w JSON
 * @return bool
 */
function logUserActivity($user_id, $activity_type, $show_id = null, $episode_id = null, $data = null) {
    // Tymczasowo wyłączone - tabela user_activity nie istnieje
    error_log("Activity logged: $activity_type for user $user_id");
    return true;
    
    /*
    $sql = "INSERT INTO user_activity (user_id, activity_type, show_id, episode_id, data, created_at) 
            VALUES (?, ?, ?, ?, ?, CURRENT_TIMESTAMP)";
    
    $json_data = $data ? json_encode($data) : null;
    
    try {
        executeQuery($sql, [$user_id, $activity_type, $show_id, $episode_id, $json_data]);
        return true;
    } catch (Exception $e) {
        error_log('Error logging user activity: ' . $e->getMessage());
        return false;
    }
    */
}

/**
 * Pobiera listy użytkownika
 * @param int $user_id ID użytkownika
 * @param bool $public_only Tylko publiczne listy
 * @return array
 */
function getUserLists($user_id, $public_only = false) {
    $where = "user_id = ?";
    $params = [$user_id];
    
    if ($public_only) {
        $where .= " AND is_public = TRUE";
    }
    
    $sql = "SELECT l.*, COUNT(li.id) as item_count
            FROM user_lists l
            LEFT JOIN user_list_items li ON l.id = li.list_id
            WHERE $where
            GROUP BY l.id
            ORDER BY l.created_at DESC";
    
    return fetchAll($sql, $params);
}

/**
 * Tworzy nową listę użytkownika
 * @param int $user_id ID użytkownika
 * @param string $name Nazwa listy
 * @param string $description Opis
 * @param bool $is_public Czy lista jest publiczna
 * @return int|false ID nowej listy lub false w przypadku błędu
 */
function createUserList($user_id, $name, $description = '', $is_public = false) {
    $sql = "INSERT INTO user_lists (user_id, name, description, is_public, created_at) 
            VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP)";
    
    try {
        executeQuery($sql, [$user_id, $name, $description, $is_public]);
        return getDB()->lastInsertId();
    } catch (Exception $e) {
        error_log('Error creating user list: ' . $e->getMessage());
        return false;
    }
}

/**
 * Formatuje typ powiadomienia na przyjazny tekst
 * @param string $type Typ powiadomienia
 * @return string
 */
function formatNotificationType($type) {
    $types = [
        'new_episode' => 'Nowy odcinek',
        'friend_activity' => 'Aktywność znajomych',
        'recommendation' => 'Rekomendacja',
        'system' => 'Systemowe'
    ];
    
    return $types[$type] ?? 'Inne';
}

/**
 * Formatuje typ aktywności na przyjazny tekst
 * @param string $activity_type Typ aktywności
 * @return string
 */
function formatActivityType($activity_type) {
    $types = [
        'watched_episode' => 'obejrzał odcinek',
        'rated_show' => 'ocenił serial',
        'added_show' => 'dodał serial',
        'completed_show' => 'ukończył serial',
        'reviewed_show' => 'napisał recenzję'
    ];
    
    return $types[$activity_type] ?? 'wykonał akcję';
}
?>
