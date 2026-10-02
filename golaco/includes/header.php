<?php
/**
 * GOLAÇO - Shared Header Layout
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Dynamic relative path resolver
$path_prefix = "";
for ($i = 0; $i < 5; $i++) {
    if (file_exists($path_prefix . 'config/db.php')) {
        break;
    }
    $path_prefix .= "../";
}

if (!file_exists($path_prefix . 'config/db.php')) {
    header('Location: ' . $path_prefix . 'install.php');
    exit;
}

require_once $path_prefix . 'config/db.php';

// Check database columns and run schema migration if columns are missing
try {
    $pdo->query("SELECT display_name FROM users LIMIT 1");
} catch (PDOException $e) {
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN display_name VARCHAR(100) DEFAULT NULL");
        $pdo->exec("ALTER TABLE users ADD COLUMN bio TEXT DEFAULT NULL");
        $pdo->exec("ALTER TABLE users ADD COLUMN avatar_path VARCHAR(255) DEFAULT NULL");
    } catch (PDOException $ex) {
        // Table alteration error, ignore
    }
}

try {
    $pdo->query("SELECT dashboard_card_bg FROM users LIMIT 1");
} catch (PDOException $e) {
    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN dashboard_card_bg VARCHAR(255) DEFAULT NULL");
    } catch (PDOException $ex) {
        // Table alteration error, ignore
    }
}

// Check authorization
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . $path_prefix . 'auth/login.php');
    exit;
}

// Load updated user credentials
$user_id = $_SESSION['user_id'];
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
    $stmt->execute([':id' => $user_id]);
    $current_user = $stmt->fetch();

    if (!$current_user) {
        session_unset();
        session_destroy();
        header('Location: ' . $path_prefix . 'auth/login.php');
        exit;
    }

    // Sync database state
    $_SESSION['username'] = $current_user['username'];
    $_SESSION['role'] = $current_user['role'];
    $_SESSION['money'] = $current_user['money'];

} catch (PDOException $e) {
    die("Erro ao carregar dados do usuário: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GOLAÇO - Brasileirão Draft & Simulação</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo $path_prefix; ?>assets/css/style.css">
</head>
<body class="<?php echo !empty($is_match_page) ? 'match-viewport-active' : ''; ?>">
    <header class="<?php echo !empty($is_match_page) ? 'nav-match-compact' : ''; ?>">
        <div class="nav-container">
            <a href="<?php echo $path_prefix; ?>dashboard.php" class="logo">GOLAÇO</a>
            
            <div class="nav-menu">
                <?php if ($_SESSION['role'] === 'admin'): ?>
                    <a href="<?php echo $path_prefix; ?>admin/index.php" class="admin-badge">Painel Admin</a>
                <?php endif; ?>

                <div class="nav-info">
                    <?php if (!empty($current_user['avatar_path'])): ?>
                        <img src="<?php echo $path_prefix . htmlspecialchars($current_user['avatar_path']); ?>" alt="Avatar" style="width: 26px; height: 26px; border-radius: 50%; border: 1px solid var(--primary); object-fit: cover; margin-right: -5px;">
                    <?php endif; ?>
                    <a href="<?php echo $path_prefix; ?>profile.php" class="nav-username" style="color: #fff; font-weight: 600;">Téc. <?php echo htmlspecialchars($current_user['display_name'] ? $current_user['display_name'] : $current_user['username']); ?></a>
                    <span class="nav-money" id="header-money">R$ <?php echo number_format($_SESSION['money'], 0, ',', '.'); ?></span>
                </div>

                <a href="<?php echo $path_prefix; ?>auth/logout.php" class="btn-logout">Sair</a>
            </div>
        </div>
    </header>
    <main class="main-content">
