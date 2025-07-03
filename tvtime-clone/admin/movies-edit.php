<?php
/**
 * Edycja filmu - Admin Panel
 */

require_once 'config/database.php';
require_once 'includes/session.php';
require_once 'includes/functions.php';

// Sprawdź czy użytkownik ma uprawnienia administratora
if (!isLoggedIn() || !isAdmin()) {
    header('Location: login.php');
    exit();
}

$movie_id = (int)($_GET['id'] ?? 0);
$errors = [];
$success_message = '';

// Pobierz film do edycji
if ($movie_id <= 0) {
    header('Location: movies-list.php');
    exit();
}

try {
    $movie = fetchOne("SELECT * FROM movies WHERE id = ?", [$movie_id]);
    if (!$movie) {
        header('Location: movies-list.php');
        exit();
    }
} catch (Exception $e) {
    header('Location: movies-list.php');
    exit();
}

$form_data = [
    'title' => $movie['title'],
    'description' => $movie['description'],
    'genre' => $movie['genre'],
    'year' => $movie['year'],
    'runtime' => $movie['runtime'],
    'director' => $movie['director'],
    'poster_url' => $movie['poster_url']
];

// Obsługa formularza
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        // Pobierz dane z formularza
        $form_data = [
            'title' => trim($_POST['title'] ?? ''),
            'description' => trim($_POST['description'] ?? ''),
            'genre' => trim($_POST['genre'] ?? ''),
            'year' => (int)($_POST['year'] ?? date('Y')),
            'runtime' => (int)($_POST['runtime'] ?? 0),
            'director' => trim($_POST['director'] ?? ''),
            'poster_url' => $movie['poster_url'] // zachowaj obecny plakat
        ];
        
        // Walidacja
        if (empty($form_data['title'])) {
            $errors[] = 'Tytuł filmu jest wymagany.';
        }
        
        if (empty($form_data['description'])) {
            $errors[] = 'Opis filmu jest wymagany.';
        }
        
        if (empty($form_data['genre'])) {
            $errors[] = 'Gatunek filmu jest wymagany.';
        }
        
        if ($form_data['year'] < 1900 || $form_data['year'] > date('Y') + 5) {
            $errors[] = 'Rok wydania musi być między 1900 a ' . (date('Y') + 5) . '.';
        }
        
        if ($form_data['runtime'] && ($form_data['runtime'] < 1 || $form_data['runtime'] > 600)) {
            $errors[] = 'Czas trwania musi być między 1 a 600 minut.';
        }
        
        // Sprawdź czy film o tym tytule już istnieje (poza edytowanym)
        if (empty($errors)) {
            try {
                $existing = fetchOne("SELECT id FROM movies WHERE title = ? AND id != ?", [$form_data['title'], $movie_id]);
                if ($existing) {
                    $errors[] = 'Film o tym tytule już istnieje w bazie danych.';
                }
            } catch (Exception $e) {
                $errors[] = 'Błąd podczas sprawdzania duplikatów.';
            }
        }
        
        // Upload nowego plakatu (opcjonalnie)
        if (empty($errors) && isset($_FILES['poster']) && $_FILES['poster']['error'] !== UPLOAD_ERR_NO_FILE) {
            $upload_result = uploadMovieImage($_FILES['poster']);
            if ($upload_result['success']) {
                // Usuń stary plakat jeśli istnieje
                if ($movie['poster_url'] && file_exists($movie['poster_url'])) {
                    unlink($movie['poster_url']);
                }
                $form_data['poster_url'] = $upload_result['filename'];
            } else {
                $errors[] = 'Błąd podczas przesyłania plakatu: ' . $upload_result['error'];
            }
        }
        
        // Aktualizuj film w bazie danych
        if (empty($errors)) {
            try {
                $sql = "UPDATE movies SET 
                        title = ?, 
                        description = ?, 
                        genre = ?, 
                        year = ?, 
                        runtime = ?, 
                        director = ?, 
                        poster_url = ?,
                        updated_at = NOW()
                        WHERE id = ?";
                
                executeQuery($sql, [
                    $form_data['title'],
                    $form_data['description'],
                    $form_data['genre'],
                    $form_data['year'],
                    $form_data['runtime'] ?: null,
                    $form_data['director'] ?: null,
                    $form_data['poster_url'] ?: null,
                    $movie_id
                ]);
                
                $success_message = 'Film został pomyślnie zaktualizowany!';
                
                // Odśwież dane filmu
                $movie = fetchOne("SELECT * FROM movies WHERE id = ?", [$movie_id]);
                
            } catch (Exception $e) {
                $errors[] = 'Błąd podczas aktualizacji filmu: ' . $e->getMessage();
            }
        }
    } else {
        $errors[] = 'Nieprawidłowy token CSRF.';
    }
}

// Funkcja do uploadu obrazka filmu
function uploadMovieImage($file) {
    $upload_dir = 'assets/uploads/movies/';
    
    // Utwórz katalog jeśli nie istnieje
    if (!is_dir($upload_dir)) {
        if (!mkdir($upload_dir, 0755, true)) {
            return ['success' => false, 'error' => 'Nie można utworzyć katalogu uploads.'];
        }
    }
    
    // Sprawdź czy plik jest obrazem
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($file['type'], $allowed_types)) {
        return ['success' => false, 'error' => 'Dozwolone są tylko pliki JPG, PNG, GIF i WebP.'];
    }
    
    // Sprawdź rozmiar pliku (max 5MB)
    if ($file['size'] > 5 * 1024 * 1024) {
        return ['success' => false, 'error' => 'Plik nie może być większy niż 5MB.'];
    }
    
    // Generuj unikalną nazwę pliku
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'movie_' . uniqid() . '.' . $extension;
    $filepath = $upload_dir . $filename;
    
    // Przenieś plik
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return ['success' => true, 'filename' => 'assets/uploads/movies/' . $filename];
    } else {
        return ['success' => false, 'error' => 'Błąd podczas przesyłania pliku.'];
    }
}

$page_title = 'Edytuj Film - Admin Panel';
require_once 'includes/header.php';
?>

<div class="main-content">
    <div class="admin-header">
        <h1><i class="fas fa-edit"></i> Edytuj Film: <?php echo htmlspecialchars($movie['title']); ?></h1>
        <div class="admin-nav">
            <a href="movies-list.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Powrót do listy
            </a>
            <a href="movies-add.php" class="btn btn-outline">
                <i class="fas fa-plus"></i> Dodaj nowy film
            </a>
        </div>
    </div>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if (!empty($success_message)): ?>
        <div class="alert alert-success">
            <?php echo htmlspecialchars($success_message); ?>
        </div>
    <?php endif; ?>

    <div class="form-container">
        <form method="POST" enctype="multipart/form-data" class="admin-form">
            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
            
            <div class="form-grid">
                <div class="form-group">
                    <label for="title">Tytuł filmu *</label>
                    <input type="text" 
                           id="title" 
                           name="title" 
                           value="<?php echo htmlspecialchars($form_data['title']); ?>" 
                           required 
                           class="form-control"
                           placeholder="Wprowadź tytuł filmu">
                </div>

                <div class="form-group">
                    <label for="year">Rok wydania *</label>
                    <input type="number" 
                           id="year" 
                           name="year" 
                           value="<?php echo $form_data['year']; ?>" 
                           min="1900" 
                           max="<?php echo date('Y') + 5; ?>" 
                           required 
                           class="form-control">
                </div>

                <div class="form-group">
                    <label for="genre">Gatunek *</label>
                    <select id="genre" name="genre" required class="form-control">
                        <option value="">Wybierz gatunek</option>
                        <option value="Akcja" <?php echo $form_data['genre'] === 'Akcja' ? 'selected' : ''; ?>>Akcja</option>
                        <option value="Komedia" <?php echo $form_data['genre'] === 'Komedia' ? 'selected' : ''; ?>>Komedia</option>
                        <option value="Dramat" <?php echo $form_data['genre'] === 'Dramat' ? 'selected' : ''; ?>>Dramat</option>
                        <option value="Horror" <?php echo $form_data['genre'] === 'Horror' ? 'selected' : ''; ?>>Horror</option>
                        <option value="Sci-Fi" <?php echo $form_data['genre'] === 'Sci-Fi' ? 'selected' : ''; ?>>Sci-Fi</option>
                        <option value="Fantasy" <?php echo $form_data['genre'] === 'Fantasy' ? 'selected' : ''; ?>>Fantasy</option>
                        <option value="Thriller" <?php echo $form_data['genre'] === 'Thriller' ? 'selected' : ''; ?>>Thriller</option>
                        <option value="Romans" <?php echo $form_data['genre'] === 'Romans' ? 'selected' : ''; ?>>Romans</option>
                        <option value="Animacja" <?php echo $form_data['genre'] === 'Animacja' ? 'selected' : ''; ?>>Animacja</option>
                        <option value="Dokumentalny" <?php echo $form_data['genre'] === 'Dokumentalny' ? 'selected' : ''; ?>>Dokumentalny</option>
                        <option value="Kryminalny" <?php echo $form_data['genre'] === 'Kryminalny' ? 'selected' : ''; ?>>Kryminalny</option>
                        <option value="Przygodowy" <?php echo $form_data['genre'] === 'Przygodowy' ? 'selected' : ''; ?>>Przygodowy</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="runtime">Czas trwania (minuty)</label>
                    <input type="number" 
                           id="runtime" 
                           name="runtime" 
                           value="<?php echo $form_data['runtime']; ?>" 
                           min="1" 
                           max="600" 
                           class="form-control"
                           placeholder="np. 120">
                </div>

                <div class="form-group">
                    <label for="director">Reżyser</label>
                    <input type="text" 
                           id="director" 
                           name="director" 
                           value="<?php echo htmlspecialchars($form_data['director']); ?>" 
                           class="form-control"
                           placeholder="Imię i nazwisko reżysera">
                </div>

                <div class="form-group">
                    <label for="poster">Zmień plakat filmu</label>
                    <input type="file" 
                           id="poster" 
                           name="poster" 
                           accept="image/*" 
                           class="form-control">
                    <small class="form-text">Dozwolone formaty: JPG, PNG, GIF, WebP. Maksymalny rozmiar: 5MB. Pozostaw puste, aby zachować obecny plakat.</small>
                    
                    <?php if ($movie['poster_url']): ?>
                        <div class="current-poster">
                            <label>Obecny plakat:</label>
                            <img src="<?php echo htmlspecialchars($movie['poster_url']); ?>" 
                                 alt="<?php echo htmlspecialchars($movie['title']); ?>" 
                                 class="poster-preview">
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="form-group full-width">
                <label for="description">Opis filmu *</label>
                <textarea id="description" 
                          name="description" 
                          required 
                          class="form-control" 
                          rows="6"
                          placeholder="Wprowadź szczegółowy opis filmu..."><?php echo htmlspecialchars($form_data['description']); ?></textarea>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Zapisz zmiany
                </button>
                <a href="movies-list.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Anuluj
                </a>
            </div>
        </form>
    </div>

    <!-- Statystyki filmu -->
    <div class="movie-stats">
        <h3>Statystyki filmu</h3>
        <div class="stats-grid">
            <?php
            try {
                $stats = fetchOne("
                    SELECT 
                        COUNT(*) as total_users,
                        COUNT(CASE WHEN status = 'watched' THEN 1 END) as watched_count,
                        COUNT(CASE WHEN status IN ('want_to_watch', 'watchlist') THEN 1 END) as watchlist_count,
                        COUNT(CASE WHEN favorite = 1 THEN 1 END) as favorite_count,
                        AVG(CASE WHEN rating > 0 THEN rating END) as avg_rating
                    FROM user_movies 
                    WHERE movie_id = ?
                ", [$movie_id]);
            } catch (Exception $e) {
                $stats = ['total_users' => 0, 'watched_count' => 0, 'watchlist_count' => 0, 'favorite_count' => 0, 'avg_rating' => 0];
            }
            ?>
            
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['total_users']; ?></div>
                <div class="stat-label">Użytkowników ma film</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['watched_count']; ?></div>
                <div class="stat-label">Obejrzało</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['watchlist_count']; ?></div>
                <div class="stat-label">Na liście życzeń</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['favorite_count']; ?></div>
                <div class="stat-label">W ulubionych</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-number"><?php echo $stats['avg_rating'] ? number_format($stats['avg_rating'], 1) : '0'; ?></div>
                <div class="stat-label">Średnia ocena</div>
            </div>
        </div>
    </div>
</div>

<style>
.admin-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #333;
}

.admin-header h1 {
    color: #ff9500;
    font-size: 1.8rem;
    margin: 0;
}

.admin-nav {
    display: flex;
    gap: 1rem;
}

.form-container {
    background: #2a2a2a;
    border-radius: 12px;
    padding: 2rem;
    border: 1px solid #333;
    margin-bottom: 2rem;
}

.admin-form {
    max-width: 800px;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.form-group {
    display: flex;
    flex-direction: column;
}

.form-group.full-width {
    grid-column: 1 / -1;
}

.form-group label {
    color: #fff;
    font-weight: 600;
    margin-bottom: 0.5rem;
    font-size: 0.9rem;
}

.form-control {
    background: #1a1a1a;
    border: 1px solid #333;
    border-radius: 8px;
    padding: 0.75rem;
    color: #fff;
    font-size: 0.9rem;
    transition: border-color 0.3s ease;
}

.form-control:focus {
    outline: none;
    border-color: #ff9500;
    box-shadow: 0 0 0 2px rgba(255, 149, 0, 0.2);
}

.form-control::placeholder {
    color: #666;
}

select.form-control {
    cursor: pointer;
}

textarea.form-control {
    resize: vertical;
    min-height: 120px;
}

.form-text {
    color: #aaa;
    font-size: 0.8rem;
    margin-top: 0.25rem;
}

.current-poster {
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid #333;
}

.current-poster label {
    margin-bottom: 0.5rem;
}

.poster-preview {
    width: 100px;
    height: 150px;
    object-fit: cover;
    border-radius: 8px;
    border: 1px solid #333;
}

.form-actions {
    display: flex;
    gap: 1rem;
    justify-content: flex-start;
    margin-top: 2rem;
    padding-top: 2rem;
    border-top: 1px solid #333;
}

.movie-stats {
    background: #2a2a2a;
    border-radius: 12px;
    padding: 2rem;
    border: 1px solid #333;
}

.movie-stats h3 {
    color: #ff9500;
    margin-bottom: 1.5rem;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 1rem;
}

.stat-card {
    background: #1a1a1a;
    border-radius: 8px;
    padding: 1.5rem;
    text-align: center;
    border: 1px solid #333;
}

.stat-number {
    font-size: 2rem;
    font-weight: 700;
    color: #ff9500;
}

.stat-label {
    color: #ccc;
    font-size: 0.9rem;
    margin-top: 0.5rem;
}

.alert {
    padding: 1rem;
    border-radius: 8px;
    margin-bottom: 2rem;
}

.alert-danger {
    background: rgba(220, 53, 69, 0.1);
    border: 1px solid rgba(220, 53, 69, 0.3);
    color: #f8d7da;
}

.alert-success {
    background: rgba(40, 167, 69, 0.1);
    border: 1px solid rgba(40, 167, 69, 0.3);
    color: #d4edda;
}

.alert ul {
    margin: 0;
    padding-left: 1.5rem;
}

.btn {
    padding: 0.75rem 1.5rem;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    border: none;
    cursor: pointer;
    transition: all 0.3s ease;
    font-size: 0.9rem;
}

.btn-primary {
    background: #ff9500;
    color: #fff;
}

.btn-primary:hover {
    background: #e68900;
    transform: translateY(-2px);
}

.btn-secondary {
    background: #6c757d;
    color: #fff;
}

.btn-secondary:hover {
    background: #5a6268;
}

.btn-outline {
    background: transparent;
    color: #ff9500;
    border: 1px solid #ff9500;
}

.btn-outline:hover {
    background: #ff9500;
    color: #fff;
}

@media (max-width: 768px) {
    .admin-header {
        flex-direction: column;
        gap: 1rem;
        align-items: stretch;
    }
    
    .admin-nav {
        justify-content: center;
    }
    
    .form-grid,
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .form-actions {
        flex-direction: column;
    }
}
</style>

<?php require_once 'includes/footer.php'; ?>
