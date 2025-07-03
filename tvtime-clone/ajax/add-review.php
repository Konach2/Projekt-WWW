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

if (!$input || !isset($input['show_id']) || !isset($input['rating']) || !isset($input['content'])) {
    echo json_encode(['success' => false, 'message' => 'Brak wymaganych danych']);
    exit();
}

// Verify CSRF token
if (!isset($input['csrf_token']) || !verifyCSRFToken($input['csrf_token'])) {
    echo json_encode(['success' => false, 'message' => 'Nieprawidłowy token bezpieczeństwa']);
    exit();
}

$user_id = getCurrentUserId();
$show_id = (int)$input['show_id'];
$rating = (float)$input['rating'];
$content = trim($input['content']);
$spoiler_warning = isset($input['spoiler_warning']) ? (bool)$input['spoiler_warning'] : false;

// Walidacja
if ($rating < 1 || $rating > 10) {
    echo json_encode(['success' => false, 'message' => 'Ocena musi być między 1 a 10']);
    exit();
}

if (strlen($content) < 10) {
    echo json_encode(['success' => false, 'message' => 'Recenzja musi mieć co najmniej 10 znaków']);
    exit();
}

if (strlen($content) > 2000) {
    echo json_encode(['success' => false, 'message' => 'Recenzja nie może być dłuższa niż 2000 znaków']);
    exit();
}

try {
    // Sprawdź czy serial istnieje
    $show = fetchOne("SELECT id FROM shows WHERE id = ?", [$show_id]);
    if (!$show) {
        echo json_encode(['success' => false, 'message' => 'Serial nie istnieje']);
        exit();
    }

    // Sprawdź czy użytkownik już nie dodał recenzji
    $existing = fetchOne("SELECT id FROM reviews WHERE user_id = ? AND show_id = ?", [$user_id, $show_id]);
    if ($existing) {
        echo json_encode(['success' => false, 'message' => 'Już dodałeś recenzję do tego serialu']);
        exit();
    }

    // Dodaj recenzję
    executeQuery("
        INSERT INTO reviews (user_id, show_id, rating, content, spoiler_warning) 
        VALUES (?, ?, ?, ?, ?)
    ", [$user_id, $show_id, $rating, $content, $spoiler_warning]);

    echo json_encode(['success' => true, 'message' => 'Recenzja została dodana']);

} catch (Exception $e) {
    error_log("Error in add-review.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Błąd podczas dodawania recenzji']);
}
?>
