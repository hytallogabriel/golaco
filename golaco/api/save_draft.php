<?php
/**
 * GOLAÇO - API - Save Drafted Team
 * Persists the drafted team and starts the Copa do Brasil tournament.
 */

header('Content-Type: application/json');
session_start();

if (!file_exists(__DIR__ . '/../config/db.php')) {
    echo json_encode(['error' => 'Banco de dados não configurado.']);
    exit;
}

require_once __DIR__ . '/../config/db.php';

// Check authorization
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Usuário não autenticado.']);
    exit;
}

// Read raw JSON post data
$raw_data = file_get_contents('php://input');
$data = json_decode($raw_data, true);

if (!$data) {
    echo json_encode(['error' => 'Dados inválidos.']);
    exit;
}

$team_name = trim($data['team_name'] ?? '');
$kit_color = trim($data['kit_color'] ?? '#00ff66');
$formation = trim($data['formation'] ?? '4-3-3');
$players = $data['players'] ?? [];

// Validate fields
if (empty($team_name)) {
    echo json_encode(['error' => 'Por favor, digite o nome do time.']);
    exit;
}

$player_ids = array_values($players);
if (count($player_ids) !== 11) {
    echo json_encode(['error' => 'O time precisa ter exatamente 11 jogadores selecionados.']);
    exit;
}

if (count(array_unique($player_ids)) !== 11) {
    echo json_encode(['error' => 'Jogadores duplicados não são permitidos. Cada atleta na sua equipe deve ser único!']);
    exit;
}

try {
    $pdo->beginTransaction();

    // 1. Mark any existing active tournaments for this user as 'eliminated'
    $deactivate = $pdo->prepare("UPDATE tournaments SET status = 'eliminated' WHERE user_id = :user_id AND status = 'active'");
    $deactivate->execute([':user_id' => $_SESSION['user_id']]);

    // 2. Insert new active tournament
    $stmt = $pdo->prepare("INSERT INTO tournaments (user_id, team_name, formation, kit_color, players_json, current_stage, status) VALUES (:user_id, :team_name, :formation, :kit_color, :players_json, 1, 'active')");
    
    $stmt->execute([
        ':user_id' => $_SESSION['user_id'],
        ':team_name' => $team_name,
        ':formation' => $formation,
        ':kit_color' => $kit_color,
        ':players_json' => json_encode($players)
    ]);

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'redirect' => 'tournament.php'
    ]);

} catch (PDOException $e) {
    $pdo->rollBack();
    echo json_encode(['error' => 'Erro ao salvar o time: ' . $e->getMessage()]);
}
