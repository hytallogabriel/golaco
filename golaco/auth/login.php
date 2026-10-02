<?php
/**
 * GOLAÇO - Login Screen
 */

session_start();

// Redirect to install if DB configuration is missing
if (!file_exists(__DIR__ . '/../config/db.php')) {
    header('Location: ../install.php');
    exit;
}

require_once __DIR__ . '/../config/db.php';

// If already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: ../dashboard.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($login) || empty($password)) {
        $error = 'Por favor, preencha todos os campos.';
    } else {
        try {
            // Find by username or email
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :login OR email = :login");
            $stmt->execute([':login' => $login]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                // Set session details
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['money'] = $user['money'];

                header('Location: ../dashboard.php');
                exit;
            } else {
                $error = 'Usuário ou senha incorretos.';
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
    <title>Login - GOLAÇO</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="auth-wrapper">
        <div class="auth-card">
            <h1 class="auth-title">GOLAÇO</h1>
            <p class="auth-subtitle">Entre no Draft do Brasileirão</p>

            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="login">Usuário ou E-mail</label>
                    <input type="text" id="login" name="login" class="form-control" placeholder="admin ou admin@golaco.com" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Senha</label>
                    <input type="password" id="password" name="password" class="form-control" placeholder="••••••••" required>
                </div>

                <button type="submit" class="btn-primary">Entrar no Campo</button>
            </form>

            <div class="form-footer">
                <p>Não tem uma conta? <a href="register.php">Cadastre-se</a></p>
                <p style="margin-top: 10px;"><a href="recover.php">Esqueceu sua senha?</a></p>
            </div>
        </div>
    </div>
</body>
</html>
