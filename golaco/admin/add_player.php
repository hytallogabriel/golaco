<?php
/**
 * GOLAÇO - Admin Add Player Card
 */

require_once __DIR__ . '/../includes/header.php';

// Verify admin role
if ($_SESSION['role'] !== 'admin') {
    header('Location: ../dashboard.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $overall = intval($_POST['overall'] ?? 0);
    $position = $_POST['position'] ?? '';
    $series = $_POST['series'] ?? '';
    $image_path = null;

    // Validation
    if (empty($name) || $overall < 1 || $overall > 99 || empty($position) || empty($series)) {
        $error = 'Por favor, preencha todos os campos corretamente. O Overall deve ser entre 1 e 99.';
    } else {
        try {
            // Process Image Upload
            if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                $file_tmp = $_FILES['image']['tmp_name'];
                $file_name = $_FILES['image']['name'];
                $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

                // Allowed extension
                $allowed = ['png', 'jpg', 'jpeg', 'webp'];

                if (!in_array($file_ext, $allowed)) {
                    $error = 'Apenas imagens PNG, JPG, JPEG ou WEBP são aceitas.';
                } else {
                    $uploads_dir = __DIR__ . '/../uploads';
                    if (!is_dir($uploads_dir)) {
                        mkdir($uploads_dir, 0777, true);
                    }

                    // Generate safe unique filename
                    $new_file_name = time() . '_' . preg_replace("/[^a-zA-Z0-9.]/", "_", pathinfo($file_name, PATHINFO_FILENAME)) . '.' . $file_ext;
                    $dest_path = $uploads_dir . '/' . $new_file_name;

                    if (move_uploaded_file($file_tmp, $dest_path)) {
                        $image_path = 'uploads/' . $new_file_name;
                    } else {
                        $error = 'Erro ao salvar a imagem enviada.';
                    }
                }
            }

            if (empty($error)) {
                // Insert player
                $stmt = $pdo->prepare("INSERT INTO players (name, overall, position, series, image_path) VALUES (:name, :overall, :position, :series, :image_path)");
                $stmt->execute([
                    ':name' => $name,
                    ':overall' => $overall,
                    ':position' => $position,
                    ':series' => $series,
                    ':image_path' => $image_path
                ]);

                $_SESSION['admin_success'] = "Carta do jogador '{$name}' criada com sucesso!";
                header('Location: index.php');
                exit;
            }

        } catch (PDOException $e) {
            $error = 'Erro ao salvar no banco: ' . $e->getMessage();
        }
    }
}
?>

<div class="admin-layout">
    <!-- Sidebar Navigation -->
    <aside class="admin-sidebar">
        <h3 style="margin-bottom: 20px; text-transform: uppercase; font-size: 0.9rem; color: var(--text-muted); letter-spacing: 1px;">Menu Admin</h3>
        <ul class="admin-sidebar-menu">
            <li class="admin-sidebar-item">
                <a href="index.php" class="admin-sidebar-link">
                    <span>🃏</span> Ver Cartas
                </a>
            </li>
            <li class="admin-sidebar-item">
                <a href="add_player.php" class="admin-sidebar-link active">
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
                <h1 style="font-size: 1.8rem;">Criar Nova Carta Golaço</h1>
                <p style="color: var(--text-muted); font-size: 0.9rem; margin-top: 4px;">Insira os dados do atleta. A carta será renderizada com visual correspondente ao overall.</p>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" action="" enctype="multipart/form-data" style="max-width: 600px;">
            <div class="form-group">
                <label for="name">Nome do Jogador</label>
                <input type="text" id="name" name="name" class="form-control" placeholder="Ex: Hulk, Arrascaeta, Estêvão" required value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div class="form-group">
                    <label for="overall">Overall (Rating)</label>
                    <input type="number" id="overall" name="overall" class="form-control" placeholder="1 a 99" min="1" max="99" required value="<?php echo htmlspecialchars($_POST['overall'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label for="position">Posição Específica</label>
                    <select id="position" name="position" class="form-control select-control" required>
                        <option value="" disabled selected>Escolha a posição</option>
                        <option value="GK" <?php echo (($_POST['position'] ?? '') === 'GK') ? 'selected' : ''; ?>>GK (Goleiro)</option>
                        <option value="CB" <?php echo (($_POST['position'] ?? '') === 'CB') ? 'selected' : ''; ?>>CB (Zagueiro)</option>
                        <option value="LB" <?php echo (($_POST['position'] ?? '') === 'LB') ? 'selected' : ''; ?>>LB (Lateral Esquerdo)</option>
                        <option value="RB" <?php echo (($_POST['position'] ?? '') === 'RB') ? 'selected' : ''; ?>>RB (Lateral Direito)</option>
                        <option value="CM" <?php echo (($_POST['position'] ?? '') === 'CM') ? 'selected' : ''; ?>>CM (Meio-Campo)</option>
                        <option value="LM" <?php echo (($_POST['position'] ?? '') === 'LM') ? 'selected' : ''; ?>>LM (Meia Esquerda)</option>
                        <option value="RM" <?php echo (($_POST['position'] ?? '') === 'RM') ? 'selected' : ''; ?>>RM (Meia Direita)</option>
                        <option value="ST" <?php echo (($_POST['position'] ?? '') === 'ST') ? 'selected' : ''; ?>>ST (Centroavante)</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="series">Série do Brasileirão</label>
                <select id="series" name="series" class="form-control select-control" required>
                    <option value="" disabled selected>Escolha a série</option>
                    <option value="A" <?php echo (($_POST['series'] ?? '') === 'A') ? 'selected' : ''; ?>>Série A</option>
                    <option value="B" <?php echo (($_POST['series'] ?? '') === 'B') ? 'selected' : ''; ?>>Série B</option>
                </select>
            </div>

            <div class="form-group">
                <label>Foto do Jogador (PNG Transparente Recomendado)</label>
                <div class="file-upload-wrapper">
                    <input type="file" name="image" class="file-upload-input" accept="image/*" onchange="updateFileLabel(this)">
                    <div class="file-upload-trigger">
                        <div class="icon">📤</div>
                        <span id="file-label">Arraste a foto ou clique para fazer upload</span>
                    </div>
                </div>
                <small style="color: var(--text-muted); display: block; margin-top: 5px;">A foto será centralizada no fundo da carta gerada pelo site.</small>
            </div>

            <button type="submit" class="btn-primary" style="margin-top: 30px;">Salvar Carta do Jogador</button>
        </form>
    </section>
</div>

<script>
function updateFileLabel(input) {
    const label = document.getElementById('file-label');
    if (input.files && input.files[0]) {
        label.innerText = input.files[0].name + " selecionado";
        label.style.color = "var(--primary)";
    } else {
        label.innerText = "Arraste a foto ou clique para fazer upload";
        label.style.color = "var(--text-muted)";
    }
}
</script>

<?php
require_once __DIR__ . '/../includes/footer.php';
?>
