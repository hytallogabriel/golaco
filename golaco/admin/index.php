<?php
/**
 * GOLAÇO - Admin Panel (Cards Management)
 */

require_once __DIR__ . '/../includes/header.php';

// Verify admin role
if ($_SESSION['role'] !== 'admin') {
    header('Location: ../dashboard.php');
    exit;
}

// Fetch all players
try {
    $stmt = $pdo->query("SELECT * FROM players ORDER BY overall DESC, name ASC");
    $players = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Erro ao buscar jogadores: " . $e->getMessage());
}

$success = $_SESSION['admin_success'] ?? '';
$error = $_SESSION['admin_error'] ?? '';
unset($_SESSION['admin_success'], $_SESSION['admin_error']);
?>

<div class="admin-layout">
    <!-- Sidebar Navigation -->
    <aside class="admin-sidebar">
        <h3 style="margin-bottom: 20px; text-transform: uppercase; font-size: 0.9rem; color: var(--text-muted); letter-spacing: 1px;">Menu Admin</h3>
        <ul class="admin-sidebar-menu">
            <li class="admin-sidebar-item">
                <a href="index.php" class="admin-sidebar-link active">
                    <span>🃏</span> Ver Cartas
                </a>
            </li>
            <li class="admin-sidebar-item">
                <a href="add_player.php" class="admin-sidebar-link">
                    <span>➕</span> Criar Nova Carta
                </a>
            </li>
            <li class="admin-sidebar-item" style="margin-top: 20px;">
                <a href="../dashboard.php" class="admin-sidebar-link" style="border: 1px solid rgba(255,255,255,0.08); background: rgba(255,255,255,0.01);">
                    <span>↩️</span> Voltar ao Jogo
                </a>
            </li>
        </ul>
    </aside>

    <!-- Main Content Area -->
    <section class="admin-content">
        <div class="admin-title-row">
            <div>
                <h1 style="font-size: 1.8rem;">Banco de Dados de Cartas</h1>
                <p style="color: var(--text-muted); font-size: 0.9rem; margin-top: 4px;">Total: <?php echo count($players); ?> jogadores cadastrados no Brasileirão</p>
            </div>
            <a href="add_player.php" class="btn-primary" style="width: auto; padding: 10px 20px;">+ Nova Carta</a>
        </div>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <!-- Search Filter -->
        <div style="margin-bottom: 30px;">
            <input type="text" id="search-card" class="form-control" placeholder="🔍 Pesquise por nome do jogador, posição ou série..." style="max-width: 400px;">
        </div>

        <!-- Cards Grid -->
        <div class="cards-grid-admin" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(130px, 1fr)); gap: 20px;" id="cards-container">
            <?php foreach ($players as $p): ?>
                <?php
                 // Determine card rank class
                 $card_rank = 'bronze';
                 if ($p['overall'] >= 90) {
                     $card_rank = 'super';
                 } elseif ($p['overall'] >= 80) {
                     $card_rank = 'gold';
                 } elseif ($p['overall'] >= 70) {
                     $card_rank = 'silver';
                 }
                 ?>
                <div class="card-item-wrapper" style="display: flex; flex-direction: column; align-items: center; text-align: center; background: rgba(255,255,255,0.01); padding: 10px; border-radius: 12px; border: 1px solid var(--border-light);" 
                     data-name="<?php echo htmlspecialchars(strtolower($p['name'])); ?>"
                     data-pos="<?php echo htmlspecialchars(strtolower($p['position'])); ?>"
                     data-series="<?php echo htmlspecialchars(strtolower($p['series'])); ?>">
                    
                    <!-- Dynamic Card Component -->
                    <?php
                    $card_class = $card_rank;
                    if ($p['image_path']) {
                        $card_class .= ' with-image';
                    }
                    ?>
                    <div class="g-card <?php echo $card_class; ?>" style="--card-bg-image: url('../<?php echo htmlspecialchars($p['image_path']); ?>')">
                        <div class="card-top">
                            <div class="card-rating">
                                <span class="card-ovr"><?php echo $p['overall']; ?></span>
                                <span class="card-pos"><?php echo $p['position']; ?></span>
                            </div>
                            <span class="card-series"><?php echo $p['series']; ?></span>
                        </div>

                        <?php if ($p['image_path']): ?>
                            <div class="card-player-img" style="background-image: url('../<?php echo htmlspecialchars($p['image_path']); ?>');"></div>
                        <?php else: ?>
                            <div class="card-player-img" style="background-image: url('../assets/images/placeholder-player.png'); opacity: 0.3;"></div>
                        <?php endif; ?>

                        <div class="card-name-container">
                            <span class="card-name"><?php echo htmlspecialchars($p['name']); ?></span>
                        </div>
                    </div>

                    <a href="delete_player.php?id=<?php echo $p['id']; ?>" class="link-delete" style="margin-top: 10px; font-weight: 700; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.5px;" onclick="return confirm('Tem certeza que deseja excluir esta carta?');">Excluir</a>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
</div>

<script>
// Instantly filter cards by name, position, or series
document.getElementById('search-card').addEventListener('input', function(e) {
    const query = e.target.value.toLowerCase().trim();
    const items = document.querySelectorAll('.card-item-wrapper');

    items.forEach(function(item) {
        const name = item.getAttribute('data-name');
        const pos = item.getAttribute('data-pos');
        const series = item.getAttribute('data-series');

        if (name.includes(query) || pos.includes(query) || series.includes(query)) {
            item.style.display = 'flex';
        } else {
            item.style.display = 'none';
        }
    });
});
</script>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>