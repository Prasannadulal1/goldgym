# Gold Gym Hetauda — static frontend + PHP/MySQL JSON API

This is a fully decoupled build: the frontend is plain HTML/CSS/JS with
**zero PHP mixed into it** — every page fetches its content from a PHP
API that returns JSON only, never HTML. Everything in this folder is
meant to be uploaded as-is to your document root (e.g. `public_html/`)
— no restructuring needed at deploy time.

## Layout (this IS the deployment layout)

```
/                       <- your domain root
  index.html            homepage shell, filled in by js/main.js
  contact.html          contact form, posts to /api/public/contact.php
  css/style.css         all styling (black + gold theme)
  js/api.js             shared fetch() helpers
  js/main.js            fetches /api/public/home.php and renders every section
  js/contact.js
  assets/               logo, fallback images
  uploads/              admin-uploaded images land here (served as static files)
  admin/                the admin panel — also static HTML/CSS/JS
    login.html, index.html (dashboard), trainers.html
    js/session.js       shared session check + sidebar (every admin page uses this)
    js/login.js, dashboard.js, trainers.js
  api/                  PHP — returns JSON ONLY, never HTML
    public/             no login required: home.php, contact.php
    admin/              require_admin() on every request: login.php, logout.php,
                         me.php, dashboard.php, trainers.php
  includes/             PHP helpers (db.php, auth.php, csrf.php, upload.php,
                         response.php, helpers.php) — blocked from direct web
                         access by includes/.htaccess, only ever require_once'd
  sql/schema.sql
  create_admin.php      run once via CLI to create your first login
```

## How the pieces talk to each other

- **Public pages** (`index.html`, `contact.html`) call `/api/public/*` —
  no auth, read-only content plus the contact form.
- **Admin pages** (`admin/*.html`) call `/api/admin/*`. Every admin page
  first calls `requireAdminSession()` (in `admin/js/session.js`), which
  hits `/api/admin/me.php`. If the session isn't valid it redirects to
  `login.html`; if it is, it hands back a CSRF token that the page then
  attaches as an `X-CSRF-Token` header on every POST/DELETE it makes.
- **Why a header instead of a hidden form field:** there's no
  server-rendered `<form>` for PHP to embed a token into anymore — the
  frontend is static HTML calling the API with `fetch()`. A custom
  header serves the same anti-CSRF purpose here, since a cross-site
  request can't attach one without triggering a CORS preflight, and the
  API doesn't grant CORS access to any other origin.
- **Image uploads**: `admin/js/trainers.js` builds a `FormData` (so the
  file goes over the wire as multipart) and posts it to
  `/api/admin/trainers.php`. The PHP side re-validates and re-encodes
  the image (see `includes/upload.php`) and writes it into `/uploads/`,
  which the web server then serves directly — the API is only ever
  involved in writing images, never in serving them back.

## Extending to the next entity (e.g. Services)

Every entity follows the exact same four-file pattern as Trainers:

1. **`api/admin/services.php`** — copy `api/admin/trainers.php`, swap
   the table name and field list in the GET/POST/DELETE blocks.
2. **`admin/services.html`** — copy `admin/trainers.html`, swap the
   form's `<label>` fields.
3. **`admin/js/services.js`** — copy `admin/js/trainers.js`, swap the
   field IDs read into `FormData` and the table columns rendered.
4. Add `['services.html', '💪', 'Services']` to the `ADMIN_NAV` array in
   `admin/js/session.js`.
5. **`api/public/home.php`** already selects from `services` — if a new
   entity needs to appear on the homepage, add its query there instead
   of creating a second public endpoint.

News is the one entity that needs different handling: its `content`
field is rich text. Sanitize it server-side (e.g. HTML Purifier) before
saving — never trust raw HTML from a `contenteditable`/WYSIWYG straight
into the database.

## Setup on cPanel

1. Create the MySQL database in cPanel, import `sql/schema.sql` via phpMyAdmin.
2. Edit the four constants at the top of `includes/db.php`.
3. Upload everything in this folder as your document root.
4. Confirm `includes/.htaccess` made it up (some FTP clients hide
   dotfiles by default) — this is what stops anyone from requesting
   `includes/db.php` directly in a browser.
5. Run `php create_admin.php youruser you@email.com "your password"`
   once via SSH (or a throwaway PHP file you delete right after) to
   create your first login, then delete `create_admin.php` from the server.
6. Log in at `/admin/login.html`.

## Security notes worth remembering later

- Every admin API endpoint calls `require_admin()` — never build a new
  one that skips it.
- Every state-changing admin request (`POST`/`DELETE`) must call
  `require_valid_csrf()`.
- Never add a second image-upload code path — extend `includes/upload.php`.
- `uploads/.htaccess` denies PHP execution as a second layer of defense
  even if upload validation is ever bypassed.
- `includes/.htaccess` denies all direct requests — that folder holds
  your DB password.
