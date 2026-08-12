<?php
require_once __DIR__ . '/db_config.php';

mysqli_report(MYSQLI_REPORT_OFF);

function saveOrderToLocalBackup(array $order): bool
{
    $backupPath = __DIR__ . '/orders_fallback.json';
    $existing = [];

    if (file_exists($backupPath)) {
        $json = file_get_contents($backupPath);
        if ($json !== false) {
            $decoded = json_decode($json, true);
            if (is_array($decoded)) {
                $existing = $decoded;
            }
        }
    }

    $existing[] = $order;
    $encoded = json_encode($existing, JSON_PRETTY_PRINT);

    if ($encoded === false) {
        return false;
    }

    return file_put_contents($backupPath, $encoded, LOCK_EX) !== false;
}

function connectDbWithBootstrap(): ?mysqli
{
    $mysqli = @new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($mysqli && !$mysqli->connect_errno) {
        return $mysqli;
    }

    $bootstrap = @new mysqli(DB_HOST, DB_USER, DB_PASS);
    if (!$bootstrap || $bootstrap->connect_errno) {
        return null;
    }

    $dbNameSafe = str_replace('`', '``', DB_NAME);
    $created = $bootstrap->query("CREATE DATABASE IF NOT EXISTS `{$dbNameSafe}` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
    $bootstrap->close();

    if (!$created) {
        return null;
    }

    $mysqliRetry = @new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if (!$mysqliRetry || $mysqliRetry->connect_errno) {
        return null;
    }

    return $mysqliRetry;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: order.html?status=invalid');
    exit;
}

$fullName = trim($_POST['fullName'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$location = trim($_POST['location'] ?? '');
$shoeType = trim($_POST['shoeType'] ?? '');
$condition = trim($_POST['condition'] ?? '');
$sizeRaw = trim($_POST['size'] ?? '');
$notes = trim($_POST['notes'] ?? '');

if ($fullName === '') {
    $errors[] = 'Full name is required.';
}
if ($phone === '') {
    $errors[] = 'Phone number is required.';
}
if ($location === '') {
    $errors[] = 'Delivery location is required.';
}
if ($shoeType === '') {
    $errors[] = 'Shoe type is required.';
}
if ($condition === '') {
    $errors[] = 'Condition is required.';
}
if ($sizeRaw === '' || !ctype_digit($sizeRaw)) {
    $errors[] = 'A valid shoe size is required.';
}

$size = (int) $sizeRaw;

$mysqli = connectDbWithBootstrap();
if (!$mysqli) {
    $saved = saveOrderToLocalBackup([
        'full_name' => $fullName,
        'phone' => $phone,
        'location' => $location,
        'shoe_type' => $shoeType,
        'shoe_condition' => $condition,
        'shoe_size' => $size,
        'notes' => $notes,
        'created_at' => date('Y-m-d H:i:s'),
    ]);

    if ($saved) {
        header('Location: order.html?status=successlocal');
        exit;
    }

    header('Location: order.html?status=dberror');
    exit;
}

$createSql = "CREATE TABLE IF NOT EXISTS orders (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(120) NOT NULL,
    phone VARCHAR(30) NOT NULL,
    location VARCHAR(80) NOT NULL,
    shoe_type VARCHAR(80) NOT NULL,
    shoe_condition VARCHAR(20) NOT NULL,
    shoe_size INT NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if (!$mysqli->query($createSql)) {
    $mysqli->close();
    $saved = saveOrderToLocalBackup([
        'full_name' => $fullName,
        'phone' => $phone,
        'location' => $location,
        'shoe_type' => $shoeType,
        'shoe_condition' => $condition,
        'shoe_size' => $size,
        'notes' => $notes,
        'created_at' => date('Y-m-d H:i:s'),
    ]);

    if ($saved) {
        header('Location: order.html?status=successlocal');
        exit;
    }

    header('Location: order.html?status=dbsetup');
    exit;
}

if (!empty($errors)) {
    $mysqli->close();
    header('Location: order.html?status=validation');
    exit;
}

$stmt = $mysqli->prepare(
    'INSERT INTO orders (full_name, phone, location, shoe_type, shoe_condition, shoe_size, notes)
     VALUES (?, ?, ?, ?, ?, ?, ?)'
);

if (!$stmt) {
    $mysqli->close();
    $saved = saveOrderToLocalBackup([
        'full_name' => $fullName,
        'phone' => $phone,
        'location' => $location,
        'shoe_type' => $shoeType,
        'shoe_condition' => $condition,
        'shoe_size' => $size,
        'notes' => $notes,
        'created_at' => date('Y-m-d H:i:s'),
    ]);

    if ($saved) {
        header('Location: order.html?status=successlocal');
        exit;
    }

    header('Location: order.html?status=failed');
    exit;
}

$stmt->bind_param('sssssis', $fullName, $phone, $location, $shoeType, $condition, $size, $notes);
$ok = $stmt->execute();
$stmt->close();
$mysqli->close();

if ($ok) {
    header('Location: order.html?status=success');
    exit;
}

$saved = saveOrderToLocalBackup([
    'full_name' => $fullName,
    'phone' => $phone,
    'location' => $location,
    'shoe_type' => $shoeType,
    'shoe_condition' => $condition,
    'shoe_size' => $size,
    'notes' => $notes,
    'created_at' => date('Y-m-d H:i:s'),
]);

if ($saved) {
    header('Location: order.html?status=successlocal');
    exit;
}

header('Location: order.html?status=failed');
exit;
