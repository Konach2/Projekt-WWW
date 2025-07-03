-- Rozszerzenie bazy danych TV Time Clone
-- Dodatkowe tabele i funkcjonalności

-- Tabela powiadomień użytkowników
CREATE TABLE IF NOT EXISTS user_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    type ENUM('new_episode', 'friend_activity', 'recommendation', 'system') DEFAULT 'system',
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    related_show_id INT NULL,
    related_user_id INT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (related_show_id) REFERENCES shows(id) ON DELETE SET NULL,
    FOREIGN KEY (related_user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user_unread (user_id, is_read),
    INDEX idx_created_at (created_at)
);

-- Tabela obserwowanych seriali (watchlist)
CREATE TABLE IF NOT EXISTS user_watchlist (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    show_id INT NOT NULL,
    priority ENUM('low', 'medium', 'high') DEFAULT 'medium',
    notes TEXT,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (show_id) REFERENCES shows(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_watchlist (user_id, show_id),
    INDEX idx_user_priority (user_id, priority)
);

-- Tabela list użytkowników (niestandardowe listy)
CREATE TABLE IF NOT EXISTS user_lists (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    is_public BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_public (user_id, is_public)
);

-- Tabela elementów list użytkowników
CREATE TABLE IF NOT EXISTS user_list_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    list_id INT NOT NULL,
    show_id INT NOT NULL,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    notes TEXT,
    FOREIGN KEY (list_id) REFERENCES user_lists(id) ON DELETE CASCADE,
    FOREIGN KEY (show_id) REFERENCES shows(id) ON DELETE CASCADE,
    UNIQUE KEY unique_list_show (list_id, show_id)
);

-- Tabela aktywności użytkowników
CREATE TABLE IF NOT EXISTS user_activity (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    activity_type ENUM('watched_episode', 'rated_show', 'added_show', 'completed_show', 'reviewed_show') NOT NULL,
    show_id INT NULL,
    episode_id INT NULL,
    data JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (show_id) REFERENCES shows(id) ON DELETE SET NULL,
    FOREIGN KEY (episode_id) REFERENCES episodes(id) ON DELETE SET NULL,
    INDEX idx_user_type_date (user_id, activity_type, created_at),
    INDEX idx_show_date (show_id, created_at)
);

-- Tabela followowania między użytkownikami (społeczność)
CREATE TABLE IF NOT EXISTS user_follows (
    id INT AUTO_INCREMENT PRIMARY KEY,
    follower_id INT NOT NULL,
    following_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (follower_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (following_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_follow (follower_id, following_id),
    INDEX idx_follower (follower_id),
    INDEX idx_following (following_id)
);

-- Dodaj kolumny do tabeli users dla rozszerzonego profilu
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS avatar_url VARCHAR(255) NULL AFTER email,
ADD COLUMN IF NOT EXISTS bio TEXT NULL AFTER avatar_url,
ADD COLUMN IF NOT EXISTS location VARCHAR(100) NULL AFTER bio,
ADD COLUMN IF NOT EXISTS birth_date DATE NULL AFTER location,
ADD COLUMN IF NOT EXISTS privacy_settings JSON NULL AFTER birth_date,
ADD COLUMN IF NOT EXISTS is_public BOOLEAN DEFAULT TRUE AFTER privacy_settings,
ADD COLUMN IF NOT EXISTS last_active TIMESTAMP NULL AFTER is_public;

-- Dodaj kolumny do tabeli shows
ALTER TABLE shows
ADD COLUMN IF NOT EXISTS imdb_id VARCHAR(20) NULL AFTER poster_url,
ADD COLUMN IF NOT EXISTS tmdb_id INT NULL AFTER imdb_id,
ADD COLUMN IF NOT EXISTS status ENUM('returning', 'ended', 'cancelled', 'in_production') DEFAULT 'returning' AFTER tmdb_id,
ADD COLUMN IF NOT EXISTS network VARCHAR(100) NULL AFTER status,
ADD COLUMN IF NOT EXISTS country VARCHAR(50) DEFAULT 'USA' AFTER network;

-- Aktualizuj tabele user_shows (zmiana statusów)
ALTER TABLE user_shows 
MODIFY COLUMN status ENUM('watching', 'completed', 'plan_to_watch', 'dropped', 'paused') DEFAULT 'plan_to_watch';

-- Dodaj kolumny do user_shows
ALTER TABLE user_shows
ADD COLUMN IF NOT EXISTS progress_episodes INT DEFAULT 0 AFTER rating,
ADD COLUMN IF NOT EXISTS total_episodes INT DEFAULT 0 AFTER progress_episodes,
ADD COLUMN IF NOT EXISTS last_watched_at TIMESTAMP NULL AFTER total_episodes,
ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER last_watched_at;

-- Przykładowe powiadomienia
INSERT IGNORE INTO user_notifications (user_id, type, title, message, related_show_id) VALUES
(1, 'system', 'Witamy w TV Time Clone!', 'Dziękujemy za dołączenie do naszej społeczności miłośników seriali. Rozpocznij od dodania swoich ulubionych seriali!', NULL),
(2, 'recommendation', 'Polecamy nowy serial', 'Na podstawie Twoich preferencji polecamy serial "Breaking Bad". Sprawdź szczegóły!', 1);

-- Przykładowa aktywność
INSERT IGNORE INTO user_activity (user_id, activity_type, show_id, data) VALUES
(1, 'added_show', 1, '{"status": "plan_to_watch", "rating": null}'),
(1, 'rated_show', 1, '{"rating": 9.5, "status": "completed"}'),
(2, 'watched_episode', 1, '{"episode_id": 1, "season": 1, "episode": 1}');

-- Przykładowe listy użytkowników
INSERT IGNORE INTO user_lists (user_id, name, description, is_public) VALUES
(1, 'Moje ulubione', 'Lista moich ulubionych seriali wszech czasów', TRUE),
(1, 'Do obejrzenia tego miesiąca', 'Seriale które planuję obejrzeć w tym miesiącu', FALSE),
(2, 'Komedie', 'Najlepsze seriale komediowe', TRUE);

-- Zaktualizuj dane przykładowe
UPDATE users SET last_active = CURRENT_TIMESTAMP WHERE id IN (1, 2);
