<?php
/**
 * Instalator bazy danych TV Time Clone
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html><html><head><title>Instalator TV Time Clone</title></head><body>";
echo "<h1>Instalator bazy danych TV Time Clone</h1>";

try {
    // Najpierw spróbuj połączyć się bez konkretnej bazy danych
    $dsn = "mysql:host=localhost;charset=utf8mb4";
    $pdo = new PDO($dsn, 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    
    echo "<p>✓ Połączenie z serwerem MySQL nawiązane</p>";
    
    // Sprawdź czy baza danych istnieje
    $db_check = $pdo->query("SHOW DATABASES LIKE 'tvtime_clone'");
    if ($db_check->rowCount() == 0) {
        echo "<p>⚠️ Baza danych 'tvtime_clone' nie istnieje. Tworzę...</p>";
        $pdo->exec("CREATE DATABASE tvtime_clone CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        echo "<p>✓ Baza danych 'tvtime_clone' została utworzona</p>";
    } else {
        echo "<p>✓ Baza danych 'tvtime_clone' istnieje</p>";
    }
    
    // Przełącz na naszą bazę danych
    $pdo->exec("USE tvtime_clone");
    
    // Sprawdź czy tabele istnieją
    $tables_check = $pdo->query("SHOW TABLES");
    $existing_tables = $tables_check->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<p>📋 Istniejące tabele: " . (empty($existing_tables) ? "BRAK" : implode(', ', $existing_tables)) . "</p>";
    
    $required_tables = ['users', 'shows', 'seasons', 'episodes', 'user_shows', 'reviews'];
    $missing_tables = array_diff($required_tables, $existing_tables);
    
    if (!empty($missing_tables)) {
        echo "<p>⚠️ Brakujące tabele: " . implode(', ', $missing_tables) . "</p>";
        echo "<p>🔧 Tworzę strukturę bazy danych...</p>";
        
        // Wczytaj i wykonaj skrypt SQL
        $sql_file = 'database-structure.sql';
        if (file_exists($sql_file)) {
            $sql_content = file_get_contents($sql_file);
            
            // Podziel na pojedyncze zapytania
            $queries = explode(';', $sql_content);
            $successful_queries = 0;
            
            foreach ($queries as $query) {
                $query = trim($query);
                if (!empty($query) && !preg_match('/^--/', $query) && !preg_match('/^CREATE DATABASE/', $query) && !preg_match('/^USE /', $query)) {
                    try {
                        $pdo->exec($query);
                        $successful_queries++;
                    } catch (PDOException $e) {
                        // Ignoruj błędy dla duplikatów danych
                        if (strpos($e->getMessage(), 'Duplicate entry') === false) {
                            echo "<p>⚠️ Błąd w zapytaniu: " . htmlspecialchars($e->getMessage()) . "</p>";
                            echo "<p>Zapytanie: " . htmlspecialchars(substr($query, 0, 100)) . "...</p>";
                        }
                    }
                }
            }
            
            echo "<p>✓ Wykonano $successful_queries zapytań z pliku SQL</p>";
        } else {
            echo "<p>❌ Nie znaleziono pliku database-structure.sql</p>";
        }
    }
    
    // Sprawdź dane w tabeli shows
    $shows_count = $pdo->query("SELECT COUNT(*) FROM shows")->fetchColumn();
    echo "<p>📺 Liczba seriali w bazie: $shows_count</p>";
    
    if ($shows_count == 0) {
        echo "<p>⚠️ Brak seriali w bazie danych. Dodaję przykładowe dane...</p>";
        
        // Dodaj przykładowe seriale
        $insert_shows = "
            INSERT INTO shows (title, description, genre, year, poster_url) VALUES
            ('Breaking Bad', 'Nauczyciel chemii z problemami finansowymi postanawia produkować metamfetaminę, aby zabezpieczyć finansowo swoją rodzinę przed śmiercią.', 'Drama', 2008, 'breaking-bad.jpg'),
            ('Game of Thrones', 'Epiczna fantasy saga o walce o Żelazny Tron w fikcyjnym świecie Westeros.', 'Fantasy', 2011, 'game-of-thrones.jpg'),
            ('The Office', 'Komedia mockumentalna pokazująca codzienne życie pracowników biurowych w Scranton, Pennsylvania.', 'Comedy', 2005, 'the-office.jpg'),
            ('Stranger Things', 'Grupa dzieci w małym miasteczku odkrywa nadprzyrodzone tajemnice.', 'Sci-Fi', 2016, 'stranger-things.jpg'),
            ('The Crown', 'Historia brytyjskiej rodziny królewskiej w XX wieku.', 'Drama', 2016, 'the-crown.jpg'),
            ('Friends', 'Grupa przyjaciół mieszkających w Nowym Jorku.', 'Comedy', 1994, 'friends.jpg')
        ";
        
        try {
            $pdo->exec($insert_shows);
            echo "<p>✓ Dodano przykładowe seriale</p>";
            
            $shows_count = $pdo->query("SELECT COUNT(*) FROM shows")->fetchColumn();
            echo "<p>📺 Nowa liczba seriali w bazie: $shows_count</p>";
        } catch (PDOException $e) {
            echo "<p>❌ Błąd podczas dodawania seriali: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    }
    
    // Sprawdź czy są użytkownicy
    $users_count = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    echo "<p>👥 Liczba użytkowników w bazie: $users_count</p>";
    
    if ($users_count == 0) {
        echo "<p>⚠️ Brak użytkowników. Tworzę konto testowe...</p>";
        $insert_user = "INSERT INTO users (username, email, password_hash) VALUES ('test', 'test@test.com', MD5('test123'))";
        try {
            $pdo->exec($insert_user);
            echo "<p>✓ Utworzono konto testowe (login: test, hasło: test123)</p>";
        } catch (PDOException $e) {
            echo "<p>❌ Błąd podczas tworzenia użytkownika: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    }
    
    echo "<h2>✅ Instalacja zakończona pomyślnie!</h2>";
    echo "<p><a href='index.php'>Przejdź do strony głównej</a> | <a href='debug_shows.php'>Uruchom debug</a></p>";
    
} catch (Exception $e) {
    echo "<h2>❌ Błąd instalacji</h2>";
    echo "<p><strong>Komunikat błędu:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p><strong>Plik:</strong> " . $e->getFile() . "</p>";
    echo "<p><strong>Linia:</strong> " . $e->getLine() . "</p>";
}

echo "</body></html>";
?>
