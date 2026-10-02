<?php
/**
 * GOLAÇO - Admin Delete Player Action
 */

session_start();

if (!file_exists(__DIR__ . '/../config/db.php')) {
    header('Location: ../install.php');
    exit;
}

require_once __DIR__ . '/../config/db.php';

// Verify admin role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../dashboard.php');
    exit;
}

$id = intval($_GET['id'] ?? 0);

if ($id > 0) {
    try {
        // Fetch player details first to delete the file
        $stmt = $pdo->prepare("SELECT image_path, name FROM players WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $player = $stmt->fetch();

        if ($player) {
            // Delete image file from uploads folder if it exists
            if (!empty($player['image_path'])) {
                $absolute_image_path = __DIR__ . '/../' . $player['image_path'];
                if (file_exists($absolute_image_path) && is_file($absolute_image_path)) {
                    unlink($absolute_image_path);
                }
            }

            // Delete player from database
            $delete = $pdo->prepare("DELETE FROM players WHERE id = :id");
            $delete->execute([':id' => $id]);

            $_SESSION['admin_success'] = "Carta de '{$player['name']}' excluída com sucesso!";
        } else {
            $_SESSION['admin_error'] = "Carta não encontrada.";
        }
    } catch (PDOException $e) {
        $_SESSION['admin_error'] = "Erro ao excluir carta: " . $e->getMessage();
    }
} else {
    $_SESSION['admin_error'] = "ID do jogador inválido.";
}

header('Location: index.php');
exit;
