<?php
/**
 * MySQL database layer.
 * Replaces JSON storage. Same function names → existing pages keep working.
 * Uses PDO prepared statements (SQL-injection safe).
 */

require_once __DIR__ . '/db_credentials.php';

function db(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) return $pdo;

    // Use require_once with a local variable — safer than require
    $cfgPath = __DIR__ . '/db_credentials.php';
    $cfg = require $cfgPath;

    // Safety check — in case PHP returned 1 from a cached require
    if (!is_array($cfg)) {
        $cfg = [
            'host'    => '127.0.0.1',
            'port'    => 3306,
            'dbname'  => 'aquafix_pro',
            'user'    => 'root',
            'pass'    => '',
            'charset' => 'utf8mb4',
        ];
    }

    $dsn = "mysql:host={$cfg['host']};port={$cfg['port']};dbname={$cfg['dbname']};charset={$cfg['charset']}";

    try {
        $pdo = new PDO($dsn, $cfg['user'], $cfg['pass'], [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        die('Database connection failed: ' . $e->getMessage() . ' — Check config/db_credentials.php');
    }
    return $pdo;
}
function db_run(string $sql, array $params = []): PDOStatement {
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

/* ---------- 2. Generic CRUD (same names as before) ---------- */

function insert_record(string $file, array $record): int {
    $table = str_replace('.json', '', $file);
    $record['created_at'] = $record['created_at'] ?? date('Y-m-d H:i:s');

    $cols  = array_keys($record);
    $marks = array_map(fn($c) => ':' . $c, $cols);
    $sql   = "INSERT INTO `$table` (`" . implode('`,`', $cols) . "`) VALUES (" . implode(',', $marks) . ")";

    $params = [];
    foreach ($record as $k => $v) $params[':' . $k] = $v;

    db_run($sql, $params);
    return (int) db()->lastInsertId();
}

function update_record(string $file, int $id, array $fields): bool {
    $table = str_replace('.json', '', $file);
    if (!$fields) return false;

    $set = implode(', ', array_map(fn($c) => "`$c` = :$c", array_keys($fields)));
    $params = [':id' => $id];
    foreach ($fields as $k => $v) $params[':' . $k] = $v;

    db_run("UPDATE `$table` SET $set WHERE id = :id", $params);
    return true;
}

function delete_record(string $file, int $id): bool {
    $table = str_replace('.json', '', $file);
    $stmt = db_run("DELETE FROM `$table` WHERE id = :id", [':id' => $id]);
    return $stmt->rowCount() > 0;
}

function get_records(string $file, string $order = 'created_at', string $dir = 'desc'): array {
    $table = str_replace('.json', '', $file);
    $dir   = strtolower($dir) === 'asc' ? 'ASC' : 'DESC';
    $allowed = ['created_at','id','name','status','email','date','booking_date'];
    if (!in_array($order, $allowed, true)) $order = 'created_at';
    return db_run("SELECT * FROM `$table` ORDER BY `$order` $dir")->fetchAll();
}

function get_record(string $file, int $id): ?array {
    $table = str_replace('.json', '', $file);
    $row = db_run("SELECT * FROM `$table` WHERE id = :id LIMIT 1", [':id' => $id])->fetch();
    return $row ?: null;
}

/* ---------- 3. Convenience wrappers ---------- */

function save_booking(array $data): int {
    $data['status'] = $data['status'] ?? 'pending';

    $serviceId = $data['service_id'] ?? null;
    if (!$serviceId && !empty($data['service'])) {
        $svc = db_run("SELECT id FROM services WHERE title = :t LIMIT 1",
            [':t' => $data['service']])->fetch();
        $serviceId = $svc['id'] ?? null;
    }

    return insert_record('bookings', [
        'user_id'      => $data['user_id']      ?? null,
        'service_id'   => $serviceId,
        'name'         => $data['name']         ?? '',
        'email'        => $data['email']        ?? '',
        'phone'        => $data['phone']        ?? '',
        'address'      => $data['address']      ?? '',
        'booking_date' => $data['date']         ?? $data['booking_date'] ?? null,
        'time_slot'    => $data['time']         ?? $data['time_slot']    ?? '',
        'message'      => $data['message']      ?? '',
        'status'       => $data['status'],
    ]);
}

function save_message(array $data): int {
    return insert_record('messages', [
        'name'    => $data['name']    ?? '',
        'email'   => $data['email']   ?? '',
        'phone'   => $data['phone']   ?? '',
        'subject' => $data['subject'] ?? '',
        'message' => $data['message'] ?? '',
        'status'  => 'new',
    ]);
}

function save_newsletter(string $email): bool {
    $exists = db_run("SELECT id FROM newsletter WHERE email = :e LIMIT 1", [':e' => $email])->fetch();
    if ($exists) return false;
    insert_record('newsletter', ['email' => $email]);
    return true;
}