<?php
/**
 * GOLAÇO - API - Get Draft Options
 * Returns exactly 3 random players strictly for the requested position,
 * excluding any players already drafted in the team.
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

$position = trim($_GET['position'] ?? '');
$exclude = trim($_GET['exclude'] ?? '');
$allowed_positions = ['GK', 'CB', 'LB', 'RB', 'CM', 'LM', 'RM', 'ST'];

if (!in_array($position, $allowed_positions)) {
    echo json_encode(['error' => 'Posição inválida.']);
    exit;
}

// Prepare exclusion clause
$exclude_clause = "";
$exclude_ids = [];
if (!empty($exclude)) {
    $exclude_ids = array_map('intval', explode(',', $exclude));
    $exclude_ids = array_filter($exclude_ids, function($id) { return $id > 0; });
    if (count($exclude_ids) > 0) {
        $exclude_clause = "AND id NOT IN (" . implode(',', $exclude_ids) . ")";
    }
}

try {
    // Strictly select 3 random players of the EXACT requested position, excluding already drafted players
    $stmt = $pdo->prepare("SELECT * FROM players WHERE position = :pos $exclude_clause ORDER BY RAND() LIMIT 3");
    $stmt->execute([':pos' => $position]);
    $players = $stmt->fetchAll();

    // If strictly necessary and database has fewer than 3 available with exclusions,
    // only select remaining players of the SAME exact position (NEVER another position)
    if (count($players) < 3) {
        $needed = 3 - count($players);
        $current_ids = array_map(function($p) { return intval($p['id']); }, $players);
        $already_picked_in_batch = array_merge($exclude_ids, $current_ids);
        
        $fallback_exclude = "";
        if (count($already_picked_in_batch) > 0) {
            $fallback_exclude = "AND id NOT IN (" . implode(',', $already_picked_in_batch) . ")";
        }

        $fallback_stmt = $pdo->prepare("SELECT * FROM players WHERE position = :pos $fallback_exclude ORDER BY RAND() LIMIT " . intval($needed));
        $fallback_stmt->execute([':pos' => $position]);
        $fallback_players = $fallback_stmt->fetchAll();

        $players = array_merge($players, $fallback_players);
    }

    // Format output
    $options = [];
    foreach ($players as $p) {
        $options[] = [
            'id' => intval($p['id']),
            'name' => $p['name'],
            'overall' => intval($p['overall']),
            'position' => $p['position'],
            'series' => $p['series'],
            'image_path' => $p['image_path'] ? $p['image_path'] : null
        ];
    }

    echo json_encode(['options' => $options]);

} catch (PDOException $e) {
    echo json_encode(['error' => 'Erro no banco: ' . $e->getMessage()]);
}

