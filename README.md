TV Time Clone – Dokumentacja Projektu
Uniwersytet w Siedlcach
Wydział Nauk Ścisłych i Przyrodniczych
Kierunek Informatyka

Przedmiot: Podstawy Technologii WWW

Temat Projektu:
TV Time Clone – Aplikacja do zarządzania listą seriali i filmów

Opracował: [Imię Nazwisko]
I rok informatyki, studia stacjonarne inżynierskie

Prowadzący:
Mgr. inż. Maciej Nazarczuk

Siedlce, rok akademicki 2024/2025, semestr letni

Spis treści
Cel projektu

Link do GitHuba

Spis funkcjonalności

Prezentacja działania

Diagram tabel

Technologie użyte

Struktura plików projektu

1. Cel projektu
Celem projektu było stworzenie nowoczesnej aplikacji webowej pozwalającej na zarządzanie osobistą listą seriali i filmów, inspirowanej popularną platformą TV Time. Użytkownik może śledzić swoje postępy w oglądaniu seriali, dodawać filmy do listy życzeń, oceniać oraz pisać recenzje. System powstał z myślą o bezpieczeństwie danych, intuicyjnej obsłudze i możliwości dalszej rozbudowy.

Projekt został wykonany samodzielnie – od projektowania bazy danych, przez implementację backendu w PHP, aż po stworzenie responsywnego interfejsu użytkownika. Zastosowano nowoczesne rozwiązania UI/UX, bezpieczne zarządzanie danymi oraz zaawansowane funkcjonalności administracyjne.

2. Link do GitHuba
Projekt dostępny jest pod następującym linkiem:
https://github.com/[username]/tvtime-clone

3. Spis funkcjonalności
Funkcje dostępne dla każdego użytkownika
Rejestracja i logowanie z bezpiecznym hashowaniem haseł (bcrypt).

Przeglądanie bazy seriali i filmów z możliwością filtrowania według gatunków.

Dodawanie tytułów do własnej listy z różnymi statusami: oglądane, chcę obejrzeć, ukończone, porzucone, wstrzymane.

Ocenianie seriali i filmów w skali 1 – 10.

Pisanie recenzji z opcją oznaczania spoilerów.

Podgląd profilu i statystyk oglądania (liczba obejrzanych odcinków, średnia ocena itp.).

System powiadomień o aktualizacjach.

Zaawansowana wyszukiwarka tytułów.

Szczegółowe karty serialu i filmu z obsługą sezonów oraz odcinków.

Dodatkowe uprawnienia administratora
Dodawanie nowych seriali i filmów do bazy danych.

Edycja i usuwanie istniejących pozycji (tytuł, opis, gatunek, rok, plakat).

Zarządzanie kontami użytkowników oraz ich rolami.

Moderacja recenzji i treści dodawanych przez użytkowników.

Panel z rozbudowanymi statystykami systemu (liczba użytkowników, aktywność itp.).

4. Prezentacja działania
WAŻNE ❗
Wstaw tutaj własne zrzuty ekranu w kolejności opisanej poniżej.

Strona główna – Eksploracja treści
Plik: explore.php
Strona główna prezentuje sekcje popularnych i najnowszych seriali oraz filmów, umożliwiając szybkie odkrywanie nowych tytułów.

System logowania i rejestracji
Pliki: login.php, register.php
Nowoczesne formularze z walidacją danych, kontrolą siły hasła i zabezpieczeniami przed duplikatami.

Modal dodawania do listy
Plik: components/add-to-list-modal.php
Intuicyjny modal umożliwiający wybór statusu i dodanie tytułu do kolekcji użytkownika.

Panel „Moje Filmy” ze statystykami
Plik: movies.php
Sekcja profilu prezentująca wizualne statystyki (liczba obejrzanych filmów, średnia ocena itp.).

Karta serialu z systemem recenzji
Plik: show-details.php
Szczegółowe informacje o serialu, lista sezonów i recenzji oraz opcje zmiany statusu.

Formularz dodawania recenzji
Plik: components/review-form.php
Interfejs z poleceniem oceny 1 – 10, polem tekstowym i przełącznikiem spoiler.

Panel administracyjny
Plik: admin/admin.php
Nawigacja do zarządzania treściami i użytkownikami.

5. Diagram tabel
Wstaw tutaj diagram ER wygenerowany np. w phpMyAdmin.

Główne tabele:

users – dane użytkowników z haszowanymi hasłami

shows – seriale

movies – filmy

user_shows – relacja użytkownik ⇄ serial (status, ocena)

user_movies – relacja użytkownik ⇄ film (status, ocena)

reviews – recenzje

seasons, episodes, user_episodes

Relacje i ograniczenia: klucze obce, indeksy wydajnościowe, CHECK (ocena 1-10).

6. Technologie użyte
Backend: PHP 8 + (PDO)

Baza danych: MySQL 8 (InnoDB)

Frontend: HTML5, CSS3, JavaScript (ES6 +)

Style: niestandardowe CSS (Flexbox, Grid, animacje)

Bezpieczeństwo: bcrypt, CSRF tokens, prepared statements

Responsywność: podejście mobile-first, media queries

7. Struktura plików projektu
text
├── index.php
├── explore.php
├── movies.php
├── shows.php
├── login.php
├── register.php
├── admin/
│   ├── admin.php
│   └── ... (pliki panelu)
├── ajax/
│   └── *.php (8 plików obsługujących AJAX)
├── assets/
│   ├── css/
│   ├── js/
│   └── images/
└── sql/
    ├── structure.sql
    ├── movies.sql
    └── extensions.sql
Kluczowe cechy techniczne
Nowoczesny modal zamiast alert().

Recenzje z ochroną spoilerów.

Statystyki użytkownika aktualizujące się w czasie rzeczywistym.

Panel administratora z pełnym CRUD.

Walidacja po stronie klienta i serwera.

Optymalizacja obrazów funkcją getImageUrl().

Elastyczny system ról użytkowników.
