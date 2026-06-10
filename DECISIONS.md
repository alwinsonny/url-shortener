# DECISIONS.md

## How the code is structured

I kept the structure flat and simple — six classes, each with one job. `Router` handles incoming requests and decides what to do with them. `UrlRepository` owns all the database queries. `UrlValidator` checks URLs before they get stored. `View` renders the HTML. `Request` wraps the superglobals so nothing else touches `$_SERVER` or `$_POST` directly. `Database` opens the connection and sets up the schema on first run.

The front controller (`public/index.php`) wires everything together. 

I wrote a minimal PSR-4 autoloader rather than pulling in Composer. For a project with no third-party PHP dependencies it felt unnecessary to add that overhead.

## Expiry handling

Expiry is stored as a Unix timestamp in the database. I briefly considered storing it as a datetime string but timestamps make the expiry check a simple integer comparison — no timezone parsing needed at runtime. The browser converts the user's local datetime to a UTC Unix timestamp before submitting, which means PHP never has to deal with timezone conversion at all.

Expired links return a 404 rather than a different status code. A 404 is the clearest signal that the resource isn't available, without leaking whether the link ever existed.

Redirects use 302 rather than 301. A permanent redirect would get cached by browsers — if a link expires, those cached redirects would keep working indefinitely. 302 forces a fresh lookup every time.

## What I'd do differently with more time

Testing is the biggest gap. I'd add unit tests for the validation and expiry logic, and an integration test that runs the full redirect flow against an in-memory SQLite database. The class structure makes this straightforward — everything is injected, so swapping in test doubles is easy.

I'd also add rate limiting on the creation endpoint. Even a simple per-IP counter in a separate SQLite table would prevent the short code space being flooded. CSRF protection on the form submission would be next.

A basic admin view to list and delete existing links would add real practical value — right now there's no way to manage links once they're created.

## What I'd do differently if the stack wasn't specified

SQLite is a good fit here — it's self-contained and needs no separate server process. Under real traffic I'd move to MySQL or PostgreSQL, mostly because SQLite serialises all writes. For the redirect path specifically, which is read-heavy, I'd put a Redis cache in front of the database — a short code lookup becomes an O(1) hash read rather than a disk-bound SQL query, with the TTL matching the link's expiry.

For the frontend, Tailwind via CDN works fine for a tool this size. In a production context I'd run the Tailwind CLI to generate a small compiled stylesheet rather than loading the full CDN bundle on every request.

## Security and reliability

All database queries use prepared statements with named parameters — no string interpolation in queries at all.

Short codes are generated with `random_int()` which draws from the OS CSPRNG. `rand()` or `uniqid()` would produce predictable output; with CSPRNG the 56 billion possible combinations can't be enumerated.

Every value rendered into HTML goes through `htmlspecialchars()` with `ENT_QUOTES | ENT_SUBSTITUTE`. The substitute flag handles malformed UTF-8 sequences rather than silently dropping them.

The URL validator blocks anything that isn't `http://` or `https://` — this prevents `javascript:` and `data:` URIs being stored and potentially used as phishing vectors.

The `.htaccess` blocks direct access to the database file and sets `X-Content-Type-Options`, `X-Frame-Options`, and `Referrer-Policy` headers on every response.

SQLite is configured with DELETE journal mode rather than WAL. DELETE journal mode keeps everything in a single file and behaves more predictably across environments.

The `storage/` directory is created with `0750` permissions, restricting write access to the web server user.

## References

- PHP PDO SQLite — https://www.php.net/manual/en/pdo.construct.php
- Tailwind CSS documentation — https://tailwindcss.com/docs
- MDN: Clipboard API — https://developer.mozilla.org/en-US/docs/Web/API/Clipboard/writeText
- Apache mod_rewrite — https://httpd.apache.org/docs/current/mod/mod_rewrite.html