# TV Time Clone – Dokumentacja Projektu

**Uniwersytet w Siedlcach**  
Wydział Nauk Ścisłych i Przyrodniczych  
Kierunek Informatyka  

**Przedmiot:** Podstawy Technologii WWW  

**Temat Projektu:**  
TV Time Clone – Aplikacja do zarządzania listą seriali i filmów  

**Opracował:** Mateusz Konachowicz  
I rok informatyki, studia stacjonarne inżynierskie  

**Prowadzący:**  
Mgr. inż. Maciej Nazarczuk  

Siedlce, rok akademicki 2024/2025, semestr letni

---

## Spis treści

1. [Cel projektu](#cel-projektu)  
2. [Link do GitHuba](#link-do-githuba)  
3. [Spis funkcjonalności](#spis-funkcjonalności)  
4. [Prezentacja działania](#prezentacja-działania)  
5. [Diagram tabel](#diagram-tabel)  
6. [Technologie użyte](#technologie-użyte)  
7. [Struktura plików projektu](#struktura-plików-projektu)  
8. [Kluczowe cechy techniczne](#kluczowe-cechy-techniczne)  

---

## Cel projektu

Celem projektu było stworzenie nowoczesnej aplikacji webowej pozwalającej na zarządzanie osobistą listą seriali i filmów, inspirowanej popularną platformą **TV Time**. Użytkownicy mogą śledzić swoje postępy w oglądaniu, dodawać filmy do listy życzeń, oceniać oraz pisać recenzje. System powstał z myślą o bezpieczeństwie danych, intuicyjnej obsłudze i możliwości dalszej rozbudowy.

Projekt wykonano **samodzielnie** – od projektowania bazy danych, przez implementację backendu w PHP, po stworzenie responsywnego interfejsu użytkownika. Zastosowano nowoczesne rozwiązania UI/UX, bezpieczne zarządzanie danymi oraz zaawansowane funkcjonalności administracyjne.

---

## Link do GitHuba

Projekt dostępny jest pod adresem:  
[https://github.com/Konach2/Projekt-WWW)

---

## Spis funkcjonalności

### Funkcje użytkownika standardowego

- Rejestracja i logowanie (hashowanie haseł **bcrypt**)
- Przeglądanie bazy seriali i filmów z filtrowaniem według gatunków
- Dodawanie pozycji do własnej listy z różnymi statusami (oglądane, chcę obejrzeć, ukończone, porzucone, wstrzymane)
- Ocenianie w skali 1–10
- Pisanie recenzji z opcją oznaczania spoilerów
- Podgląd statystyk i profilu użytkownika
- System powiadomień o aktualizacjach
- Zaawansowana wyszukiwarka treści
- Szczegółowe karty serialu i filmu wraz z sezonami i odcinkami

### Uprawnienia administratora

- Dodawanie, edycja i usuwanie seriali oraz filmów
- Zarządzanie kontami użytkowników i rolami
- Moderacja recenzji i treści użytkowników
- Panel administracyjny z rozbudowanymi statystykami

---

## Prezentacja działania

W tej sekcji należy umieścić zrzuty ekranu prezentujące kluczowe funkcjonalności aplikacji.  
Poniżej znajduje się lista widoków do udokumentowania (obrazy umieść w osobnym pliku):

1. Strona główna – eksploracja treści (`explore.php`).
2. Formularz logowania i rejestracji (`login.php`, `register.php`).
3. Modal dodawania do listy.
4. Panel „Moje Filmy” ze statystykami.
5. Karta serialu z systemem recenzji.
6. Formularz dodawania recenzji.
7. Panel administracyjny oraz lista i formularz dodawania serialu.

---

## Diagram tabel

Wstaw wygenerowany diagram ER (np. z phpMyAdmin).

**Główne tabele:**
- `users` – dane użytkowników (hasła bcrypt)
- `shows`, `movies` – informacje o tytułach
- `user_shows`, `user_movies` – relacje użytkownik–tytuł (status, ocena)
- `reviews` – recenzje
- `seasons`, `episodes`, `user_episodes`

Relacje zabezpieczone kluczami obcymi, indeksy oraz ograniczenia `CHECK` (ocena 1–10).

---

## Technologie użyte

- **Backend:** PHP 8 (PDO)
- **Baza danych:** MySQL 8 (InnoDB)
- **Frontend:** HTML5, CSS3, JavaScript (ES6)
- **Style:** własne CSS (Flexbox, Grid, animacje)
- **Bezpieczeństwo:** bcrypt, CSRF tokens, prepared statements
- **Responsywność:** podejście mobile-first, media queries

---

## Struktura plików projektu

