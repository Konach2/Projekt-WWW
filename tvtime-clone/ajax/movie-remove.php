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

if (!$input || !isset($input['movie_id'])) {
    echo json_encode(['success' => false, 'message' => 'Brak wymaganych danych']);
    exit();
}

// Verify CSRF token
if (!isset($input['csrf_token']) || !verifyCSRFToken($input['csrf_token'])) {
    echo json_encode(['success' => false, 'message' => 'Nieprawidłowy token bezpieczeństwa']);
    exit();
}

$user_id = getCurrentUserId();
$movie_id = (int)$input['movie_id'];

try {
    // Usuń film z listy użytkownika
    $deleted = executeQuery("DELETE FROM user_movies WHERE user_id = ? AND movie_id = ?", [$user_id, $movie_id]);
    
    if ($deleted->rowCount() > 0) {
        // Usuń również powiązane recenzje
        executeQuery("DELETE FROM movie_reviews WHERE user_id = ? AND movie_id = ?", [$user_id, $movie_id]);
        
        echo json_encode(['success' => true, 'message' => 'Film usunięty z listy']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Film nie został znaleziony na Twojej liście']);
    }

} catch (Exception $e) {
    error_log("Error in movie-remove.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Błąd podczas usuwania']);
}
?>
