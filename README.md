## ZealPHP Project Template

To get started, visit https://github.com/sibidharan/zealphp

## Project layout

| Path | Purpose |
|---|---|
| `public/` | **Document root** — the folder requests resolve against (`public/about.php` → `/about`), plus static assets. This is the **default**; point it elsewhere with `App::documentRoot('…')` in `app.php`, before `App::init()`. |
| `route/` | Explicit route files, auto-loaded at startup |
| `api/` | File-based REST API (`api/users/get.php` → `GET /api/users`) |
| `template/` | Views rendered via `App::render()` |
| `src/` | Your classes (PSR-4, `App\` namespace) |

## Documentation

Full guides, API reference, and the interactive tutorial: https://php.zeal.ninja