# URL Shortener

A simple URL shortener built with PHP ,SQLite,Tailwind and vanila JS. Paste a long URL, get a short one back. Optionally set an expiry date — after which the link returns a 404.

---

## Requirements

- PHP 8.2 or later with the `pdo_sqlite` extension

---

## Running locally

- Clone the repository
```bash
    git clone https://github.com/alwinsonny/url-shortener.git
    cd url-shortener
```
- Start the server
```bash
    php -S localhost:8000 -t public public/index.php
```
- Then 
    Open `http://localhost:8000` in your browser. The database is created automatically on first run.

- If you get a database error on first run, set write permission on the storage directory:

```bash
    chmod 755 storage
```