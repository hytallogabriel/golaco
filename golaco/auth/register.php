<?php
/**
 * GOLAÇO - User Registration Screen
 */

session_start();

if (!file_exists(__DIR__ . '/../config/db.php')) {
    header('Location: ../install.php');
    exit;
}

require_once __DIR__ . '/../config/db.php';

if (isset($_SESSION['user_id'])) {
    header('Location: ../dashboard.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    if (empty($username) || empty($email) || empty($password) || empty($password_confirm)) {
        $error = 'Por favor, preencha todos os campos.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Por favor, insira um e-mail válido.';
    } elseif (strlen($username) < 3 || strlen($username) > 30) {
        $error = 'O usuário deve ter entre 3 e 30 caracteres.';
    } elseif (strlen($password) < 6) {
        $error = 'A senha deve ter pelo menos 6 caracteres.';
    } elseif ($password !== $password_confirm) {
        $error = 'As senhas não coincidem.';
    } else {
        try {
            // Check if username or email already exists
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = :username OR email = :email");
            $stmt->execute([':username' => $username, ':email' => $email]);
            if ($stmt->fetchColumn() > 0) {
                $error = 'Nome de usuário ou e-mail já cadastrado.';
            } else {
                // Register user
                $password_hash = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role, money) VALUES (:username, :email, :password, 'user', 1000)");
                $stmt->execute([
                    ':username' => $username,
                    ':email' => $email,
                    ':password' => $password_hash
                ]);

                // Auto-login
                $new_id = $pdo->lastInsertId();
                $_SESSION['user_id'] = $new_id;
                $_SESSION['username'] = $username;
                $_SESSION['role'] = 'user';
                $_SESSION['money'] = 1000;

                header('Location: ../dashboard.php');
                exit;
            }
        } catch (PDOException $e) {
            $error = 'Erro no servidor: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro - GOLAÇO</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="auth-wrapper">
        <div class="auth-card">
            <h1 class="auth-title">GOLAÇO</h1>
            <p class="auth-subtitle">Crie sua Conta de Técnico</p>

            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="username">Nome de Usuário</label>
                    <input type="text" id="username" name="username" class="form-control" placeholder="ex: tite10" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required>
                </div>

                <div class="form-group">
                    <label for="email">E-mail</label>
                    <input type="email" id="email" name="email" class="form-control" placeholder="ex: tite@golaco.com" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Senha</label>
                    <input type="password" id="password" name="password" class="form-control" placeholder="Mínimo 6 caracteres" required>
                </div>

                <div class="form-group">
                    <label for="password_confirm">Confirmar Senha</label>
                    <input type="password" id="password_confirm" name="password_confirm" class="form-control" placeholder="Repita a senha" required>
                </div>

                <button type="submit" class="btn-primary">Criar Time & Entrar</button>
            </form>

            <div class="form-footer">
                <p>Já tem uma conta? <a href="login.php">Entre aqui</a></p>
            </div>
        </div>
    </div>
</body>
</html>
