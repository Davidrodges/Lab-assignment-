<?php
mysqli_report(MYSQLI_REPORT_OFF);

$host = '127.0.0.1';
$appUser = 'solemarket_app';
$appPass = 'SoleMarket@123';
$dbName = 'solemarket_db';

$message = '';
$messageClass = 'error';
$steps = [];

function esc(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rootUser = trim($_POST['rootUser'] ?? 'root');
    $rootPass = trim($_POST['rootPass'] ?? '');

    $admin = @new mysqli($host, $rootUser, $rootPass);
    if (!$admin || $admin->connect_errno) {
        $message = 'Root/admin login failed. Check credentials and try again.';
    } else {
        $steps[] = 'Connected as admin user.';

        $dbNameSafe = str_replace('`', '``', $dbName);
        $appUserSafe = str_replace("'", "''", $appUser);
        $appPassSafe = str_replace("'", "''", $appPass);

        if ($admin->query("CREATE DATABASE IF NOT EXISTS `{$dbNameSafe}` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci")) {
            $steps[] = 'Database ready: ' . $dbName;
        } else {
            $message = 'Failed creating database: ' . $admin->error;
        }

        if ($message === '' && $admin->query("CREATE USER IF NOT EXISTS '{$appUserSafe}'@'127.0.0.1' IDENTIFIED BY '{$appPassSafe}'")) {
            $steps[] = 'Application user created/verified.';
        } elseif ($message === '') {
            $message = 'Failed creating app user: ' . $admin->error;
        }

        if ($message === '' && $admin->query("GRANT ALL PRIVILEGES ON `{$dbNameSafe}`.* TO '{$appUserSafe}'@'127.0.0.1'")) {
            $steps[] = 'Privileges granted to application user.';
        } elseif ($message === '') {
            $message = 'Failed granting privileges: ' . $admin->error;
        }

        if ($message === '' && $admin->query('FLUSH PRIVILEGES')) {
            $steps[] = 'Privileges flushed.';
        } elseif ($message === '') {
            $message = 'Failed flushing privileges: ' . $admin->error;
        }

        if ($message === '') {
            $verify = @new mysqli($host, $appUser, $appPass, $dbName);
            if ($verify && !$verify->connect_errno) {
                $createTableSql = "CREATE TABLE IF NOT EXISTS orders (
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

                if ($verify->query($createTableSql)) {
                    $steps[] = 'Orders table verified.';
                    $message = 'Database setup complete. You can now submit orders.';
                    $messageClass = 'success';
                } else {
                    $message = 'Connected with app user but failed creating orders table: ' . $verify->error;
                }

                $verify->close();
            } else {
                $message = 'Could not verify app login after setup.';
            }
        }

        $admin->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>SoleMarket Kenya | Database Setup</title>
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
    <h1 class="section-title">Database Quick Setup</h1>
    <p class="lead">Run this once with your MariaDB root/admin credentials.</p>

    <?php if ($message !== ''): ?>
      <p class="form-message <?php echo esc($messageClass); ?>"><?php echo esc($message); ?></p>
    <?php endif; ?>

    <?php if (!empty($steps)): ?>
      <section class="records-wrap">
        <h2>Setup Steps</h2>
        <ul>
          <?php foreach ($steps as $step): ?>
            <li><?php echo esc($step); ?></li>
          <?php endforeach; ?>
        </ul>
      </section>
    <?php endif; ?>

    <form class="order-form" method="post" action="setup_db.php" novalidate>
      <div class="form-grid">
        <div class="form-group">
          <label for="rootUser">Admin Username</label>
          <input id="rootUser" name="rootUser" type="text" value="root" required />
        </div>

        <div class="form-group">
          <label for="rootPass">Admin Password</label>
          <input id="rootPass" name="rootPass" type="password" required />
        </div>
      </div>

      <button type="submit" class="btn btn-primary">Run Setup</button>
    </form>

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
