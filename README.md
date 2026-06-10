# URL Shortener

A simple URL shortener built with PHP ,SQLite,Tailwind and vanila JS. Paste a long URL, get a short one back. Optionally set an expiry date — after which the link returns a 404.

---

## Requirements

- PHP 8.2 or later with the `pdo_sqlite` extension

---

## Running locally

```bash
php -S localhost:8000 -t public public/index.php
```

Open `http://localhost:8000` in your browser. The database is created automatically on first run.

If you get a database error on first run, set write permission on the storage directory:

```bash
chmod 755 storage
```

## Running in a sub-directory

The spec example uses `http://localhost/urls` — to match that:

```bash
APP_BASE_URL=http://localhost:8000 APP_BASE_PATH=/urls php -S localhost:8000 -t public public/index.php
```