-- TV Time Clone Database Structure
-- Autor: System
-- Data: 2025-06-29

-- Tworzenie bazy danych
CREATE DATABASE IF NOT EXISTS tvtime_clone CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE tvtime_clone;

-- Tabela użytkowników
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'user') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_email (email)
);

-- Tabela seriali
CREATE TABLE shows (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    genre VARCHAR(100) NOT NULL,
    year INT NOT NULL,
    poster_url VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_title (title),
    INDEX idx_genre (genre),
    INDEX idx_year (year)
);

-- Tabela sezonów
CREATE TABLE seasons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    show_id INT NOT NULL,
    season_number INT NOT NULL,
    episode_count INT NOT NULL DEFAULT 0,
    FOREIGN KEY (show_id) REFERENCES shows(id) ON DELETE CASCADE,
    UNIQUE KEY unique_season (show_id, season_number),
    INDEX idx_show_season (show_id, season_number)
);

-- Tabela odcinków
CREATE TABLE episodes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    season_id INT NOT NULL,
    episode_number INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    FOREIGN KEY (season_id) REFERENCES seasons(id) ON DELETE CASCADE,
    UNIQUE KEY unique_episode (season_id, episode_number),
    INDEX idx_season_episode (season_id, episode_number)
);

-- Tabela seriali użytkowników
CREATE TABLE user_shows (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    show_id INT NOT NULL,
    status ENUM('watching', 'completed', 'plan_to_watch', 'dropped') DEFAULT 'plan_to_watch',
    rating DECIMAL(2,1) DEFAULT NULL CHECK (rating >= 1.0 AND rating <= 10.0),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (show_id) REFERENCES shows(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_show (user_id, show_id),
    INDEX idx_user_status (user_id, status),
    INDEX idx_show_rating (show_id, rating)
);

-- Tabela obejrzanych odcinków
CREATE TABLE user_episodes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    episode_id INT NOT NULL,
    watched BOOLEAN DEFAULT FALSE,
    watched_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (episode_id) REFERENCES episodes(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_episode (user_id, episode_id),
    INDEX idx_user_watched (user_id, watched),
    INDEX idx_episode_watched (episode_id, watched)
);

-- Tabela recenzji
CREATE TABLE reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    show_id INT NOT NULL,
    rating DECIMAL(2,1) NOT NULL CHECK (rating >= 1.0 AND rating <= 10.0),
    content TEXT NOT NULL,
    spoiler_warning BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (show_id) REFERENCES shows(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_review (user_id, show_id),
    INDEX idx_show_rating (show_id, rating),
    INDEX idx_created_at (created_at)
);

-- Wstawienie przykładowych danych testowych

-- Użytkownicy testowi (hasła: admin123 i user123)
INSERT INTO users (username, email, password_hash, role) VALUES
('admin', 'admin@tvtime.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
('user', 'user@tvtime.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user');

-- Przykładowe seriale
INSERT INTO shows (title, description, genre, year, poster_url) VALUES
('Breaking Bad', 'Nauczyciel chemii z problemami finansowymi postanawia produkować metamfetaminę, aby zabezpieczyć finansowo swoją rodzinę przed śmiercią.', 'Drama', 2008, 'breaking-bad.jpg'),
('Game of Thrones', 'Epiczna fantasy saga o walce o Żelazny Tron w fikcyjnym świecie Westeros.', 'Fantasy', 2011, 'game-of-thrones.jpg'),
('The Office', 'Komedia mockumentalna pokazująca codzienne życie pracowników biurowych w Scranton, Pennsylvania.', 'Comedy', 2005, 'the-office.jpg');

-- Sezony dla Breaking Bad
INSERT INTO seasons (show_id, season_number, episode_count) VALUES
(1, 1, 7),
(1, 2, 13),
(1, 3, 13),
(1, 4, 13),
(1, 5, 16);

-- Sezony dla Game of Thrones
INSERT INTO seasons (show_id, season_number, episode_count) VALUES
(2, 1, 10),
(2, 2, 10),
(2, 3, 10);

-- Sezony dla The Office
INSERT INTO seasons (show_id, season_number, episode_count) VALUES
(3, 1, 6),
(3, 2, 22),
(3, 3, 25);

-- Przykładowe odcinki Breaking Bad Sezon 1
INSERT INTO episodes (season_id, episode_number, title, description) VALUES
(1, 1, 'Pilot', 'Walter White zostaje zdiagnozowany z rakiem płuc i postanawia zacząć gotować metamfetaminę.'),
(1, 2, 'Cat\'s in the Bag...', 'Walter i Jesse muszą poradzić sobie z konsekwencjami swojego pierwszego gotowania.'),
(1, 3, 'And the Bag\'s in the River', 'Walter musi podjąć trudną decyzję dotyczącą Krazy-8.'),
(1, 4, 'Cancer Man', 'Walter mówi rodzinie o swojej diagnozie.'),
(1, 5, 'Gray Matter', 'Walter rozważa przyjęcie pomocy od swoich dawnych partnerów biznesowych.'),
(1, 6, 'Crazy Handful of Nothin\'', 'Walter postanawia wziąć sprawy w swoje ręce.'),
(1, 7, 'A No-Rough-Stuff-Type Deal', 'Walter i Jesse próbują znaleźć nowego dystrybutora.');

-- Przykładowe odcinki Game of Thrones Sezon 1
INSERT INTO episodes (season_id, episode_number, title, description) VALUES
(4, 1, 'Winter Is Coming', 'Ned Stark zostaje wezwany do stolicy, aby służyć królowi.'),
(4, 2, 'The Kingsroad', 'Ned wyrusza do Królewskiej Przystani wraz z królem.'),
(4, 3, 'Lord Snow', 'Jon Snow przybywa na Mur i rozpoczyna szkolenie.'),
(4, 4, 'Cripples, Bastards, and Broken Things', 'Ned bada śmierć poprzedniego Lorda Ręki.'),
(4, 5, 'The Wolf and the Lion', 'Ned konfrontuje się z Cersei Lannister.');

-- Przykładowe odcinki The Office Sezon 1
INSERT INTO episodes (season_id, episode_number, title, description) VALUES
(7, 1, 'Pilot', 'Poznajemy pracowników biura Dunder Mifflin w Scranton.'),
(7, 2, 'Diversity Day', 'Michael organizuje szkolenie z różnorodności kulturowej.'),
(7, 3, 'Health Care', 'Dwight zostaje odpowiedzialny za wybór planu opieki zdrowotnej.'),
(7, 4, 'The Alliance', 'Jim i Dwight tworzą sojusz w biurze.'),
(7, 5, 'Basketball', 'Pracownicy biura grają w koszykówkę przeciwko magazynowi.');

-- Przykładowe seriale użytkowników
INSERT INTO user_shows (user_id, show_id, status, rating) VALUES
(1, 1, 'completed', 9.5),
(1, 2, 'watching', 8.7),
(1, 3, 'plan_to_watch', NULL),
(2, 1, 'watching', 9.0),
(2, 3, 'completed', 8.2);

-- Przykładowe obejrzane odcinki
INSERT INTO user_episodes (user_id, episode_id, watched, watched_at) VALUES
(1, 1, TRUE, '2025-01-15 20:30:00'),
(1, 2, TRUE, '2025-01-16 21:00:00'),
(1, 3, TRUE, '2025-01-17 20:45:00'),
(2, 1, TRUE, '2025-02-01 19:30:00'),
(2, 2, TRUE, '2025-02-02 20:15:00');

-- Przykładowe recenzje
INSERT INTO reviews (user_id, show_id, rating, content, spoiler_warning) VALUES
(1, 1, 9.5, 'Absolutnie fenomenalny serial! Brayn Cranston w roli Waltera White to arcydzieło aktorstwa. Każdy odcinek trzyma w napięciu.', FALSE),
(2, 3, 8.2, 'Świetna komedia biurowa. Michael Scott to jeden z najlepszych komediowych bohaterów telewizji. Polecam każdemu!', FALSE),
(1, 2, 8.7, 'Epicka fantasy z niesamowitymi postaciami i fabułą. Ostatnie sezony trochę rozczarowały, ale początek był genialny!', TRUE);
