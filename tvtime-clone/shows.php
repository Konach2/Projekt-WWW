<?php
// Zakładka: Seriale
require_once 'config/database.php';
require_once 'includes/session.php';
require_once 'includes/functions.php';
require_once 'includes/header.php';

if (!isLoggedIn()) {
    echo '<div class="main-content"><h1>Seriale</h1><p>Aby zobaczyć swoje seriale, zaloguj się.</p></div>';
    require_once 'includes/footer.php';
    exit();
}

$user_id = $_SESSION['user_id'];
$shows = fetchAll("SELECT s.id, s.title, s.year, s.genre, s.poster_url, us.status FROM shows s
    JOIN user_shows us ON us.show_id = s.id
    WHERE us.user_id = ? ORDER BY us.updated_at DESC", [$user_id]);
?>
<div class="main-content">
    <h1>Seriale</h1>
    <?php if (empty($shows)): ?>
        <p>Nie masz jeszcze żadnych seriali na swoim koncie.</p>
    <?php else: ?>
        <div class="shows-list">
            <?php foreach ($shows as $show): ?>
                <div class="show-card">
                    <div class="show-cover">
                        <img src="<?php echo getImageUrl($show['poster_url']); ?>" alt="<?php echo htmlspecialchars($show['title']); ?>">
                    </div>
                    <div class="show-info">
                        <h2><?php echo htmlspecialchars($show['title']); ?></h2>
                        <p class="show-meta"><?php echo htmlspecialchars($show['genre']); ?> | <?php echo $show['year']; ?></p>
                        <span class="show-status show-status-<?php echo strtolower($show['status']); ?>">
                            <?php 
                            switch($show['status']) {
                                case 'watching':
                                case 'WATCHING':
                                    echo 'Oglądam';
                                    break;
                                case 'completed':
                                case 'COMPLETED':
                                    echo 'Ukończone';
                                    break;
                                case 'plan_to_watch':
                                case 'PLAN_TO_WATCH':
                                    echo 'Do obejrzenia';
                                    break;
                                case 'on_hold':
                                case 'ON_HOLD':
                                    echo 'Wstrzymane';
                                    break;
                                case 'dropped':
                                case 'DROPPED':
                                    echo 'Porzucone';
                                    break;
                                default:
                                    echo 'Nieznany';
                            }
                            ?>
                        </span>
                        <a href="show-details.php?id=<?php echo $show['id']; ?>" class="btn btn-details">Szczegóły</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
<style>
.shows-list {
    display: flex;
    flex-wrap: wrap;
    gap: 1.5rem;
}
.show-card {
    background: #232323;
    border-radius: 10px;
    box-shadow: 0 2px 8px #0002;
    display: flex;
    width: 350px;
    overflow: hidden;
    margin-bottom: 1rem;
    position: relative;
}
.show-cover img {
    width: 110px;
    height: 160px;
    object-fit: cover;
    background: #111;
}
.show-info {
    padding: 1rem;
    padding-right: 1rem;
    flex: 1;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}
.show-info h2 {
    font-size: 1.2rem;
    margin: 0 0 0.5rem 0;
    color: #fff;
    padding-right: 85px;
}
.show-meta {
    color: #aaa;
    font-size: 0.95rem;
    margin-bottom: 0.5rem;
}
.show-status {
    position: absolute;
    top: 0.75rem;
    right: 0.75rem;
    padding: 0.2rem 0.7rem;
    border-radius: 12px;
    font-size: 0.8rem;
    font-weight: 500;
    color: #fff;
    z-index: 2;
}
.show-status-watching { background: #2ecc40; }
.show-status-completed { background: #3498db; }
.show-status-plan_to_watch { background: #ff9500; }
.show-status-on_hold { background: #f39c12; }
.show-status-dropped { background: #e74c3c; }
.btn.btn-details {
    background: #ff9500;
    color: #fff;
    border: none;
    border-radius: 6px;
    padding: 0.5rem 1.2rem;
    text-decoration: none;
    font-weight: 600;
    transition: background 0.2s;
    margin-top: 0.5rem;
    display: inline-block;
}
.btn.btn-details:hover {
    background: #ffa733;
}
</style>
<?php require_once 'includes/footer.php'; ?>
