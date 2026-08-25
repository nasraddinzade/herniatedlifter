# Herniated Lifter — early-access capture

A self-contained landing page + sign-up collector with its own statistics.
No Composer, no external services. The **client is a Next.js app** (statically
exported to plain HTML/CSS/JS); the **backend is plain PHP 8 + SQLite (via
PDO)**. Everything runs on Hostinger shared hosting after the files land in
`public_html` — the static export is served directly, PHP handles the API and
admin.

## What's in here

| File / dir            | Purpose                                                          |
|-----------------------|------------------------------------------------------------------|
| `index.html`, `_next/`| The landing page — **built** static export of the Next.js client.|
| `client/`             | Next.js **source** (App Router + TypeScript). Not web-served.    |
| `api/subscribe.php`   | POST endpoint that stores sign-ups (JSON in, JSON out).          |
| `api/visit.php`       | Visit-logging beacon called by the client on load.               |
| `admin.php`           | Password-protected stats dashboard + CSV export.                 |
| `db.php`              | Shared SQLite layer (schema is auto-created on first request).   |
| `config.php.example`  | Template for `config.php` (admin password hash + salt).          |
| `data/`               | SQLite database and logs. Blocked from the web by `.htaccess`.   |
| `.htaccess`           | Serves `index.html` first and hardens access to secrets/DB.      |

The database file `data/app.sqlite` and the tables are created automatically
the first time any page is opened — you don't run any migration.

> **Emails / sign-ups** are stored in SQLite (`data/app.sqlite`, table
> `signups`) on the server. Nothing is emailed out; you read them in `admin.php`
> and via its **Export CSV** button.

## The Next.js client

Source lives in `client/`; the **built** static files (`index.html`, `_next/`,
`404.html`) are copied to the repo root and committed, so Hostinger's Git
auto-deploy publishes them to `public_html` with no build step on the server.

```bash
cd client
npm install
npm run dev      # local dev at http://localhost:3000

npm run deploy   # production build + copy the export to the repo root
```

After `npm run deploy`, commit the changed root files (`index.html`, `_next/…`)
and push — auto-deploy does the rest. The form posts to `/api/subscribe.php`
and the visit beacon to `/api/visit.php` on the same origin, so no CORS or
config is involved.

---

## Deploy to Hostinger (shared hosting)

> **Git auto-deploy.** This repo is set up to deploy from Git, and `config.php`
> (admin hash + salt) is committed, so a push deploys a fully working admin with
> no extra step. To change the admin password, run the command in step 2 and
> commit the new `config.php`. Keep the repository **private** — the hash lives
> in it. Steps 1–2 below are only needed for a plain FTP upload instead.

### 1. Upload the files

Using the **File Manager** or **FTP**, upload the whole project into
`public_html` so you get:

```
public_html/
├── index.html          # built Next.js export (landing)
├── 404.html
├── _next/              # built JS/CSS/fonts
├── admin.php
├── db.php
├── config.php
├── config.php.example
├── .htaccess
├── api/
│   ├── subscribe.php
│   └── visit.php
├── client/             # Next.js source (blocked from the web)
└── data/
    └── .htaccess
```

> If the site lives in a subfolder instead of the domain root, the form still
> works because it posts to the root-relative path `/api/subscribe.php`. Adjust
> that path in `index.php` if you deploy under a sub-path.

### 2. Create `config.php` and set the admin password

Copy the template and generate your secrets. In Hostinger you can run PHP from
the **SSH terminal** (Hosting → Advanced → SSH), or paste the commands into a
one-off PHP file and delete it afterwards.

```bash
cp config.php.example config.php

# Admin password hash — replace YOUR_PASSWORD with the password you'll type
# into admin.php. Copy the whole $2y$... string into ADMIN_PASSWORD_HASH.
php -r "echo password_hash('YOUR_PASSWORD', PASSWORD_DEFAULT), PHP_EOL;"

# Random salt for hashing visitor IP+UA — copy the output into APP_SALT.
php -r "echo bin2hex(random_bytes(16)), PHP_EOL;"
```

Edit `config.php` and paste both values in. The plain password is never stored
— only its hash lives in `config.php`, and `admin.php` checks it with
`password_verify()`.

> No SSH? Create a temporary `gen.php` with
> `<?php echo password_hash('YOUR_PASSWORD', PASSWORD_DEFAULT);`, open it once in
> the browser, copy the hash into `config.php`, then **delete `gen.php`**.

### 3. Make `data/` writable

PHP must be able to create `data/app.sqlite`. On most Hostinger setups the
default permissions already allow this. If you see a database error, set the
directory to `755` (or `775`) in the File Manager permissions dialog, e.g.:

```bash
chmod 755 data
```

The database, WAL/SHM sidecars and `app.log` appear inside `data/`
automatically after the first visit.

### 4. Verify the database is NOT reachable from the web

Open these URLs in a browser — **all must return HTTP 403 (Forbidden)**:

```
https://yourdomain.com/data/app.sqlite
https://yourdomain.com/data/app.log
https://yourdomain.com/config.php        (blank or 403 — never shows the hash)
```

If `app.sqlite` downloads instead of 403'ing, your host isn't applying the
`data/.htaccess`. In that case move the `data/` directory **above**
`public_html` and update `HL_DATA_DIR` / `HL_DB_FILE` / `HL_LOG_FILE` in
`db.php` to point there.

### 5. Add the source tag to campaign links (optional)

Append `?src=` to the URL you post on each platform so the dashboard can break
down visits and sign-ups by channel:

```
https://yourdomain.com/?src=tt        TikTok
https://yourdomain.com/?src=yt        YouTube
https://yourdomain.com/?src=ig        Instagram
https://yourdomain.com/?src=reddit    Reddit
```

The tag is remembered in the browser session, so the sign-up is attributed to
the same source the visitor arrived from. Anything else (or no tag) counts as
`direct`.

---

## Using the admin dashboard

Open `https://yourdomain.com/admin.php`, sign in with your password, and you'll
see:

- visits total / humans-only / ≈ unique, all-time and last 7 days;
- number of sign-ups;
- the headline **conversion %** (human visits → sign-ups);
- a per-source breakdown;
- the list of e-mails with dates and an **Export CSV** button.

Sign out with the link in the top-right.

---

## Local test (optional)

With PHP installed locally you can run the built-in server from the project
root:

```bash
php -S localhost:8000
```

Then:

- open `http://localhost:8000/?src=tt` and submit the form → you should see the
  "You're in" state;
- open `http://localhost:8000/admin.php` → the sign-up appears in the table;
- submit the same e-mail again → still shows success, but no duplicate row;
- a request with a filled `_gotcha` field returns `{"ok":true}` but is not
  stored.

## Notes

- All queries use prepared statements.
- The raw IP is never stored — only `sha256(ip + user-agent + APP_SALT)`.
- Errors are written to `data/app.log`, never shown to visitors.
- Rate limit: max 5 sign-up requests per minute per visitor hash (HTTP 429).

---

## License

Released under the [MIT License](LICENSE) — Copyright (c) 2026 Ramin Nasraddinzade.
