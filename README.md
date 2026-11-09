## ZealPHP Project Template

To get started, visit https://github.com/sibidharan/zealphp

## Project layout

| Path | Purpose |
|---|---|
| `public/` | **Document root** — the folder requests resolve against (`public/about.php` → `/about`), plus static assets. This is the **default**; point it elsewhere with `App::documentRoot('…')` in `app.php`, before `App::init()`. |
| `route/` | Explicit route files, auto-loaded at startup |
| `api/` | File-based REST API (`api/users/get.php` → `GET /api/users`) |
| `template/` | Views. Each page has a folder named after its public file (`template/playground/` for `public/playground.php`) with its components in `components/`; components several pages share go in `template/components/` |
| `src/` | Your classes (PSR-4, `App\` namespace) |

Routes and APIs are wiring: they call a `src/` class and return data or a component (`App::component()`), never HTML they build. Pages are built from components. The rules: [Where code goes](https://php.zeal.ninja/docs/guide/directory-structure#where-code-goes).

## Documentation

Full guides, API reference, and the interactive tutorial: https://php.zeal.ninja