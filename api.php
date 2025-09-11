<?php

declare(strict_types=1);

function db(): PDO {
    $dbFile = getenv('DB_FILE');
    $dsn = 'sqlite:' . $dbFile;
    $pdo = new PDO($dsn);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec('CREATE TABLE IF NOT EXISTS rooms (
        id TEXT PRIMARY KEY,
        token TEXT NOT NULL,
        created_at INTEGER NOT NULL
    )');
    $pdo->exec('CREATE TABLE IF NOT EXISTS events (
        seq INTEGER PRIMARY KEY AUTOINCREMENT,
        room_id TEXT NOT NULL,
        ts INTEGER NOT NULL,
        type TEXT NOT NULL,
        payload TEXT NOT NULL
    )');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_events_room_seq ON events(room_id, seq)');
    $stmt = $pdo->prepare('SELECT id FROM rooms WHERE created_at < :cutoff');
    $stmt->execute([':cutoff' => $cutoff]);
    $expiredRooms = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if ($expiredRooms) {
        $in = str_repeat('?,', count($expiredRooms) - 1) . '?';
        $pdo->prepare("DELETE FROM events WHERE room_id IN ($in)")->execute($expiredRooms);
        $pdo->prepare("DELETE FROM rooms WHERE id IN ($in)")->execute($expiredRooms);
    }

    return $pdo;
}

function uuid(): string {
    return bin2hex(random_bytes(16));
}

function now(): int {
    return time();
}

function json_body(): array {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    
    return is_array($data) ? $data : [];
}

function success($obj): void {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => true, 'data' => $obj], JSON_UNESCAPED_UNICODE);
    exit;
}

function error($message, $code = 400): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['ok' => false, 'error' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

function sanitize_text($s): string {
    $s = trim((string) $s);

    return preg_replace('/[\x00-\x1F\x7F]/u', '', $s);
}

function validateToken(string $room_id, string $token): void {
    global $pdo;

    $stmt = $pdo->prepare('SELECT token FROM rooms WHERE id = ?');
    $stmt->execute([$room_id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        error('Room not found', 404);
    }
    
    if ($row['token'] !== $token) {
        error('Invalid token', 403);
    }
}

$pdo = db();
$action = $_GET['action'] ?? '';

if ($action === 'create_room') {
    $id = uuid();
    $token = uuid();
    $stmt = $pdo->prepare('INSERT INTO rooms(id, token, created_at) VALUES (?, ?, ?)');
    $stmt->execute([$id, $token, now()]);
    success(['id' => $id, 'token' => $token]);
}

if ($action === 'room') {
    $id = $_GET['id'] ?? '';
    $token = $_GET['token'] ?? '';
    $stmt = $pdo->prepare('SELECT id, token FROM rooms WHERE id = ?');
    $stmt->execute([$id]);
    $room = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$room) {
        error('Room not found', 404);
    }

    if ($room['token'] !== $token) {
        error('Invalid token', 403);
    }

    success(['id' => $room['id']]);
}

if ($action === 'append_event') {
    $body = json_body();
    $room_id = $body['room_id'] ?? '';
    $token = $body['token'] ?? '';
    $type = sanitize_text($body['type'] ?? '');
    $payload = $body['payload'] ?? null;
    if (!$room_id || !$token || !$type || !is_array($payload)) {
        error('Missing fields');
    }

    validateToken($room_id, $token);

    $allowed = ['add_item', 'remove_item', 'toggle', 'rename_item', 'clear_checked', 'move_item'];
    if (!in_array($type, $allowed, true)) {
        error('Invalid event type');
    }

    $payload = array_map(function($v) {
        if (is_string($v)) {
            return sanitize_text($v);
        }

        return $v;
    }, $payload);

    $stmt = $pdo->prepare('INSERT INTO events(room_id, ts, type, payload) VALUES (?, ?, ?, ?)');
    $stmt->execute([$room_id, now(), $type, json_encode($payload)]);
    $seq = (int) $pdo->lastInsertId();
    success(['seq' => $seq]);
}

if ($action === 'events') {
    $room_id = $_GET['room_id'] ?? '';
    $token = $_GET['token'] ?? '';
    $since = (int) ($_GET['since'] ?? 0);
    if (!$room_id || !$token) {
        error('Missing fields');
    }
    
    validateToken($room_id, $token);

    $stmt = $pdo->prepare('SELECT seq, ts, type, payload FROM events WHERE room_id = ? AND seq > ? ORDER BY seq ASC');
    $stmt->execute([$room_id, $since]);
    $events = [];
    while ($e = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $evt = [
            'seq' => (int)$e['seq'],
            'ts' => (int)$e['ts'],
            'type' => $e['type'],
            'payload' => json_decode($e['payload'], true)
        ];
        $events[] = $evt;
    }
    success(['events' => $events]);
}

error('Unknown action', 404);
