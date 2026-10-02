<?php
/**
 * GOLAÇO - Real-time Match Simulation Screen
 * FIFA / EA Sports FC Tactical Broadcast Layout for Copa do Brasil
 */

$is_match_page = true;
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/crests.php';

// Find active tournament
try {
    $stmt = $pdo->prepare("SELECT * FROM tournaments WHERE user_id = :user_id AND status = 'active' LIMIT 1");
    $stmt->execute([':user_id' => $_SESSION['user_id']]);
    $tournament = $stmt->fetch();
} catch (PDOException $e) {
    $tournament = null;
}

if (!$tournament) {
    header('Location: dashboard.php');
    exit;
}

// Fetch details for the 11 drafted players
$players_json = json_decode($tournament['players_json'], true);
$player_ids = array_values($players_json);
$team_players = [];

if (count($player_ids) === 11) {
    try {
        $placeholders = implode(',', array_fill(0, count($player_ids), '?'));
        $stmt = $pdo->prepare("SELECT * FROM players WHERE id IN ($placeholders)");
        $stmt->execute($player_ids);
        $fetched_players = $stmt->fetchAll();

        // Map players back to their specific tactical slots (e.g. GK, CB1, LM)
        foreach ($players_json as $slotId => $playerId) {
            foreach ($fetched_players as $p) {
                if (intval($p['id']) === intval($playerId)) {
                    $team_players[$slotId] = [
                        'id' => intval($p['id']),
                        'name' => $p['name'],
                        'overall' => intval($p['overall']),
                        'position' => $p['position'],
                        'series' => $p['series']
                    ];
                    break;
                }
            }
        }
    } catch (PDOException $e) {
        die("Erro ao carregar elenco: " . $e->getMessage());
    }
} else {
    die("Elenco incompleto ou inválido.");
}

// Calculate team average overall
$total_ovr = 0;
foreach ($team_players as $slot => $p) {
    $total_ovr += $p['overall'];
}
$user_overall = round($total_ovr / 11);

// Determine opponent name and rating based on current stage
$tournament_id = intval($tournament['id']);
srand($tournament_id);

$opponents_pool = [
    'Flamengo', 'Palmeiras', 'São Paulo', 'Corinthians', 'Grêmio', 
    'Internacional', 'Vasco', 'Fluminense', 'Botafogo', 'Cruzeiro', 
    'Atlético-MG', 'Bahia', 'Fortaleza', 'Athletico-PR', 'Santos',
    'Sport Recife', 'Ceará', 'Goiás', 'Coritiba', 'Novorizontino'
];
shuffle($opponents_pool);

$current_stage = intval($tournament['current_stage']);
$opponent_name = $opponents_pool[$current_stage - 1] ?? 'Seleção do Brasileirão';

// Opponent difficulty rises each round
$opponent_overall_map = [
    1 => 74, // Oitavas
    2 => 78, // Quartas
    3 => 82, // Semifinal
    4 => 86  // Final
];
$opponent_overall = $opponent_overall_map[$current_stage] ?? 75;

$stage_titles = [
    1 => 'OITAVAS DE FINAL',
    2 => 'QUARTAS DE FINAL',
    3 => 'SEMIFINAL',
    4 => 'GRANDE FINAL'
];
$stage_title = $stage_titles[$current_stage] ?? 'MATA-MATA';

$competition_name = 'COPA DO BRASIL';
$competition_logo_svg = getCopaDoBrasilLogoSvg(48, 48);

srand(); // Reset rand seed

// Team crests (dynamic by club name — see includes/crests.php)
$user_crest_svg = getTeamBadgeSvg($tournament['team_name'], $tournament['kit_color'], 50);
$opponent_crest_svg = getTeamBadgeSvg($opponent_name, '#ef4444', 50);
?>

<link rel="stylesheet" href="assets/css/match.css">

<div class="fifa-match-wrapper" style="animation: fadeIn 0.4s ease-in-out;">
    <!-- TOP TOURNAMENT & SCOREBOARD BROADCAST HEADER (CENTERED) -->
    <div class="fifa-header">
        <!-- Copa do Brasil Brand (Centered Directly Above Scoreboard) -->
        <div class="fifa-brand-container centered-top">
            <?php echo $competition_logo_svg; ?>
            <div class="fifa-brand-text">
                <span class="fifa-brand-title" id="competition-title"><?php echo htmlspecialchars($competition_name); ?></span>
                <span class="fifa-brand-stage"><?php echo $stage_title; ?></span>
            </div>
        </div>

        <!-- Scoreboard Bar (Centered Horizontally) -->
        <div class="fifa-scoreboard golaco-scoreboard">
            <div class="fifa-sb-team home">
                <div class="fifa-crest-wrap" title="<?php echo htmlspecialchars($tournament['team_name']); ?>">
                    <?php echo $user_crest_svg; ?>
                </div>
                <span class="fifa-team-title" style="color: #fff;"><?php echo htmlspecialchars(mb_strtoupper($tournament['team_name'])); ?></span>
            </div>

            <div class="fifa-sb-center">
                <div class="fifa-digital-score">
                    <span id="score-home" class="fifa-score-val">0</span>
                    <span class="fifa-score-separator">×</span>
                    <span id="score-away" class="fifa-score-val">0</span>
                </div>
                <div class="fifa-time-row">
                    <span id="match-timer" class="fifa-digital-clock">00:00</span>
                    <span id="match-phase-badge" class="sb-phase-badge live-1h">1º TEMPO</span>
                    <span id="match-added-time" class="sb-added-time" style="display: none;">+0'</span>
                </div>
                <!-- Penalty Shootout Score Row -->
                <div id="penalty-score-row" class="fifa-penalty-row" style="display: none;">
                    <span id="pen-score-home" class="pen-score-num">0</span>
                    <span class="pen-score-label">PÊNALTIS</span>
                    <span id="pen-score-away" class="pen-score-num">0</span>
                </div>
            </div>

            <div class="fifa-sb-team away">
                <span class="fifa-team-title" style="color: #fff;"><?php echo htmlspecialchars(mb_strtoupper($opponent_name)); ?></span>
                <div class="fifa-crest-wrap" title="<?php echo htmlspecialchars($opponent_name); ?>">
                    <?php echo $opponent_crest_svg; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- MAIN TACTICAL SIMULATION ARENA (3-COLUMN BROADCAST VIEW) -->
    <div class="fifa-arena-grid">
        <!-- LEFT COLUMN: HOME TEAM LINEUP (LINEUP / ESCALAÇÃO) -->
        <div class="fifa-lineup-col home">
            <div class="fifa-lineup-header" data-formation="<?php echo htmlspecialchars($tournament['formation'] ?? '4-3-3'); ?>">
                <span class="fifa-tab-sub">ESCALAÇÃO</span>
                <span class="fifa-tab-badge">TITULARES</span>
            </div>
            <div class="fifa-lineup-list" id="home-lineup-list">
                <!-- Dynamically populated by simulation engine -->
                <div class="fifa-lineup-placeholder">Carregando escalação...</div>
            </div>
        </div>

        <!-- CENTER COLUMN: 2D TACTICAL FIELD (CANVAS) -->
        <div class="fifa-pitch-col">
            <div class="sim-pitch-wrapper">
                <canvas id="sim-canvas" class="sim-canvas" width="800" height="460"></canvas>
            </div>

            <!-- TABS BAR BELOW PITCH -->
            <div class="fifa-tabs-container">
                <button type="button" class="fifa-tab-btn" data-tab="stamina">ENERGIA</button>
                <button type="button" class="fifa-tab-btn active" data-tab="ratings">NOTAS</button>
                <button type="button" class="fifa-tab-btn" data-tab="stats">ESTATÍSTICAS</button>
                <button type="button" class="fifa-tab-btn" data-tab="feed">ACONTECIMENTOS</button>
            </div>

            <!-- ACTION & NAVIGATION CONTROLS BAR (PRODUCTION READY, NO DEBUG) -->
            <div class="fifa-action-bar">
                <div class="fifa-action-left">
                    <button id="btn-start-match" class="btn-fifa-primary">▶ Iniciar Partida ⚽</button>
                    <div class="speed-toggle-container">
                        <span class="speed-label">Velocidade:</span>
                        <button type="button" class="btn-speed active" data-speed="1">1x</button>
                        <button type="button" class="btn-speed" data-speed="1.5">1.5x</button>
                        <button type="button" class="btn-speed" data-speed="2.5">2.5x</button>
                    </div>
                </div>

                <div class="fifa-action-right">
                    <button type="button" id="btn-abandon-copa" class="btn-fifa-danger">🚪 Abandonar Copa</button>
                </div>
            </div>

            <!-- DRAWER PANELS BELOW PITCH (NEVER OBSTRUCTING THE FIELD) -->
            <div class="fifa-bottom-panels-container">
                <div id="fifa-panel-stats" class="fifa-extra-panel" style="display: none;">
                    <div class="fifa-panel-header">
                        <div class="fifa-panel-title-wrap">
                            <h3>📊 Estatísticas da Partida</h3>
                            <span class="live-indicator"><span class="live-dot"></span> TEMPO REAL</span>
                        </div>
                        <button type="button" class="btn-panel-close" id="btn-close-stats" title="Fechar painel">&times;</button>
                    </div>
                    <div class="stats-table" id="live-stats-table">
                        <!-- Dynamic stats table -->
                    </div>
                </div>

                <div id="fifa-panel-feed" class="fifa-extra-panel" style="display: none;">
                    <div class="fifa-panel-header">
                        <div class="fifa-panel-title-wrap">
                            <h3>📢 Acontecimentos da Partida</h3>
                            <span class="live-indicator"><span class="live-dot"></span> TRANSMISSÃO AO VIVO</span>
                        </div>
                        <button type="button" class="btn-panel-close" id="btn-close-feed" title="Fechar painel">&times;</button>
                    </div>
                    <div class="events-log" id="events-log">
                        <div class="event-item">⚽ Clique em "Iniciar Partida" para rolar a bola na Copa do Brasil!</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- RIGHT COLUMN: AWAY TEAM LINEUP (LINEUP / ESCALAÇÃO) -->
        <div class="fifa-lineup-col away">
            <div class="fifa-lineup-header">
                <span class="fifa-tab-sub">ESCALAÇÃO</span>
                <span class="fifa-tab-badge">TITULARES</span>
            </div>
            <div class="fifa-lineup-list" id="away-lineup-list">
                <!-- Dynamically populated by simulation engine -->
                <div class="fifa-lineup-placeholder">Carregando escalação...</div>
            </div>
        </div>
    </div>
</div>

<!-- ABANDON TOURNAMENT CONFIRMATION MODAL -->
<div id="abandon-dialog-overlay" class="match-dialog-overlay">
    <div class="match-dialog" style="max-width: 480px; border-color: rgba(239, 68, 68, 0.4);">
        <div style="font-size: 2.8rem; margin-bottom: 10px;">⚠️</div>
        <h1 class="md-result-title lose" style="font-size: 1.8rem; margin-bottom: 10px;">ABANDONAR COPA?</h1>
        <p class="md-result-desc" style="font-size: 0.95rem; line-height: 1.5; margin-bottom: 25px;">
            Tem certeza de que deseja desistir da <strong>Copa do Brasil</strong>? Seu time será desclassificado e você poderá montar um novo elenco no draft imediatamente.
        </p>

        <div style="display: flex; gap: 12px; justify-content: center;">
            <button id="btn-cancel-abandon" class="btn-secondary" style="width: auto; padding: 12px 24px;">Cancelar</button>
            <button id="btn-confirm-abandon" class="btn-primary" style="width: auto; padding: 12px 24px; background: #ef4444; border-color: #ef4444; color: #fff;">Confirmar Desistência</button>
        </div>
    </div>
</div>

<!-- HALFTIME STATS MODAL DIALOG -->
<div id="halftime-dialog-overlay" class="match-dialog-overlay">
    <div class="match-dialog halftime-dialog">
        <div class="ht-badge">⏸️ FIM DO PRIMEIRO TEMPO</div>
        <h1 class="ht-title">INTERVALO DE JOGO</h1>
        
        <div class="ht-scoreboard ht-scoreboard-crests">
            <div class="ht-team">
                <div class="ht-crest"><?php echo getTeamBadgeSvg($tournament['team_name'], $tournament['kit_color'], 44); ?></div>
                <span class="ht-team-name" style="color: <?php echo htmlspecialchars($tournament['kit_color']); ?>;"><?php echo htmlspecialchars($tournament['team_name']); ?></span>
                <span class="ht-score" id="ht-score-home">0</span>
            </div>
            <span class="ht-vs">×</span>
            <div class="ht-team">
                <span class="ht-score" id="ht-score-away">0</span>
                <span class="ht-team-name" style="color: #ff4d4d;"><?php echo htmlspecialchars($opponent_name); ?></span>
                <div class="ht-crest"><?php echo getTeamBadgeSvg($opponent_name, '#ef4444', 44); ?></div>
            </div>
        </div>

        <!-- Comparative Stats Table -->
        <div class="stats-table" id="ht-stats-table">
            <!-- Dynamic stats inserted here -->
        </div>

        <button id="btn-resume-halftime" class="btn-primary" style="max-width: 280px; margin: 25px auto 0 auto;">Começar 2º Tempo ▶️ (<span id="ht-countdown">5</span>s)</button>
    </div>
</div>

<!-- POST-MATCH RESULTS MODAL DIALOG -->
<div id="match-dialog-overlay" class="match-dialog-overlay">
    <div class="match-dialog">
        <p class="md-ft-label">FIM DE JOGO</p>
        <div class="md-ft-crests" id="md-ft-crests"></div>
        <h1 id="md-title" class="md-result-title win">VITÓRIA!</h1>
        <p id="md-desc" class="md-result-desc">Você venceu a partida de oitavas de final por 2 x 1.</p>
        
        <div id="md-reward-container" class="md-reward-box">
            <span>🪙 +R$</span>
            <span id="md-reward-val">500</span>
        </div>

        <!-- Full-Time Match Summary Stats -->
        <div class="stats-table" id="ft-stats-table" style="margin-bottom: 25px;">
            <!-- Dynamic stats inserted here -->
        </div>

        <button id="btn-confirm-match" class="btn-primary" style="max-width: 250px; margin: 0 auto;">Continuar Torneio</button>
    </div>
</div>

<!-- Pass PHP Variables securely into JS simulation engine -->
<script>
    const matchConfig = {
        tournamentId: <?php echo $tournament_id; ?>,
        currentStage: <?php echo $current_stage; ?>,
        competitionName: <?php echo json_encode($competition_name); ?>,
        stageTitle: <?php echo json_encode($stage_title); ?>,
        userTeamName: <?php echo json_encode($tournament['team_name']); ?>,
        userKitColor: <?php echo json_encode($tournament['kit_color']); ?>,
        userFormation: <?php echo json_encode($tournament['formation'] ?? '4-3-3'); ?>,
        userOverall: <?php echo $user_overall; ?>,
        opponentTeamName: <?php echo json_encode($opponent_name); ?>,
        opponentOverall: <?php echo $opponent_overall; ?>,
        playersList: <?php echo json_encode($team_players); ?>,
        userCrestHtml: <?php echo json_encode($user_crest_svg); ?>,
        opponentCrestHtml: <?php echo json_encode($opponent_crest_svg); ?>
    };
</script>

<script src="assets/js/match/config.js"></script>
<script src="assets/js/simulation.js"></script>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
