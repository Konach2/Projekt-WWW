-- Movies Extension for TV Time Clone
-- Dodanie obsługi filmów jako osobnych jednostek

-- Tabela filmów
CREATE TABLE IF NOT EXISTS movies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    original_title VARCHAR(255),
    year INT NOT NULL,
    genre VARCHAR(100),
    director VARCHAR(255),
    cast TEXT,
    description TEXT,
    poster_url VARCHAR(500),
    backdrop_url VARCHAR(500),
    runtime INT DEFAULT 0,
    imdb_id VARCHAR(20),
    tmdb_id INT,
    budget BIGINT DEFAULT 0,
    revenue BIGINT DEFAULT 0,
    release_date DATE,
    country VARCHAR(50) DEFAULT 'USA',
    language VARCHAR(10) DEFAULT 'en',
    status ENUM('released', 'in_production', 'post_production', 'planned', 'cancelled') DEFAULT 'released',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_year (year),
    INDEX idx_genre (genre),
    INDEX idx_title (title),
    INDEX idx_release_date (release_date)
);

-- Tabela relacji użytkownik-film
CREATE TABLE IF NOT EXISTS user_movies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    movie_id INT NOT NULL,
    status ENUM('watched', 'want_to_watch', 'watchlist', 'dropped') DEFAULT 'want_to_watch',
    rating DECIMAL(3,1) DEFAULT NULL,
    watched_at TIMESTAMP NULL,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    notes TEXT,
    favorite BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (movie_id) REFERENCES movies(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_movie (user_id, movie_id),
    INDEX idx_user_status (user_id, status),
    INDEX idx_user_rating (user_id, rating),
    INDEX idx_movie_rating (movie_id, rating)
);

-- Tabela recenzji filmów
CREATE TABLE IF NOT EXISTS movie_reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    movie_id INT NOT NULL,
    rating DECIMAL(3,1) NOT NULL,
    content TEXT NOT NULL,
    spoiler_warning BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (movie_id) REFERENCES movies(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_movie_review (user_id, movie_id),
    INDEX idx_movie_rating (movie_id, rating),
    INDEX idx_created_at (created_at)
);

-- Rozszerzenie tabeli user_activity o filmy
ALTER TABLE user_activity 
MODIFY COLUMN activity_type ENUM('watched_episode', 'rated_show', 'added_show', 'completed_show', 'reviewed_show', 'watched_movie', 'rated_movie', 'added_movie', 'reviewed_movie') NOT NULL;

ALTER TABLE user_activity 
ADD COLUMN IF NOT EXISTS movie_id INT NULL AFTER episode_id,
ADD FOREIGN KEY (movie_id) REFERENCES movies(id) ON DELETE SET NULL;

-- Przykładowe filmy
INSERT IGNORE INTO movies (title, original_title, year, genre, director, description, runtime, release_date, country) VALUES
('Ojciec chrzestny', 'The Godfather', 1972, 'Dramat/Kryminał', 'Francis Ford Coppola', 'Historia starzejącego się patriarchy dynastii przestępczej, który przekazuje kontrolę nad swoim klanowym imperium swojemu niechętnemu synowi.', 175, '1972-03-24', 'USA'),
('Skazani na Shawshank', 'The Shawshank Redemption', 1994, 'Dramat', 'Frank Darabont', 'Dwóch uwięzionych mężczyzn nawiązuje więzi przez lata, znajdując pocieszenie i ostateczne odkupienie poprzez akty zwykłej przyzwoitości.', 142, '1994-09-23', 'USA'),
('Mroczny Rycerz', 'The Dark Knight', 2008, 'Akcja/Kryminał', 'Christopher Nolan', 'Gdy zagrożenie znane jako Joker sieje chaos wśród mieszkańców Gotham, Batman musi zaakceptować jeden z największych psychologicznych i fizycznych testów swojej zdolności do walki z niesprawiedliwością.', 152, '2008-07-18', 'USA'),
('Forrest Gump', 'Forrest Gump', 1994, 'Dramat/Romans', 'Robert Zemeckis', 'Prezydenci tego kraju, wojna w Wietnamie, skandal Watergate - Forrest Gump w jakiś sposób ma wpływ na każde z tych wydarzeń.', 142, '1994-07-06', 'USA'),
('Inception', 'Inception', 2010, 'Sci-Fi/Akcja', 'Christopher Nolan', 'Złodziej specjalizujący się w wyciąganiu sekretów z podświadomości podczas stanu snu, otrzymuje zadanie zaszczepienia idei w umyśle dyrektora generalnego.', 148, '2010-07-16', 'USA'),
('Matrix', 'The Matrix', 1999, 'Sci-Fi/Akcja', 'The Wachowskis', 'Haker komputerowy dowiaduje się od tajemniczych rebeliantów o prawdziwej naturze rzeczywistości i swojej roli w wojnie przeciwko jej kontrolerom.', 136, '1999-03-31', 'USA'),
('Władca Pierścieni: Powrót Króla', 'The Lord of the Rings: The Return of the King', 2003, 'Fantasy/Przygodowy', 'Peter Jackson', 'Gandalf i Aragorn prowadzą Świat Ludzi w ostatniej bitwie przeciwko armii Saurona, aby odwrócić jego uwagę od Froda i Sama, gdy zbliżają się do Góry Przeznaczenia ze swoim koszmarem.', 201, '2003-12-17', 'Nowa Zelandia'),
('Dobry, zły i brzydki', 'Il buono, il brutto, il cattivo', 1966, 'Western', 'Sergio Leone', 'Kanonier i porywacz łączą siły z zdezerterowatem, aby zlokalizować skrzynię skradzionego złota Konfederacji pochowaną na cmentarzu.', 178, '1966-12-23', 'Włochy'),
('Lista Schindlera', 'Schindler\'s List', 1993, 'Biograficzny/Dramat', 'Steven Spielberg', 'W okupowanej przez Niemców Polsce przemysłowiec Oskar Schindler stopniowo przejmuje się troską o swoich żydowskich pracowników po tym, jak był świadkiem ich prześladowania przez nazistów.', 195, '1993-12-15', 'USA'),
('Wyzwanie', 'Goodfellas', 1990, 'Biograficzny/Kryminał', 'Martin Scorsese', 'Historia Henryego Hilla i jego życia w mafii, obejmująca jego relacje z żoną Karen Hill i jego partnerami mafijnymi Jimmy Conway i Tommy DeVito.', 146, '1990-09-19', 'USA');

-- Przykładowe user_movies (dla użytkowników testowych)
INSERT IGNORE INTO user_movies (user_id, movie_id, status, rating, watched_at) VALUES
(1, 1, 'watched', 9.5, '2024-01-15 20:00:00'),
(1, 2, 'watched', 9.8, '2024-01-20 19:30:00'),
(1, 3, 'watched', 9.2, '2024-02-01 21:00:00'),
(1, 4, 'want_to_watch', NULL, NULL),
(1, 5, 'watchlist', NULL, NULL),
(2, 1, 'watched', 9.0, '2024-01-10 20:30:00'),
(2, 6, 'watched', 8.5, '2024-01-25 20:15:00'),
(2, 7, 'want_to_watch', NULL, NULL);

-- Przykładowe recenzje filmów
INSERT IGNORE INTO movie_reviews (user_id, movie_id, rating, content, spoiler_warning) VALUES
(1, 1, 9.5, 'Arcydzieło kina. Niezrównana gra aktorska, perfekcyjna reżyseria i historia, która pozostaje aktualna przez dziesięciolecia. Obowiązkowa pozycja dla każdego miłośnika filmu.', FALSE),
(1, 2, 9.8, 'Najbardziej wzruszający film jaki widziałem. Historia nadziei, przyjaźni i odkupienia opowiedziana w sposób, który pozostaje z widzem na długo po napisach końcowych.', FALSE),
(2, 1, 9.0, 'Klasyk, który ustanowił standardy dla filmów gangsterskich. Brando w roli życia, a każda scena to kinematograficzne mistrzostwo.', FALSE);

-- Aktualizuj aktywność użytkowników
INSERT IGNORE INTO user_activity (user_id, activity_type, movie_id, data) VALUES
(1, 'watched_movie', 1, '{"rating": 9.5, "status": "watched"}'),
(1, 'rated_movie', 1, '{"rating": 9.5, "review": true}'),
(1, 'watched_movie', 2, '{"rating": 9.8, "status": "watched"}'),
(2, 'watched_movie', 1, '{"rating": 9.0, "status": "watched"}'),
(2, 'added_movie', 7, '{"status": "want_to_watch"}');
