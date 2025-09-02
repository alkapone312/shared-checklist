# Shared Checklist

## Quick start
1. Copy the contents to a PHP-enabled server (PHP 8+, SQLite enabled).
2. Ensure the server can write to `data/` (it will store the SQLite DB).
3. Open `index.php` in your browser to create a room and get an invitation link.

## Features
- Anonymous rooms via invitation link (room id + token).
- Event sourcing (append-only `events` table).
- Clients reconstruct state from events.
- Polling every 10 seconds for new events (`api.php?action=events`).
- No frameworks: pure PHP, HTML, CSS, JS.

## Files
- `index.php` — create a room and get the invite link.
- `room.php` — groceries UI.
- `api.php` — JSON API to create rooms and append/list events.
- `style.css` — basic styling.

## Notes
- This is a minimal application; no authentication beyond the invitation token.
- Input is sanitized lightly on both client and server.
- SQLite DB for persistence.
