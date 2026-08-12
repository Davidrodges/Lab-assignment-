<?php
require_once __DIR__ . '/db_config.php';

mysqli_report(MYSQLI_REPORT_OFF);

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

function esc(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function loadLocalOrders(): array
{
  $backupPath = __DIR__ . '/orders_fallback.json';
  if (!file_exists($backupPath)) {
    return [];
  }

  $json = file_get_contents($backupPath);
  if ($json === false) {
    return [];
  }

  $decoded = json_decode($json, true);
  if (!is_array($decoded)) {
    return [];
  }

  return array_reverse($decoded);
}

$orders = [];
$dbError = '';
$dataNotice = '';

$mysqli = connectDbWithBootstrap();
if (!$mysqli) {
  $orders = loadLocalOrders();
  if (!empty($orders)) {
      $dataNotice = 'Database is unavailable. Showing locally saved fallback orders.';
  } else {
      $dbError = 'Database connection failed and no local fallback orders were found.';
  }
}

if (!$dbError && $mysqli) {
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
        $dbError = 'Could not initialize orders table: ' . $mysqli->error;
    }
}

if (!$dbError && $mysqli) {
    $result = $mysqli->query(
        'SELECT id, full_name, phone, location, shoe_type, shoe_condition, shoe_size, notes, created_at
         FROM orders
         ORDER BY id DESC
         LIMIT 20'
    );

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $orders[] = $row;
        }
        $result->free();
    } else {
        $dbError = 'Could not retrieve records: ' . $mysqli->error;
    }
}

if ($mysqli) {
    $mysqli->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>SoleMarket Kenya | Saved Orders</title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="styles.css" />
</head>
<body>
  <header class="site-header">
    <div class="container nav-wrap">
      <a class="logo" href="index.html">SoleMarket Kenya</a>
      <nav class="main-nav" aria-label="Main navigation">
        <a href="index.html">Home</a>
        <a href="shop.html">Shop</a>
        <a href="gallery.html">Gallery</a>
        <a href="order.html">Order</a>
      </nav>
    </div>
  </header>

  <main class="container section">
    <h1 class="section-title">Latest Saved Orders</h1>
    <p class="lead">This page shows the 20 most recent orders saved in MySQL.</p>

    <?php if ($dbError !== ''): ?>
      <p class="form-message error"><?php echo esc($dbError); ?></p>
    <?php endif; ?>

    <?php if ($dataNotice !== ''): ?>
      <p class="form-message success"><?php echo esc($dataNotice); ?></p>
    <?php endif; ?>

    <section class="records-wrap">
      <?php if (empty($orders)): ?>
        <p>No saved records found yet.</p>
      <?php else: ?>
        <table class="records-table" aria-label="Latest saved orders">
          <thead>
            <tr>
              <th>ID</th>
              <th>Name</th>
              <th>Phone</th>
              <th>Location</th>
              <th>Shoe Type</th>
              <th>Condition</th>
              <th>Size</th>
              <th>Notes</th>
              <th>Created</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($orders as $order): ?>
              <tr>
                <td><?php echo esc((string) ($order['id'] ?? 'local')); ?></td>
                <td><?php echo esc($order['full_name']); ?></td>
                <td><?php echo esc($order['phone']); ?></td>
                <td><?php echo esc($order['location']); ?></td>
                <td><?php echo esc($order['shoe_type']); ?></td>
                <td><?php echo esc($order['shoe_condition']); ?></td>
                <td><?php echo esc((string) ($order['shoe_size'] ?? '')); ?></td>
                <td><?php echo esc((string) ($order['notes'] ?? '')); ?></td>
                <td><?php echo esc((string) ($order['created_at'] ?? '')); ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </section>

    <div class="hero-actions">
      <a class="btn btn-ghost" href="order.html">Back to Order Form</a>
    </div>
  </main>

  <footer class="site-footer">
    <div class="container footer-grid">
      <p>SoleMarket Kenya</p>
      <p>Phone: +254 700 123 456</p>
      <p>Email: hello@solemarket.co.ke</p>
    </div>
  </footer>
</body>
</html>
