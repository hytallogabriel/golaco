<?php
/**
 * GOLAÇO - Draft Team Screen - "CRIE SEU TIME" Experience
 */

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/crests.php';

// If user already has an active tournament, redirect them to the tournament view
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM tournaments WHERE user_id = :user_id AND status = 'active'");
    $stmt->execute([':user_id' => $_SESSION['user_id']]);
    if ($stmt->fetchColumn() > 0) {
        header('Location: tournament.php');
        exit;
    }
} catch (PDOException $e) {
    // Ignore and proceed
}
?>

<!-- STEP 1: "CRIE SEU TIME" MODERN TWO-COLUMN SETUP SECTION -->
<section id="draft-setup-section" class="create-team-section" style="animation: fadeIn 0.4s ease-in-out;">
    <div class="create-team-container">
        <!-- LEFT COLUMN: CONFIGURATION FORM -->
        <div class="create-team-card">
            <div class="create-header">
                <span class="create-badge">TEMPORADA OFICIAL</span>
                <h1 class="create-title">CRIE SEU TIME</h1>
                <p class="create-subtitle">Personalize a identidade do seu clube, escolha o esquema tático e monte um esquadrão campeão no futebol de botão.</p>
            </div>

            <form id="draft-setup-form">
                <div class="form-group">
                    <label for="team_name" class="create-label">Nome do Clube</label>
                    <div class="input-with-icon">
                        <span class="input-icon">🛡️</span>
                        <input type="text" id="team_name" class="form-control form-control-modern" placeholder="Ex: Galáticos FC, Meninos da Vila, Botão de Ouro" maxlength="40" required value="Golaço Esporte Clube">
                    </div>
                </div>

                <div class="form-group">
                    <label for="formation" class="create-label">Formação Tática</label>
                    <select id="formation" class="form-control select-control-modern" required>
                        <option value="4-3-3" selected>4-3-3 (Ofensivo / Pontas Abertos)</option>
                        <option value="4-4-2">4-4-2 (Clássico / Duas Linhas de 4)</option>
                        <option value="4-2-3-1">4-2-3-1 (Moderno / Meia Armador Central)</option>
                        <option value="3-5-2">3-5-2 (Pressão / Alas e Três Zagueiros)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="create-label">Cor do Uniforme Principal</label>
                    <div class="kit-color-selection">
                        <div class="color-picker-box">
                            <input type="color" id="kit_color" class="color-native-input" value="#00ff66">
                        </div>
                        <div class="swatches-row">
                            <button type="button" class="swatch-btn active" data-color="#00ff66" style="background: #00ff66;" title="Verde Seleção"></button>
                            <button type="button" class="swatch-btn" data-color="#ffd700" style="background: #ffd700;" title="Amarelo Ouro"></button>
                            <button type="button" class="swatch-btn" data-color="#00e5ff" style="background: #00e5ff;" title="Azul Cyber"></button>
                            <button type="button" class="swatch-btn" data-color="#ef4444" style="background: #ef4444;" title="Rubro-Negro"></button>
                            <button type="button" class="swatch-btn" data-color="#ffffff" style="background: #ffffff;" title="Branco Real"></button>
                            <button type="button" class="swatch-btn" data-color="#9333ea" style="background: #9333ea;" title="Roxo Imperial"></button>
                        </div>
                    </div>
                    <small style="color: var(--text-muted); font-size: 0.8rem; margin-top: 6px; display: block;">Esta cor será utilizada nos botões e escudo do seu time durante as partidas.</small>
                </div>

                <button type="submit" class="btn-primary btn-advance-draft">
                    <span>Avançar para o Campo</span>
                    <span class="btn-arrow">📋</span>
                </button>
            </form>
        </div>

        <!-- RIGHT COLUMN: LIVE MINI-TACTICAL FIELD PREVIEW -->
        <div class="create-preview-card">
            <div class="preview-header">
                <div class="preview-team-info">
                    <div id="preview-crest-display" class="preview-crest"></div>
                    <div>
                        <div id="preview-team-title" class="preview-team-name">Golaço Esporte Clube</div>
                        <div id="preview-formation-tag" class="preview-tag">4-3-3</div>
                    </div>
                </div>
                <span class="preview-badge-status">PREVIEW TÁTICO</span>
            </div>

            <!-- MINI PITCH CANVAS / SVG -->
            <div class="mini-pitch-container">
                <div class="mini-pitch-board" id="mini-pitch-board">
                    <!-- Dynamic button discs rendered here via JS -->
                </div>
            </div>

            <div class="preview-footer-tip">
                💡 <strong>Dica Tática:</strong> Alterne entre as formações acima para visualizar a distribuição dos 11 botões em campo antes de iniciar o sorteio de jogadores.
            </div>
        </div>
    </div>
</section>

<!-- STEP 2: INTERACTIVE ARENA SECTION (HIDDEN ON START) -->
<section id="draft-arena-section" class="draft-layout" style="display: none; animation: fadeIn 0.5s ease-in-out;">
    <!-- The Soccer Pitch -->
    <div class="pitch-container">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
            <div>
                <h2 style="font-size: 1.3rem; text-transform: uppercase; color: #fff; font-weight: 900; letter-spacing: 0.5px; margin: 0;">Escalar Time Titular</h2>
                <p style="color: var(--text-muted); font-size: 0.85rem; margin: 2px 0 0 0;">Toque em cada posição para sortear 3 cartas de jogadores</p>
            </div>
            <a href="dashboard.php" style="font-size: 0.85rem; color: var(--danger); font-weight: 700; text-decoration: none; padding: 6px 12px; background: rgba(239, 68, 68, 0.1); border-radius: 8px; border: 1px solid rgba(239, 68, 68, 0.3);">Abandonar Draft ❌</a>
        </div>
        <div id="pitch" class="pitch">
            <!-- Position rows will be injected here by JS -->
        </div>
    </div>

    <!-- Stats & Options Side Panel -->
    <div class="draft-panel">
        <div class="draft-panel-top">
            <div id="panel-crest-wrap" class="panel-crest-wrap"></div>
            <div>
                <h2 id="panel-team-name" style="margin: 0; font-size: 1.25rem;">Nome do Time</h2>
                <div style="display: flex; gap: 8px; margin-top: 4px;">
                    <span class="clash-pill formation" id="panel-formation">4-3-3</span>
                    <span class="clash-pill series">COPA DO BRASIL</span>
                </div>
            </div>
        </div>
        
        <div class="draft-stat-row">
            <span class="draft-stat-label">Uniforme Selecionado</span>
            <div id="panel-kit-preview" class="color-preview" style="border-width: 2px; border-color: rgba(255,255,255,0.4);"></div>
        </div>

        <div class="draft-stat-row">
            <span class="draft-stat-label">Escalação Completa</span>
            <span class="draft-stat-val" id="draft-progress-val" style="color: var(--primary); font-size: 1.2rem; font-weight: 900;">0/11</span>
        </div>

        <div class="draft-stat-row ovr-highlight-box">
            <div>
                <span class="draft-stat-label" style="color: #fff; font-weight: 800; letter-spacing: 1px;">OVERALL DO TIME</span>
                <div style="font-size: 0.75rem; color: var(--text-muted);">Média técnica dos 11 titulares</div>
            </div>
            <span class="draft-stat-val ovr-glow" id="team-ovr-val" style="font-size: 2.4rem; color: var(--primary); font-weight: 900;">0</span>
        </div>

        <div class="draft-tip-box">
            💡 <strong>Dica:</strong> Busque atletas com Overall alto para ter vantagem técnica nas jogadas, passes e finalizações da simulação!
        </div>

        <!-- Submit Team Button -->
        <button id="btn-submit-draft" class="btn-primary" style="display: none; margin-top: 25px; padding: 18px; font-size: 1.15rem; font-weight: 900; letter-spacing: 1px; animation: pulse 1.5s infinite;">
            🏆 CONFIRMAR ELENCO E JOGAR
        </button>
    </div>
</section>

<!-- SELECTION CARDS MODAL OVERLAY (UPGRADED) -->
<div id="selection-overlay" class="selection-overlay">
    <div class="selection-container">
        <div class="selection-header">
            <div class="selection-badge">MERCADO DA BOLA</div>
            <h2 class="selection-title">Sorteio de Cartas</h2>
            <p class="selection-subtitle">Escolha o jogador ideal para a posição de <strong id="selection-position-label" style="color: var(--primary); font-size: 1.1rem;">Posição</strong></p>
        </div>
        
        <div id="cards-options-row" class="cards-options-row">
            <!-- 5 Cards loaded via JS -->
        </div>

        <div style="text-align: center; margin-top: 20px;">
            <button class="btn-secondary" style="max-width: 220px; margin: 0 auto; padding: 10px 22px; font-weight: 700;" onclick="closeSelectionModal()">Cancelar Escolha</button>
        </div>
    </div>
</div>

<style>
/* "CRIE SEU TIME" MODERN LAYOUT */
.create-team-section {
    max-width: 1200px;
    margin: 20px auto 40px;
}

.create-team-container {
    display: grid;
    grid-template-columns: 1.1fr 1fr;
    gap: 30px;
    align-items: stretch;
}

.create-team-card {
    background: linear-gradient(180deg, rgba(22, 31, 51, 0.95) 0%, rgba(13, 19, 33, 0.98) 100%);
    border: 1px solid var(--border-light);
    border-radius: 20px;
    padding: 32px;
    box-shadow: 0 15px 45px rgba(0, 0, 0, 0.55);
}

.create-header {
    margin-bottom: 25px;
}

.create-badge {
    display: inline-block;
    background: rgba(0, 255, 102, 0.12);
    border: 1px solid rgba(0, 255, 102, 0.35);
    color: var(--primary);
    font-size: 0.72rem;
    font-weight: 900;
    letter-spacing: 2px;
    padding: 3px 12px;
    border-radius: 20px;
    margin-bottom: 8px;
}

.create-title {
    font-size: 2.2rem;
    font-weight: 900;
    letter-spacing: 1px;
    color: #fff;
    margin: 0 0 6px 0;
}

.create-subtitle {
    color: var(--text-muted);
    font-size: 0.9rem;
    line-height: 1.5;
    margin: 0;
}

.create-label {
    display: block;
    font-size: 0.82rem;
    font-weight: 800;
    color: #fff;
    letter-spacing: 0.8px;
    text-transform: uppercase;
    margin-bottom: 8px;
}

.input-with-icon {
    position: relative;
    display: flex;
    align-items: center;
}

.input-icon {
    position: absolute;
    left: 14px;
    font-size: 1.1rem;
    pointer-events: none;
}

.form-control-modern {
    padding-left: 44px;
    background: rgba(0, 0, 0, 0.45);
    border: 1px solid rgba(255, 255, 255, 0.12);
    border-radius: 12px;
    color: #fff;
    font-size: 1rem;
    font-weight: 600;
    height: 48px;
    transition: all 0.2s ease;
}

.form-control-modern:focus {
    border-color: var(--primary);
    box-shadow: 0 0 15px rgba(0, 255, 102, 0.25);
}

.select-control-modern {
    background: rgba(0, 0, 0, 0.45);
    border: 1px solid rgba(255, 255, 255, 0.12);
    border-radius: 12px;
    color: #fff;
    font-size: 0.95rem;
    font-weight: 700;
    height: 48px;
    padding: 0 14px;
    cursor: pointer;
}

.kit-color-selection {
    display: flex;
    align-items: center;
    gap: 14px;
}

.color-picker-box {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    overflow: hidden;
    border: 2px solid rgba(255, 255, 255, 0.2);
    cursor: pointer;
    flex-shrink: 0;
}

.color-native-input {
    width: 140%;
    height: 140%;
    margin: -20%;
    cursor: pointer;
    border: none;
}

.swatches-row {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
}

.swatch-btn {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    border: 2px solid rgba(0, 0, 0, 0.4);
    cursor: pointer;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.swatch-btn:hover, .swatch-btn.active {
    transform: scale(1.18);
    box-shadow: 0 0 12px rgba(255, 255, 255, 0.6);
    border-color: #fff;
}

.btn-advance-draft {
    width: 100%;
    margin-top: 25px;
    padding: 16px;
    font-size: 1.1rem;
    font-weight: 900;
    letter-spacing: 1px;
    text-transform: uppercase;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
}

/* RIGHT COLUMN - PREVIEW CARD */
.create-preview-card {
    background: linear-gradient(180deg, rgba(22, 31, 51, 0.95) 0%, rgba(13, 19, 33, 0.98) 100%);
    border: 1px solid var(--border-light);
    border-radius: 20px;
    padding: 24px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}

.preview-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;
    padding-bottom: 12px;
    border-bottom: 1px solid var(--border-light);
}

.preview-team-info {
    display: flex;
    align-items: center;
    gap: 12px;
}

.preview-crest {
    width: 44px;
    height: 44px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.preview-team-name {
    font-size: 1.15rem;
    font-weight: 900;
    color: #fff;
}

.preview-tag {
    font-size: 0.72rem;
    color: var(--primary);
    font-weight: 800;
    letter-spacing: 1px;
}

.preview-badge-status {
    font-size: 0.65rem;
    font-weight: 800;
    letter-spacing: 1.5px;
    background: rgba(56, 189, 248, 0.12);
    color: #38bdf8;
    border: 1px solid rgba(56, 189, 248, 0.3);
    padding: 3px 8px;
    border-radius: 8px;
}

/* MINI PITCH BOARD */
.mini-pitch-container {
    flex: 1;
    min-height: 280px;
    background: #0d4722;
    border: 2px solid rgba(0, 255, 102, 0.25);
    border-radius: 14px;
    position: relative;
    overflow: hidden;
    box-shadow: inset 0 0 35px rgba(0, 0, 0, 0.6);
}

.mini-pitch-board {
    position: absolute;
    inset: 0;
    background: 
        radial-gradient(circle at center, rgba(0, 255, 102, 0.08), transparent 70%),
        repeating-linear-gradient(90deg, transparent, transparent 35px, rgba(0,0,0,0.1) 35px, rgba(0,0,0,0.1) 70px);
}

.mini-pitch-board::before {
    content: '';
    position: absolute;
    left: 50%; top: 50%;
    transform: translate(-50%, -50%);
    width: 70px; height: 70px;
    border: 1px solid rgba(255, 255, 255, 0.35);
    border-radius: 50%;
}

.mini-pitch-board::after {
    content: '';
    position: absolute;
    top: 0; bottom: 0; left: 50%;
    width: 1px;
    background: rgba(255, 255, 255, 0.35);
}

/* MINI BUTTON DISCS IN PREVIEW */
.mini-btn-disc {
    position: absolute;
    width: 22px;
    height: 22px;
    border-radius: 50%;
    transform: translate(-50%, -50%);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.6);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 8px;
    font-weight: 900;
    color: #fff;
    border: 1.5px solid rgba(255, 255, 255, 0.7);
    transition: left 0.4s cubic-bezier(0.34, 1.56, 0.64, 1), top 0.4s cubic-bezier(0.34, 1.56, 0.64, 1), background-color 0.3s ease;
}

.preview-footer-tip {
    font-size: 0.8rem;
    color: var(--text-muted);
    margin-top: 14px;
    line-height: 1.4;
    background: rgba(255, 255, 255, 0.02);
    padding: 10px 14px;
    border-radius: 8px;
    border: 1px solid var(--border-light);
}

.panel-crest-wrap {
    width: 48px;
    height: 48px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 8px;
}

.ovr-highlight-box {
    background: linear-gradient(135deg, rgba(0, 255, 102, 0.08) 0%, rgba(0, 255, 102, 0.02) 100%) !important;
    border: 1px solid rgba(0, 255, 102, 0.25) !important;
    border-radius: 12px;
    padding: 14px 16px !important;
}

.draft-tip-box {
    margin-top: 20px;
    font-size: 0.82rem;
    color: var(--text-muted);
    line-height: 1.5;
    background: rgba(255,255,255,0.02);
    padding: 12px;
    border-radius: 8px;
    border: 1px solid var(--border-light);
}

/* SELECTION MODAL CARDS */
.selection-header {
    text-align: center;
    margin-bottom: 25px;
}

.selection-badge {
    display: inline-block;
    background: rgba(255, 215, 0, 0.12);
    border: 1px solid rgba(255, 215, 0, 0.35);
    color: #ffd700;
    font-size: 0.7rem;
    font-weight: 900;
    letter-spacing: 2px;
    padding: 2px 10px;
    border-radius: 12px;
    margin-bottom: 6px;
}

@media (max-width: 900px) {
    .create-team-container {
        grid-template-columns: 1fr;
    }
}
</style>

<script src="assets/js/draft.js"></script>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
