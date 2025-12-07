<?php
/**
 * One-off backfill to populate ea_appointment_services and fill total_* aggregates.
 * Idempotent: checks for existing rows per (appointment_id, service_id) and only fills NULL aggregates.
 */

declare(strict_types=1);

function env(string $key, ?string $fallback = null): ?string
{
    $val = getenv($key);
    return $val === false ? $fallback : $val;
}

$dbHost = env('DB_HOST', 'ea-db');
$dbName = env('EA_DB_NAME', env('DB_NAME', 'easyappointments'));
$dbUser = env('EA_DB_USER', env('DB_USERNAME', 'easyappointments'));
$dbPass = env('EA_DB_PASSWORD', env('DB_PASSWORD', ''));

if (!$dbName || !$dbUser) {
    fwrite(STDERR, "Missing DB credentials; ensure EA_DB_NAME/EA_DB_USER/EA_DB_PASSWORD are set.\n");
    exit(1);
}

$dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $dbHost, $dbName);

try {
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (Throwable $e) {
    fwrite(STDERR, "DB connection failed: " . $e->getMessage() . "\n");
    exit(1);
}

// Preload services to avoid repeated queries.
$services = [];
foreach ($pdo->query('SELECT id, duration, price FROM ea_services') as $row) {
    $services[(int)$row['id']] = [
        'duration' => $row['duration'] !== null ? (int)$row['duration'] : null,
        'price' => $row['price'] !== null ? (float)$row['price'] : null,
    ];
}

$existsStmt = $pdo->prepare('SELECT 1 FROM ea_appointment_services WHERE appointment_id = ? AND service_id = ? LIMIT 1');
$insertStmt = $pdo->prepare('INSERT INTO ea_appointment_services (appointment_id, service_id, duration, price, position) VALUES (?, ?, ?, ?, 1)');
$updateTotalsStmt = $pdo->prepare('UPDATE ea_appointments SET total_duration = COALESCE(total_duration, ?), total_price = COALESCE(total_price, ?) WHERE id = ?');

$inserted = 0;
$updated = 0;

$appointments = $pdo->query('SELECT id, id_services, total_duration, total_price FROM ea_appointments WHERE id_services IS NOT NULL');

foreach ($appointments as $appt) {
    $apptId = (int)$appt['id'];
    $serviceId = (int)$appt['id_services'];
    if (!isset($services[$serviceId])) {
        continue; // Skip if service missing.
    }

    $existsStmt->execute([$apptId, $serviceId]);
    $exists = (bool)$existsStmt->fetchColumn();

    $duration = $services[$serviceId]['duration'];
    $price = $services[$serviceId]['price'];

    if (!$exists) {
        $insertStmt->execute([$apptId, $serviceId, $duration, $price]);
        $inserted++;
    }

    if ($appt['total_duration'] === null || $appt['total_price'] === null) {
        $updateTotalsStmt->execute([$duration, $price, $apptId]);
        $updated++;
    }
}

fwrite(STDOUT, sprintf("Backfill complete. Inserted: %d, updated totals: %d\n", $inserted, $updated));
