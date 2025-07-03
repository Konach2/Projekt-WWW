<?php
require_once 'config/database.php';
require_once 'includes/session.php';
require_once 'includes/functions.php';

$movie_id = (int)($_GET['id'] ?? 0);
if (!$movie_id) {
    header('Location: index.php');
    exit();
}

try {
    $movie = fetchOne("SELECT * FROM movies WHERE id = ?", [$movie_id]);
    if (!$movie) {
        header('Location: index.php');
        exit();
    }
    
    // Pobierz status użytkownika dla tego filmu
    $user_status = null;
    if (isLoggedIn()) {
        $user_status = fetchOne("SELECT * FROM user_movies WHERE user_id = ? AND movie_id = ?", 
                                [getCurrentUserId(), $movie_id]);
    }
    
    // Średnia ocena i liczba ocen
    $rating_stats = fetchOne("
        SELECT 
            AVG(rating) as avg_rating, 
            COUNT(rating) as rating_count,
            COUNT(*) as total_users
        FROM user_movies 
        WHERE movie_id = ? AND rating > 0
    ", [$movie_id]);
    
    // Recenzje
    $reviews = fetchAll("
        SELECT mr.*, u.username 
        FROM movie_reviews mr
        JOIN users u ON mr.user_id = u.id
        WHERE mr.movie_id = ?
        ORDER BY mr.created_at DESC
        LIMIT 10
    ", [$movie_id]);
    
    // Sprawdź czy użytkownik dodał recenzję
    $user_review = null;
    if (isLoggedIn()) {
        $user_review = fetchOne("SELECT * FROM movie_reviews WHERE user_id = ? AND movie_id = ?", 
                                [getCurrentUserId(), $movie_id]);
    }
    
} catch (Exception $e) {
    header('Location: index.php');
    exit();
}

$page_title = htmlspecialchars($movie['title']) . ' - TV Time Clone';
require_once 'includes/header.php';
?>

<div class="movie-details">
    <!-- Hero Section -->
    <div class="movie-hero">
        <div class="movie-backdrop">
            <?php if ($movie['backdrop_url']): ?>
                <img src="<?php echo htmlspecialchars($movie['backdrop_url']); ?>" alt="<?php echo htmlspecialchars($movie['title']); ?>">
            <?php else: ?>
                <div class="backdrop-placeholder"></div>
            <?php endif; ?>
            <div class="backdrop-overlay"></div>
        </div>
        
        <div class="movie-hero-content">
            <div class="movie-poster-large">
                <img src="<?php echo $movie['poster_url'] ? htmlspecialchars($movie['poster_url']) : 'assets/images/placeholder-show.jpg'; ?>" 
                     alt="<?php echo htmlspecialchars($movie['title']); ?>">
            </div>
            
            <div class="movie-main-info">
                <h1 class="movie-title"><?php echo htmlspecialchars($movie['title']); ?></h1>
                
                <?php if ($movie['original_title'] && $movie['original_title'] !== $movie['title']): ?>
                    <p class="original-title"><?php echo htmlspecialchars($movie['original_title']); ?></p>
                <?php endif; ?>
                
                <div class="movie-meta">
                    <span class="year"><?php echo $movie['year']; ?></span>
                    <span class="genre"><?php echo htmlspecialchars($movie['genre']); ?></span>
                    <?php if ($movie['runtime']): ?>
                        <span class="runtime"><?php echo $movie['runtime']; ?>min</span>
                    <?php endif; ?>
                    <span class="country"><?php echo htmlspecialchars($movie['country']); ?></span>
                </div>
                
                <?php if ($rating_stats && $rating_stats['avg_rating']): ?>
                    <div class="movie-rating-main">
                        <div class="rating-display">
                            <div class="rating-stars">
                                <?php
                                $avg_rating = $rating_stats['avg_rating'];
                                $full_stars = floor($avg_rating / 2);
                                $half_star = ($avg_rating / 2 - $full_stars) >= 0.5;
                                
                                for ($i = 1; $i <= 5; $i++) {
                                    if ($i <= $full_stars) {
                                        echo '<i class="fas fa-star"></i>';
                                    } elseif ($i == $full_stars + 1 && $half_star) {
                                        echo '<i class="fas fa-star-half-alt"></i>';
                                    } else {
                                        echo '<i class="far fa-star"></i>';
                                    }
                                }
                                ?>
                            </div>
                            <span class="rating-value"><?php echo number_format($avg_rating, 1); ?></span>
                            <span class="rating-count">(<?php echo $rating_stats['rating_count']; ?> ocen)</span>
                        </div>
                    </div>
                <?php endif; ?>
                
                <p class="movie-description"><?php echo nl2br(htmlspecialchars($movie['description'])); ?></p>
                
                <?php if ($movie['director']): ?>
                    <div class="movie-credits">
                        <p><strong>Reżyseria:</strong> <?php echo htmlspecialchars($movie['director']); ?></p>
                        <?php if ($movie['cast']): ?>
                            <p><strong>Obsada:</strong> <?php echo htmlspecialchars($movie['cast']); ?></p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                
                <?php if (isLoggedIn()): ?>
                    <div class="movie-actions">
                        <?php if ($user_status): ?>
                            <!-- User has this movie in their list -->
                            <div class="user-movie-controls">
                                <div class="status-control">
                                    <label>Status:</label>
                                    <select class="form-control status-select status-<?php echo $user_status['status']; ?>" 
                                            onchange="updateMovieStatus(<?php echo $movie_id; ?>, this.value)">
                                        <option value="want_to_watch" <?php echo $user_status['status'] === 'want_to_watch' ? 'selected' : ''; ?>>Chcę obejrzeć</option>
                                        <option value="watched" <?php echo $user_status['status'] === 'watched' ? 'selected' : ''; ?>>Obejrzany</option>
                                        <option value="dropped" <?php echo $user_status['status'] === 'dropped' ? 'selected' : ''; ?>>Porzucony</option>
                                    </select>
                                </div>
                                
                                <div class="rating-control">
                                    <label>Twoja ocena:</label>
                                    <input type="number" class="form-control" min="0" max="10" step="0.1" 
                                           value="<?php echo $user_status['rating'] ?? ''; ?>"
                                           placeholder="0-10"
                                           onchange="updateMovieRating(<?php echo $movie_id; ?>, this.value)">
                                </div>
                                
                                <div class="favorite-control">
                                    <button class="btn btn-favorite <?php echo $user_status['favorite'] ? 'active' : ''; ?>" 
                                            onclick="toggleMovieFavorite(<?php echo $movie_id; ?>, <?php echo $user_status['favorite'] ? 'false' : 'true'; ?>)">
                                        <i class="fas fa-heart"></i>
                                        <?php echo $user_status['favorite'] ? 'Usuń z ulubionych' : 'Dodaj do ulubionych'; ?>
                                    </button>
                                </div>
                            </div>
                        <?php else: ?>
                            <!-- User doesn't have this movie -->
                            <div class="add-to-list-actions">
                                <button class="btn btn-primary" onclick="addMovieToList(<?php echo $movie_id; ?>, 'watched')">
                                    <i class="fas fa-check"></i>
                                    Obejrzałem
                                </button>
                                <button class="btn btn-secondary" onclick="addMovieToList(<?php echo $movie_id; ?>, 'want_to_watch')">
                                    <i class="fas fa-bookmark"></i>
                                    Chcę obejrzeć
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="login-prompt">
                        <p><a href="login.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>">Zaloguj się</a>, aby dodać film do swojej listy i wystawić ocenę.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Reviews Section -->
    <div class="movie-content">
        <section class="reviews-section">
            <h2>
                <i class="fas fa-star"></i>
                Recenzje
                <?php if (count($reviews) > 0): ?>
                    <span class="section-count">(<?php echo count($reviews); ?>)</span>
                <?php endif; ?>
            </h2>
            
            <?php if (isLoggedIn()): ?>
                <?php if (!$user_review): ?>
                    <!-- Add Review Form -->
                    <div class="add-review-card">
                        <h3>Dodaj swoją recenzję</h3>
                        <form id="reviewForm" class="review-form">
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="review_rating">Ocena (1-10):</label>
                                    <input 
                                        type="number" 
                                        id="review_rating" 
                                        name="rating"
                                        class="form-control" 
                                        min="1" 
                                        max="10" 
                                        step="0.1"
                                        required
                                        placeholder="8.5"
                                    >
                                </div>
                                
                                <div class="form-group">
                                    <div class="form-check">
                                        <input type="checkbox" id="spoiler_warning" name="spoiler_warning" class="form-check-input">
                                        <label for="spoiler_warning" class="form-check-label">
                                            Zawiera spoilery
                                        </label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="review_content">Twoja recenzja:</label>
                                <textarea 
                                    id="review_content" 
                                    name="content"
                                    class="form-control" 
                                    rows="5"
                                    required
                                    placeholder="Podziel się swoją opinią o tym filmie..."
                                ></textarea>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane"></i>
                                Opublikuj recenzję
                            </button>
                        </form>
                    </div>
                <?php else: ?>
                    <!-- User's existing review -->
                    <div class="user-review-card">
                        <h3>Twoja recenzja</h3>
                        <div class="review-item">
                            <div class="review-header">
                                <div class="review-rating">
                                    <div class="rating-stars">
                                        <?php
                                        $user_rating = $user_review['rating'];
                                        $full_stars = floor($user_rating / 2);
                                        $half_star = ($user_rating / 2 - $full_stars) >= 0.5;
                                        
                                        for ($i = 1; $i <= 5; $i++) {
                                            if ($i <= $full_stars) {
                                                echo '<i class="fas fa-star"></i>';
                                            } elseif ($i == $full_stars + 1 && $half_star) {
                                                echo '<i class="fas fa-star-half-alt"></i>';
                                            } else {
                                                echo '<i class="far fa-star"></i>';
                                            }
                                        }
                                        ?>
                                    </div>
                                    <span class="rating-value"><?php echo number_format($user_rating, 1); ?></span>
                                </div>
                                
                                <div class="review-date">
                                    <?php echo date('d.m.Y', strtotime($user_review['created_at'])); ?>
                                </div>
                            </div>
                            
                            <?php if ($user_review['spoiler_warning']): ?>
                                <div class="spoiler-warning">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    Uwaga: ta recenzja może zawierać spoilery
                                </div>
                            <?php endif; ?>
                            
                            <div class="review-content">
                                <?php echo nl2br(htmlspecialchars($user_review['content'])); ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
            
            <!-- Reviews List -->
            <?php if (!empty($reviews)): ?>
                <div class="reviews-list">
                    <?php foreach ($reviews as $review): ?>
                        <?php if (isLoggedIn() && $review['user_id'] == getCurrentUserId()) continue; ?>
                        
                        <div class="review-item">
                            <div class="review-header">
                                <div class="review-author">
                                    <i class="fas fa-user-circle"></i>
                                    <span><?php echo htmlspecialchars($review['username']); ?></span>
                                </div>
                                
                                <div class="review-rating">
                                    <div class="rating-stars">
                                        <?php
                                        $review_rating = $review['rating'];
                                        $full_stars = floor($review_rating / 2);
                                        $half_star = ($review_rating / 2 - $full_stars) >= 0.5;
                                        
                                        for ($i = 1; $i <= 5; $i++) {
                                            if ($i <= $full_stars) {
                                                echo '<i class="fas fa-star"></i>';
                                            } elseif ($i == $full_stars + 1 && $half_star) {
                                                echo '<i class="fas fa-star-half-alt"></i>';
                                            } else {
                                                echo '<i class="far fa-star"></i>';
                                            }
                                        }
                                        ?>
                                    </div>
                                    <span class="rating-value"><?php echo number_format($review_rating, 1); ?></span>
                                </div>
                                
                                <div class="review-date">
                                    <?php echo date('d.m.Y', strtotime($review['created_at'])); ?>
                                </div>
                            </div>
                            
                            <?php if ($review['spoiler_warning']): ?>
                                <div class="spoiler-warning">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    Uwaga: ta recenzja może zawierać spoilery
                                    <button class="btn btn-sm spoiler-toggle" onclick="toggleSpoiler(this)">
                                        Pokaż
                                    </button>
                                </div>
                            <?php endif; ?>
                            
                            <div class="review-content <?php echo $review['spoiler_warning'] ? 'spoiler-hidden' : ''; ?>">
                                <?php echo nl2br(htmlspecialchars($review['content'])); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="no-reviews">
                    <div class="no-reviews-icon">
                        <i class="fas fa-star"></i>
                    </div>
                    <h3>Brak recenzji</h3>
                    <p>
                        <?php if (isLoggedIn()): ?>
                            Bądź pierwszą osobą, która podzieli się opinią o tym filmie!
                        <?php else: ?>
                            <a href="login.php?redirect=<?php echo urlencode($_SERVER['REQUEST_URI']); ?>">Zaloguj się</a>, 
                            aby dodać pierwszą recenzję tego filmu.
                        <?php endif; ?>
                    </p>
                </div>
            <?php endif; ?>
        </section>
    </div>
</div>

<style>
/* Movie details styles */
.movie-hero {
    position: relative;
    margin-bottom: 3rem;
}

.movie-backdrop {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 100%;
    z-index: -1;
}

.movie-backdrop img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.backdrop-placeholder {
    width: 100%;
    height: 100%;
    background: linear-gradient(135deg, #2a2a2a 0%, #1a1a1a 100%);
}

.backdrop-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(26, 26, 26, 0.8);
}

.movie-hero-content {
    display: grid;
    grid-template-columns: 300px 1fr;
    gap: 3rem;
    padding: 3rem 0;
    align-items: start;
    position: relative;
    z-index: 1;
}

.movie-poster-large img {
    width: 100%;
    border-radius: 12px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
}

.movie-title {
    font-size: 2.5rem;
    margin-bottom: 0.5rem;
    color: #ffffff;
}

.original-title {
    color: #cccccc;
    font-style: italic;
    margin-bottom: 1rem;
}

.movie-meta {
    display: flex;
    gap: 1rem;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
}

.movie-meta span {
    background-color: #3a3a3a;
    color: #cccccc;
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.9rem;
}

.movie-rating-main {
    margin-bottom: 2rem;
}

.rating-display {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.rating-stars {
    color: #ff9500;
    font-size: 1.2rem;
}

.rating-value {
    font-size: 1.5rem;
    font-weight: 600;
    color: #ffffff;
}

.rating-count {
    color: #888888;
}

.movie-description {
    font-size: 1.1rem;
    line-height: 1.6;
    color: #cccccc;
    margin-bottom: 2rem;
}

.movie-credits {
    margin-bottom: 2rem;
    color: #cccccc;
}

.movie-credits p {
    margin-bottom: 0.5rem;
}

.movie-actions {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.user-movie-controls {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

.status-control, .rating-control {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.favorite-control {
    grid-column: 1 / -1;
}

.status-control label, .rating-control label {
    font-size: 0.9rem;
    color: #cccccc;
}

.status-select {
    padding: 0.5rem;
    border-radius: 8px;
    font-weight: 500;
}

.status-select.status-watched { border-left: 4px solid #00c851; }
.status-select.status-want_to_watch { border-left: 4px solid #33b5e5; }
.status-select.status-dropped { border-left: 4px solid #ff4444; }

.btn-favorite {
    background: #2a2a2a;
    color: #cccccc;
    border: 1px solid #3a3a3a;
}

.btn-favorite.active {
    background: #ff4444;
    color: white;
    border-color: #ff4444;
}

.add-to-list-actions {
    display: flex;
    gap: 1rem;
}

.login-prompt {
    color: #cccccc;
}

.login-prompt a {
    color: #ff9500;
}

.movie-content {
    max-width: 1200px;
    margin: 0 auto;
}

.reviews-section h2 {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 2rem;
    color: #ff9500;
}

.add-review-card, .user-review-card {
    background-color: #2a2a2a;
    border: 1px solid #3a3a3a;
    border-radius: 12px;
    padding: 2rem;
    margin-bottom: 2rem;
}

.review-form .form-row {
    display: grid;
    grid-template-columns: 1fr auto;
    gap: 1rem;
    margin-bottom: 1rem;
}

.reviews-list {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.review-item {
    background-color: #2a2a2a;
    border: 1px solid #3a3a3a;
    border-radius: 12px;
    padding: 1.5rem;
}

.review-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
    flex-wrap: wrap;
    gap: 1rem;
}

.review-author {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: #cccccc;
}

.review-author i {
    color: #ff9500;
    font-size: 1.2rem;
}

.review-date {
    color: #888888;
    font-size: 0.9rem;
}

.spoiler-warning {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    background-color: rgba(255, 187, 51, 0.1);
    border: 1px solid #ffbb33;
    color: #ffbb33;
    padding: 0.75rem;
    border-radius: 8px;
    margin-bottom: 1rem;
    font-size: 0.9rem;
}

.review-content {
    color: #cccccc;
    line-height: 1.6;
}

.review-content.spoiler-hidden {
    filter: blur(5px);
    transition: filter 0.3s ease;
}

.no-reviews {
    text-align: center;
    padding: 3rem;
    color: #888888;
}

.no-reviews-icon {
    font-size: 3rem;
    margin-bottom: 1rem;
    color: #ff9500;
}

/* Mobile responsive */
@media (max-width: 768px) {
    .movie-hero-content {
        grid-template-columns: 1fr;
        gap: 2rem;
        text-align: center;
    }
    
    .movie-poster-large {
        max-width: 300px;
        margin: 0 auto;
    }
    
    .movie-title {
        font-size: 2rem;
    }
    
    .user-movie-controls {
        grid-template-columns: 1fr;
    }
    
    .add-to-list-actions {
        flex-direction: column;
    }
    
    .review-form .form-row {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
// Movie functions
function addMovieToList(movieId, status) {
    updateMovieStatus(movieId, status);
}

function updateMovieStatus(movieId, status, rating = null) {
    fetch('ajax/movie-status.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            movie_id: movieId,
            status: status,
            rating: rating,
            csrf_token: document.querySelector('input[name="csrf_token"]')?.value || ''
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showSuccess('Status filmu zaktualizowany!');
            setTimeout(() => window.location.reload(), 1000);
        } else {
            showError(data.message || 'Błąd podczas aktualizacji');
        }
    })
    .catch(() => showError('Błąd połączenia'));
}

function updateMovieRating(movieId, rating) {
    if (rating === '' || rating < 0 || rating > 10) return;
    updateMovieStatus(movieId, null, rating);
}

function toggleMovieFavorite(movieId, isFavorite) {
    fetch('ajax/movie-favorite.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            movie_id: movieId,
            favorite: isFavorite,
            csrf_token: document.querySelector('input[name="csrf_token"]')?.value || ''
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showSuccess('Zaktualizowano ulubione!');
            setTimeout(() => window.location.reload(), 1000);
        } else {
            showError(data.message || 'Błąd podczas aktualizacji');
        }
    })
    .catch(() => showError('Błąd połączenia'));
}

function toggleSpoiler(button) {
    const content = button.closest('.review-item').querySelector('.review-content');
    
    if (content.classList.contains('spoiler-hidden')) {
        content.classList.remove('spoiler-hidden');
        button.textContent = 'Ukryj';
    } else {
        content.classList.add('spoiler-hidden');
        button.textContent = 'Pokaż';
    }
}

// Review form handling
document.getElementById('reviewForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    formData.append('movie_id', <?php echo $movie_id; ?>);
    formData.append('csrf_token', document.querySelector('input[name="csrf_token"]').value);
    
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Dodawanie...';
    submitBtn.disabled = true;
    
    fetch('ajax/add-movie-review.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showSuccess('Recenzja została dodana pomyślnie!');
            setTimeout(() => window.location.reload(), 1500);
        } else {
            showError(data.message || 'Błąd podczas dodawania recenzji');
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        }
    })
    .catch(error => {
        showError('Błąd połączenia. Spróbuj ponownie.');
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
    });
});
</script>

<input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

<?php include 'includes/footer.php'; ?>
