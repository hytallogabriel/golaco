<?php
/**
 * GOLAÇO - User Profile Screen
 */

require_once __DIR__ . '/includes/header.php';

$error = '';
$success = '';

// Re-fetch current user details to get display_name, bio, avatar_path
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
    $stmt->execute([':id' => $_SESSION['user_id']]);
    $user_data = $stmt->fetch();
} catch (PDOException $e) {
    die("Erro ao carregar dados do perfil: " . $e->getMessage());
}

// Fetch tournament statistics
try {
    // Total played
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM tournaments WHERE user_id = :user_id");
    $stmt->execute([':user_id' => $_SESSION['user_id']]);
    $tournaments_played = $stmt->fetchColumn();

    // Total won
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM tournaments WHERE user_id = :user_id AND status = 'completed'");
    $stmt->execute([':user_id' => $_SESSION['user_id']]);
    $tournaments_won = $stmt->fetchColumn();
} catch (PDOException $e) {
    $tournaments_played = 0;
    $tournaments_won = 0;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $display_name = trim($_POST['display_name'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    $avatar_path = $user_data['avatar_path'];
    $dashboard_card_bg = $user_data['dashboard_card_bg'];

    // Handle profile picture upload
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['avatar']['tmp_name'];
        $file_name = $_FILES['avatar']['name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed = ['png', 'jpg', 'jpeg', 'webp'];

        if (!in_array($file_ext, $allowed)) {
            $error = 'Apenas imagens PNG, JPG, JPEG ou WEBP são aceitas para o avatar.';
        } else {
            $uploads_dir = __DIR__ . '/uploads';
            if (!is_dir($uploads_dir)) {
                mkdir($uploads_dir, 0777, true);
            }

            // Safe unique filename
            $new_file_name = 'avatar_' . $_SESSION['user_id'] . '_' . time() . '.' . $file_ext;
            $dest_path = $uploads_dir . '/' . $new_file_name;

            // Delete old avatar file if exists
            if (!empty($user_data['avatar_path'])) {
                $old_file = __DIR__ . '/' . $user_data['avatar_path'];
                if (file_exists($old_file) && is_file($old_file)) {
                    unlink($old_file);
                }
            }

            if (move_uploaded_file($file_tmp, $dest_path)) {
                $avatar_path = 'uploads/' . $new_file_name;
            } else {
                $error = 'Erro ao salvar a foto de perfil.';
            }
        }
    }

    // Handle dashboard card background upload
    if (empty($error) && isset($_FILES['dashboard_card_bg']) && $_FILES['dashboard_card_bg']['error'] === UPLOAD_ERR_OK) {
        $file_tmp = $_FILES['dashboard_card_bg']['tmp_name'];
        $file_name = $_FILES['dashboard_card_bg']['name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed = ['png', 'jpg', 'jpeg', 'webp'];

        if (!in_array($file_ext, $allowed)) {
            $error = 'Apenas imagens PNG, JPG, JPEG ou WEBP são aceitas para o fundo do card.';
        } else {
            $uploads_dir = __DIR__ . '/uploads';
            if (!is_dir($uploads_dir)) {
                mkdir($uploads_dir, 0777, true);
            }

            // Safe unique filename
            $new_file_name = 'card_bg_' . $_SESSION['user_id'] . '_' . time() . '.' . $file_ext;
            $dest_path = $uploads_dir . '/' . $new_file_name;

            // Delete old background file if exists
            if (!empty($user_data['dashboard_card_bg'])) {
                $old_file = __DIR__ . '/' . $user_data['dashboard_card_bg'];
                if (file_exists($old_file) && is_file($old_file)) {
                    unlink($old_file);
                }
            }

            if (move_uploaded_file($file_tmp, $dest_path)) {
                $dashboard_card_bg = 'uploads/' . $new_file_name;
            } else {
                $error = 'Erro ao salvar a imagem de fundo do card.';
            }
        }
    }

    if (empty($error)) {
        try {
            // Update users table
            $stmt = $pdo->prepare("UPDATE users SET display_name = :display_name, bio = :bio, avatar_path = :avatar_path, dashboard_card_bg = :dashboard_card_bg WHERE id = :id");
            $stmt->execute([
                ':display_name' => $display_name,
                ':bio' => $bio,
                ':avatar_path' => $avatar_path,
                ':dashboard_card_bg' => $dashboard_card_bg,
                ':id' => $_SESSION['user_id']
            ]);

            $success = 'Perfil atualizado com sucesso!';
            
            // Re-fetch to display updated data
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
            $stmt->execute([':id' => $_SESSION['user_id']]);
            $user_data = $stmt->fetch();
        } catch (PDOException $e) {
            $error = 'Erro ao atualizar dados: ' . $e->getMessage();
        }
    }
}
?>

<div style="margin-bottom: 30px; animation: fadeIn 0.4s ease-in-out;">
    <h1 style="font-size: 2.2rem; margin-bottom: 5px;">Meu Perfil</h1>
    <p style="color: var(--text-muted); font-size: 0.95rem;">Visualize sua carreira de técnico e customize sua identidade</p>
</div>

<div style="display: grid; grid-template-columns: 1fr 340px; gap: 30px; align-items: start; animation: fadeIn 0.5s ease-in-out;">
    <!-- LEFT PANEL: EDIT FORM -->
    <div class="auth-card" style="max-width: 100%; padding: 30px; border-radius: 16px;">
        <h2 style="font-size: 1.3rem; margin-bottom: 20px; text-transform: uppercase; border-bottom: 2px solid var(--divider); padding-bottom: 10px; letter-spacing: 0.5px;">Editar Perfil</h2>
        
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" action="" enctype="multipart/form-data">
            <div class="form-group">
                <label for="display_name">Nome de Técnico (Exibição)</label>
                <input type="text" id="display_name" name="display_name" class="form-control" placeholder="ex: Professor Abel" value="<?php echo htmlspecialchars($user_data['display_name'] ?? ''); ?>" maxlength="100">
            </div>

            <div class="form-group">
                <label for="bio">Biografia / Descrição Tática</label>
                <textarea id="bio" name="bio" class="form-control" placeholder="Descreva seu estilo de jogo, formações favoritas ou conquistas..." rows="4" style="resize: vertical; font-family: inherit;"><?php echo htmlspecialchars($user_data['bio'] ?? ''); ?></textarea>
            </div>

            <div class="form-group">
                <label>Foto de Perfil (Avatar)</label>
                <div class="file-upload-wrapper">
                    <input type="file" name="avatar" class="file-upload-input" accept="image/*" onchange="updateFileLabel(this, 'file-label')">
                    <div class="file-upload-trigger">
                        <div class="icon">👤</div>
                        <span id="file-label">Escolha sua foto de técnico</span>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label>Foto de Fundo dos Cards da Tela Principal</label>
                <div class="file-upload-wrapper">
                    <input type="file" name="dashboard_card_bg" class="file-upload-input" accept="image/*" onchange="updateFileLabel(this, 'bg-file-label')">
                    <div class="file-upload-trigger">
                        <div class="icon">🖼️</div>
                        <span id="bg-file-label"><?php echo !empty($user_data['dashboard_card_bg']) ? 'Alterar foto de fundo do card' : 'Escolha a foto de fundo dos cards'; ?></span>
                    </div>
                </div>
            </div>

            <div style="display: flex; gap: 15px; margin-top: 30px;">
                <button type="submit" class="btn-primary" style="flex: 1;">Salvar Alterações</button>
                <a href="dashboard.php" class="btn-secondary" style="flex: 1; display: flex; align-items: center; justify-content: center;">Voltar ao Painel</a>
            </div>
        </form>
    </div>

    <!-- RIGHT PANEL: CARD DISPLAY & STATS -->
    <div style="display: flex; flex-direction: column; gap: 30px;">
        <!-- PROFILE SUMMARY CARD -->
        <div class="dash-card" style="min-height: auto; padding: 25px; align-items: center; border-color: rgba(234, 179, 8, 0.2);">
            <!-- Avatar Display -->
            <?php if (!empty($user_data['avatar_path'])): ?>
                <img src="<?php echo htmlspecialchars($user_data['avatar_path']); ?>" alt="Avatar" style="width: 110px; height: 110px; border-radius: 50%; border: 3px solid var(--primary); object-fit: cover; box-shadow: 0 4px 15px rgba(0,255,102,0.2);">
            <?php else: ?>
                <div style="width: 110px; height: 110px; border-radius: 50%; border: 3px dashed var(--border-light); background: rgba(255,255,255,0.02); display: flex; justify-content: center; align-items: center; font-size: 3rem; color: var(--text-muted);">👤</div>
            <?php endif; ?>

            <h2 style="font-size: 1.4rem; margin-top: 15px; margin-bottom: 5px; color: #fff;">
                Téc. <?php echo htmlspecialchars($user_data['display_name'] ? $user_data['display_name'] : $user_data['username']); ?>
            </h2>

            <div class="admin-badge" style="background: linear-gradient(135deg, var(--divider) 0%, #a67c00 100%); color: #000; padding: 4px 14px; margin-bottom: 15px;">
                ⭐ Peladeiro
            </div>

            <?php if (!empty($user_data['bio'])): ?>
                <p style="font-size: 0.85rem; font-style: italic; color: var(--text-muted); line-height: 1.4; border-top: 1px solid var(--border-light); padding-top: 15px; width: 100%;">
                    "<?php echo htmlspecialchars($user_data['bio']); ?>"
                </p>
            <?php else: ?>
                <p style="font-size: 0.85rem; font-style: italic; color: var(--text-muted); line-height: 1.4; border-top: 1px solid var(--border-light); padding-top: 15px; width: 100%;">
                    "Sem biografia tática definida ainda."
                </p>
            <?php endif; ?>
        </div>

        <!-- STATS CARD -->
        <div class="draft-panel" style="padding: 20px;">
            <h3 style="font-size: 1rem; border-bottom: 2px solid var(--divider); padding-bottom: 8px; margin-bottom: 15px; text-transform: uppercase; letter-spacing: 0.5px;">Estatísticas de Carreira</h3>
            
            <div class="draft-stat-row" style="margin-bottom: 10px;">
                <span class="draft-stat-label">Copas Disputadas</span>
                <span class="draft-stat-val" style="color: #fff; font-size: 1.2rem;"><?php echo $tournaments_played; ?></span>
            </div>

            <div class="draft-stat-row" style="margin-bottom: 10px;">
                <span class="draft-stat-label">Copas Vencidas</span>
                <span class="draft-stat-val" style="color: var(--primary); font-size: 1.2rem; font-weight: 800;"><?php echo $tournaments_won; ?></span>
            </div>

            <div class="draft-stat-row" style="margin-bottom: 10px;">
                <span class="draft-stat-label">Moedas GOLAÇO</span>
                <span class="draft-stat-val" style="color: var(--divider); font-size: 1.2rem; font-weight: 800;">R$ <?php echo number_format($user_data['money'], 0, ',', '.'); ?></span>
            </div>

            <div class="draft-stat-row" style="margin-bottom: 0; background: none; border-top: 1px solid var(--border-light); padding-top: 15px; border-radius: 0;">
                <span class="draft-stat-label" style="font-size: 0.75rem;">Membro desde:</span>
                <span class="draft-stat-val" style="font-size: 0.85rem; color: #fff; font-weight: 500;">
                    <?php echo date('d/m/Y', strtotime($user_data['created_at'])); ?>
                </span>
            </div>
        </div>
    </div>
</div>

<script>
function updateFileLabel(input, labelId) {
    const label = document.getElementById(labelId);
    if (input.files && input.files[0]) {
        label.innerText = input.files[0].name + " selecionado";
        label.style.color = "var(--primary)";
    } else {
        label.innerText = labelId === 'file-label' ? "Escolha sua foto de técnico" : "Escolha a foto de fundo dos cards";
        label.style.color = "var(--text-muted)";
    }
}
</script>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
