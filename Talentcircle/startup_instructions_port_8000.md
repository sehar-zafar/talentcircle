# Serve the whole app (frontend + API) on `http://localhost:8000`

Your `signin.html` is currently being loaded from a static server on port **5500**.
To make the page load from **8000**, start a server from the same backend folder and route all non-`/api/*` requests to the frontend.

## Option A (recommended): PHP built-in server from `backend/`
1. Stop the Live Server (5500).
2. Run this in a terminal:

```bat
cd c:/xampp/htdocs/talentcircle/Talentcircle
php -S localhost:8000 -t backend
```

3. Visit:
- `http://localhost:8000/signin.html`

> Note: For this to work, you must have a router/script that serves frontend files for non-API routes.

## Option B: Keep PHP server, but create a simple router
Because `backend/public/index.php` only handles `/api/*`, you need a router that serves `../` static html when the request is not `/api/*`.

Create a `c:/xampp/htdocs/talentcircle/Talentcircle/router.php` with the logic to:
- if URL starts with `/api/` → include `backend/routes/api.php`
- else → serve the file from the project root (`./signin.html`, `./home.html`, etc.)

Then run:
```bat
cd c:/xampp/htdocs/talentcircle/Talentcircle
php -S localhost:8000 router.php
```

## Option C: Configure a webserver (Apache/Nginx) to map:
- document root → `Talentcircle/`
- `/api/*` → `Talentcircle/backend/routes/api.php`

(Only do this if you want a production-like setup.)

---

## Quick check
Open DevTools → Network → click a failing request like `/api/login`.
- If it shows port **8000**, the API origin is correct.
- If it shows port **5500**, you have `tc_api_origin` overridden in localStorage.

