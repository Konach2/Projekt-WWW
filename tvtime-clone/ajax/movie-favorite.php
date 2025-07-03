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

if (!$input || !isset($input['movie_id']) || !isset($input['favorite'])) {
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
$favorite = $input['favorite'] === 'true' || $input['favorite'] === true;

try {
    // Sprawdź czy użytkownik ma ten film na liście
    $existing = fetchOne("SELECT * FROM user_movies WHERE user_id = ? AND movie_id = ?", [$user_id, $movie_id]);
    
    if (!$existing) {
        // Dodaj film do listy z statusem want_to_watch jeśli nie istnieje
        executeQuery("INSERT INTO user_movies (user_id, movie_id, status, favorite) VALUES (?, ?, 'want_to_watch', ?)", 
                [$user_id, $movie_id, $favorite ? 1 : 0]);
    } else {
        // Aktualizuj status ulubionych
        executeQuery("UPDATE user_movies SET favorite = ?, updated_at = NOW() WHERE user_id = ? AND movie_id = ?", 
                [$favorite ? 1 : 0, $user_id, $movie_id]);
    }

    echo json_encode(['success' => true, 'message' => $favorite ? 'Dodano do ulubionych' : 'Usunięto z ulubionych']);

} catch (Exception $e) {
    error_log("Error in movie-favorite.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Błąd podczas aktualizacji']);
}
?>
