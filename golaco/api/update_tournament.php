<?php
/**
 * GOLAÇO - API - Update Tournament Progression
 * Processes match outcomes, advances stages, and awards money prizes.
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

$raw_data = file_get_contents('php://input');
$data = json_decode($raw_data, true);

if (!$data) {
    echo json_encode(['error' => 'Dados inválidos.']);
    exit;
}

$result = trim($data['result'] ?? ''); // 'win' or 'lose'
$tournament_id = intval($data['tournament_id'] ?? 0);

if ($tournament_id <= 0 || !in_array($result, ['win', 'lose'])) {
    echo json_encode(['error' => 'Parâmetros inválidos.']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Fetch active tournament for the logged-in user
    $stmt = $pdo->prepare("SELECT * FROM tournaments WHERE id = :id AND user_id = :user_id AND status = 'active'");
    $stmt->execute([':id' => $tournament_id, ':user_id' => $_SESSION['user_id']]);
    $tournament = $stmt->fetch();

    if (!$tournament) {
        echo json_encode(['error' => 'Torneio ativo não encontrado.']);
        $pdo->rollBack();
        exit;
    }

    $current_stage = intval($tournament['current_stage']);
    $reward = 0;
    $new_stage = $current_stage;
    $new_status = 'active';

    if ($result === 'win') {
        // Calculate dynamic reward based on stage
        switch ($current_stage) {
            case 1: // Win Oitavas -> go to Quartas
                $reward = 500;
                $new_stage = 2;
                break;
            case 2: // Win Quartas -> go to Semifinal
                $reward = 1000;
                $new_stage = 3;
                break;
            case 3: // Win Semifinal -> go to Final
                $reward = 2000;
                $new_stage = 4;
                break;
            case 4: // Win Final -> champion!
                $reward = 5000;
                $new_stage = 5;
                $new_status = 'completed';
                break;
        }

        // 1. Update user money in database
        $update_money = $pdo->prepare("UPDATE users SET money = money + :reward WHERE id = :user_id");
        $update_money->execute([':reward' => $reward, ':user_id' => $_SESSION['user_id']]);
        
        // Update session money representation
        $_SESSION['money'] += $reward;

        // 2. Update tournament stage/status
        $update_tourney = $pdo->prepare("UPDATE tournaments SET current_stage = :stage, status = :status WHERE id = :id");
        $update_tourney->execute([':stage' => $new_stage, ':status' => $new_status, ':id' => $tournament_id]);

        $message = "Parabéns! Você venceu a partida.";
        if ($new_status === 'completed') {
            $message = "PARABÉNS! Você é o grande campeão da Copa do Brasil! R$ {$reward} foram adicionados à sua conta.";
        } else {
            $message .= " Prêmio de R$ {$reward} recebido! Avançou para a próxima fase.";
        }

    } else {
        // User lost: set status to 'eliminated'
        $new_status = 'eliminated';
        $update_tourney = $pdo->prepare("UPDATE tournaments SET status = 'eliminated' WHERE id = :id");
        $update_tourney->execute([':id' => $tournament_id]);
        $message = "Você foi derrotado e eliminado da Copa do Brasil! Mais sorte no próximo draft.";
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => $message,
        'redirect' => 'tournament.php'
    ]);

} catch (PDOException $e) {
    $pdo->rollBack();
    echo json_encode(['error' => 'Erro ao atualizar torneio: ' . $e->getMessage()]);
}
