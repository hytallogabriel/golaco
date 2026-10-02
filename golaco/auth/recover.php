<?php
/**
 * GOLAÇO - Password Recovery Screen
 */

session_start();

if (!file_exists(__DIR__ . '/../config/db.php')) {
    header('Location: ../install.php');
    exit;
}

require_once __DIR__ . '/../config/db.php';

$error = '';
$success = '';
$generated_token = '';
$step = 1; // 1 = Request, 2 = Reset

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'request') {
        $login = trim($_POST['login'] ?? '');
        if (empty($login)) {
            $error = 'Preencha seu usuário ou e-mail.';
        } else {
            try {
                $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :login OR email = :login");
                $stmt->execute([':login' => $login]);
                $user = $stmt->fetch();

                if ($user) {
                    // Generate a token
                    $token = rand(100000, 999999);
                    
                    // Save token to DB
                    $update = $pdo->prepare("UPDATE users SET reset_token = :token WHERE id = :id");
                    $update->execute([':token' => $token, ':id' => $user['id']]);

                    $_SESSION['recover_user_id'] = $user['id'];
                    $generated_token = $token;
                    $step = 2; // Advance to resetting password
                } else {
                    $error = 'Nenhum usuário encontrado com esses dados.';
                }
            } catch (PDOException $e) {
                $error = 'Erro no banco: ' . $e->getMessage();
            }
        }
    } elseif (isset($_POST['action']) && $_POST['action'] === 'reset') {
        $token_input = trim($_POST['token'] ?? '');
        $new_password = $_POST['new_password'] ?? '';
        $new_password_confirm = $_POST['new_password_confirm'] ?? '';
        $user_id = $_SESSION['recover_user_id'] ?? null;

        if (empty($token_input) || empty($new_password) || empty($new_password_confirm)) {
            $error = 'Todos os campos são obrigatórios.';
            $step = 2;
        } elseif (strlen($new_password) < 6) {
            $error = 'A senha deve ter pelo menos 6 caracteres.';
            $step = 2;
        } elseif ($new_password !== $new_password_confirm) {
            $error = 'As senhas não coincidem.';
            $step = 2;
        } elseif (!$user_id) {
            $error = 'Sessão expirada. Tente novamente.';
            $step = 1;
        } else {
            try {
                // Verify token
                $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
                $stmt->execute([':id' => $user_id]);
                $user = $stmt->fetch();

                if ($user && $user['reset_token'] !== null && $user['reset_token'] == $token_input) {
                    // Reset password and clear token
                    $password_hash = password_hash($new_password, PASSWORD_BCRYPT);
                    $update = $pdo->prepare("UPDATE users SET password = :password, reset_token = NULL WHERE id = :id");
                    $update->execute([':password' => $password_hash, ':id' => $user_id]);

                    unset($_SESSION['recover_user_id']);
                    $success = 'Senha redefinida com sucesso! Você já pode entrar.';
                    $step = 1;
                } else {
                    $error = 'Código de recuperação inválido.';
                    $step = 2;
                }
            } catch (PDOException $e) {
                $error = 'Erro no banco: ' . $e->getMessage();
                $step = 2;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Senha - GOLAÇO</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .demo-box {
            background: rgba(255, 204, 0, 0.1);
            border: 1px solid rgba(255, 204, 0, 0.3);
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            text-align: center;
        }
        .demo-box p {
            font-size: 0.85rem;
            color: var(--text-muted);
            margin-bottom: 8px;
        }
        .demo-token {
            font-size: 1.6rem;
            font-weight: 800;
            letter-spacing: 3px;
            color: var(--gold);
        }
    </style>
</head>
<body>
    <div class="auth-wrapper">
        <div class="auth-card">
            <h1 class="auth-title">GOLAÇO</h1>
            <p class="auth-subtitle">Recuperação de Senha</p>

            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <?php if ($step == 2 && $generated_token): ?>
                <div class="demo-box">
                    <p>🔔 <strong>Demonstração Local:</strong> Copie o código gerado abaixo para redefinir a senha:</p>
                    <div class="demo-token"><?php echo $generated_token; ?></div>
                </div>
            <?php endif; ?>

            <?php if ($step == 1): ?>
                <!-- Step 1: Request Password Recovery -->
                <form method="POST" action="">
                    <input type="hidden" name="action" value="request">
                    
                    <div class="form-group">
                        <label for="login">Nome de Usuário ou E-mail</label>
                        <input type="text" id="login" name="login" class="form-control" placeholder="Digite seu usuário ou e-mail" required>
                    </div>

                    <button type="submit" class="btn-primary">Gerar Código de Recuperação</button>
                </form>
            <?php else: ?>
                <!-- Step 2: Input Token and Reset Password -->
                <form method="POST" action="">
                    <input type="hidden" name="action" value="reset">

                    <div class="form-group">
                        <label for="token">Código de 6 dígitos</label>
                        <input type="text" id="token" name="token" class="form-control" placeholder="Digite o código" required>
                    </div>

                    <div class="form-group">
                        <label for="new_password">Nova Senha</label>
                        <input type="password" id="new_password" name="new_password" class="form-control" placeholder="Mínimo 6 caracteres" required>
                    </div>

                    <div class="form-group">
                        <label for="new_password_confirm">Confirmar Nova Senha</label>
                        <input type="password" id="new_password_confirm" name="new_password_confirm" class="form-control" placeholder="Repita a nova senha" required>
                    </div>

                    <button type="submit" class="btn-primary">Redefinir Senha</button>
                </form>
            <?php endif; ?>

            <div class="form-footer">
                <p>Voltar para o <a href="login.php">Login</a></p>
            </div>
        </div>
    </div>
</body>
</html>
