<?php
/**
 * GOLAÇO - Dashboard (Main Screen)
 */

require_once __DIR__ . '/includes/header.php';

// Check if user has an active Copa do Brasil tournament
try {
    $stmt = $pdo->prepare("SELECT * FROM tournaments WHERE user_id = :user_id AND status = 'active' LIMIT 1");
    $stmt->execute([':user_id' => $_SESSION['user_id']]);
    $active_tournament = $stmt->fetch();
} catch (PDOException $e) {
    $active_tournament = null;
}
?>

<div style="margin-bottom: 40px; animation: fadeIn 0.5s ease-in-out;">
    <h1 style="font-size: 2.2rem; margin-bottom: 5px;">Painel de Controle</h1>
    <p style="color: var(--text-muted);">Bem-vindo ao GOLAÇO, Técnico <?php echo htmlspecialchars($_SESSION['username']); ?>! O que quer fazer hoje?</p>
</div>

<?php
$card_style = "";
if (!empty($current_user['dashboard_card_bg'])) {
    $bg_url = htmlspecialchars($current_user['dashboard_card_bg']);
    $card_style = "background-image: linear-gradient(135deg, rgba(22, 31, 51, 0.85) 0%, rgba(13, 19, 33, 0.9) 100%), url('{$bg_url}'); background-size: cover; background-position: center;";
}
?>

<div class="dashboard-grid" style="grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));">
    <!-- DRAFT CARD -->
    <div class="dash-card" style="<?php echo $card_style; ?>">
        <div>
            <div style="font-size: 3rem; margin-bottom: 15px; text-shadow: 0 0 10px rgba(75, 224, 123, 0.45);">📋</div>
            <h2>Draft do Brasileirão</h2>
            <?php if ($active_tournament): ?>
                <p>Você tem um time ativo disputando a Copa do Brasil (<strong><?php echo htmlspecialchars($active_tournament['team_name']); ?></strong>). Retome a competição e conquiste a glória!</p>
            <?php else: ?>
                <p>Escolha sua tática, defina o uniforme, dê o nome ao seu clube e sorteie cartas de jogadores da Série A e B do Brasileirão para montar um time invencível.</p>
            <?php endif; ?>
        </div>
        
        <?php if ($active_tournament): ?>
            <a href="tournament.php" class="btn-primary" style="margin-top: 15px;">Continuar Copa do Brasil</a>
        <?php else: ?>
            <a href="draft.php" class="btn-primary" style="margin-top: 15px;">Iniciar Novo Draft</a>
        <?php endif; ?>
    </div>

    <!-- MY TEAM CARD (DEACTIVATED) -->
    <div class="dash-card disabled" style="<?php echo $card_style; ?>">
        <div>
            <div style="font-size: 3rem; margin-bottom: 15px; text-shadow: 0 0 10px rgba(75, 224, 123, 0.45);">🛡️</div>
            <h2>Meu Time</h2>
            <p>Monte seu elenco permanente com os jogadores que você comprou e crie uma identidade de clube de longo prazo.</p>
        </div>
        <button class="btn-primary" style="cursor: not-allowed; margin-top: 15px;" disabled>Em Breve 🔒</button>
    </div>

    <!-- LOJA CARD (DEACTIVATED) -->
    <div class="dash-card disabled" style="<?php echo $card_style; ?>">
        <div>
            <div style="font-size: 3rem; margin-bottom: 15px; text-shadow: 0 0 10px rgba(75, 224, 123, 0.45);">🛒</div>
            <h2>Loja GOLAÇO</h2>
            <p>Compre pacotes de cartas de jogadores lendários, itens cosméticos exclusivos para seus botões e estádios personalizados.</p>
        </div>
        <button class="btn-primary" style="cursor: not-allowed; margin-top: 15px;" disabled>Em Breve 🔒</button>
    </div>

    <!-- RANKING CARD (DEACTIVATED) -->
    <div class="dash-card disabled" style="<?php echo $card_style; ?>">
        <div>
            <div style="font-size: 3rem; margin-bottom: 15px; text-shadow: 0 0 10px rgba(75, 224, 123, 0.45);">🏆</div>
            <h2>Ranking Geral</h2>
            <p>Dispute a liderança com outros técnicos do GOLAÇO, suba nas divisões de prestígio e ganhe recompensas ao fim da temporada.</p>
        </div>
        <button class="btn-primary" style="cursor: not-allowed; margin-top: 15px;" disabled>Em Breve 🔒</button>
    </div>
</div>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
