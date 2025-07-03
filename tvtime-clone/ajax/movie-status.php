<?php
require_once '../config/database.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('log_errors', 1);

// Debug logging
error_log("movie-status.php called - Method: " . $_SERVER['REQUEST_METHOD']);

if (!isLoggedIn()) {
    error_log("movie-status.php: User not logged in");
    echo json_encode(['success' => false, 'message' => 'Musisz być zalogowany']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error_log("movie-status.php: Invalid method - " . $_SERVER['REQUEST_METHOD']);
    echo json_encode(['success' => false, 'message' => 'Nieprawidłowa metoda']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
error_log("movie-status.php: Input data - " . json_encode($input));

if (!$input || !isset($input['movie_id'])) {
    error_log("movie-status.php: Missing movie_id");
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
$status = $input['status'] ?? null;
$rating = isset($input['rating']) ? (float)$input['rating'] : null;

try {
    // Sprawdź czy film istnieje
    $movie = fetchOne("SELECT * FROM movies WHERE id = ?", [$movie_id]);
    if (!$movie) {
        echo json_encode(['success' => false, 'message' => 'Film nie istnieje']);
        exit();
    }

    // Sprawdź czy użytkownik już ma ten film
    $existing = fetchOne("SELECT * FROM user_movies WHERE user_id = ? AND movie_id = ?", [$user_id, $movie_id]);

    if ($existing) {
        // Aktualizuj istniejący wpis
        $update_data = [];
        $update_params = [];
        
        if ($status) {
            $update_data[] = "status = ?";
            $update_params[] = $status;
        }
        
        if ($rating !== null) {
            $update_data[] = "rating = ?";
            $update_params[] = $rating;
        }
        
        if ($status === 'watched') {
            $update_data[] = "watched_at = NOW()";
        }
        
        $update_data[] = "updated_at = NOW()";
        $update_params[] = $user_id;
        $update_params[] = $movie_id;
        
        $sql = "UPDATE user_movies SET " . implode(', ', $update_data) . " WHERE user_id = ? AND movie_id = ?";
        executeQuery($sql, $update_params);
    } else {
        // Dodaj nowy wpis
        $insert_status = $status ?: 'want_to_watch';
        $watched_at = ($insert_status === 'watched') ? 'NOW()' : 'NULL';
        
        executeQuery("INSERT INTO user_movies (user_id, movie_id, status, rating, watched_at) VALUES (?, ?, ?, ?, " . $watched_at . ")", 
                [$user_id, $movie_id, $insert_status, $rating]);
    }

    // Loguj aktywność - tymczasowo wyłączone (tabela user_activity nie istnieje)
    /*
    $activity_type = 'added_movie';
    if ($status === 'watched') {
        $activity_type = 'watched_movie';
    } elseif ($rating !== null) {
        $activity_type = 'rated_movie';
    }

    $activity_data = [
        'status' => $status ?: ($existing ? $existing['status'] : 'want_to_watch'),
        'rating' => $rating
    ];

    executeQuery("INSERT INTO user_activity (user_id, activity_type, movie_id, data) VALUES (?, ?, ?, ?)", 
            [$user_id, $activity_type, $movie_id, json_encode($activity_data)]);
    */

    echo json_encode(['success' => true, 'message' => 'Status filmu zaktualizowany']);

} catch (Exception $e) {
    error_log("Error in movie-status.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Błąd podczas aktualizacji']);
}
?>
