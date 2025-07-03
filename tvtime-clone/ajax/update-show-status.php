<?php
require_once '../config/database.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Musisz być zalogowany']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Nieprawidłowa metoda']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['show_id']) || !isset($input['status'])) {
    echo json_encode(['success' => false, 'message' => 'Brakuje wymaganych danych']);
    exit();
}

$show_id = (int)$input['show_id'];
$status = $input['status'];
$user_id = getCurrentUserId();

$allowed_statuses = ['watching', 'plan_to_watch', 'completed'];
if (!in_array($status, $allowed_statuses)) {
    echo json_encode(['success' => false, 'message' => 'Nieprawidłowy status']);
    exit();
}

try {
    // Sprawdź czy serial istnieje
    $show = fetchOne("SELECT id FROM shows WHERE id = ?", [$show_id]);
    if (!$show) {
        echo json_encode(['success' => false, 'message' => 'Serial nie istnieje']);
        exit();
    }
    
    // Sprawdź czy użytkownik już ma ten serial
    $existing = fetchOne("SELECT id FROM user_shows WHERE user_id = ? AND show_id = ?", [$user_id, $show_id]);
    
    if ($existing) {
        // Aktualizuj status
        $stmt = getDB()->prepare("UPDATE user_shows SET status = ?, updated_at = NOW() WHERE user_id = ? AND show_id = ?");
        $stmt->execute([$status, $user_id, $show_id]);
    } else {
        // Dodaj nowy wpis
        $stmt = getDB()->prepare("INSERT INTO user_shows (user_id, show_id, status, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())");
        $stmt->execute([$user_id, $show_id, $status]);
    }
    
    echo json_encode(['success' => true, 'message' => 'Status zaktualizowany']);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Błąd bazy danych']);
}
?>
