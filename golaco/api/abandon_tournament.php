<?php
/**
 * GOLAÇO - API - Abandon Tournament
 * Allows user to forfeit / abandon an in-progress tournament and start a new draft.
 */

header('Content-Type: application/json');
session_start();

if (!file_exists(__DIR__ . '/../config/db.php')) {
    echo json_encode(['error' => 'Banco de dados não configurado.']);
    exit;
}

require_once __DIR__ . '/../config/db.php';

// Verify session
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Usuário não autenticado.']);
    exit;
}

try {
    // Find active tournament for the logged-in user
    $stmt = $pdo->prepare("SELECT id FROM tournaments WHERE user_id = :user_id AND status = 'active' LIMIT 1");
    $stmt->execute([':user_id' => $_SESSION['user_id']]);
    $tourney_id = $stmt->fetchColumn();

    if (!$tourney_id) {
        echo json_encode(['error' => 'Nenhum torneio ativo encontrado para abandonar.']);
        exit;
    }

    // Set tournament status to 'eliminated' so user can start a fresh draft
    $update = $pdo->prepare("UPDATE tournaments SET status = 'eliminated' WHERE id = :id AND user_id = :user_id");
    $update->execute([':id' => $tourney_id, ':user_id' => $_SESSION['user_id']]);

    echo json_encode([
        'success' => true,
        'message' => 'Copa abandonada com sucesso. Você já pode iniciar um novo draft!',
        'redirect' => 'draft.php'
    ]);

} catch (PDOException $e) {
    echo json_encode(['error' => 'Erro ao abandonar copa: ' . $e->getMessage()]);
}
