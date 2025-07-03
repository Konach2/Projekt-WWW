<?php
require_once '../config/database.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Nieprawidłowa metoda']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['query'])) {
    echo json_encode(['success' => false, 'message' => 'Brak zapytania']);
    exit();
}

$query = trim($input['query']);
$type = $input['type'] ?? 'shows';

if (strlen($query) < 2) {
    echo json_encode(['success' => true, 'results' => []]);
    exit();
}

try {
    $results = [];
    $searchPattern = '%' . $query . '%';
    
    if ($type === 'shows') {
        // Wyszukiwanie seriali
        $shows = fetchAll("
            SELECT id, title, genre, year, cover as poster 
            FROM shows 
            WHERE title LIKE ? OR genre LIKE ? 
            ORDER BY 
                CASE WHEN title LIKE ? THEN 1 ELSE 2 END,
                title ASC
            LIMIT 10
        ", [$searchPattern, $searchPattern, $query . '%']);
        
        $results = $shows;
        
    } elseif ($type === 'movies') {
        // Wyszukiwanie filmów
        $movies = fetchAll("
            SELECT id, title, genre, year, poster_url as poster 
            FROM movies 
            WHERE title LIKE ? OR genre LIKE ? OR director LIKE ?
            ORDER BY 
                CASE WHEN title LIKE ? THEN 1 ELSE 2 END,
                title ASC
            LIMIT 10
        ", [$searchPattern, $searchPattern, $searchPattern, $query . '%']);
        
        $results = $movies;
    }
    
    echo json_encode(['success' => true, 'results' => $results]);

} catch (Exception $e) {
    error_log("Error in search-all.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Błąd podczas wyszukiwania']);
}
?>
