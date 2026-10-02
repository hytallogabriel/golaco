/**
 * GOLAÇO - Interactive Draft System & Live Tactical Preview
 */

document.addEventListener('DOMContentLoaded', function() {
    // State Variables
    let teamName = 'Golaço Esporte Clube';
    let kitColor = '#00ff66';
    let formation = '4-3-3';
    let draftedPlayers = {}; // Maps slotId to player object: { id, name, overall, position, series, image_path }
    let activeSlotId = null;

    // Element references
    const setupForm = document.getElementById('draft-setup-form');
    const setupSection = document.getElementById('draft-setup-section');
    const arenaSection = document.getElementById('draft-arena-section');
    const pitch = document.getElementById('pitch');
    const ovrDisplay = document.getElementById('team-ovr-val');
    const progressDisplay = document.getElementById('draft-progress-val');
    const btnSubmitDraft = document.getElementById('btn-submit-draft');
    
    // Preview elements in step 1
    const teamNameInput = document.getElementById('team_name');
    const formationSelect = document.getElementById('formation');
    const kitColorInput = document.getElementById('kit_color');
    const swatchBtns = document.querySelectorAll('.swatch-btn');
    const miniPitchBoard = document.getElementById('mini-pitch-board');
    const previewTeamTitle = document.getElementById('preview-team-title');
    const previewFormationTag = document.getElementById('preview-formation-tag');
    const previewCrestDisplay = document.getElementById('preview-crest-display');

    // Selection Overlay elements
    const selectionOverlay = document.getElementById('selection-overlay');
    const cardsOptionsRow = document.getElementById('cards-options-row');
    const selectionPositionLabel = document.getElementById('selection-position-label');

    // Formation row slot layouts for pitch
    const formationLayouts = {
        '4-3-3': [
            { className: 'formation-forwards', slots: ['LM', 'ST', 'RM'] },
            { className: 'formation-midfield', slots: ['CM1', 'CM2', 'CM3'] },
            { className: 'formation-defense', slots: ['LB', 'CB1', 'CB2', 'RB'] },
            { className: 'formation-goalkeeper', slots: ['GK'] }
        ],
        '4-4-2': [
            { className: 'formation-forwards', slots: ['ST1', 'ST2'] },
            { className: 'formation-midfield', slots: ['LM', 'CM1', 'CM2', 'RM'] },
            { className: 'formation-defense', slots: ['LB', 'CB1', 'CB2', 'RB'] },
            { className: 'formation-goalkeeper', slots: ['GK'] }
        ],
        '4-2-3-1': [
            { className: 'formation-forwards', slots: ['ST'] },
            { className: 'formation-midfield', slots: ['LM', 'CAM', 'RM'] },
            { className: 'formation-midfield', slots: ['CM1', 'CM2'] },
            { className: 'formation-defense', slots: ['LB', 'CB1', 'CB2', 'RB'] },
            { className: 'formation-goalkeeper', slots: ['GK'] }
        ],
        '3-5-2': [
            { className: 'formation-forwards', slots: ['ST1', 'ST2'] },
            { className: 'formation-midfield', slots: ['LM', 'CM1', 'CM2', 'CM3', 'RM'] },
            { className: 'formation-defense', slots: ['CB1', 'CB2', 'CB3'] },
            { className: 'formation-goalkeeper', slots: ['GK'] }
        ]
    };

    // Normalized coordinates (0-100%) for live mini tactical preview
    const miniPitchCoordinates = {
        '4-3-3': [
            { slot: 'GK', x: 10, y: 50, num: 1 },
            { slot: 'LB', x: 28, y: 18, num: 6 },
            { slot: 'CB1', x: 26, y: 38, num: 3 },
            { slot: 'CB2', x: 26, y: 62, num: 4 },
            { slot: 'RB', x: 28, y: 82, num: 2 },
            { slot: 'CM1', x: 46, y: 50, num: 5 },
            { slot: 'CM2', x: 56, y: 32, num: 8 },
            { slot: 'CM3', x: 56, y: 68, num: 10 },
            { slot: 'LM', x: 78, y: 22, num: 11 },
            { slot: 'ST', x: 84, y: 50, num: 9 },
            { slot: 'RM', x: 78, y: 78, num: 7 }
        ],
        '4-4-2': [
            { slot: 'GK', x: 10, y: 50, num: 1 },
            { slot: 'LB', x: 28, y: 18, num: 6 },
            { slot: 'CB1', x: 26, y: 38, num: 3 },
            { slot: 'CB2', x: 26, y: 62, num: 4 },
            { slot: 'RB', x: 28, y: 82, num: 2 },
            { slot: 'LM', x: 52, y: 20, num: 11 },
            { slot: 'CM1', x: 52, y: 40, num: 5 },
            { slot: 'CM2', x: 52, y: 60, num: 8 },
            { slot: 'RM', x: 52, y: 80, num: 7 },
            { slot: 'ST1', x: 82, y: 40, num: 9 },
            { slot: 'ST2', x: 82, y: 60, num: 10 }
        ],
        '4-2-3-1': [
            { slot: 'GK', x: 10, y: 50, num: 1 },
            { slot: 'LB', x: 28, y: 18, num: 6 },
            { slot: 'CB1', x: 26, y: 38, num: 3 },
            { slot: 'CB2', x: 26, y: 62, num: 4 },
            { slot: 'RB', x: 28, y: 82, num: 2 },
            { slot: 'CM1', x: 45, y: 38, num: 5 },
            { slot: 'CM2', x: 45, y: 62, num: 8 },
            { slot: 'LM', x: 68, y: 20, num: 11 },
            { slot: 'CAM', x: 70, y: 50, num: 10 },
            { slot: 'RM', x: 68, y: 80, num: 7 },
            { slot: 'ST', x: 86, y: 50, num: 9 }
        ],
        '3-5-2': [
            { slot: 'GK', x: 10, y: 50, num: 1 },
            { slot: 'CB1', x: 26, y: 30, num: 3 },
            { slot: 'CB2', x: 25, y: 50, num: 4 },
            { slot: 'CB3', x: 26, y: 70, num: 14 },
            { slot: 'LM', x: 50, y: 14, num: 6 },
            { slot: 'CM1', x: 48, y: 36, num: 5 },
            { slot: 'CM2', x: 56, y: 50, num: 8 },
            { slot: 'CM3', x: 48, y: 64, num: 10 },
            { slot: 'RM', x: 50, y: 86, num: 2 },
            { slot: 'ST1', x: 82, y: 40, num: 9 },
            { slot: 'ST2', x: 82, y: 60, num: 11 }
        ]
    };

    // Renders the live mini-tactical preview pitch
    function renderMiniPitchPreview() {
        if (!miniPitchBoard) return;
        const coords = miniPitchCoordinates[formation] || miniPitchCoordinates['4-3-3'];
        
        let existingDiscs = miniPitchBoard.querySelectorAll('.mini-btn-disc');
        if (existingDiscs.length === 11) {
            // Animate existing discs to new positions
            coords.forEach((c, idx) => {
                const disc = existingDiscs[idx];
                disc.style.left = `${c.x}%`;
                disc.style.top = `${c.y}%`;
                disc.style.backgroundColor = c.slot === 'GK' ? '#00e5ff' : kitColor;
                disc.innerText = c.num;
                disc.title = `${c.slot} #${c.num}`;
            });
        } else {
            miniPitchBoard.innerHTML = '';
            coords.forEach(c => {
                const disc = document.createElement('div');
                disc.className = 'mini-btn-disc';
                disc.style.left = `${c.x}%`;
                disc.style.top = `${c.y}%`;
                disc.style.backgroundColor = c.slot === 'GK' ? '#00e5ff' : kitColor;
                disc.innerText = c.num;
                disc.title = `${c.slot} #${c.num}`;
                miniPitchBoard.appendChild(disc);
            });
        }

        // Update preview header
        if (previewTeamTitle) previewTeamTitle.innerText = teamName || 'Meu Time';
        if (previewFormationTag) previewFormationTag.innerText = formation;
        renderMiniCrest();
    }

    function renderMiniCrest() {
        if (!previewCrestDisplay) return;
        const initials = (teamName || 'GEC').trim().substring(0, 3).toUpperCase();
        previewCrestDisplay.innerHTML = `
            <svg width="40" height="40" viewBox="0 0 60 60" fill="none">
                <path d="M30 5L52 14V34C52 47 30 56 30 56C30 56 8 47 8 34V14L30 5Z" fill="${kitColor}" stroke="#ffd700" stroke-width="2.5"/>
                <text x="30" y="38" font-family="'Outfit', sans-serif" font-weight="900" font-size="13" fill="#ffffff" text-anchor="middle">${initials}</text>
            </svg>
        `;
    }

    // Bind Setup Form Listeners for Live Preview
    if (teamNameInput) {
        teamNameInput.addEventListener('input', function() {
            teamName = this.value.trim() || 'Meu Time';
            if (previewTeamTitle) previewTeamTitle.innerText = teamName;
            renderMiniCrest();
        });
    }

    if (formationSelect) {
        formationSelect.addEventListener('change', function() {
            formation = this.value;
            renderMiniPitchPreview();
        });
    }

    if (kitColorInput) {
        kitColorInput.addEventListener('input', function() {
            kitColor = this.value;
            swatchBtns.forEach(b => b.classList.remove('active'));
            renderMiniPitchPreview();
        });
    }

    swatchBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            swatchBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            kitColor = this.getAttribute('data-color');
            if (kitColorInput) kitColorInput.value = kitColor;
            renderMiniPitchPreview();
        });
    });

    // Initial render of preview
    renderMiniPitchPreview();

    // Helper to map slot ID to database player position
    function getBasePosition(slotId) {
        if (slotId.startsWith('GK')) return 'GK';
        if (slotId.startsWith('CB')) return 'CB';
        if (slotId.startsWith('LB')) return 'LB';
        if (slotId.startsWith('RB')) return 'RB';
        if (slotId.startsWith('CM')) return 'CM';
        if (slotId.startsWith('CAM')) return 'CM';
        if (slotId.startsWith('LM')) return 'LM';
        if (slotId.startsWith('RM')) return 'RM';
        if (slotId.startsWith('ST')) return 'ST';
        return 'ST';
    }

    // Helper to get user-friendly name of the position
    function getFriendlyPositionName(slotId) {
        const base = getBasePosition(slotId);
        const map = {
            'GK': 'Goleiro',
            'CB': 'Zagueiro',
            'LB': 'Lateral Esquerdo',
            'RB': 'Lateral Direito',
            'CM': 'Meio-Campista',
            'CAM': 'Meia Armador',
            'LM': 'Meia Esquerda',
            'RM': 'Meia Direita',
            'ST': 'Centroavante'
        };
        return map[slotId] || map[base] || slotId;
    }

    // Step 1: Submit Setup Form
    if (setupForm) {
        setupForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            teamName = document.getElementById('team_name').value.trim();
            kitColor = document.getElementById('kit_color').value;
            formation = document.getElementById('formation').value;

            if (!teamName) {
                alert('Por favor, digite o nome do seu time.');
                return;
            }

            // Sync UI panel values
            const pNameEl = document.getElementById('panel-team-name');
            if (pNameEl) pNameEl.innerText = teamName;
            const pKitEl = document.getElementById('panel-kit-preview');
            if (pKitEl) pKitEl.style.backgroundColor = kitColor;
            const pFormEl = document.getElementById('panel-formation');
            if (pFormEl) pFormEl.innerText = formation;

            const pCrestEl = document.getElementById('panel-crest-wrap');
            if (pCrestEl) {
                const initials = teamName.substring(0, 3).toUpperCase();
                pCrestEl.innerHTML = `
                    <svg width="46" height="46" viewBox="0 0 60 60" fill="none">
                        <path d="M30 5L52 14V34C52 47 30 56 30 56C30 56 8 47 8 34V14L30 5Z" fill="${kitColor}" stroke="#ffd700" stroke-width="2.5"/>
                        <text x="30" y="38" font-family="'Outfit', sans-serif" font-weight="900" font-size="13" fill="#ffffff" text-anchor="middle">${initials}</text>
                    </svg>
                `;
            }

            // Render pitch slots
            renderPitch();

            // Transition screens smoothly
            setupSection.style.display = 'none';
            arenaSection.style.display = 'grid';
        });
    }

    // Step 2: Render pitch based on selected formation
    function renderPitch() {
        pitch.innerHTML = '';
        
        // Add layout markings
        const penaltyTop = document.createElement('div');
        penaltyTop.className = 'pitch-penalty-area-top';
        const penaltyBottom = document.createElement('div');
        penaltyBottom.className = 'pitch-penalty-area-bottom';
        const centerCircle = document.createElement('div');
        centerCircle.className = 'pitch-center-circle';
        
        pitch.appendChild(penaltyTop);
        pitch.appendChild(penaltyBottom);
        pitch.appendChild(centerCircle);

        // Create formation rows
        const rows = formationLayouts[formation] || formationLayouts['4-3-3'];
        const rowHeight = `${Math.floor(100 / rows.length)}%`;

        rows.forEach(function(rowData) {
            const rowDiv = document.createElement('div');
            rowDiv.className = `formation-row ${rowData.className}`;
            rowDiv.style.display = 'flex';
            rowDiv.style.justifyContent = 'space-around';
            rowDiv.style.alignItems = 'center';
            rowDiv.style.height = rowHeight;

            rowData.slots.forEach(function(slotId) {
                const slot = document.createElement('div');
                slot.id = `slot-${slotId}`;
                slot.className = 'slot-placeholder';
                
                // Content of placeholder
                slot.innerHTML = `
                    <span class="pos-tag">${slotId}</span>
                    <span class="add-icon">➕</span>
                `;

                // Bind click event
                slot.addEventListener('click', function() {
                    openSelectionModal(slotId);
                });

                rowDiv.appendChild(slot);
            });

            pitch.appendChild(rowDiv);
        });

        updateDraftProgress();
    }

    // Step 3: Open selection overlay and query player cards
    function openSelectionModal(slotId) {
        activeSlotId = slotId;
        const basePos = getBasePosition(slotId);
        selectionPositionLabel.innerText = getFriendlyPositionName(slotId);

        // Gather already drafted player IDs to exclude, EXCEPT the one currently in this slot (if re-selecting)
        const excludeIds = Object.entries(draftedPlayers)
            .filter(([sId, p]) => sId !== slotId && p && p.id)
            .map(([sId, p]) => p.id)
            .join(',');

        // Clear previous options and show loading
        cardsOptionsRow.innerHTML = `
            <div style="color: var(--primary); font-weight: 800; font-size: 1.15rem; padding: 40px; text-align: center; width: 100%;">
                <div style="font-size: 2rem; margin-bottom: 8px; animation: bounce 1s infinite;">⚽</div>
                Sorteando 3 cartas do Brasileirão Série A & B...
            </div>
        `;
        selectionOverlay.classList.add('active');

        // Fetch draft card options
        fetch(`api/get_draft_options.php?position=${basePos}&exclude=${excludeIds}`)
            .then(res => res.json())
            .then(data => {
                if (data.error) {
                    cardsOptionsRow.innerHTML = `<div class="alert alert-danger">${data.error}</div>`;
                    return;
                }

                renderCardOptions(data.options);
            })
            .catch(err => {
                cardsOptionsRow.innerHTML = '<div class="alert alert-danger">Erro de conexão ao buscar opções.</div>';
                console.error(err);
            });
    }

    // Close Modal helper
    window.closeSelectionModal = function() {
        selectionOverlay.classList.remove('active');
        activeSlotId = null;
    };

    // Step 4: Render the 5 card options in the modal
    function renderCardOptions(options) {
        cardsOptionsRow.innerHTML = '';
        
        if (!options || options.length === 0) {
            cardsOptionsRow.innerHTML = '<div style="color: var(--danger); font-weight: 800; padding: 20px; text-align: center; width: 100%;">Nenhum jogador disponível no banco de dados para esta posição.</div>';
            return;
        }

        options.forEach(function(player, idx) {
            // Determine card visual rank
            let cardRankClass = 'bronze';
            let rankBadge = 'BRONZE';
            if (player.overall >= 84) {
                cardRankClass = 'super';
                rankBadge = 'ELITE';
            } else if (player.overall >= 80) {
                cardRankClass = 'gold';
                rankBadge = 'OURO';
            } else if (player.overall >= 74) {
                cardRankClass = 'silver';
                rankBadge = 'PRATA';
            }

            const card = document.createElement('div');
            card.className = `g-card ${cardRankClass}`;
            card.style.animation = `fadeInUp 0.3s ease-out ${idx * 0.08}s both`;
            
            const imgStyle = player.image_path ? `style="background-image: url('${player.image_path}')"` : '';

            card.innerHTML = `
                <div class="card-rarity-pill">${rankBadge}</div>
                <div class="card-top">
                    <div class="card-rating">
                        <span class="card-ovr">${player.overall}</span>
                        <span class="card-pos">${player.position}</span>
                    </div>
                    <span class="card-series">SÉRIE ${player.series}</span>
                </div>
                <div class="card-player-img" ${imgStyle}></div>
                <div class="card-name-container">
                    <span class="card-name">${player.name}</span>
                </div>
            `;

            // Select player on click with highlight feedback
            card.addEventListener('click', function() {
                card.style.transform = 'scale(1.08)';
                card.style.borderColor = '#00ff66';
                card.style.boxShadow = '0 0 25px rgba(0, 255, 102, 0.8)';
                setTimeout(() => {
                    selectPlayer(player);
                }, 180);
            });

            cardsOptionsRow.appendChild(card);
        });
    }

    // Step 5: Place selected player in the active pitch slot
    function selectPlayer(player) {
        if (!activeSlotId) return;

        // Check if player is already drafted in another slot
        const isDuplicate = Object.entries(draftedPlayers).some(([sId, p]) => sId !== activeSlotId && p && p.id === player.id);
        if (isDuplicate) {
            alert(`O jogador ${player.name} já foi escalado em outra posição da sua equipe! Escolha outro atleta para manter o elenco único.`);
            return;
        }

        // Save selection in state
        draftedPlayers[activeSlotId] = player;

        // Update Slot visual on pitch
        const slot = document.getElementById(`slot-${activeSlotId}`);
        slot.className = 'slot-card-filled';
        slot.style.border = 'none';
        slot.style.background = 'none';
        slot.style.width = 'auto';
        slot.style.height = 'auto';
        slot.style.transform = 'none';
        slot.style.boxShadow = 'none';

        let cardRankClass = 'bronze';
        if (player.overall >= 84) cardRankClass = 'super';
        else if (player.overall >= 80) cardRankClass = 'gold';
        else if (player.overall >= 74) cardRankClass = 'silver';

        const imgStyle = player.image_path ? `style="background-image: url('${player.image_path}')"` : '';

        // Insert actual visual card inside the pitch slot
        slot.innerHTML = `
            <div class="g-card ${cardRankClass}" title="${player.name} (OVR ${player.overall}) - Clique para trocar">
                <div class="card-top">
                    <div class="card-rating">
                        <span class="card-ovr">${player.overall}</span>
                        <span class="card-pos">${player.position}</span>
                    </div>
                    <span class="card-series">${player.series}</span>
                </div>
                <div class="card-player-img" ${imgStyle}></div>
                <div class="card-name-container">
                    <span class="card-name">${player.name}</span>
                </div>
            </div>
        `;

        // Update overall and counts
        recalculateTeamOverall();
        updateDraftProgress();

        // Close modal
        closeSelectionModal();
    }

    // Step 6: Recalculate team statistics
    function recalculateTeamOverall() {
        const keys = Object.keys(draftedPlayers);
        if (keys.length === 0) {
            ovrDisplay.innerText = '0';
            return;
        }

        let sum = 0;
        keys.forEach(function(key) {
            sum += draftedPlayers[key].overall;
        });

        const average = Math.round(sum / keys.length);
        ovrDisplay.innerText = average;
        ovrDisplay.classList.add('ovr-glow');
    }

    // Step 7: Update draft completion progress
    function updateDraftProgress() {
        const count = Object.keys(draftedPlayers).length;
        progressDisplay.innerText = `${count}/11`;

        // Show button to submit if full roster is drafted
        if (count === 11) {
            btnSubmitDraft.style.display = 'block';
            btnSubmitDraft.scrollIntoView({ behavior: 'smooth' });
        } else {
            btnSubmitDraft.style.display = 'none';
        }
    }

    // Step 8: Save drafted team to server
    if (btnSubmitDraft) {
        btnSubmitDraft.addEventListener('click', function() {
            const count = Object.keys(draftedPlayers).length;
            if (count !== 11) {
                alert('Você precisa preencher todos os 11 jogadores antes de jogar.');
                return;
            }

            const playerIds = Object.values(draftedPlayers).map(p => p.id);
            const uniqueIds = new Set(playerIds);
            if (uniqueIds.size !== 11) {
                alert('Atenção: Não é permitido ter jogadores repetidos na escalação. Todos os 11 jogadores devem ser atletas distintos.');
                return;
            }

            // Map player IDs
            const playerIdsMap = {};
            Object.keys(draftedPlayers).forEach(function(slotId) {
                playerIdsMap[slotId] = draftedPlayers[slotId].id;
            });

            const payload = {
                team_name: teamName,
                kit_color: kitColor,
                formation: formation,
                players: playerIdsMap
            };

            btnSubmitDraft.innerText = 'ENVIANDO TIME... ⚽';
            btnSubmitDraft.disabled = true;

            fetch('api/save_draft.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(payload)
            })
            .then(res => res.json())
            .then(data => {
                if (data.error) {
                    alert(data.error);
                    btnSubmitDraft.innerText = 'CONFIRMAR ELENCO E JOGAR 🏆';
                    btnSubmitDraft.disabled = false;
                    return;
                }

                if (data.success && data.redirect) {
                    window.location.href = data.redirect;
                }
            })
            .catch(err => {
                alert('Erro de conexão ao salvar seu time.');
                btnSubmitDraft.innerText = 'CONFIRMAR ELENCO E JOGAR 🏆';
                btnSubmitDraft.disabled = false;
                console.error(err);
            });
        });
    }
});
