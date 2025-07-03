<?php
require_once 'config/database.php';
require_once 'includes/session.php';
require_once 'includes/functions.php';

$show_id = (int)($_GET['id'] ?? 0);
if (!$show_id) {
    header('Location: index.php');
    exit();
}

try {
    $show = fetchOne("SELECT * FROM shows WHERE id = ?", [$show_id]);
    if (!$show) {
        header('Location: index.php');
        exit();
    }
    
    // Pobierz status użytkownika dla tego serialu
    $user_status = null;
    if (isLoggedIn()) {
        $user_status = fetchOne("SELECT status, rating FROM user_shows WHERE user_id = ? AND show_id = ?", 
                                [getCurrentUserId(), $show_id]);
    }
    
    // Średnia ocena
    $avg_rating = fetchOne("SELECT AVG(rating) as avg FROM user_shows WHERE show_id = ? AND rating > 0", [$show_id]);
    
    // Pobierz recenzje dla tego serialu
    $reviews = fetchAll("
        SELECT r.*, u.username 
        FROM reviews r 
        JOIN users u ON r.user_id = u.id 
        WHERE r.show_id = ? 
        ORDER BY r.created_at DESC
    ", [$show_id]);
    
    // Sprawdź czy użytkownik już dodał recenzję
    $user_review = null;
    if (isLoggedIn()) {
        $user_review = fetchOne("SELECT * FROM reviews WHERE user_id = ? AND show_id = ?", 
                                [getCurrentUserId(), $show_id]);
    }
    
} catch (Exception $e) {
    header('Location: index.php');
    exit();
}

$page_title = htmlspecialchars($show['title']) . ' - TV Time Clone';
require_once 'includes/header.php';
?>
<div class="show-details">
    <div class="show-hero">
        <div class="show-backdrop">
            <?php
            $poster = getImageUrl($show['poster_url']);
            ?>
            <img src="<?php echo $poster; ?>" alt="<?php echo htmlspecialchars($show['title']); ?>">
        </div>
        <div class="show-overlay">
            <div class="show-main-info">
                <div class="show-poster">
                    <img src="<?php echo $poster; ?>" alt="<?php echo htmlspecialchars($show['title']); ?>">
                </div>
                <div class="show-info">
                    <h1><?php echo htmlspecialchars($show['title']); ?></h1>
                    <div class="show-meta">
                        <span class="genre"><?php echo htmlspecialchars($show['genre']); ?></span>
                        <span class="year"><?php echo $show['year']; ?></span>
                        <?php if ($avg_rating && $avg_rating['avg']): ?>
                            <span class="rating">⭐ <?php echo number_format($avg_rating['avg'], 1); ?></span>
                        <?php endif; ?>
                        <?php if (isLoggedIn() && $user_status): ?>
                            <span class="show-status status-<?php echo $user_status['status']; ?>" style="margin-left:1rem;float:right;">
                                <?php 
                                $statuses = [
                                    'watching' => 'Oglądam',
                                    'completed' => 'Ukończone',
                                    'plan_to_watch' => 'Chcę obejrzeć'
                                ];
                                echo $statuses[$user_status['status']] ?? ucfirst($user_status['status']);
                                ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    <p class="show-description"><?php echo nl2br(htmlspecialchars($show['description'])); ?></p>
                    <?php if (isLoggedIn()): ?>
                    <div class="show-actions">
                        <?php if ($user_status): ?>
                            <button class="btn btn-primary" onclick="toggleStatusPanel()">
                                <i class="fas fa-edit"></i> Zmień status
                            </button>
                            <div id="status-panel" style="display:none; margin-top:1rem;">
                                <button class="btn btn-outline" onclick="setShowStatus(<?php echo $show_id; ?>, 'watching')">Oglądam</button>
                                <button class="btn btn-outline" onclick="setShowStatus(<?php echo $show_id; ?>, 'completed')">Ukończone</button>
                                <button class="btn btn-outline" onclick="setShowStatus(<?php echo $show_id; ?>, 'plan_to_watch')">Chcę obejrzeć</button>
                            </div>
                        <?php else: ?>
                            <button class="btn btn-primary" onclick="addToWatchlist(<?php echo $show_id; ?>, 'watching')">
                                <i class="fas fa-play"></i> Oglądam
                            </button>
                            <button class="btn btn-secondary" onclick="addToWatchlist(<?php echo $show_id; ?>, 'plan_to_watch')">
                                <i class="fas fa-bookmark"></i> Chcę obejrzeć
                            </button>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="show-content">
        <div class="watch-providers">
            <h3>Gdzie obejrzeć</h3>
            <div class="providers-list">
                <div class="provider">
                    <img src="https://images.justwatch.com/icon/190848813/s100/netflix.webp" alt="Netflix">
                    <span>Netflix</span>
                </div>
                <div class="provider">
                    <img src="https://images.justwatch.com/icon/52449861/s100/disney-plus.webp" alt="Disney+">
                    <span>Disney+</span>
                </div>
                <div class="provider">
                    <img src="https://images.justwatch.com/icon/430997/s100/amazon-prime-video.webp" alt="Prime Video">
                    <span>Prime Video</span>
                </div>
            </div>
        </div>
        
        <div class="show-stats">
            <h3>Informacje</h3>
            <div class="stats-grid">
                <div class="stat">
                    <span class="label">Rok premiery:</span>
                    <span class="value"><?php echo $show['year']; ?></span>
                </div>
                <div class="stat">
                    <span class="label">Gatunek:</span>
                    <span class="value"><?php echo htmlspecialchars($show['genre']); ?></span>
                </div>
                <?php if ($avg_rating && $avg_rating['avg']): ?>
                <div class="stat">
                    <span class="label">Średnia ocena:</span>
                    <span class="value">⭐ <?php echo number_format($avg_rating['avg'], 1); ?>/5</span>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Sekcja recenzji -->
        <div class="reviews-section">
            <h3>Recenzje użytkowników</h3>
            
            <?php if (isLoggedIn()): ?>
                <?php if (!$user_review): ?>
                    <!-- Formularz dodawania recenzji -->
                    <div class="add-review-form">
                        <h4>Dodaj swoją recenzję</h4>
                        <form id="reviewForm">
                            <div class="rating-input">
                                <label>Ocena (1-10):</label>
                                <input type="number" id="reviewRating" min="1" max="10" step="0.1" required>
                            </div>
                            <div class="content-input">
                                <label>Treść recenzji:</label>
                                <textarea id="reviewContent" rows="4" placeholder="Napisz swoją recenzję..." required></textarea>
                            </div>
                            <div class="spoiler-input">
                                <label>
                                    <input type="checkbox" id="spoilerWarning">
                                    Ta recenzja zawiera spoilery
                                </label>
                            </div>
                            <button type="submit" class="btn btn-primary">Dodaj recenzję</button>
                        </form>
                    </div>
                <?php else: ?>
                    <div class="user-review-notice">
                        <p><i class="fas fa-info-circle"></i> Już dodałeś recenzję do tego serialu.</p>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="login-prompt">
                    <p><a href="login.php">Zaloguj się</a>, aby dodać recenzję.</p>
                </div>
            <?php endif; ?>
            
            <!-- Lista recenzji -->
            <div class="reviews-list">
                <?php if (!empty($reviews)): ?>
                    <?php foreach ($reviews as $review): ?>
                        <div class="review-item <?php echo $review['spoiler_warning'] ? 'has-spoiler' : ''; ?>">
                            <div class="review-header">
                                <div class="review-author">
                                    <strong><?php echo htmlspecialchars($review['username']); ?></strong>
                                    <span class="review-rating">
                                        <i class="fas fa-star"></i>
                                        <?php echo number_format($review['rating'], 1); ?>/10
                                    </span>
                                </div>
                                <div class="review-date">
                                    <?php echo date('d.m.Y', strtotime($review['created_at'])); ?>
                                </div>
                            </div>
                            
                            <?php if ($review['spoiler_warning']): ?>
                                <div class="spoiler-warning">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    Ta recenzja zawiera spoilery
                                    <button class="show-spoiler-btn" onclick="toggleSpoiler(this)">
                                        Pokaż mimo to
                                    </button>
                                </div>
                                <div class="review-content spoiler-hidden">
                                    <?php echo nl2br(htmlspecialchars($review['content'])); ?>
                                </div>
                            <?php else: ?>
                                <div class="review-content">
                                    <?php echo nl2br(htmlspecialchars($review['content'])); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-reviews">
                        <p>Brak recenzji dla tego serialu. Bądź pierwszy i dodaj swoją!</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<style>
.show-details {
    margin: 0;
    padding: 0;
}

.show-hero {
    position: relative;
    height: 70vh;
    min-height: 500px;
    overflow: hidden;
}

.show-backdrop {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 1;
}

.show-backdrop img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    filter: blur(10px) brightness(0.3);
}

.show-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(to bottom, rgba(0,0,0,0.3), rgba(0,0,0,0.8));
    z-index: 2;
    display: flex;
    align-items: center;
    padding: 2rem;
}

.show-main-info {
    display: flex;
    gap: 2rem;
    max-width: 1200px;
    width: 100%;
}

.show-poster img {
    width: 300px;
    height: 450px;
    object-fit: cover;
    border-radius: 12px;
    box-shadow: 0 8px 32px rgba(0,0,0,0.5);
}

.show-info {
    flex: 1;
    color: white;
}

.show-info h1 {
    font-size: 3rem;
    margin-bottom: 1rem;
    font-weight: 700;
}

.show-meta {
    display: flex;
    gap: 1rem;
    margin-bottom: 1.5rem;
    font-size: 1.1rem;
}

.show-meta span {
    background: rgba(255,255,255,0.2);
    padding: 0.5rem 1rem;
    border-radius: 20px;
    backdrop-filter: blur(10px);
}

.show-description {
    font-size: 1.2rem;
    line-height: 1.6;
    margin-bottom: 2rem;
    max-width: 600px;
}

.show-actions {
    display: flex;
    gap: 1rem;
    align-items: center;
}

.show-status {
    display: inline-block;
    padding: 0.2rem 0.8rem;
    border-radius: 12px;
    font-size: 1rem;
    font-weight: 600;
    margin-left: 0.5rem;
}

.status-watching { background: #00c851; color: #fff; }
.status-completed { background: #007bff; color: #fff; }
.status-plan_to_watch { background: #ff9500; color: #fff; }

.show-content {
    padding: 3rem 2rem;
    max-width: 1200px;
    margin: 0 auto;
}

.watch-providers {
    margin-bottom: 3rem;
}

.watch-providers h3 {
    color: #ff9500;
    margin-bottom: 1.5rem;
    font-size: 1.5rem;
}

.providers-list {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}

.provider {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    background: #2a2a2a;
    padding: 1rem 1.5rem;
    border-radius: 25px;
    border: 1px solid #3a3a3a;
    transition: transform 0.2s;
}

.provider:hover {
    transform: translateY(-2px);
}

.provider img {
    width: 32px;
    height: 32px;
    border-radius: 6px;
}

.show-stats h3 {
    color: #ff9500;
    margin-bottom: 1.5rem;
    font-size: 1.5rem;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1rem;
}

.stat {
    background: #2a2a2a;
    padding: 1.5rem;
    border-radius: 12px;
    border: 1px solid #3a3a3a;
}

.stat .label {
    display: block;
    color: #aaa;
    font-size: 0.9rem;
    margin-bottom: 0.5rem;
}

.stat .value {
    display: block;
    color: #fff;
    font-size: 1.2rem;
    font-weight: 600;
}

/* Styles for reviews section */
.reviews-section {
    background: #1a1a1a;
    padding: 2rem;
    border-radius: 12px;
    margin-top: 2rem;
}

.reviews-section h3, .reviews-section h4 {
    color: #ff9500;
    margin-bottom: 1.5rem;
}

.add-review-form {
    background: #2a2a2a;
    padding: 1.5rem;
    border-radius: 8px;
    margin-bottom: 2rem;
    border: 1px solid #3a3a3a;
}

.add-review-form label {
    color: #fff;
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
}

.rating-input, .content-input, .spoiler-input {
    margin-bottom: 1rem;
}

.rating-input input {
    width: 100px;
    padding: 0.5rem;
    border: 1px solid #3a3a3a;
    background: #1a1a1a;
    color: #fff;
    border-radius: 4px;
}

.content-input textarea {
    width: 100%;
    padding: 0.8rem;
    border: 1px solid #3a3a3a;
    background: #1a1a1a;
    color: #fff;
    border-radius: 4px;
    resize: vertical;
    font-family: inherit;
}

.spoiler-input label {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    cursor: pointer;
}

.spoiler-input input[type="checkbox"] {
    margin: 0;
}

.reviews-list {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.review-item {
    background: #2a2a2a;
    padding: 1.5rem;
    border-radius: 8px;
    border: 1px solid #3a3a3a;
}

.review-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.review-author {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.review-author strong {
    color: #fff;
}

.review-rating {
    color: #ff9500;
    font-size: 0.9rem;
}

.review-date {
    color: #aaa;
    font-size: 0.9rem;
}

.review-content {
    color: #ddd;
    line-height: 1.6;
}

.spoiler-warning {
    background: #3a2a00;
    border: 1px solid #ff9500;
    padding: 0.8rem;
    border-radius: 4px;
    margin-bottom: 1rem;
    color: #ff9500;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.show-spoiler-btn {
    background: #ff9500;
    color: #000;
    border: none;
    padding: 0.3rem 0.8rem;
    border-radius: 4px;
    cursor: pointer;
    font-size: 0.8rem;
    margin-left: auto;
}

.spoiler-hidden {
    display: none;
}

.user-review-notice, .login-prompt, .no-reviews {
    text-align: center;
    padding: 1rem;
    color: #aaa;
    font-style: italic;
}

.login-prompt a {
    color: #ff9500;
    text-decoration: none;
}

.login-prompt a:hover {
    text-decoration: underline;
}

@media (max-width: 768px) {
    .show-main-info {
        flex-direction: column;
        align-items: center;
        text-align: center;
    }
    
    .show-poster img {
        width: 200px;
        height: 300px;
    }
    
    .show-info h1 {
        font-size: 2rem;
    }
    
    .show-meta {
        justify-content: center;
        flex-wrap: wrap;
    }
    
    .show-overlay {
        padding: 1rem;
    }
}
</style>

<script>
function addToWatchlist(showId, status) {
    fetch('ajax/update-show-status.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            show_id: showId,
            status: status,
            csrf_token: document.querySelector('input[name="csrf_token"]').value
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Błąd: ' + data.message);
        }
    });
}

function toggleStatusPanel() {
    const panel = document.getElementById('status-panel');
    panel.style.display = panel.style.display === 'none' ? 'block' : 'none';
}

function setShowStatus(showId, status) {
    addToWatchlist(showId, status);
}

// Obsługa formularza recenzji
document.addEventListener('DOMContentLoaded', function() {
    const reviewForm = document.getElementById('reviewForm');
    if (reviewForm) {
        reviewForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const rating = document.getElementById('reviewRating').value;
            const content = document.getElementById('reviewContent').value;
            const spoilerWarning = document.getElementById('spoilerWarning').checked;
            
            if (!rating || !content) {
                alert('Wypełnij wszystkie wymagane pola.');
                return;
            }
            
            fetch('ajax/add-review.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    show_id: <?php echo $show_id; ?>,
                    rating: parseFloat(rating),
                    content: content,
                    spoiler_warning: spoilerWarning,
                    csrf_token: document.querySelector('input[name="csrf_token"]').value
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Recenzja została dodana!');
                    location.reload();
                } else {
                    alert('Błąd: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Wystąpił błąd podczas dodawania recenzji.');
            });
        });
    }
});

// Toggle spoilerów
function toggleSpoiler(button) {
    const reviewItem = button.closest('.review-item');
    const spoilerContent = reviewItem.querySelector('.spoiler-hidden');
    
    if (spoilerContent.style.display === 'none' || spoilerContent.style.display === '') {
        spoilerContent.style.display = 'block';
        button.textContent = 'Ukryj spoiler';
    } else {
        spoilerContent.style.display = 'none';
        button.textContent = 'Pokaż mimo to';
    }
}
</script>

<input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">

<?php require_once 'includes/footer.php'; ?>
