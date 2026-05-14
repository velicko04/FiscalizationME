# FiscalizationME

FiscalizationME je Laravel aplikacija za rad sa firmama, kupcima, ugovorima, fakturama i fiskalizacijom računa u Crnoj Gori.

## Funkcionalnosti

- Upravljanje firmama, kupcima, proizvodima i PDV stopama
- Kreiranje i pregled ugovora
- Automatsko generisanje faktura iz aktivnih ugovora
- Pregled faktura, stavki fakture i PDF izvoz
- XML generisanje i fiskalizacioni logovi
- Storno faktura
- AI chat asistent za:
  - kreiranje ugovora kroz prirodan jezik
  - kreiranje faktura za ugovor
  - pregled ugovora i faktura
  - preuzimanje PDF fakture
  - slanje fakture na email
  - prikaz nefiskalizovanih faktura

## Tehnologije

- PHP 8.2+
- Laravel 12
- MySQL / SQLite
- Vite
- DomPDF
- Ollama / Gemma za lokalni AI chat
- Apple Foundation Models eksperimentalno

## Pokretanje projekta

```bash
cd laravel
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm run build
php artisan serve
```

Za development:

```bash
composer run dev
```

## AI chat

Chat se nalazi na:

```text
/chat
```

Za Gemma/Ollama provider potrebno je da lokalni Ollama servis bude pokrenut i da model bude dostupan u `ChatLlmProvidersTrait`.

## Korisne komande

Generisanje faktura iz ugovora:

```bash
php artisan invoices:generate
```

Test matrica za kreiranje ugovora kroz chat:

```bash
php artisan chat:contract-matrix
```

## Napomena

Projekat je u aktivnom razvoju. AI asistent koristi preview/potvrdu prije upisa osjetljivih akcija kao što su kreiranje ugovora, kreiranje fakture i slanje fakture na email.
