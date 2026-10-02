<?php
/**
 * GOLAÇO - Copa do Brasil Tournament Tree & Pre-Match Arena
 */

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/crests.php';

// Find active tournament for this user
try {
    $stmt = $pdo->prepare("SELECT * FROM tournaments WHERE user_id = :user_id AND status = 'active' LIMIT 1");
    $stmt->execute([':user_id' => $_SESSION['user_id']]);
    $tournament = $stmt->fetch();
} catch (PDOException $e) {
    $tournament = null;
}

// If no active tournament, check if there's any recently completed/lost one to show results, otherwise redirect
if (!$tournament) {
    // Look for last finished tournament
    $stmt = $pdo->prepare("SELECT * FROM tournaments WHERE user_id = :user_id ORDER BY id DESC LIMIT 1");
    $stmt->execute([':user_id' => $_SESSION['user_id']]);
    $last_tournament = $stmt->fetch();

    if ($last_tournament) {
        $status = $last_tournament['status'];
        $team_name = htmlspecialchars($last_tournament['team_name']);
        
        echo "<div class='stage-card' style='margin-top: 50px; animation: fadeIn 0.5s ease;'>";
        if ($status === 'completed') {
            echo "<h1 class='md-result-title win' style='font-size: 3rem;'>🏆 CAMPEÃO!</h1>";
            echo "<p class='md-result-desc' style='margin-top: 15px;'>Seu time <strong>{$team_name}</strong> venceu a Copa do Brasil e garantiu a taça dourada!</p>";
        } else {
            echo "<h1 class='md-result-title lose'>Eliminado ❌</h1>";
            echo "<p class='md-result-desc' style='margin-top: 15px;'>Seu time <strong>{$team_name}</strong> foi eliminado nesta copa. Monte uma nova estratégia!</p>";
        }
        echo "<br><a href='draft.php' class='btn-primary' style='display: inline-block; max-width: 250px; margin: 0 auto;'>Iniciar Novo Draft</a>";
        echo "<a href='dashboard.php' class='btn-secondary' style='display: inline-block; max-width: 200px; margin: 15px auto 0 auto;'>Voltar ao Painel</a>";
        echo "</div>";
        
        require_once __DIR__ . '/includes/footer.php';
        exit;
    } else {
        header('Location: draft.php');
        exit;
    }
}

// Load user roster details (OVR & key player)
$user_overall = 75;
$best_player = null;
if (!empty($tournament['players_json'])) {
    $p_ids = array_values(json_decode($tournament['players_json'], true) ?: []);
    if (!empty($p_ids)) {
        $ph = implode(',', array_fill(0, count($p_ids), '?'));
        $pstmt = $pdo->prepare("SELECT * FROM players WHERE id IN ($ph) ORDER BY overall DESC");
        $pstmt->execute($p_ids);
        $roster = $pstmt->fetchAll();
        if (!empty($roster)) {
            $sum = 0;
            foreach ($roster as $rp) { $sum += $rp['overall']; }
            $user_overall = round($sum / count($roster));
            $best_player = $roster[0];
        }
    }
}

// TOURNAMENT TREE SEEDING ENGINE
$tournament_id = intval($tournament['id']);
srand($tournament_id);

$opponents_pool = [
    'Flamengo', 'Palmeiras', 'São Paulo', 'Corinthians', 'Grêmio', 
    'Internacional', 'Vasco', 'Fluminense', 'Botafogo', 'Cruzeiro', 
    'Atlético-MG', 'Bahia', 'Fortaleza', 'Athletico-PR', 'Santos',
    'Sport Recife', 'Ceará', 'Goiás', 'Coritiba', 'Novorizontino'
];

// Shuffle pool deterministically based on seed
shuffle($opponents_pool);

// The 16 teams participating in the Round of 16
$teams = array_merge([$tournament['team_name']], array_slice($opponents_pool, 0, 15));

// Map matches deterministically
// Round 1: Oitavas (8 Matches, 16 Teams)
$oitavas = [];
for ($i = 0; $i < 8; $i++) {
    $teamA = $teams[$i * 2];
    $teamB = $teams[$i * 2 + 1];
    
    $scoreA = rand(0, 3);
    $scoreB = rand(0, 3);
    if ($scoreA === $scoreB) {
        $scoreA += (rand(0, 1) === 0) ? 1 : 0;
        if ($scoreA === $scoreB) $scoreB += 1;
    }
    
    // User is always in Match 1 (index 0 vs index 1)
    if ($i === 0) {
        $user_won_stage1 = ($tournament['current_stage'] > 1);
        $user_eliminated_stage1 = ($tournament['status'] === 'eliminated' && $tournament['current_stage'] === 1);
        
        if ($user_won_stage1) {
            $scoreA = 2; $scoreB = 1;
            $winner = $teamA;
        } elseif ($user_eliminated_stage1) {
            $scoreA = 0; $scoreB = 2;
            $winner = $teamB;
        } else {
            $scoreA = '-'; $scoreB = '-';
            $winner = null;
        }
    } else {
        $winner = ($scoreA > $scoreB) ? $teamA : $teamB;
    }

    $oitavas[$i] = [
        'teamA' => $teamA,
        'teamB' => $teamB,
        'scoreA' => $scoreA,
        'scoreB' => $scoreB,
        'winner' => $winner
    ];
}

// Round 2: Quartas (4 Matches, 8 Teams)
$quartas = [];
for ($i = 0; $i < 4; $i++) {
    $teamA = $oitavas[$i * 2]['winner'];
    $teamB = $oitavas[$i * 2 + 1]['winner'];
    
    $scoreA = rand(0, 3);
    $scoreB = rand(0, 3);
    if ($scoreA === $scoreB) {
        $scoreA += (rand(0, 1) === 0) ? 1 : 0;
        if ($scoreA === $scoreB) $scoreB += 1;
    }

    if ($i === 0) {
        if ($tournament['current_stage'] > 2) {
            $scoreA = 1; $scoreB = 0;
            $winner = $teamA;
        } elseif ($tournament['status'] === 'eliminated' && $tournament['current_stage'] === 2) {
            $scoreA = 1; $scoreB = 2;
            $winner = $teamB;
        } else {
            $scoreA = '-'; $scoreB = '-';
            $winner = null;
        }
    } else {
        $winner = ($teamA && $teamB) ? (($scoreA > $scoreB) ? $teamA : $teamB) : null;
    }

    $quartas[$i] = [
        'teamA' => $teamA,
        'teamB' => $teamB,
        'scoreA' => $teamA ? $scoreA : '-',
        'scoreB' => $teamB ? $scoreB : '-',
        'winner' => $winner
    ];
}

// Round 3: Semifinal (2 Matches, 4 Teams)
$semis = [];
for ($i = 0; $i < 2; $i++) {
    $teamA = $quartas[$i * 2]['winner'];
    $teamB = $quartas[$i * 2 + 1]['winner'];
    
    $scoreA = rand(0, 3);
    $scoreB = rand(0, 3);
    if ($scoreA === $scoreB) {
        $scoreA += (rand(0, 1) === 0) ? 1 : 0;
        if ($scoreA === $scoreB) $scoreB += 1;
    }

    if ($i === 0) {
        if ($tournament['current_stage'] > 3) {
            $scoreA = 3; $scoreB = 1;
            $winner = $teamA;
        } elseif ($tournament['status'] === 'eliminated' && $tournament['current_stage'] === 3) {
            $scoreA = 0; $scoreB = 1;
            $winner = $teamB;
        } else {
            $scoreA = '-'; $scoreB = '-';
            $winner = null;
        }
    } else {
        $winner = ($teamA && $teamB) ? (($scoreA > $scoreB) ? $teamA : $teamB) : null;
    }

    $semis[$i] = [
        'teamA' => $teamA,
        'teamB' => $teamB,
        'scoreA' => $teamA ? $scoreA : '-',
        'scoreB' => $teamB ? $scoreB : '-',
        'winner' => $winner
    ];
}

// Round 4: Final (1 Match, 2 Teams)
$teamA = $semis[0]['winner'];
$teamB = $semis[1]['winner'];
$scoreA = rand(0, 3);
$scoreB = rand(0, 3);
if ($scoreA === $scoreB) {
    $scoreA += (rand(0, 1) === 0) ? 1 : 0;
    if ($scoreA === $scoreB) $scoreB += 1;
}

if ($tournament['current_stage'] > 4) {
    $scoreA = 2; $scoreB = 0;
    $winner = $teamA;
} elseif ($tournament['status'] === 'eliminated' && $tournament['current_stage'] === 4) {
    $scoreA = 1; $scoreB = 2;
    $winner = $teamB;
} else {
    $scoreA = '-'; $scoreB = '-';
    $winner = null;
}

$final = [
    'teamA' => $teamA,
    'teamB' => $teamB,
    'scoreA' => $teamA ? $scoreA : '-',
    'scoreB' => $teamB ? $scoreB : '-',
    'winner' => $winner
];

// Determine user's active stage details
$stage_names = [
    1 => 'OITAVAS DE FINAL',
    2 => 'QUARTAS DE FINAL',
    3 => 'SEMIFINAIS',
    4 => 'GRANDE FINAL'
];

$current_stage_name = $stage_names[$tournament['current_stage']] ?? 'FINALIZADO';

// Find current opponent name
$current_opponent = '';
if ($tournament['current_stage'] === 1) {
    $current_opponent = $oitavas[0]['teamB'];
} elseif ($tournament['current_stage'] === 2) {
    $current_opponent = $quartas[0]['teamB'];
} elseif ($tournament['current_stage'] === 3) {
    $current_opponent = $semis[0]['teamB'];
} elseif ($tournament['current_stage'] === 4) {
    $current_opponent = $final['teamB'];
}

$stage_ovrs = [1 => 74, 2 => 78, 3 => 82, 4 => 86];
$opponent_overall = $stage_ovrs[$tournament['current_stage']] ?? 75;

// Reset seeding
srand();

$competition_name = 'COPA DO BRASIL';
$competition_logo = getCopaDoBrasilLogoSvg(46, 46);
$user_crest = getTeamBadgeSvg($tournament['team_name'], $tournament['kit_color'], 76);
$opponent_crest = getTeamBadgeSvg($current_opponent, '#ef4444', 76);
?>

<div style="margin-bottom: 25px; animation: fadeIn 0.4s ease-in-out;">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
        <div style="display: flex; align-items: center; gap: 14px;">
            <?php echo $competition_logo; ?>
            <div>
                <h1 class="tournament-title" style="text-align: left; margin: 0; background: linear-gradient(to right, #ffd700, var(--primary)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; font-size: 1.9rem; font-weight: 900; letter-spacing: 1px;"><?php echo htmlspecialchars($competition_name); ?></h1>
                <p style="color: var(--text-muted); font-size: 0.9rem; margin-top: 2px;">Chaveamento oficial de mata-mata com decisão por pênaltis</p>
            </div>
        </div>
        <div style="display: flex; gap: 10px;">
            <button type="button" id="btn-abandon-tournament" class="btn-secondary" style="width: auto; padding: 10px 18px; background: rgba(239, 68, 68, 0.12); border-color: rgba(239, 68, 68, 0.4); color: #ff6b6b; font-weight: 700; font-size: 0.85rem;">🚪 Abandonar Copa</button>
            <a href="dashboard.php" class="btn-secondary" style="width: auto; padding: 10px 18px; font-size: 0.85rem;">Voltar ao Painel</a>
        </div>
    </div>
</div>

<script>
document.getElementById('btn-abandon-tournament')?.addEventListener('click', function() {
    if (confirm('Atenção: Tem certeza de que deseja abandonar esta Copa do Brasil? Seu time será desclassificado e você poderá iniciar um novo draft imediatamente.')) {
        this.innerText = 'Abandonando...';
        this.disabled = true;
        fetch('api/abandon_tournament.php', { method: 'POST' })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    window.location.href = data.redirect || 'draft.php';
                } else {
                    alert(data.error || 'Erro ao abandonar copa.');
                    this.innerText = '🚪 Abandonar Copa';
                    this.disabled = false;
                }
            })
            .catch(err => {
                alert('Erro na requisição.');
                this.innerText = '🚪 Abandonar Copa';
                this.disabled = false;
            });
    }
});
</script>

<!-- REDESIGNED PRE-MATCH SHOWDOWN ARENA CARD (MATCHING BROADCAST TV FEEL) -->
<div class="pre-match-card" style="margin-bottom: 45px; animation: fadeIn 0.5s ease-in-out;">
    <div class="pre-match-header">
        <div class="pre-match-badge"><?php echo $competition_name; ?></div>
        <div class="pre-match-stage-title"><?php echo $current_stage_name; ?></div>
        <div class="pre-match-sub">Partida única eliminatória • Decisão por pênaltis em caso de empate</div>
    </div>

    <div class="pre-match-clash">
        <!-- USER TEAM CARD -->
        <div class="clash-team home">
            <div class="clash-crest-wrapper" style="box-shadow: 0 0 25px rgba(0, 255, 102, 0.25);">
                <?php echo $user_crest; ?>
            </div>
            <h2 class="clash-team-name" style="color: #fff;"><?php echo htmlspecialchars($tournament['team_name']); ?></h2>
            <div class="clash-meta">
                <span class="clash-pill ovr">OVR <?php echo $user_overall; ?></span>
                <span class="clash-pill formation"><?php echo htmlspecialchars($tournament['formation'] ?? '4-3-3'); ?></span>
            </div>
            <?php if ($best_player): ?>
                <div class="clash-star">
                    ⭐ Destaque: <strong><?php echo htmlspecialchars($best_player['name']); ?></strong> (<?php echo $best_player['overall']; ?>)
                </div>
            <?php endif; ?>
        </div>

        <!-- VS CLASH CENTER BADGE -->
        <div class="clash-center">
            <div class="clash-vs-circle">VS</div>
            <div class="clash-status-pill">TEMPO REAL</div>
        </div>

        <!-- OPPONENT TEAM CARD -->
        <div class="clash-team away">
            <div class="clash-crest-wrapper" style="box-shadow: 0 0 25px rgba(239, 68, 68, 0.25);">
                <?php echo $opponent_crest; ?>
            </div>
            <h2 class="clash-team-name" style="color: #fff;"><?php echo htmlspecialchars($current_opponent); ?></h2>
            <div class="clash-meta">
                <span class="clash-pill ovr opponent">OVR <?php echo $opponent_overall; ?></span>
                <span class="clash-pill series">SÉRIE A</span>
            </div>
            <div class="clash-star opponent">
                🛡️ Adversário Tradicional • Fase Eliminatória
            </div>
        </div>
    </div>

    <!-- PRE-MATCH CTA ACTION -->
    <div class="pre-match-action">
        <a href="match.php" class="btn-primary btn-launch-match">
            <span class="btn-launch-icon">⚽</span>
            <span class="btn-launch-text">ENTRAR EM CAMPO</span>
            <span class="btn-launch-tag">SIMULAÇÃO 2D</span>
        </a>
    </div>
</div>

<!-- SLEEK TOURNAMENT BRACKET WITH CLUB CRESTS -->
<div class="bracket-section-header">
    <h2 style="font-size: 1.35rem; font-weight: 900; letter-spacing: 1px; text-transform: uppercase; margin: 0; color: #fff;">Chaveamento Oficial</h2>
    <span style="font-size: 0.8rem; color: var(--text-muted); font-weight: 600;">16 CLUBES • MATA-MATA</span>
</div>

<div class="bracket-container">
    <!-- COLUMN 1: OITAVAS -->
    <div class="bracket-column">
        <div class="bracket-header">Oitavas de Final</div>
        <?php foreach ($oitavas as $idx => $m): ?>
            <?php 
            $is_user_match = ($idx === 0);
            $winner_team = $m['winner'];
            $crestA = getTeamBadgeSvg($m['teamA'], ($m['teamA'] === $tournament['team_name'] ? $tournament['kit_color'] : '#38bdf8'), 24);
            $crestB = getTeamBadgeSvg($m['teamB'], ($m['teamB'] === $tournament['team_name'] ? $tournament['kit_color'] : '#ef4444'), 24);
            ?>
            <div class="match-box <?php echo ($is_user_match && $tournament['current_stage'] == 1) ? 'active' : ''; ?>">
                <div class="match-team <?php 
                    echo $is_user_match ? 'user-team ' : ''; 
                    echo ($winner_team === $m['teamA']) ? 'winner' : (($winner_team !== null) ? 'loser' : ''); 
                ?>">
                    <span class="m-team-content">
                        <span class="m-crest-mini"><?php echo $crestA; ?></span>
                        <span class="m-name"><?php echo htmlspecialchars($m['teamA']); ?></span>
                    </span>
                    <span class="match-score"><?php echo $m['scoreA']; ?></span>
                </div>
                <div class="match-team <?php 
                    echo ($winner_team === $m['teamB']) ? 'winner' : (($winner_team !== null) ? 'loser' : ''); 
                ?>">
                    <span class="m-team-content">
                        <span class="m-crest-mini"><?php echo $crestB; ?></span>
                        <span class="m-name"><?php echo htmlspecialchars($m['teamB']); ?></span>
                    </span>
                    <span class="match-score"><?php echo $m['scoreB']; ?></span>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- COLUMN 2: QUARTAS -->
    <div class="bracket-column">
        <div class="bracket-header">Quartas de Final</div>
        <?php foreach ($quartas as $idx => $m): ?>
            <?php 
            $is_user_match = ($idx === 0);
            $winner_team = $m['winner'];
            $crestA = $m['teamA'] ? getTeamBadgeSvg($m['teamA'], ($m['teamA'] === $tournament['team_name'] ? $tournament['kit_color'] : '#38bdf8'), 24) : '';
            $crestB = $m['teamB'] ? getTeamBadgeSvg($m['teamB'], ($m['teamB'] === $tournament['team_name'] ? $tournament['kit_color'] : '#ef4444'), 24) : '';
            ?>
            <div class="match-box <?php echo ($is_user_match && $tournament['current_stage'] == 2) ? 'active' : ''; ?>">
                <div class="match-team <?php 
                    echo ($is_user_match && $m['teamA']) ? 'user-team ' : ''; 
                    echo ($winner_team === $m['teamA'] && $m['teamA']) ? 'winner' : (($winner_team !== null) ? 'loser' : ''); 
                ?>">
                    <span class="m-team-content">
                        <?php if ($crestA): ?><span class="m-crest-mini"><?php echo $crestA; ?></span><?php endif; ?>
                        <span class="m-name"><?php echo $m['teamA'] ? htmlspecialchars($m['teamA']) : 'A definir'; ?></span>
                    </span>
                    <span class="match-score"><?php echo $m['scoreA']; ?></span>
                </div>
                <div class="match-team <?php 
                    echo ($winner_team === $m['teamB'] && $m['teamB']) ? 'winner' : (($winner_team !== null) ? 'loser' : ''); 
                ?>">
                    <span class="m-team-content">
                        <?php if ($crestB): ?><span class="m-crest-mini"><?php echo $crestB; ?></span><?php endif; ?>
                        <span class="m-name"><?php echo $m['teamB'] ? htmlspecialchars($m['teamB']) : 'A definir'; ?></span>
                    </span>
                    <span class="match-score"><?php echo $m['scoreB']; ?></span>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- COLUMN 3: SEMIFINAIS -->
    <div class="bracket-column">
        <div class="bracket-header">Semifinais</div>
        <?php foreach ($semis as $idx => $m): ?>
            <?php 
            $is_user_match = ($idx === 0);
            $winner_team = $m['winner'];
            $crestA = $m['teamA'] ? getTeamBadgeSvg($m['teamA'], ($m['teamA'] === $tournament['team_name'] ? $tournament['kit_color'] : '#38bdf8'), 24) : '';
            $crestB = $m['teamB'] ? getTeamBadgeSvg($m['teamB'], ($m['teamB'] === $tournament['team_name'] ? $tournament['kit_color'] : '#ef4444'), 24) : '';
            ?>
            <div class="match-box <?php echo ($is_user_match && $tournament['current_stage'] == 3) ? 'active' : ''; ?>">
                <div class="match-team <?php 
                    echo ($is_user_match && $m['teamA']) ? 'user-team ' : ''; 
                    echo ($winner_team === $m['teamA'] && $m['teamA']) ? 'winner' : (($winner_team !== null) ? 'loser' : ''); 
                ?>">
                    <span class="m-team-content">
                        <?php if ($crestA): ?><span class="m-crest-mini"><?php echo $crestA; ?></span><?php endif; ?>
                        <span class="m-name"><?php echo $m['teamA'] ? htmlspecialchars($m['teamA']) : 'A definir'; ?></span>
                    </span>
                    <span class="match-score"><?php echo $m['scoreA']; ?></span>
                </div>
                <div class="match-team <?php 
                    echo ($winner_team === $m['teamB'] && $m['teamB']) ? 'winner' : (($winner_team !== null) ? 'loser' : ''); 
                ?>">
                    <span class="m-team-content">
                        <?php if ($crestB): ?><span class="m-crest-mini"><?php echo $crestB; ?></span><?php endif; ?>
                        <span class="m-name"><?php echo $m['teamB'] ? htmlspecialchars($m['teamB']) : 'A definir'; ?></span>
                    </span>
                    <span class="match-score"><?php echo $m['scoreB']; ?></span>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- COLUMN 4: GRANDE FINAL -->
    <div class="bracket-column" style="justify-content: center;">
        <div class="bracket-header">Grande Final</div>
        <?php 
        $winner_team = $final['winner'];
        $crestA = $final['teamA'] ? getTeamBadgeSvg($final['teamA'], ($final['teamA'] === $tournament['team_name'] ? $tournament['kit_color'] : '#38bdf8'), 28) : '';
        $crestB = $final['teamB'] ? getTeamBadgeSvg($final['teamB'], ($final['teamB'] === $tournament['team_name'] ? $tournament['kit_color'] : '#ef4444'), 28) : '';
        ?>
        <div class="match-box <?php echo ($tournament['current_stage'] == 4) ? 'active' : ''; ?>">
            <div class="match-team <?php 
                echo ($final['teamA']) ? 'user-team ' : ''; 
                echo ($winner_team === $final['teamA'] && $final['teamA']) ? 'winner' : (($winner_team !== null) ? 'loser' : ''); 
            ?>">
                <span class="m-team-content">
                    <?php if ($crestA): ?><span class="m-crest-mini"><?php echo $crestA; ?></span><?php endif; ?>
                    <span class="m-name"><?php echo $final['teamA'] ? htmlspecialchars($final['teamA']) : 'A definir'; ?></span>
                </span>
                <span class="match-score"><?php echo $final['scoreA']; ?></span>
            </div>
            <div class="match-team <?php 
                echo ($winner_team === $final['teamB'] && $final['teamB']) ? 'winner' : (($winner_team !== null) ? 'loser' : ''); 
            ?>">
                <span class="m-team-content">
                    <?php if ($crestB): ?><span class="m-crest-mini"><?php echo $crestB; ?></span><?php endif; ?>
                    <span class="m-name"><?php echo $final['teamB'] ? htmlspecialchars($final['teamB']) : 'A definir'; ?></span>
                </span>
                <span class="match-score"><?php echo $final['scoreB']; ?></span>
            </div>
        </div>

        <?php if ($tournament['current_stage'] == 5): ?>
            <div style="text-align: center; margin-top: 20px; font-size: 2.8rem; animation: bounce 1.5s infinite;">🏆</div>
        <?php endif; ?>
    </div>
</div>

<style>
/* PRE-MATCH SHOWDOWN STYLING */
.pre-match-card {
    background: linear-gradient(180deg, rgba(22, 31, 51, 0.95) 0%, rgba(13, 19, 33, 0.98) 100%);
    border: 1px solid var(--border-light);
    border-radius: 20px;
    padding: 30px;
    box-shadow: 0 15px 45px rgba(0, 0, 0, 0.6);
    position: relative;
    overflow: hidden;
}

.pre-match-card::before {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0; height: 3px;
    background: linear-gradient(90deg, var(--primary), #ffd700, #38bdf8);
}

.pre-match-header {
    text-align: center;
    margin-bottom: 25px;
}

.pre-match-badge {
    display: inline-block;
    background: rgba(255, 215, 0, 0.12);
    border: 1px solid rgba(255, 215, 0, 0.35);
    color: #ffd700;
    font-size: 0.75rem;
    font-weight: 900;
    letter-spacing: 2px;
    padding: 4px 14px;
    border-radius: 20px;
    margin-bottom: 6px;
    text-transform: uppercase;
}

.pre-match-stage-title {
    font-size: 2rem;
    font-weight: 900;
    letter-spacing: 1.5px;
    color: #fff;
    line-height: 1.1;
}

.pre-match-sub {
    font-size: 0.85rem;
    color: var(--text-muted);
    margin-top: 5px;
}

.pre-match-clash {
    display: grid;
    grid-template-columns: 1fr auto 1fr;
    align-items: center;
    gap: 25px;
    margin: 20px 0 30px;
}

.clash-team {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    padding: 20px;
    background: rgba(255, 255, 255, 0.02);
    border-radius: 16px;
    border: 1px solid rgba(255, 255, 255, 0.05);
}

.clash-team.home {
    border-left: 3px solid var(--primary);
}

.clash-team.away {
    border-right: 3px solid #ef4444;
}

.clash-crest-wrapper {
    width: 86px;
    height: 86px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background: rgba(0, 0, 0, 0.4);
    margin-bottom: 12px;
    transition: transform 0.3s ease;
}

.clash-team:hover .clash-crest-wrapper {
    transform: scale(1.06);
}

.clash-team-name {
    font-size: 1.45rem;
    font-weight: 900;
    margin-bottom: 8px;
    letter-spacing: 0.5px;
}

.clash-meta {
    display: flex;
    gap: 8px;
    margin-bottom: 10px;
}

.clash-pill {
    padding: 3px 10px;
    border-radius: 12px;
    font-size: 0.75rem;
    font-weight: 800;
    letter-spacing: 0.5px;
}

.clash-pill.ovr {
    background: rgba(0, 255, 102, 0.15);
    color: var(--primary);
    border: 1px solid rgba(0, 255, 102, 0.3);
}

.clash-pill.ovr.opponent {
    background: rgba(239, 68, 68, 0.15);
    color: #f87171;
    border: 1px solid rgba(239, 68, 68, 0.3);
}

.clash-pill.formation, .clash-pill.series {
    background: rgba(255, 255, 255, 0.06);
    color: var(--text-muted);
}

.clash-star {
    font-size: 0.8rem;
    color: #ffd700;
    margin-top: 4px;
}

.clash-star.opponent {
    color: #94a3b8;
}

.clash-center {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
}

.clash-vs-circle {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: linear-gradient(135deg, #ffd700, #e5a910);
    color: #07101e;
    font-size: 1.4rem;
    font-weight: 900;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 0 25px rgba(255, 215, 0, 0.45);
    letter-spacing: 1px;
}

.clash-status-pill {
    font-size: 0.65rem;
    font-weight: 800;
    letter-spacing: 1.5px;
    color: var(--primary);
    background: rgba(0, 255, 102, 0.1);
    padding: 2px 8px;
    border-radius: 10px;
}

.pre-match-action {
    display: flex;
    justify-content: center;
}

.btn-launch-match {
    display: inline-flex;
    align-items: center;
    gap: 12px;
    padding: 16px 40px;
    border-radius: 14px;
    font-size: 1.15rem;
    font-weight: 900;
    letter-spacing: 1px;
    text-transform: uppercase;
    text-decoration: none;
    box-shadow: 0 6px 30px rgba(0, 255, 102, 0.4);
    animation: pulse 1.8s infinite;
}

.btn-launch-icon {
    font-size: 1.4rem;
}

.btn-launch-tag {
    font-size: 0.7rem;
    background: rgba(0, 0, 0, 0.3);
    padding: 2px 8px;
    border-radius: 8px;
    letter-spacing: 1px;
}

.bracket-section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid var(--border-light);
    padding-bottom: 10px;
    margin-bottom: 20px;
}

.m-team-content {
    display: flex;
    align-items: center;
    gap: 8px;
    overflow: hidden;
}

.m-crest-mini {
    width: 22px;
    height: 22px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.m-name {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    font-size: 0.82rem;
}

.match-box.active {
    border-color: var(--primary) !important;
    box-shadow: 0 0 20px rgba(0, 255, 102, 0.35) !important;
}

@keyframes pulse {
    0% { transform: scale(1); box-shadow: 0 4px 18px rgba(0, 255, 102, 0.4); }
    50% { transform: scale(1.025); box-shadow: 0 8px 30px rgba(0, 255, 102, 0.65); }
    100% { transform: scale(1); box-shadow: 0 4px 18px rgba(0, 255, 102, 0.4); }
}

@keyframes bounce {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-10px); }
}

@media (max-width: 860px) {
    .pre-match-clash {
        grid-template-columns: 1fr;
        gap: 15px;
    }
}
</style>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
