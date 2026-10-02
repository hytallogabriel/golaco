/**
 * GOLAÇO - Real-Time 2D Button Soccer Simulation Engine (Futebol de Botão)
 * Next-Gen Tactical Simulation & Broadcast Presentation Edition
 */

document.addEventListener('DOMContentLoaded', function() {
    if (typeof matchConfig === 'undefined') return;

    const GM = window.GolacoMatch || {};
    let DEBUG_SIMULATION = GM.DEBUG_SIMULATION !== false;
    const MATCH_STATE = GM.MATCH_STATE || {
        PRE_MATCH: 'PRE_MATCH', FIRST_HALF: 'FIRST_HALF', FIRST_HALF_ADDED: 'FIRST_HALF_ADDED',
        HALFTIME: 'HALFTIME', SECOND_HALF: 'SECOND_HALF', SECOND_HALF_ADDED: 'SECOND_HALF_ADDED',
        SET_PIECE: 'SET_PIECE', GOAL_MOMENT: 'GOAL_MOMENT', PENALTY_SHOOTOUT: 'PENALTY_SHOOTOUT', FULL_TIME: 'FULL_TIME'
    };
    const BASE_MATCH_REAL_SECONDS = GM.BASE_MATCH_REAL_SECONDS || 70;

    // ========================================================
    // 1. CANVAS & UI ELEMENTS
    // ========================================================
    const canvas = document.getElementById('sim-canvas');
    const debugPanel = document.getElementById('sim-debug-panel');
    if (!canvas) return;
    const ctx = canvas.getContext('2d');

    const btnStart = document.getElementById('btn-start-match');
    const btnToggleDebug = document.getElementById('btn-toggle-debug');
    const btnForcePenalties = document.getElementById('btn-force-penalties');
    const eventsLog = document.getElementById('events-log');
    const timerDisplay = document.getElementById('match-timer');
    const addedTimeDisplay = document.getElementById('match-added-time');
    const phaseBadge = document.getElementById('match-phase-badge');
    const scoreHomeDisplay = document.getElementById('score-home');
    const scoreAwayDisplay = document.getElementById('score-away');
    const penaltyScoreRow = document.getElementById('penalty-score-row');
    const penScoreHome = document.getElementById('pen-score-home');
    const penScoreAway = document.getElementById('pen-score-away');

    // Lineup Columns & Tabs
    const homeLineupList = document.getElementById('home-lineup-list');
    const awayLineupList = document.getElementById('away-lineup-list');
    const tabButtons = document.querySelectorAll('.fifa-tab-btn');
    const panelStats = document.getElementById('fifa-panel-stats');
    const panelFeed = document.getElementById('fifa-panel-feed');
    const liveStatsTable = document.getElementById('live-stats-table');

    // Abandon Tournament Modal
    const btnAbandonCopa = document.getElementById('btn-abandon-copa');
    const abandonOverlay = document.getElementById('abandon-dialog-overlay');
    const btnCancelAbandon = document.getElementById('btn-cancel-abandon');
    const btnConfirmAbandon = document.getElementById('btn-confirm-abandon');

    // Halftime & Fulltime Modals
    const halftimeOverlay = document.getElementById('halftime-dialog-overlay');
    const htScoreHome = document.getElementById('ht-score-home');
    const htScoreAway = document.getElementById('ht-score-away');
    const htStatsTable = document.getElementById('ht-stats-table');
    const btnResumeHalftime = document.getElementById('btn-resume-halftime');
    const htCountdownSpan = document.getElementById('ht-countdown');

    const dialogOverlay = document.getElementById('match-dialog-overlay');
    const mdTitle = document.getElementById('md-title');
    const mdDesc = document.getElementById('md-desc');
    const mdRewardContainer = document.getElementById('md-reward-container');
    const mdRewardVal = document.getElementById('md-reward-val');
    const ftStatsTable = document.getElementById('ft-stats-table');
    const btnConfirm = document.getElementById('btn-confirm-match');

    // Speed Controls
    const speedButtons = document.querySelectorAll('.btn-speed');
    let MATCH_SPEED = GM.MATCH_SPEED || 1.0;
    let matchSpeedMultiplier = MATCH_SPEED;

    let currentTab = 'ratings';

    // ========================================================
    // 2. PITCH GEOMETRY & SPECIFICATIONS (MESA DE BOTÃO)
    // ========================================================
    const PITCH = {
        left: 45,
        right: canvas.width - 45, // 755
        top: 30,
        bottom: canvas.height - 30, // 430
        goalWidth: 32,
        goalTop: canvas.height / 2 - 55, // 175
        goalBottom: canvas.height / 2 + 55, // 285
        goalCenterY: canvas.height / 2, // 230
        sixYardWidth: 32,
        penaltyBoxWidth: 105,
        penaltyBoxHeight: 180,
        penaltySpotDistance: 85,
        centerRadius: 60,
        cornerArcRadius: 16
    };

    // ========================================================
    // 3. SPEED & DEBUG CONTROLS
    // ========================================================
    speedButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            speedButtons.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            matchSpeedMultiplier = MATCH_SPEED * (parseFloat(this.getAttribute('data-speed')) || 1.0);
        });
    });

    if (btnToggleDebug) {
        btnToggleDebug.addEventListener('click', function() {
            DEBUG_SIMULATION = !DEBUG_SIMULATION;
            this.classList.toggle('active', DEBUG_SIMULATION);
            if (debugPanel) debugPanel.classList.toggle('visible', DEBUG_SIMULATION);
            if (btnForcePenalties) btnForcePenalties.style.display = DEBUG_SIMULATION ? 'inline-block' : 'none';
        });
    }

    if (btnForcePenalties) {
        btnForcePenalties.addEventListener('click', function() {
            if (currentState === MATCH_STATE.PRE_MATCH) {
                initSimulation();
            }
            stats.home.goals = 1;
            stats.away.goals = 1;
            if (scoreHomeDisplay) scoreHomeDisplay.innerText = '1';
            if (scoreAwayDisplay) scoreAwayDisplay.innerText = '1';
            eventManager.log('🛠️ [DEBUG] Empate forçado para testar Disputa de Pênaltis!', 'info');
            startPenaltyShootout();
        });
    }

    // Keyboard shortcut 'D' to toggle debug HUD
    window.addEventListener('keydown', function(e) {
        if (e.key === 'd' || e.key === 'D') {
            if (document.activeElement && document.activeElement.tagName === 'INPUT') return;
            DEBUG_SIMULATION = !DEBUG_SIMULATION;
            if (btnToggleDebug) btnToggleDebug.classList.toggle('active', DEBUG_SIMULATION);
            if (debugPanel) debugPanel.classList.toggle('visible', DEBUG_SIMULATION);
            if (btnForcePenalties) btnForcePenalties.style.display = DEBUG_SIMULATION ? 'inline-block' : 'none';
        }
    });

    if (DEBUG_SIMULATION && debugPanel) {
        debugPanel.classList.add('visible');
        if (btnToggleDebug) btnToggleDebug.classList.add('active');
        if (btnForcePenalties) btnForcePenalties.style.display = 'inline-block';
    }

    if (matchConfig.competitionName) {
        const compEl = document.getElementById('competition-title');
        if (compEl) compEl.textContent = matchConfig.competitionName;
    }

    // Tab buttons switching
    tabButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const tab = this.getAttribute('data-tab');
            tabButtons.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            currentTab = tab;

            if (tab === 'stats') {
                if (panelStats) panelStats.style.display = 'block';
                if (panelFeed) panelFeed.style.display = 'none';
                renderStatsTable(liveStatsTable);
            } else if (tab === 'feed') {
                if (panelStats) panelStats.style.display = 'none';
                if (panelFeed) panelFeed.style.display = 'block';
            } else {
                if (panelStats) panelStats.style.display = 'none';
                if (panelFeed) panelFeed.style.display = 'none';
            }

            updateLineupDOM();
        });
    });

    // Abandon modal handlers
    if (btnAbandonCopa) {
        btnAbandonCopa.addEventListener('click', function() {
            if (abandonOverlay) abandonOverlay.classList.add('active');
        });
    }

    if (btnCancelAbandon) {
        btnCancelAbandon.addEventListener('click', function() {
            if (abandonOverlay) abandonOverlay.classList.remove('active');
        });
    }

    if (btnConfirmAbandon) {
        btnConfirmAbandon.addEventListener('click', function() {
            btnConfirmAbandon.innerText = 'Desistindo...';
            btnConfirmAbandon.disabled = true;

            fetch('api/abandon_tournament.php', { method: 'POST' })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        window.location.href = data.redirect || 'draft.php';
                    } else {
                        alert(data.error || 'Erro ao abandonar copa.');
                        btnConfirmAbandon.innerText = 'Confirmar Desistência';
                        btnConfirmAbandon.disabled = false;
                    }
                })
                .catch(() => {
                    alert('Erro na requisição.');
                    btnConfirmAbandon.innerText = 'Confirmar Desistência';
                    btnConfirmAbandon.disabled = false;
                });
        });
    }

    // ========================================================
    // 4. MATCH STATE & AI PHASES
    // ========================================================
    let currentState = MATCH_STATE.PRE_MATCH;
    let teamAiPhase = { home: 'BUILD_UP', away: 'BUILD_UP' };
    let subStateSeconds = 0;
    let currentActionDescription = 'Pré-jogo';
    let halftimeIntervalId = null;
    let animationFrameId = null;
    let lastTimestamp = 0;
    let lineupUiAccum = 0;
    let stuckBallTimer = 0;
    let setPieceWatchdogTimer = 0;
    let trackedTimeouts = [];
    let trackedIntervals = [];
    let subCheckAccum = 0;
    let benchPlayers = [];
    let substitutionsMade = 0;
    const MAX_SUBSTITUTIONS = 5;

    // --- Timer tracking (prevents leaked timeouts/intervals across matches) ---
    function trackTimeout(fn, ms) {
        const id = setTimeout(() => {
            trackedTimeouts = trackedTimeouts.filter(t => t !== id);
            fn();
        }, ms);
        trackedTimeouts.push(id);
        return id;
    }

    function trackInterval(fn, ms) {
        const id = setInterval(fn, ms);
        trackedIntervals.push(id);
        return id;
    }

    function clearTrackedTimers() {
        trackedTimeouts.forEach(clearTimeout);
        trackedIntervals.forEach(clearInterval);
        trackedTimeouts = [];
        trackedIntervals = [];
    }

    function cleanupSimulation() {
        if (animationFrameId) {
            cancelAnimationFrame(animationFrameId);
            animationFrameId = null;
        }
        if (halftimeIntervalId) {
            clearInterval(halftimeIntervalId);
            halftimeIntervalId = null;
        }
        clearTrackedTimers();
    }

    window.addEventListener('beforeunload', cleanupSimulation);

    function isClockRunningState() {
        return (
            currentState === MATCH_STATE.FIRST_HALF ||
            currentState === MATCH_STATE.FIRST_HALF_ADDED ||
            currentState === MATCH_STATE.SECOND_HALF ||
            currentState === MATCH_STATE.SECOND_HALF_ADDED
        );
    }

    function isSimulationActiveState() {
        return isClockRunningState() || currentState === MATCH_STATE.SET_PIECE;
    }

    function resolvePostGoalMatchState() {
        if (!clock.isHalftimeProcessed) {
            if (currentState === MATCH_STATE.FIRST_HALF_ADDED || clock.matchMinute >= 45) {
                return MATCH_STATE.FIRST_HALF_ADDED;
            }
            return MATCH_STATE.FIRST_HALF;
        }
        if (currentState === MATCH_STATE.SECOND_HALF_ADDED || clock.matchMinute >= 90) {
            return MATCH_STATE.SECOND_HALF_ADDED;
        }
        return MATCH_STATE.SECOND_HALF;
    }

    // ========================================================
    // 5. STATS TRACKER (LIVE & PRECISE)
    // ========================================================
    const stats = {
        home: {
            goals: 0,
            shots: 0,
            shotsOnTarget: 0,
            passesAttempted: 0,
            passesCompleted: 0,
            tackles: 0,
            corners: 0,
            fouls: 0,
            yellowCards: 0,
            redCards: 0,
            possessionSeconds: 0
        },
        away: {
            goals: 0,
            shots: 0,
            shotsOnTarget: 0,
            passesAttempted: 0,
            passesCompleted: 0,
            tackles: 0,
            corners: 0,
            fouls: 0,
            yellowCards: 0,
            redCards: 0,
            possessionSeconds: 0
        },
        totalPossessionTime: 0.001
    };

    // ========================================================
    // 6. CLOCK MANAGER (DRIFT-FREE, CONTINUOUS)
    // ========================================================
    const clock = {
        matchMinute: 0,
        matchSecond: 0,
        simulatedSeconds: 0,
        firstHalfAddedMins: 1 + Math.floor(Math.random() * 2),
        secondHalfAddedMins: 2 + Math.floor(Math.random() * 3),
        currentAddedMin: 0,
        isHalftimeProcessed: false,
        isFulltimeProcessed: false,

        reset: function() {
            this.matchMinute = 0;
            this.matchSecond = 0;
            this.simulatedSeconds = 0;
            this.currentAddedMin = 0;
            this.isHalftimeProcessed = false;
            this.isFulltimeProcessed = false;
            this.firstHalfAddedMins = 1 + Math.floor(Math.random() * 2);
            this.secondHalfAddedMins = 2 + Math.floor(Math.random() * 3);
            if (addedTimeDisplay) addedTimeDisplay.style.display = 'none';
        },

        matchSecondsPerRealSecond: function() {
            return (90 * 60) / BASE_MATCH_REAL_SECONDS;
        },

        syncDisplayFromSimulated: function() {
            const totalMin = Math.floor(this.simulatedSeconds / 60);
            this.matchMinute = totalMin;
            this.matchSecond = Math.floor(this.simulatedSeconds % 60);
        },

        update: function(dtSeconds) {
            if (!isClockRunningState()) return;

            const advance = dtSeconds * matchSpeedMultiplier * this.matchSecondsPerRealSecond();
            this.simulatedSeconds += advance;
            this.syncDisplayFromSimulated();

            const firstHalfEnd = 45 * 60;
            const firstHalfStoppageEnd = firstHalfEnd + this.firstHalfAddedMins * 60;
            const secondHalfEnd = 90 * 60;
            const secondHalfStoppageEnd = secondHalfEnd + this.secondHalfAddedMins * 60;

            if (currentState === MATCH_STATE.FIRST_HALF) {
                if (this.simulatedSeconds >= firstHalfEnd) {
                    this.simulatedSeconds = firstHalfEnd;
                    this.syncDisplayFromSimulated();
                    currentState = MATCH_STATE.FIRST_HALF_ADDED;
                    this.currentAddedMin = 0;
                    if (addedTimeDisplay) {
                        addedTimeDisplay.innerText = `+${this.firstHalfAddedMins}'`;
                        addedTimeDisplay.style.display = 'inline-block';
                    }
                    if (phaseBadge) {
                        phaseBadge.innerText = 'ACRÉSCIMOS';
                        phaseBadge.className = 'sb-phase-badge added-time';
                    }
                    eventManager.log(`⏱️ Quarto árbitro indica +${this.firstHalfAddedMins} min de acréscimo no 1º tempo.`, 'info');
                }
            } else if (currentState === MATCH_STATE.FIRST_HALF_ADDED) {
                this.currentAddedMin = Math.floor((this.simulatedSeconds - firstHalfEnd) / 60);
                if (this.simulatedSeconds >= firstHalfStoppageEnd && !this.isHalftimeProcessed) {
                    this.isHalftimeProcessed = true;
                    this.simulatedSeconds = firstHalfStoppageEnd;
                    this.syncDisplayFromSimulated();
                    triggerHalftime();
                }
            } else if (currentState === MATCH_STATE.SECOND_HALF) {
                if (this.matchMinute >= 90) {
                    this.simulatedSeconds = secondHalfEnd;
                    this.syncDisplayFromSimulated();
                    currentState = MATCH_STATE.SECOND_HALF_ADDED;
                    this.currentAddedMin = 0;
                    if (addedTimeDisplay) {
                        addedTimeDisplay.innerText = `+${this.secondHalfAddedMins}'`;
                        addedTimeDisplay.style.display = 'inline-block';
                    }
                    if (phaseBadge) {
                        phaseBadge.innerText = 'ACRÉSCIMOS';
                        phaseBadge.className = 'sb-phase-badge added-time';
                    }
                    eventManager.log(`⏱️ Subiu a placa: +${this.secondHalfAddedMins} minutos de acréscimo final!`, 'info');
                }
            } else if (currentState === MATCH_STATE.SECOND_HALF_ADDED) {
                this.currentAddedMin = Math.floor((this.simulatedSeconds - secondHalfEnd) / 60);
                if (this.simulatedSeconds >= secondHalfStoppageEnd && !this.isFulltimeProcessed) {
                    this.isFulltimeProcessed = true;
                    triggerFullTime();
                }
            }

            let minStr = this.matchMinute.toString().padStart(2, '0');
            let secStr = this.matchSecond.toString().padStart(2, '0');
            if (currentState === MATCH_STATE.FIRST_HALF_ADDED) {
                minStr = `45+${Math.min(this.firstHalfAddedMins, this.currentAddedMin + 1)}`;
            } else if (currentState === MATCH_STATE.SECOND_HALF_ADDED) {
                minStr = `90+${Math.min(this.secondHalfAddedMins, this.currentAddedMin + 1)}`;
            } else if (currentState === MATCH_STATE.HALFTIME) {
                minStr = 'INT';
                secStr = '';
            }

            if (timerDisplay) {
                timerDisplay.innerText = secStr ? `${minStr}:${secStr}` : minStr;
            }

            lineupUiAccum += dtSeconds;
            if (lineupUiAccum >= 2.0) {
                lineupUiAccum = 0;
                updateLineupDOM();
            }
        },

        getCurrentTimeFormatted: function() {
            if (currentState === MATCH_STATE.HALFTIME) return 'INT';
            if (currentState === MATCH_STATE.FIRST_HALF_ADDED) {
                return `45+${Math.max(1, this.currentAddedMin + 1)}'`;
            }
            if (currentState === MATCH_STATE.SECOND_HALF_ADDED) {
                return `90+${Math.max(1, this.currentAddedMin + 1)}'`;
            }
            if (this.matchMinute === 0 && this.matchSecond < 30) return "0'";
            return `${this.matchMinute}'`;
        }
    };

    // ========================================================
    // 7. EVENT MANAGER (BROADCAST NARRATIVE)
    // ========================================================
    const eventManager = {
        lastEventText: '',
        lastEventMinute: -1,

        log: function(message, type = 'info') {
            if (currentState === MATCH_STATE.HALFTIME && type !== 'halftime') {
                return;
            }

            const timeStr = clock.getCurrentTimeFormatted();
            if (this.lastEventText === message && this.lastEventMinute === clock.matchMinute) {
                return;
            }
            this.lastEventText = message;
            this.lastEventMinute = clock.matchMinute;

            let cssClass = 'event-item';
            if (type === 'goal') cssClass += ' goal';
            else if (type === 'yellow') cssClass += ' card-yellow';
            else if (type === 'red') cssClass += ' card-red';
            else if (type === 'corner') cssClass += ' corner';
            else if (type === 'save') cssClass += ' save';

            const row = `<div class="${cssClass}"><strong>${timeStr}</strong> — ${message}</div>`;
            if (eventsLog) {
                eventsLog.insertAdjacentHTML('afterbegin', row);
            }
        }
    };

    // ========================================================
    // 8. BALL ENTITY (FÍSICA REAL DE FUTEBOL DE BOTÃO)
    // ========================================================
    const ball = {
        x: canvas.width / 2,
        y: canvas.height / 2,
        z: 0,
        vx: 0,
        vy: 0,
        vz: 0,
        radius: 6.5,
        friction: 0.980,
        carrier: null,
        targetReceiver: null,
        lastTouchedBy: null,
        lastTeam: 'home',
        state: 'LOOSE', // 'LOOSE', 'CONTROLLED', 'PASSING', 'CROSSING', 'SHOOTING', 'DEFLECTED', 'SAVED', 'OUT', 'GOAL'
        shotTargetY: 230,
        shotAssister: null,

        reset: function(x = canvas.width / 2, y = canvas.height / 2) {
            this.x = x;
            this.y = y;
            this.z = 0;
            this.vx = 0;
            this.vy = 0;
            this.vz = 0;
            this.carrier = null;
            this.targetReceiver = null;
            this.state = 'LOOSE';
        },

        kick: function(vx, vy, vz = 0, kicker = null, isShot = false, receiver = null) {
            this.carrier = null;
            this.vx = vx;
            this.vy = vy;
            this.vz = vz;
            this.targetReceiver = receiver;
            if (kicker) {
                this.lastTouchedBy = kicker;
                this.lastTeam = kicker.isHome ? 'home' : 'away';
            }
            if (isShot) {
                this.state = 'SHOOTING';
                this.saveResolved = false;
            } else if (vz > 1.0) {
                this.state = 'CROSSING';
            } else {
                this.state = 'PASSING';
            }
        }
    };

    // ========================================================
    // 9. PLAYERS, FORMATIONS & TACTICAL ROLES
    // ========================================================
    let players = [];
    let ballTrail = [];
    let confetti = [];
    let floatTexts = [];
    let screenShake = 0;
    let goalNetGlow = 0;
    let goalScorerInfo = null;

    // Tactical formation coordinate maps (normalized across pitch half)
    const tacticalFormations = {
        '4-3-3': [
            { role: 'GK', normX: 0.05, normY: 0.50, slot: 'GK', number: 1 },
            { role: 'LB', normX: 0.22, normY: 0.16, slot: 'LB', number: 6 },
            { role: 'CB', normX: 0.18, normY: 0.38, slot: 'CB1', number: 3 },
            { role: 'CB', normX: 0.18, normY: 0.62, slot: 'CB2', number: 4 },
            { role: 'RB', normX: 0.22, normY: 0.84, slot: 'RB', number: 2 },
            { role: 'CDM', normX: 0.35, normY: 0.50, slot: 'CM1', number: 5 },
            { role: 'CM', normX: 0.45, normY: 0.32, slot: 'CM2', number: 8 },
            { role: 'CM', normX: 0.45, normY: 0.68, slot: 'CM3', number: 10 },
            { role: 'WINGER', normX: 0.66, normY: 0.20, slot: 'LM', number: 11 },
            { role: 'ST', normX: 0.72, normY: 0.50, slot: 'ST', number: 9 },
            { role: 'WINGER', normX: 0.66, normY: 0.80, slot: 'RM', number: 7 }
        ],
        '4-4-2': [
            { role: 'GK', normX: 0.05, normY: 0.50, slot: 'GK', number: 1 },
            { role: 'LB', normX: 0.22, normY: 0.16, slot: 'LB', number: 6 },
            { role: 'CB', normX: 0.18, normY: 0.38, slot: 'CB1', number: 3 },
            { role: 'CB', normX: 0.18, normY: 0.62, slot: 'CB2', number: 4 },
            { role: 'RB', normX: 0.22, normY: 0.84, slot: 'RB', number: 2 },
            { role: 'WINGER', normX: 0.44, normY: 0.18, slot: 'LM', number: 11 },
            { role: 'CDM', normX: 0.42, normY: 0.40, slot: 'CM1', number: 5 },
            { role: 'CM', normX: 0.42, normY: 0.60, slot: 'CM2', number: 8 },
            { role: 'WINGER', normX: 0.44, normY: 0.82, slot: 'RM', number: 7 },
            { role: 'ST', normX: 0.70, normY: 0.40, slot: 'ST1', number: 9 },
            { role: 'ST', normX: 0.70, normY: 0.60, slot: 'ST2', number: 10 }
        ],
        '4-2-3-1': [
            { role: 'GK', normX: 0.05, normY: 0.50, slot: 'GK', number: 1 },
            { role: 'LB', normX: 0.22, normY: 0.16, slot: 'LB', number: 6 },
            { role: 'CB', normX: 0.18, normY: 0.38, slot: 'CB1', number: 3 },
            { role: 'CB', normX: 0.18, normY: 0.62, slot: 'CB2', number: 4 },
            { role: 'RB', normX: 0.22, normY: 0.84, slot: 'RB', number: 2 },
            { role: 'CDM', normX: 0.36, normY: 0.40, slot: 'CM1', number: 5 },
            { role: 'CDM', normX: 0.36, normY: 0.60, slot: 'CM2', number: 8 },
            { role: 'WINGER', normX: 0.56, normY: 0.18, slot: 'LM', number: 11 },
            { role: 'CAM', normX: 0.58, normY: 0.50, slot: 'CAM', number: 10 },
            { role: 'WINGER', normX: 0.56, normY: 0.82, slot: 'RM', number: 7 },
            { role: 'ST', normX: 0.74, normY: 0.50, slot: 'ST', number: 9 }
        ],
        '3-5-2': [
            { role: 'GK', normX: 0.05, normY: 0.50, slot: 'GK', number: 1 },
            { role: 'CB', normX: 0.18, normY: 0.28, slot: 'CB1', number: 3 },
            { role: 'CB', normX: 0.17, normY: 0.50, slot: 'CB2', number: 4 },
            { role: 'CB', normX: 0.18, normY: 0.72, slot: 'CB3', number: 14 },
            { role: 'LB', normX: 0.38, normY: 0.12, slot: 'LM', number: 6 },
            { role: 'CDM', normX: 0.40, normY: 0.36, slot: 'CM1', number: 5 },
            { role: 'CAM', normX: 0.48, normY: 0.50, slot: 'CM2', number: 8 },
            { role: 'CM', normX: 0.40, normY: 0.64, slot: 'CM3', number: 10 },
            { role: 'RB', normX: 0.38, normY: 0.88, slot: 'RM', number: 2 },
            { role: 'ST', normX: 0.70, normY: 0.40, slot: 'ST1', number: 9 },
            { role: 'ST', normX: 0.70, normY: 0.60, slot: 'ST2', number: 11 }
        ]
    };

    function deriveAttributes(ovr, role) {
        const base = Math.max(50, Math.min(95, ovr));
        const variance = (Math.random() - 0.5) * 4;

        let attrs = {
            pace: base + variance,
            passing: base + variance,
            shooting: base + variance,
            defending: base + variance,
            physical: base + variance,
            dribbling: base + variance,
            vision: base + variance,
            positioning: base + variance,
            reflexes: base + variance,
            technique: base + variance
        };

        if (role === 'GK') {
            attrs.reflexes += 12;
            attrs.positioning += 10;
            attrs.defending += 6;
            attrs.shooting -= 35;
        } else if (role === 'CB') {
            attrs.defending += 10;
            attrs.physical += 8;
            attrs.positioning += 7;
            attrs.shooting -= 20;
        } else if (role === 'LB' || role === 'RB') {
            attrs.pace += 7;
            attrs.defending += 5;
            attrs.passing += 6;
            attrs.vision += 5;
        } else if (role === 'CDM') {
            attrs.defending += 7;
            attrs.physical += 6;
            attrs.passing += 7;
            attrs.vision += 6;
        } else if (role === 'CM' || role === 'CAM') {
            attrs.passing += 10;
            attrs.vision += 10;
            attrs.technique += 8;
            attrs.dribbling += 7;
        } else if (role === 'WINGER') {
            attrs.pace += 10;
            attrs.dribbling += 8;
            attrs.passing += 7;
            attrs.shooting += 5;
        } else if (role === 'ST') {
            attrs.shooting += 12;
            attrs.positioning += 9;
            attrs.pace += 6;
            attrs.defending -= 25;
        }
        return attrs;
    }

    function createOpponentSquad(teamName) {
        const firstNames = ['Lucas', 'Mateus', 'Gabriel', 'Felipe', 'Rodrigo', 'Thiago', 'Guilherme', 'Bruno', 'Arthur', 'Diego', 'Gustavo'];
        const lastNames = ['Silva', 'Santos', 'Souza', 'Oliveira', 'Pereira', 'Lima', 'Ferreira', 'Costa', 'Rodrigues', 'Almeida', 'Gomes'];
        const squad = [];
        for (let i = 0; i < 11; i++) {
            squad.push({
                name: `${firstNames[i % firstNames.length]} ${lastNames[(i + 4) % lastNames.length]}`,
                overall: matchConfig.opponentOverall
            });
        }
        return squad;
    }

    function getShortName(fullName) {
        const parts = fullName.trim().split(' ');
        if (parts.length > 1) {
            return `${parts[0][0]}. ${parts[parts.length - 1]}`;
        }
        return fullName;
    }

    // Initialize simulation
    function initSimulation() {
        cleanupSimulation();
        players = [];
        ballTrail = [];
        confetti = [];
        floatTexts = [];
        clock.reset();

        // Optional reserves supplied by the caller (matchConfig.benchList).
        benchPlayers = Array.isArray(matchConfig.benchList) ? matchConfig.benchList.slice() : [];
        substitutionsMade = 0;
        subCheckAccum = 0;

        stats.home = { goals: 0, shots: 0, shotsOnTarget: 0, passesAttempted: 0, passesCompleted: 0, tackles: 0, corners: 0, fouls: 0, yellowCards: 0, redCards: 0, possessionSeconds: 0 };
        stats.away = { goals: 0, shots: 0, shotsOnTarget: 0, passesAttempted: 0, passesCompleted: 0, tackles: 0, corners: 0, fouls: 0, yellowCards: 0, redCards: 0, possessionSeconds: 0 };
        stats.totalPossessionTime = 0.001;

        if (scoreHomeDisplay) scoreHomeDisplay.innerText = '0';
        if (scoreAwayDisplay) scoreAwayDisplay.innerText = '0';
        if (penaltyScoreRow) penaltyScoreRow.style.display = 'none';

        const userFormationKey = matchConfig.userFormation || '4-3-3';
        const homeLayout = tacticalFormations[userFormationKey] || tacticalFormations['4-3-3'];
        const awayLayout = tacticalFormations['4-3-3'];

        // 1. HOME TEAM (User)
        const userPlayerKeys = Object.keys(matchConfig.playersList);
        homeLayout.forEach((pos, idx) => {
            const slotKey = userPlayerKeys[idx];
            const pData = slotKey ? matchConfig.playersList[slotKey] : { name: `Jogador ${idx + 1}`, overall: matchConfig.userOverall, id: idx + 1 };
            const isGK = pos.role === 'GK';

            const baseX = PITCH.left + pos.normX * (canvas.width / 2 - PITCH.left - 10);
            const baseY = PITCH.top + pos.normY * (PITCH.bottom - PITCH.top);

            const attrs = deriveAttributes(pData.overall, pos.role);

            players.push({
                id: pData.id,
                name: getShortName(pData.name),
                fullName: pData.name,
                overall: pData.overall,
                role: pos.role,
                slot: pos.slot,
                number: pos.number || (idx + 1),
                isHome: true,
                isGK: isGK,
                color: isGK ? '#00e5ff' : (matchConfig.userKitColor || '#00ff66'),
                // Physics Disc Properties (Mesa de Botão)
                x: baseX,
                y: baseY,
                baseX: baseX,
                baseY: baseY,
                targetX: baseX,
                targetY: baseY,
                vx: 0,
                vy: 0,
                mass: isGK ? 2.4 : 1.85,
                radius: isGK ? 15.5 : 14.5,
                maxSpeed: 2.1 + (attrs.pace / 100) * 1.5,
                accel: 0.15 + (attrs.pace / 100) * 0.08,
                friction: 0.92,
                tacticalState: 'POSITIONING',
                aiPhase: 'BUILD_UP',
                actionIntent: null,
                cooldown: 0,
                attrs: attrs,
                // Match Live Ratings & Stamina
                rating: 6.0,
                stamina: 100,
                goals: 0,
                assists: 0,
                yellowCards: 0,
                redCard: false,
                opacity: 1.0,
                phaseShift: idx * 0.7
            });
        });

        // 2. AWAY TEAM (Opponent)
        const opponentSquad = createOpponentSquad(matchConfig.opponentTeamName);
        awayLayout.forEach((pos, idx) => {
            const pData = opponentSquad[idx];
            const isGK = pos.role === 'GK';

            const baseX = PITCH.right - pos.normX * (PITCH.right - canvas.width / 2 - 10);
            const baseY = PITCH.top + pos.normY * (PITCH.bottom - PITCH.top);

            const attrs = deriveAttributes(matchConfig.opponentOverall, pos.role);

            players.push({
                id: 4000 + idx,
                name: getShortName(pData.name),
                fullName: pData.name,
                overall: matchConfig.opponentOverall,
                role: pos.role,
                slot: pos.slot,
                number: pos.number || (idx + 1),
                isHome: false,
                isGK: isGK,
                color: isGK ? '#ffd700' : '#ef4444',
                // Physics Disc Properties (Mesa de Botão)
                x: baseX,
                y: baseY,
                baseX: baseX,
                baseY: baseY,
                targetX: baseX,
                targetY: baseY,
                vx: 0,
                vy: 0,
                mass: isGK ? 2.4 : 1.85,
                radius: isGK ? 15.5 : 14.5,
                maxSpeed: 2.1 + (attrs.pace / 100) * 1.5,
                accel: 0.15 + (attrs.pace / 100) * 0.08,
                friction: 0.92,
                tacticalState: 'POSITIONING',
                aiPhase: 'BUILD_UP',
                actionIntent: null,
                cooldown: 0,
                attrs: attrs,
                // Match Live Ratings & Stamina
                rating: 6.0,
                stamina: 100,
                goals: 0,
                assists: 0,
                yellowCards: 0,
                redCard: false,
                opacity: 1.0,
                phaseShift: (idx + 11) * 0.7
            });
        });

        updateLineupDOM();
        resetKickoff(true);
    }

    // Render 11 players in Left & Right Columns
    function updateLineupDOM() {
        if (!homeLineupList || !awayLineupList) return;

        const homePlayers = players.filter(p => p.isHome);
        const awayPlayers = players.filter(p => !p.isHome);

        const buildRows = (list, isHome) => {
            return list.map(p => {
                let statusBadges = '';
                for (let i = 0; i < p.goals; i++) statusBadges += ' ⚽';
                if (p.yellowCards === 1) statusBadges += ' 🟨';
                if (p.redCard) statusBadges += ' 🟥';

                let ratingClass = 'mid';
                if (p.rating >= 7.5) ratingClass = 'high';
                else if (p.rating < 6.0) ratingClass = 'low';

                if (currentTab === 'stamina') {
                    const stPct = Math.round(p.stamina);
                    if (isHome) {
                        return `
                            <div class="fifa-player-item" title="${p.fullName} - Energia: ${stPct}%">
                                <span class="fifa-player-num">${p.number}</span>
                                <div>
                                    <div class="fifa-player-name">${p.name}${statusBadges}</div>
                                    <div class="fifa-player-stamina-wrap"><div class="fifa-player-stamina-bar" style="width: ${stPct}%;"></div></div>
                                </div>
                                <span class="fifa-player-num" style="font-size: 0.7rem;">${stPct}%</span>
                            </div>
                        `;
                    } else {
                        return `
                            <div class="fifa-player-item" title="${p.fullName} - Energia: ${stPct}%">
                                <span class="fifa-player-num" style="font-size: 0.7rem;">${stPct}%</span>
                                <div>
                                    <div class="fifa-player-name">${p.name}${statusBadges}</div>
                                    <div class="fifa-player-stamina-wrap"><div class="fifa-player-stamina-bar" style="width: ${stPct}%;"></div></div>
                                </div>
                                <span class="fifa-player-num">${p.number}</span>
                            </div>
                        `;
                    }
                } else {
                    if (isHome) {
                        return `
                            <div class="fifa-player-item" title="${p.fullName} - OVR ${p.overall}">
                                <span class="fifa-player-num">${p.number}</span>
                                <span class="fifa-player-name">${p.name}${statusBadges}</span>
                                <span class="fifa-player-rating ${ratingClass}">${p.rating.toFixed(1)}</span>
                            </div>
                        `;
                    } else {
                        return `
                            <div class="fifa-player-item" title="${p.fullName} - OVR ${p.overall}">
                                <span class="fifa-player-rating ${ratingClass}">${p.rating.toFixed(1)}</span>
                                <span class="fifa-player-name">${p.name}${statusBadges}</span>
                                <span class="fifa-player-num">${p.number}</span>
                            </div>
                        `;
                    }
                }
            }).join('');
        };

        homeLineupList.innerHTML = buildRows(homePlayers, true);
        awayLineupList.innerHTML = buildRows(awayPlayers, false);
    }

    // Reset Kickoff
    function resetKickoff(isHomeKickoff) {
        ball.reset(canvas.width / 2, canvas.height / 2);
        ball.carrier = null;
        ball.state = 'LOOSE';

        players.forEach(p => {
            if (p.redCard) return;
            p.targetX = p.baseX;
            p.targetY = p.baseY;
            p.x = p.baseX;
            p.y = p.baseY;
            p.vx = 0;
            p.vy = 0;
            p.tacticalState = 'POSITIONING';
        });

        const kicker = players.find(p => p.isHome === isHomeKickoff && (p.role === 'ST' || p.role === 'WINGER') && !p.redCard);
        if (kicker) {
            kicker.x = isHomeKickoff ? canvas.width / 2 - 18 : canvas.width / 2 + 18;
            kicker.y = canvas.height / 2;
            kicker.targetX = kicker.x;
            kicker.targetY = kicker.y;
            trackTimeout(() => {
                if (kicker && !ball.carrier) {
                    takePossession(kicker);
                }
            }, 300);
        }

        subStateSeconds = 1.0;
        currentActionDescription = 'Saída de bola';
    }

    // ========================================================
    // 10. GAME LOOP WITH DELTATIME (RESILIENT TO 60/120/144 FPS)
    // ========================================================
    if (btnStart) {
        btnStart.addEventListener('click', function() {
            if (currentState !== MATCH_STATE.PRE_MATCH) return;

            initSimulation();
            currentState = MATCH_STATE.FIRST_HALF;
            btnStart.style.display = 'none';

            if (phaseBadge) {
                phaseBadge.innerText = '1º TEMPO';
                phaseBadge.className = 'sb-phase-badge live-1h';
            }

            eventManager.log('🔔 Apito Inicial! A bola rola na Copa do Brasil.', 'info');
            addFloatText('APITO INICIAL! ⚽', canvas.width / 2, canvas.height / 2, '#00ff66');

            lastTimestamp = performance.now();
            animationFrameId = requestAnimationFrame(gameLoop);
        });
    }

    function gameLoop(timestamp) {
        if (!lastTimestamp) lastTimestamp = timestamp;
        let dt = (timestamp - lastTimestamp) / 1000;
        lastTimestamp = timestamp;

        if (dt > 0.1) dt = 0.1; // Safety clamp against background tab drift

        update(dt);
        render();

        // Stop the loop permanently once the match is fully over; keep running otherwise.
        if (currentState !== MATCH_STATE.FULL_TIME) {
            animationFrameId = requestAnimationFrame(gameLoop);
        } else {
            animationFrameId = null;
        }
    }

    // ========================================================
    // 11. CENTRAL UPDATE ROUTINE
    // ========================================================
    function update(dt) {
        if (currentState === MATCH_STATE.PRE_MATCH) {
            updateVisualFX(dt);
            return;
        }

        clock.update(dt);

        if (currentState === MATCH_STATE.HALFTIME) {
            updateVisualFX(dt);
            return;
        }

        if (currentState === MATCH_STATE.PENALTY_SHOOTOUT) {
            updatePenaltyShootout(dt);
            updateVisualFX(dt);
            return;
        }

        if (currentState === MATCH_STATE.GOAL_MOMENT) {
            updateStateTransitions(dt);
            updatePlayerPhysics(dt);
            updateBallPhysics(dt);
            updateVisualFX(dt);
            return;
        }

        if (currentState === MATCH_STATE.FULL_TIME) {
            updateVisualFX(dt);
            return;
        }

        updatePossessionStats(dt);
        updateSubstitutions(dt);
        updateStateTransitions(dt);
        updatePlayerPhysics(dt);
        updateTacticalAI(dt);
        updateBallPhysics(dt);
        resolveCollisions();
        resolveBallPlayerCollisions();
        checkAntiStuckWatchdog(dt);
        updateVisualFX(dt);
    }

    function updatePossessionStats(dt) {
        if (!isClockRunningState()) return;

        stats.totalPossessionTime += dt;
        if (ball.carrier) {
            if (ball.carrier.isHome) stats.home.possessionSeconds += dt;
            else stats.away.possessionSeconds += dt;
        } else if (ball.lastTouchedBy) {
            if (ball.lastTouchedBy.isHome) stats.home.possessionSeconds += dt * 0.5;
            else stats.away.possessionSeconds += dt * 0.5;
        }

        // Gradual stamina expenditure
        players.forEach(p => {
            if (p.stamina > 50) {
                p.stamina -= (dt * 0.04 * matchSpeedMultiplier);
            }
        });
    }

    // ========================================================
    // 11b. AUTOMATIC SUBSTITUTIONS (when reserves are provided)
    // ========================================================
    function updateSubstitutions(dt, force = false) {
        if (!benchPlayers.length) return;
        if (substitutionsMade >= MAX_SUBSTITUTIONS) return;

        subCheckAccum += dt;
        if (!force && subCheckAccum < 1.0) return;
        subCheckAccum = 0;

        let candidates = players
            .filter(p => p.isHome && !p.redCard && !p.isGK && p !== ball.carrier)
            .sort((a, b) => a.stamina - b.stamina);

        if (force) {
            // Halftime: manager refreshes the most-worn outfielders (up to 3)
            candidates = candidates.slice(0, Math.min(3, MAX_SUBSTITUTIONS - substitutionsMade));
        } else {
            candidates = candidates.filter(p => p.stamina < 30);
        }

        if (!candidates.length) return;

        candidates.forEach(onField => {
            if (substitutionsMade >= MAX_SUBSTITUTIONS) return;
            const benchIdx = benchPlayers.findIndex(b => b && !b.used);
            if (benchIdx === -1) return;

            const bench = benchPlayers[benchIdx];
            bench.used = true;
            substitutionsMade++;

            const incomingAttrs = deriveAttributes(bench.overall || onField.overall, onField.role);
            const outgoingName = onField.fullName;

            onField.id = bench.id != null ? bench.id : onField.id;
            onField.fullName = bench.name || outgoingName;
            onField.name = getShortName(onField.fullName);
            onField.overall = bench.overall || onField.overall;
            onField.attrs = incomingAttrs;
            onField.maxSpeed = 2.1 + (incomingAttrs.pace / 100) * 1.5;
            onField.stamina = 100;
            onField.rating = 6.0;
            onField.goals = 0;
            onField.assists = 0;
            onField.yellowCards = 0;
            onField.opacity = 1.0;
            onField.cooldown = 30;

            eventManager.log(`🔄 Substituição: ${onField.fullName} entra no lugar de ${outgoingName}.`, 'info');
            addFloatText('SUBSTITUIÇÃO 🔄', onField.x, onField.y - 20, '#38bdf8');
        });

        updateLineupDOM();
    }

    function updateStateTransitions(dt) {
        if (subStateSeconds > 0) {
            subStateSeconds -= dt;
            return;
        }

        if (currentState === MATCH_STATE.GOAL_MOMENT) {
            const isHomeScored = (goalScorerInfo && goalScorerInfo.isHome);
            resetKickoff(!isHomeScored);
            currentState = resolvePostGoalMatchState();
            goalScorerInfo = null;
            if (phaseBadge) {
                const is2H = currentState === MATCH_STATE.SECOND_HALF || currentState === MATCH_STATE.SECOND_HALF_ADDED;
                if (currentState === MATCH_STATE.FIRST_HALF_ADDED || currentState === MATCH_STATE.SECOND_HALF_ADDED) {
                    phaseBadge.innerText = 'ACRÉSCIMOS';
                    phaseBadge.className = 'sb-phase-badge added-time';
                } else {
                    phaseBadge.innerText = is2H ? '2º TEMPO' : '1º TEMPO';
                    phaseBadge.className = is2H ? 'sb-phase-badge live-2h' : 'sb-phase-badge live-1h';
                }
            }
        }
    }

    function exitSetPieceToOpenPlay() {
        if (currentState !== MATCH_STATE.SET_PIECE) return;
        if (!clock.isHalftimeProcessed) {
            currentState = clock.matchMinute >= 45 ? MATCH_STATE.FIRST_HALF_ADDED : MATCH_STATE.FIRST_HALF;
        } else {
            currentState = clock.matchMinute >= 90 ? MATCH_STATE.SECOND_HALF_ADDED : MATCH_STATE.SECOND_HALF;
        }
        setPieceWatchdogTimer = 0;
    }

    // ========================================================
    // 12. HALFTIME SEQUENCE
    // ========================================================
    function triggerHalftime() {
        // Manager makes changes at the break (before the feed is frozen for halftime)
        updateSubstitutions(0, true);

        currentState = MATCH_STATE.HALFTIME;
        ball.vx = 0; ball.vy = 0; ball.vz = 0; ball.carrier = null;
        players.forEach(p => { p.vx = 0; p.vy = 0; });

        if (phaseBadge) {
            phaseBadge.innerText = 'INTERVALO';
            phaseBadge.className = 'sb-phase-badge halftime';
        }
        if (addedTimeDisplay) addedTimeDisplay.style.display = 'none';
        if (timerDisplay) timerDisplay.innerText = 'INTERVALO';

        eventManager.log('⏸️ 45\' — Fim do primeiro tempo! Equipes conversam no vestiário.', 'halftime');
        addFloatText('INTERVALO DE JOGO ⏸️', canvas.width / 2, canvas.height / 2, '#38bdf8');

        renderStatsTable(htStatsTable);
        if (htScoreHome) htScoreHome.innerText = stats.home.goals;
        if (htScoreAway) htScoreAway.innerText = stats.away.goals;
        if (halftimeOverlay) halftimeOverlay.classList.add('active');

        let countdown = 5;
        if (htCountdownSpan) htCountdownSpan.innerText = countdown;

        if (halftimeIntervalId) clearInterval(halftimeIntervalId);
        halftimeIntervalId = setInterval(() => {
            countdown--;
            if (htCountdownSpan) htCountdownSpan.innerText = countdown;
            if (countdown <= 0) {
                clearInterval(halftimeIntervalId);
                halftimeIntervalId = null;
                resumeSecondHalf();
            }
        }, 1000);

        if (btnResumeHalftime) {
            btnResumeHalftime.onclick = function() {
                if (halftimeIntervalId) {
                    clearInterval(halftimeIntervalId);
                    halftimeIntervalId = null;
                }
                resumeSecondHalf();
            };
        }
    }

    function resumeSecondHalf() {
        if (halftimeOverlay) halftimeOverlay.classList.remove('active');

        currentState = MATCH_STATE.SECOND_HALF;
        clock.simulatedSeconds = 46 * 60;
        clock.syncDisplayFromSimulated();

        if (phaseBadge) {
            phaseBadge.innerText = '2º TEMPO';
            phaseBadge.className = 'sb-phase-badge live-2h';
        }

        resetKickoff(false);
        eventManager.log('▶️ Apito do árbitro! Começa o segundo tempo da partida.', 'info');
        addFloatText('2º TEMPO ▶️', canvas.width / 2, canvas.height / 2, '#38bdf8');
    }

    // ========================================================
    // 13. FULL-TIME & PENALTY ROUTING
    // ========================================================
    function triggerFullTime() {
        ball.vx = 0; ball.vy = 0; ball.carrier = null;
        if (addedTimeDisplay) addedTimeDisplay.style.display = 'none';

        eventManager.log(`🏁 Fim do tempo regulamentar! Placar: ${stats.home.goals} × ${stats.away.goals}.`, 'info');

        if (stats.home.goals > stats.away.goals) {
            currentState = MATCH_STATE.FULL_TIME;
            if (phaseBadge) { phaseBadge.innerText = 'FIM DE JOGO'; phaseBadge.className = 'sb-phase-badge'; }
            showPostMatchDialog('win', `Vitória maiúscula! Você venceu por ${stats.home.goals} × ${stats.away.goals} e garantiu a classificação.`);
        } else if (stats.home.goals < stats.away.goals) {
            currentState = MATCH_STATE.FULL_TIME;
            if (phaseBadge) { phaseBadge.innerText = 'FIM DE JOGO'; phaseBadge.className = 'sb-phase-badge'; }
            showPostMatchDialog('lose', `Derrota por ${stats.home.goals} × ${stats.away.goals}. Seu time foi eliminado da Copa.`);
        } else {
            // TIED IN ELIMINATION TOURNAMENT -> PENALTY SHOOTOUT (NEVER FREEZES!)
            eventManager.log('⚖️ Empate no tempo normal! A vaga será decidida na DISPUTA DE PÊNALTIS!', 'info');
            addFloatText('DECISÃO NOS PÊNALTIS! ⚖️', canvas.width / 2, canvas.height / 2, '#ffd700');
            trackTimeout(startPenaltyShootout, 1200);
        }
    }

    // ========================================================
    // 14. BUTTON DISC PHYSICS & STEERING WITH INERTIA
    // ========================================================
    function updatePlayerPhysics(dt) {
        const simScale = matchSpeedMultiplier;
        const fs = dt * 60;
        players.forEach(p => {
            if (p.redCard && p.opacity <= 0) return;
            if (p.cooldown > 0) p.cooldown -= dt * 60 * simScale;

            const dx = p.targetX - p.x;
            const dy = p.targetY - p.y;
            const dist = Math.sqrt(dx * dx + dy * dy);

            if (dist > 2.5) {
                const targetVx = (dx / dist) * Math.min(p.maxSpeed, dist * 0.14);
                const targetVy = (dy / dist) * Math.min(p.maxSpeed, dist * 0.14);
                const accelStep = Math.min(1, 1 - Math.pow(1 - p.accel, fs));

                p.vx += (targetVx - p.vx) * accelStep;
                p.vy += (targetVy - p.vy) * accelStep;
            } else {
                const fr = Math.pow(p.friction, fs);
                p.vx *= fr;
                p.vy *= fr;
            }

            p.x += p.vx * fs;
            p.y += p.vy * fs;

            // Strict Pitch boundaries
            p.x = Math.max(PITCH.left + p.radius, Math.min(PITCH.right - p.radius, p.x));
            p.y = Math.max(PITCH.top + p.radius, Math.min(PITCH.bottom - p.radius, p.y));
        });
    }

    // Elastic collisions between player buttons + teammate anti-clumping repulsion
    function resolveCollisions() {
        for (let i = 0; i < players.length; i++) {
            const p1 = players[i];
            if (p1.redCard) continue;

            for (let j = i + 1; j < players.length; j++) {
                const p2 = players[j];
                if (p2.redCard) continue;

                const dx = p2.x - p1.x;
                const dy = p2.y - p1.y;
                const dist = Math.sqrt(dx * dx + dy * dy);
                const minDist = p1.radius + p2.radius;

                // 1. Solid Contact collision
                if (dist < minDist && dist > 0.001) {
                    const overlap = (minDist - dist) * 0.52;
                    const nx = dx / dist;
                    const ny = dy / dist;

                    p1.x -= nx * overlap;
                    p1.y -= ny * overlap;
                    p2.x += nx * overlap;
                    p2.y += ny * overlap;

                    const kx = p1.vx - p2.vx;
                    const ky = p1.vy - p2.vy;
                    const p = 1.8 * (nx * kx + ny * ky) / (p1.mass + p2.mass);

                    p1.vx -= p * p2.mass * nx;
                    p1.vy -= p * p2.mass * ny;
                    p2.vx += p * p1.mass * nx;
                    p2.vy += p * p1.mass * ny;
                }
                // 2. Anti-clumping soft spacing between teammates (Item 21)
                else if (p1.isHome === p2.isHome && dist < 36 && dist > minDist && !p1.isGK && !p2.isGK) {
                    const repel = (36 - dist) * 0.025;
                    const nx = dx / dist;
                    const ny = dy / dist;
                    p1.x -= nx * repel;
                    p1.y -= ny * repel;
                    p2.x += nx * repel;
                    p2.y += ny * repel;
                }
            }
        }
    }

    function resolveBallPlayerCollisions() {
        if (ball.carrier || currentState === MATCH_STATE.HALFTIME) return;

        const ballSpeed = Math.sqrt(ball.vx * ball.vx + ball.vy * ball.vy);

        shuffledPlayers().forEach(p => {
            if (p.redCard) return;

            const dx = ball.x - p.x;
            const dy = ball.y - p.y;
            const dist = Math.sqrt(dx * dx + dy * dy);
            const minDist = ball.radius + p.radius;

            if (dist >= minDist || dist < 0.001) return;

            const nx = dx / dist;
            const ny = dy / dist;
            const overlap = minDist - dist;

            ball.x += nx * overlap * 0.6;
            ball.y += ny * overlap * 0.6;
            p.x -= nx * overlap * 0.4;
            p.y -= ny * overlap * 0.4;

            // Safe reception or control
            if (ballSpeed < 3.2 && ball.state !== 'SHOOTING') {
                takePossession(p);
                return;
            }

            // Deflection impulse
            const relNormal = ball.vx * nx + ball.vy * ny;
            if (relNormal < 0) {
                ball.vx -= (1.45 * relNormal) * nx;
                ball.vy -= (1.45 * relNormal) * ny;
                ball.state = 'DEFLECTED';
                ball.lastTouchedBy = p;
                ball.lastTeam = p.isHome ? 'home' : 'away';
            }
        });
    }

    function countNearbyOpponents(player, radius) {
        let n = 0;
        players.forEach(o => {
            if (o.isHome === player.isHome || o.redCard || o.isGK) return;
            if (distance(player, o) < radius) n++;
        });
        return n;
    }

    // Randomized update order: without this, whichever team sits first in the
    // players array wins every simultaneous 50/50 loose-ball / tackle contest,
    // creating a large structural bias toward the home team.
    function shuffledPlayers() {
        const arr = players.slice();
        for (let i = arr.length - 1; i > 0; i--) {
            const j = Math.floor(Math.random() * (i + 1));
            const tmp = arr[i];
            arr[i] = arr[j];
            arr[j] = tmp;
        }
        return arr;
    }

    // ========================================================
    // 15. DYNAMIC TACTICAL MOVEMENT (BLOCK & BALL-CENTRIC)
    // ========================================================
    function updateTacticalAI(dt) {
        if (!isSimulationActiveState()) return;

        const ballX = ball.x;
        const ballY = ball.y;

        // Determine team possessing the ball
        const possessingTeam = ball.carrier ? (ball.carrier.isHome ? 'home' : 'away') : ball.lastTeam;

        // Team block shift: team in possession pushes up, defending team drops back
        const homeInPossession = (possessingTeam === 'home');
        const homeBlockOffset = homeInPossession ? 55 : -35;
        const awayBlockOffset = !homeInPossession ? -55 : 35;

        // Lateral ball shift: whole team tilts towards the play
        const ballLateralRatio = (ballY - canvas.height / 2) / (canvas.height / 2);

        shuffledPlayers().forEach(p => {
            if (p.redCard) return;

            if (p.isGK) {
                updateGoalkeeperAI(p, dt);
                return;
            }

            if (ball.carrier === p) {
                updateBallCarrierAI(p, dt);
                return;
            }

            // During set pieces, keep arranged positions (wall, box crowding) intact.
            if (currentState === MATCH_STATE.SET_PIECE) return;

            // Calculate dynamic anchor based on role and phase
            const isAttacking = (p.isHome === homeInPossession);
            const teamBlock = p.isHome ? homeBlockOffset : awayBlockOffset;

            let anchorX = p.baseX + teamBlock + (ballX - canvas.width / 2) * 0.28;
            let anchorY = p.baseY + ballLateralRatio * 18;

            // Role-specific tactical adjustments
            switch (p.role) {
                case 'CB':
                    // Center-backs maintain defensive depth & protect box
                    if (p.isHome) anchorX = Math.min(canvas.width * 0.40, Math.max(PITCH.left + 50, anchorX));
                    else anchorX = Math.max(canvas.width * 0.60, Math.min(PITCH.right - 50, anchorX));
                    break;

                case 'LB':
                case 'RB':
                    // Fullbacks provide width, overlap in attack, tuck in when defending
                    if (isAttacking && (p.isHome ? ballX > canvas.width * 0.45 : ballX < canvas.width * 0.55)) {
                        // Overlapping run along flank
                        const overlapDir = p.isHome ? 1 : -1;
                        anchorX += overlapDir * 40;
                    }
                    break;

                case 'CDM':
                    // Defensive midfielder stays central, covers space, offers safe pass
                    if (p.isHome) anchorX = Math.min(canvas.width * 0.55, Math.max(PITCH.left + 80, anchorX));
                    else anchorX = Math.max(canvas.width * 0.45, Math.min(PITCH.right - 80, anchorX));
                    anchorY = PITCH.goalCenterY + (p.baseY - PITCH.goalCenterY) * 0.6;
                    break;

                case 'CM':
                case 'CAM':
                    // Midfielders create passing triangles
                    if (isAttacking) {
                        const pushDir = p.isHome ? 1 : -1;
                        anchorX += pushDir * 30;
                    }
                    break;

                case 'WINGER':
                    // Wingers stretch field width and attack depth
                    if (isAttacking) {
                        const attackDir = p.isHome ? 1 : -1;
                        anchorX += attackDir * 45;
                    }
                    break;

                case 'ST':
                    // Striker attacks penalty box depth
                    if (isAttacking) {
                        const targetBoxX = p.isHome ? PITCH.right - 70 : PITCH.left + 70;
                        anchorX = p.isHome ? Math.max(anchorX, targetBoxX - 40) : Math.min(anchorX, targetBoxX + 40);
                    }
                    break;
            }

            // Natural organic breathing offset
            const breatheT = clock.simulatedSeconds * 0.05 + p.phaseShift;
            let targetX = anchorX + Math.cos(breatheT) * 6;
            let targetY = anchorY + Math.sin(breatheT) * 6;

            const distToBall = distance(p, ball);

            // 1. Intercepting a pass traveling towards him
            if (ball.state === 'PASSING' && ball.targetReceiver === p) {
                targetX = ball.x + ball.vx * 4;
                targetY = ball.y + ball.vy * 4;
            }
            // 2. Challenging loose ball
            else if (!ball.carrier && distToBall < 135) {
                targetX = ball.x;
                targetY = ball.y;
            }
            // 3. Closing down opponent carrier (Pressing)
            else if (ball.carrier && ball.carrier.isHome !== p.isHome && distToBall < 95) {
                targetX = ball.carrier.x;
                targetY = ball.carrier.y;

                if (distToBall < p.radius + ball.carrier.radius + 6 && p.cooldown <= 0 && ball.carrier.cooldown <= 0) {
                    attemptTackle(p, ball.carrier);
                }
            }
            // 4. Teammate support run (Triangulation)
            else if (ball.carrier && ball.carrier.isHome === p.isHome) {
                if (distToBall < 110 && distToBall > 50) {
                    // Position at supportive angle
                    const supportAngle = (p.number % 2 === 0) ? 0.7 : -0.7;
                    targetX = ball.carrier.x + (p.isHome ? 25 : -25);
                    targetY = ball.carrier.y + Math.sin(supportAngle) * 45;
                }
            }

            p.targetX = targetX;
            p.targetY = targetY;

            // Collect loose ball on touch
            if (!ball.carrier && distToBall < p.radius + ball.radius + 4 && ball.state !== 'SHOOTING') {
                takePossession(p);
            }
        });
    }

    // Goalkeeper AI: positional guard, reflex diving, safe handling
    function updateGoalkeeperAI(gk, dt) {
        const fs = dt * 60;
        const goalLineX = gk.isHome ? PITCH.left + 16 : PITCH.right - 16;
        gk.targetX = goalLineX;

        if (ball.state === 'SHOOTING') {
            const diveSpeed = 3.8 + (gk.attrs.reflexes / 100) * 2.8;
            const targetY = Math.max(PITCH.goalTop + 14, Math.min(PITCH.goalBottom - 14, ball.shotTargetY));

            if (gk.y < targetY - 4) gk.y += diveSpeed * fs;
            else if (gk.y > targetY + 4) gk.y -= diveSpeed * fs;

            if (distance(gk, ball) < gk.radius + ball.radius + 10) {
                resolveGoalkeeperSave(gk);
            }
        } else {
            // Guard arc between goal posts
            const desiredY = Math.max(PITCH.goalTop + 16, Math.min(PITCH.goalBottom - 16, ball.y));
            gk.targetY = desiredY;

            const dist = distance(gk, ball);
            if (!ball.carrier && dist < 55) {
                takePossession(gk);
                eventManager.log(`🧤 Defesa de ${gk.fullName}, que segura e recoloca em jogo.`, 'save');
                trackTimeout(() => {
                    if (ball.carrier === gk) distributeGoalKick(gk);
                }, 600);
            }
        }
    }

    // ========================================================
    // 16. BALL CARRIER DECISION TREE (ELIMINATES RUN-TO-GOAL)
    // ========================================================
    function updateBallCarrierAI(carrier, dt) {
        const fs = dt * 60;
        const attackDir = carrier.isHome ? 1 : -1;
        const opponentGoalX = carrier.isHome ? PITCH.right : PITCH.left;
        const distToGoal = Math.abs(opponentGoalX - carrier.x);
        const phaseKey = carrier.isHome ? 'home' : 'away';

        // Evaluate tactical phase
        if (distToGoal > 320) teamAiPhase[phaseKey] = 'BUILD_UP';
        else if (distToGoal > 210) teamAiPhase[phaseKey] = 'PROGRESSION';
        else if (distToGoal > 130) teamAiPhase[phaseKey] = 'FINAL_THIRD';
        else teamAiPhase[phaseKey] = 'CHANCE_CREATION';
        carrier.aiPhase = teamAiPhase[phaseKey];

        // Smooth physical ball carriage (frame-rate normalized)
        const targetBx = carrier.x + attackDir * (carrier.radius + ball.radius - 1);
        const targetBy = carrier.y;
        const carryLerp = 1 - Math.pow(1 - 0.45, fs);
        ball.x += (targetBx - ball.x) * carryLerp;
        ball.y += (targetBy - ball.y) * carryLerp;
        ball.vx = carrier.vx * 0.85;
        ball.vy = carrier.vy * 0.85;

        // Set-piece takers must wait for the arranged restart (wall/cross) to be executed.
        if (currentState === MATCH_STATE.SET_PIECE) return;

        if (carrier.cooldown > 0) return;

        const pressure = countNearbyOpponents(carrier, 85);
        const receiver = findPassCandidate(carrier);
        const isWide = (carrier.y < PITCH.top + 80 || carrier.y > PITCH.bottom - 80);
        const inBoxRange = distToGoal < 185;

        // 1. SHOOT: attackers pull the trigger in and around the box
        if (inBoxRange) {
            const rangeFactor = distToGoal < 100 ? 1.0 : (distToGoal < 150 ? 0.9 : 0.8);
            const shotQuality = (carrier.attrs.shooting / 100) * 0.85 * rangeFactor - pressure * 0.05;
            const pointBlank = distToGoal < 95;
            const shootingPhase = carrier.aiPhase === 'CHANCE_CREATION' || carrier.aiPhase === 'FINAL_THIRD';
            if (pointBlank || (shootingPhase && shotQuality > 0.33)) {
                executeShot(carrier);
                return;
            }
        }

        // 2. CROSS: Wide in final third
        if (isWide && distToGoal < 260 && carrier.aiPhase !== 'BUILD_UP') {
            const strikerTarget = players.find(p => p.isHome === carrier.isHome && p.role === 'ST' && !p.redCard);
            if (strikerTarget) {
                executeCross(carrier, strikerTarget);
                return;
            }
        }

        // 3. PASS UNDER PRESSURE (Crucial: prevents running straight into tackler!)
        if (pressure >= 1 && receiver) {
            executePass(carrier, receiver);
            return;
        }

        // 4. PROGRESSION PASS: Open teammate with forward advantage
        if (receiver) {
            const forwardProgress = carrier.isHome ? (receiver.x - carrier.x) : (carrier.x - receiver.x);
            if (forwardProgress > 35) {
                executePass(carrier, receiver);
                return;
            }
        }

        // 5. CONTROLLED DRIBBLE (Into space, NOT straight into defenders)
        let driveY = carrier.y;
        if (distToGoal < 260) {
            driveY = PITCH.goalCenterY + (carrier.y - PITCH.goalCenterY) * 0.4;
        }
        carrier.targetX = carrier.x + attackDir * (35 + (carrier.attrs.pace / 100) * 20);
        carrier.targetY = driveY;
        currentActionDescription = 'Condução';
    }

    function takePossession(player) {
        ball.carrier = player;
        ball.targetReceiver = null;
        ball.lastTouchedBy = player;
        ball.lastTeam = player.isHome ? 'home' : 'away';
        ball.state = 'CONTROLLED';
        player.cooldown = 6;
    }

    // ========================================================
    // 17. TRUE PASSING SYSTEM WITH TRAJECTORY & ERROR
    // ========================================================
    function findPassCandidate(passer) {
        const teammates = players.filter(p => (
            p.isHome === passer.isHome &&
            !p.isGK &&
            !p.redCard &&
            p !== passer
        ));

        let best = null;
        let bestScore = -Infinity;

        teammates.forEach(t => {
            const d = distance(passer, t);
            if (d < 40 || d > 310) return;

            const forwardProgress = passer.isHome ? (t.x - passer.x) : (passer.x - t.x);
            const opponentsNearTarget = countNearbyOpponents(t, 60);

            // Evaluate open passing lane & support score
            const score = (forwardProgress * 1.4) + (t.attrs.positioning * 0.5) - (d * 0.25) - (opponentsNearTarget * 25);
            if (score > bestScore) {
                bestScore = score;
                best = t;
            }
        });

        return best;
    }

    function executePass(passer, receiver) {
        const teamKey = passer.isHome ? 'home' : 'away';
        stats[teamKey].passesAttempted++;

        // Pass error calculation based on passing stat, vision & pressure
        const pressure = countNearbyOpponents(passer, 80);
        const passQuality = (passer.attrs.passing + passer.attrs.vision) / 200 - (pressure * 0.1);
        const isAccurate = Math.random() < Math.max(0.65, passQuality);

        // Anticipate receiver movement
        const leadDist = 18;
        const leadX = receiver.x + (receiver.vx * leadDist);
        const leadY = receiver.y + (receiver.vy * leadDist);

        const errorAngle = isAccurate ? 0 : (Math.random() - 0.5) * 0.35;
        const dx = leadX - passer.x;
        const dy = leadY - passer.y;
        const baseAngle = Math.atan2(dy, dx) + errorAngle;
        const dist = Math.sqrt(dx * dx + dy * dy);

        const power = 7.0 + (passer.attrs.passing / 100) * 3.0;
        const vx = Math.cos(baseAngle) * power;
        const vy = Math.sin(baseAngle) * power;

        ball.kick(vx, vy, 0, passer, false, receiver);
        ball.shotAssister = passer;
        passer.cooldown = 16;
        receiver.cooldown = 8;

        if (isAccurate) {
            stats[teamKey].passesCompleted++;
            passer.rating = Math.min(10.0, passer.rating + 0.1);
        }

        currentActionDescription = `Passe (${passer.name} ➔ ${receiver.name})`;
        addFloatText('PASSE! ⚽', passer.x, passer.y - 18, passer.isHome ? '#00ff66' : '#f87171');
    }

    function executeCross(passer, receiver) {
        const teamKey = passer.isHome ? 'home' : 'away';
        stats[teamKey].passesAttempted++;

        const targetX = passer.isHome ? PITCH.right - 70 : PITCH.left + 70;
        const targetY = PITCH.goalCenterY + (Math.random() - 0.5) * 70;

        const dx = targetX - passer.x;
        const dy = targetY - passer.y;
        const dist = Math.sqrt(dx * dx + dy * dy);

        // Lofted cross with height (z > 0)
        ball.kick((dx / dist) * 7.5, (dy / dist) * 7.5, 4.5, passer, false, receiver);
        ball.shotAssister = passer;
        passer.cooldown = 32;

        passer.rating = Math.min(10.0, passer.rating + 0.2);
        currentActionDescription = 'Cruzamento';
        eventManager.log(`🎯 Cruzamento perigoso alçado na grande área por ${passer.fullName}!`, 'info');
        addFloatText('CRUZAMENTO! 🎯', passer.x, passer.y - 18, '#ffd700');
    }

    function executeShot(shooter) {
        const teamKey = shooter.isHome ? 'home' : 'away';
        stats[teamKey].shots++;

        const opponentGoalX = shooter.isHome ? PITCH.right : PITCH.left;
        const corners = [
            PITCH.goalTop + 16,
            PITCH.goalBottom - 16,
            PITCH.goalCenterY - 14,
            PITCH.goalCenterY + 14
        ];
        const targetY = corners[Math.floor(Math.random() * corners.length)] + (Math.random() - 0.5) * 14;
        ball.shotTargetY = targetY;

        const isOnTarget = (Math.random() < 0.65 + (shooter.attrs.shooting / 100) * 0.25);
        if (isOnTarget) {
            stats[teamKey].shotsOnTarget++;
            shooter.rating = Math.min(10.0, shooter.rating + 0.3);
        }

        const aimY = isOnTarget ? targetY : (targetY + (Math.random() > 0.5 ? 45 : -45));
        const dx = opponentGoalX - shooter.x;
        const dy = aimY - shooter.y;
        const dist = Math.sqrt(dx * dx + dy * dy);

        const power = 11.0 + (shooter.attrs.shooting / 100) * 4.8;
        ball.kick((dx / dist) * power, (dy / dist) * power, Math.random() * 2, shooter, true);
        shooter.cooldown = 36;

        screenShake = 12;
        currentActionDescription = 'Finalização';
        eventManager.log(`💥 FINALIZAÇÃO! Chute firme de ${shooter.fullName} em direção ao gol!`, 'info');
        addFloatText('CHUTE! 💥', shooter.x, shooter.y - 20, shooter.isHome ? '#00ff66' : '#ef4444');
    }

    function attemptTackle(defender, attacker) {
        defender.cooldown = 24;
        attacker.cooldown = 20;

        const defScore = defender.attrs.defending + Math.random() * 25;
        const attScore = attacker.attrs.dribbling + Math.random() * 25;

        // Reckless challenges are more likely when the attacker is the better dribbler
        const dribbleEdge = attacker.attrs.dribbling - defender.attrs.defending;
        const foulChance = Math.min(0.24, 0.09 + Math.max(0, dribbleEdge) * 0.0016);
        if (Math.random() < foulChance) {
            triggerFoul(defender, attacker);
            return;
        }

        if (defScore > attScore) {
            const teamKey = defender.isHome ? 'home' : 'away';
            stats[teamKey].tackles++;
            defender.rating = Math.min(10.0, defender.rating + 0.35);

            ball.carrier = null;
            ball.kick((Math.random() - 0.5) * 4, (Math.random() - 0.5) * 4, 0, defender, false);
            takePossession(defender);

            currentActionDescription = 'Desarme limpo';
            eventManager.log(`🛡️ Desarme preciso de ${defender.fullName} para recuperar a posse de bola.`, 'info');
            addFloatText('DESARME! 🛡️', defender.x, defender.y - 18, defender.isHome ? '#00ff66' : '#38bdf8');
        }
    }

    function triggerFoul(offender, victim) {
        if (currentState === MATCH_STATE.HALFTIME ||
            currentState === MATCH_STATE.GOAL_MOMENT ||
            currentState === MATCH_STATE.PENALTY_SHOOTOUT ||
            currentState === MATCH_STATE.SET_PIECE ||
            currentState === MATCH_STATE.FULL_TIME) {
            return;
        }

        const teamKey = offender.isHome ? 'home' : 'away';
        stats[teamKey].fouls++;
        offender.rating = Math.max(4.0, offender.rating - 0.2);

        ball.carrier = null;
        ball.vx = 0; ball.vy = 0; ball.vz = 0; ball.z = 0;

        screenShake = 8;
        currentActionDescription = 'Falta marcada';
        eventManager.log(`🛑 Falta cometida por ${offender.fullName}! O árbitro paralisa o lance.`, 'info');
        addFloatText('FALTA! 🛑', offender.x, offender.y - 20, '#ffd700');

        const roll = Math.random();
        if (roll < 0.12) {
            offender.yellowCards++;
            stats[teamKey].yellowCards++;
            if (offender.yellowCards === 2) {
                stats[teamKey].redCards++;
                eventManager.log(`🟨 Segundo amarelo para ${offender.fullName}! 🟥 EXPULSO DE CAMPO!`, 'red');
                executeExpulsion(offender);
            } else {
                eventManager.log(`🟨 Cartão Amarelo para ${offender.fullName}.`, 'yellow');
                addFloatText('AMARELO! 🟨', offender.x, offender.y - 28, '#ffd700');
            }
        }

        startFreeKick(victim);
    }

    // ========================================================
    // 16b. DIRECT/INDIRECT FREE KICK (REAL RESTART WITH WALL)
    // ========================================================
    function startFreeKick(victim) {
        if (!victim) return;

        const forHomeTeam = victim.isHome;

        currentState = MATCH_STATE.SET_PIECE;
        subStateSeconds = 1.0;
        setPieceWatchdogTimer = 0;

        const foulX = Math.max(PITCH.left + 30, Math.min(PITCH.right - 30, victim.x));
        const foulY = Math.max(PITCH.top + 20, Math.min(PITCH.bottom - 20, victim.y));

        const taker = (!victim.redCard)
            ? victim
            : players.find(p => p.isHome === forHomeTeam && !p.isGK && !p.redCard);
        if (!taker) {
            exitSetPieceToOpenPlay();
            return;
        }

        ball.reset(foulX, foulY);

        taker.x = foulX;
        taker.y = foulY;
        taker.vx = 0; taker.vy = 0;
        takePossession(taker);

        const opponentGoalX = forHomeTeam ? PITCH.right : PITCH.left;
        const distToGoal = Math.hypot(opponentGoalX - foulX, PITCH.goalCenterY - foulY);

        // Build a defensive wall for close-range free kicks
        if (distToGoal < 250) {
            const towardGoal = Math.atan2(PITCH.goalCenterY - foulY, opponentGoalX - foulX);
            const wallX = foulX + Math.cos(towardGoal) * 34;
            const wallY = foulY + Math.sin(towardGoal) * 34;
            const wallSize = distToGoal < 170 ? 4 : 3;
            const perp = towardGoal + Math.PI / 2;

            const defenders = players
                .filter(p => p.isHome !== forHomeTeam && !p.isGK && !p.redCard)
                .slice(0, wallSize);

            defenders.forEach((d, i) => {
                const off = (i - (defenders.length - 1) / 2) * 16;
                d.x = wallX + Math.cos(perp) * off;
                d.y = wallY + Math.sin(perp) * off;
                d.vx = 0; d.vy = 0;
                d.targetX = d.x;
                d.targetY = d.y;
                d.cooldown = 40;
            });
        }

        const teamName = forHomeTeam ? matchConfig.userTeamName : matchConfig.opponentTeamName;
        eventManager.log(`🎯 Bola parada a favor do ${teamName}${distToGoal < 250 ? ' em posição perigosa!' : '.'}`, 'info');
        addFloatText(distToGoal < 250 ? 'FALTA PERIGOSA! 🎯' : 'BOLA PARADA 🎯', foulX, foulY - 18, '#ffd700');

        trackTimeout(() => {
            if (currentState !== MATCH_STATE.SET_PIECE) return;
            executeFreeKick(taker, forHomeTeam, distToGoal);
            exitSetPieceToOpenPlay();
        }, 900);
    }

    function executeFreeKick(taker, forHomeTeam, distToGoal) {
        if (!taker || taker.redCard) return;

        const strikerTarget = players.find(p => p.isHome === forHomeTeam && p.role === 'ST' && !p.redCard);

        // 1. Direct shot from a dangerous central position
        if (distToGoal < 235 && Math.abs(taker.y - PITCH.goalCenterY) < 150) {
            eventManager.log(`💥 ${taker.fullName} vai cobrar DIRETO para o gol!`, 'info');
            executeShot(taker);
            return;
        }

        // 2. Lofted cross into the box from wide/deep areas
        if (distToGoal < 300 && strikerTarget) {
            executeCross(taker, strikerTarget);
            return;
        }

        // 3. Short restart pass
        const target = findPassCandidate(taker) || taker;
        executePass(taker, target);
    }

    function executeExpulsion(player) {
        player.redCard = true;
        addFloatText('EXPULSO! 🟥', player.x, player.y - 32, '#ef4444');

        let fadeTicks = 0;
        const fader = trackInterval(() => {
            player.opacity -= 0.1;
            fadeTicks++;
            if (fadeTicks >= 10) {
                clearInterval(fader);
                trackedIntervals = trackedIntervals.filter(t => t !== fader);
                player.opacity = 0;
                player.x = -999;
                player.y = -999;
            }
        }, 40);

        if (player.isGK) {
            const replacement = players.find(p => p.isHome === player.isHome && !p.redCard && !p.isGK);
            if (replacement) {
                replacement.isGK = true;
                replacement.role = 'GK';
                replacement.color = player.isHome ? '#00e5ff' : '#ffd700';
                replacement.name += ' (GOL)';
                eventManager.log(`🧤 O goleiro foi expulso! ${replacement.fullName} assume as luvas!`, 'info');
            }
        }
    }

    function resolveGoalkeeperSave(gk) {
        // Resolve at most once per shot so a beaten keeper cannot re-collect the ball.
        if (ball.saveResolved) return;
        ball.saveResolved = true;

        const shooter = ball.lastTouchedBy;
        const shooterRating = shooter ? shooter.attrs.shooting : 75;
        const gkRating = gk.attrs.reflexes;

        const saveChance = Math.min(0.9, Math.max(0.25, 0.72 + (gkRating - shooterRating) * 0.006));

        if (Math.random() < saveChance) {
            ball.state = 'SAVED';
            screenShake = 14;
            gk.rating = Math.min(10.0, gk.rating + 0.6);
            addFloatText('DEFESAÇA! 🧤', gk.x, gk.y - 22, '#38bdf8');
            eventManager.log(`🧤 ESPETACULAR! Grande defesa de ${gk.fullName}!`, 'save');

            if (Math.random() < 0.45) {
                triggerCornerKick(!gk.isHome);
            } else {
                // Rebound into box!
                const deflectDirX = gk.isHome ? 1 : -1;
                ball.kick(deflectDirX * (3.5 + Math.random() * 3.5), (Math.random() - 0.5) * 6, 1.2, gk, false);
                ball.state = 'LOOSE';
            }
        } else {
            // Beaten keeper: the shot has beaten him, so it finishes in the net.
            triggerGoal(shooter ? shooter.isHome : ball.lastTeam === 'home');
        }
    }

    function distributeGoalKick(gk) {
        const forwardDir = gk.isHome ? 1 : -1;
        const teammate = players.find(p => p.isHome === gk.isHome && !p.isGK && !p.redCard && (p.role === 'CM' || p.role === 'CDM'));
        if (teammate) {
            executePass(gk, teammate);
        } else {
            ball.kick(forwardDir * 7.5, (Math.random() - 0.5) * 4, 1.5, gk, false);
        }
    }

    // ========================================================
    // 18. BALL PHYSICS, NET COLLISION & GOAL DETECTION
    // ========================================================
    function updateBallPhysics(dt) {
        if (ball.carrier) return;

        const fs = dt * 60;

        ball.x += ball.vx * fs;
        ball.y += ball.vy * fs;
        ball.z += ball.vz * fs;

        if (ball.z > 0) {
            ball.vz -= 0.35 * fs;
            if (ball.z <= 0) {
                ball.z = 0;
                ball.vz = -ball.vz * 0.42;
            }
        }

        const ballFr = Math.pow(ball.friction, fs);
        ball.vx *= ballFr;
        ball.vy *= ballFr;

        checkGoalpostCollisions();

        // 1. LEFT GOAL CHECK
        if (ball.x - ball.radius < PITCH.left) {
            if (ball.y >= PITCH.goalTop - 4 && ball.y <= PITCH.goalBottom + 4) {
                // Completely crosses goal line
                if (ball.x < PITCH.left - 6 && currentState !== MATCH_STATE.GOAL_MOMENT && currentState !== MATCH_STATE.HALFTIME) {
                    triggerGoal(false); // Away scored
                } else if (ball.x < PITCH.left - 18) {
                    // Net backstop: damp and hold inside net
                    ball.vx *= 0.3;
                    ball.vy *= 0.3;
                }
            } else {
                handleGoalLineExit(false);
            }
        }

        // 2. RIGHT GOAL CHECK
        if (ball.x + ball.radius > PITCH.right) {
            if (ball.y >= PITCH.goalTop - 4 && ball.y <= PITCH.goalBottom + 4) {
                // Completely crosses goal line
                if (ball.x > PITCH.right + 6 && currentState !== MATCH_STATE.GOAL_MOMENT && currentState !== MATCH_STATE.HALFTIME) {
                    triggerGoal(true); // Home scored
                } else if (ball.x > PITCH.right + 18) {
                    // Net backstop: damp and hold inside net
                    ball.vx *= 0.3;
                    ball.vy *= 0.3;
                }
            } else {
                handleGoalLineExit(true);
            }
        }

        // 3. TOUCHLINE CHECK (Lateral)
        if (ball.y - ball.radius < PITCH.top || ball.y + ball.radius > PITCH.bottom) {
            handleTouchlineExit();
        }
    }

    function checkGoalpostCollisions() {
        const posts = [
            { x: PITCH.left, y: PITCH.goalTop },
            { x: PITCH.left, y: PITCH.goalBottom },
            { x: PITCH.right, y: PITCH.goalTop },
            { x: PITCH.right, y: PITCH.goalBottom }
        ];

        posts.forEach(post => {
            const dx = ball.x - post.x;
            const dy = ball.y - post.y;
            const dist = Math.sqrt(dx * dx + dy * dy);

            if (dist < ball.radius + 4.5) {
                const angle = Math.atan2(dy, dx);
                ball.vx = Math.cos(angle) * 7.5;
                ball.vy = Math.sin(angle) * 7.5;
                screenShake = 16;
                addFloatText('NA TRAVE! 💥', post.x, post.y - 18, '#ffd700');
                eventManager.log('💥 NA TRAVE! Um chute sensacional carimba o poste!', 'info');
            }
        });
    }

    // ========================================================
    // 19. ANTI-STUCK WATCHDOG (SOLVES ITEM 9 & 51)
    // ========================================================
    function checkAntiStuckWatchdog(dt) {
        const ballSpeed = Math.sqrt(ball.vx * ball.vx + ball.vy * ball.vy);

        // Check if ball is virtually motionless in a dead-zone or goalmouth
        if (!ball.carrier && ballSpeed < 0.25) {
            stuckBallTimer += dt;
            if (stuckBallTimer > 2.2) {
                stuckBallTimer = 0;

                // If near home goal mouth -> Home GK claims
                if (ball.x < PITCH.left + 80 && Math.abs(ball.y - PITCH.goalCenterY) < 80) {
                    const homeGK = players.find(p => p.isHome && p.isGK);
                    if (homeGK) {
                        takePossession(homeGK);
                        distributeGoalKick(homeGK);
                        return;
                    }
                }
                // If near away goal mouth -> Away GK claims
                if (ball.x > PITCH.right - 80 && Math.abs(ball.y - PITCH.goalCenterY) < 80) {
                    const awayGK = players.find(p => !p.isHome && p.isGK);
                    if (awayGK) {
                        takePossession(awayGK);
                        distributeGoalKick(awayGK);
                        return;
                    }
                }

                // If in open field, nearest player claims it
                let nearest = null;
                let minDist = Infinity;
                players.forEach(p => {
                    if (p.redCard) return;
                    const d = distance(p, ball);
                    if (d < minDist) { minDist = d; nearest = p; }
                });
                if (nearest) {
                    takePossession(nearest);
                    nearest.cooldown = 10;
                }
            }
        } else {
            stuckBallTimer = 0;
        }

        // Set-piece watchdog: ensure set-piece never hangs permanently
        if (currentState === MATCH_STATE.SET_PIECE) {
            setPieceWatchdogTimer += dt;
            if (setPieceWatchdogTimer > 4.5) {
                exitSetPieceToOpenPlay();
            }
        }
    }

    // ========================================================
    // 20. SET PIECES (CORNER, GOAL KICK, THROW-IN)
    // ========================================================
    function handleTouchlineExit() {
        if (currentState === MATCH_STATE.HALFTIME || currentState === MATCH_STATE.GOAL_MOMENT || currentState === MATCH_STATE.SET_PIECE) return;

        currentState = MATCH_STATE.SET_PIECE;
        subStateSeconds = 1.0;
        setPieceWatchdogTimer = 0;
        ball.vx = 0; ball.vy = 0;

        const exitY = ball.y < PITCH.top ? PITCH.top + 2 : PITCH.bottom - 2;
        const throwInX = Math.max(PITCH.left + 50, Math.min(PITCH.right - 50, ball.x));

        const throwInTeamHome = (ball.lastTeam !== 'home');
        const thrower = players.find(p => p.isHome === throwInTeamHome && !p.isGK && !p.redCard);

        ball.reset(throwInX, exitY);
        if (thrower) {
            thrower.x = throwInX;
            thrower.y = exitY;
            takePossession(thrower);
            eventManager.log(`🏳️ Lateral a favor do ${throwInTeamHome ? matchConfig.userTeamName : matchConfig.opponentTeamName}.`, 'info');
            addFloatText('LATERAL 🏳️', throwInX, exitY - 16, '#38bdf8');
            trackTimeout(() => {
                const target = findPassCandidate(thrower) || thrower;
                executePass(thrower, target);
                exitSetPieceToOpenPlay();
            }, 600);
        }
    }

    function handleGoalLineExit(isRightGoal) {
        if (currentState === MATCH_STATE.HALFTIME || currentState === MATCH_STATE.GOAL_MOMENT) return;

        const defendingTeamHome = !isRightGoal;
        const lastTouchHome = (ball.lastTeam === 'home');

        if (defendingTeamHome === lastTouchHome) {
            triggerCornerKick(!defendingTeamHome);
        } else {
            triggerGoalKick(defendingTeamHome);
        }
    }

    function triggerCornerKick(forHomeTeam) {
        if (currentState !== MATCH_STATE.HALFTIME && currentState !== MATCH_STATE.GOAL_MOMENT) {
            currentState = MATCH_STATE.SET_PIECE;
            subStateSeconds = 1.4;
            setPieceWatchdogTimer = 0;
        }

        const teamKey = forHomeTeam ? 'home' : 'away';
        stats[teamKey].corners++;

        const cornerX = forHomeTeam ? PITCH.right : PITCH.left;
        const cornerY = ball.y < canvas.height / 2 ? PITCH.top : PITCH.bottom;

        ball.reset(cornerX, cornerY);

        const kicker = players.find(p => p.isHome === forHomeTeam && !p.isGK && !p.redCard && (p.role === 'WINGER' || p.role === 'CM'));
        if (kicker) {
            kicker.x = cornerX;
            kicker.y = cornerY;
            takePossession(kicker);

            // Crowd players inside the penalty area for realistic corner flow!
            const boxCenterX = forHomeTeam ? PITCH.right - 65 : PITCH.left + 65;
            players.forEach(p => {
                if (p === kicker || p.isGK || p.redCard) return;
                p.targetX = boxCenterX + (Math.random() - 0.5) * 50;
                p.targetY = PITCH.goalCenterY + (Math.random() - 0.5) * 75;
            });

            eventManager.log(`🚩 ESCANTEIO para o ${forHomeTeam ? matchConfig.userTeamName : matchConfig.opponentTeamName}!`, 'corner');
            addFloatText('ESCANTEIO! 🚩', cornerX, cornerY - 18, '#38bdf8');

            trackTimeout(() => {
                const targetST = players.find(p => p.isHome === forHomeTeam && p.role === 'ST' && !p.redCard);
                executeCross(kicker, targetST);
                exitSetPieceToOpenPlay();
            }, 700);
        }
    }

    function triggerGoalKick(forHomeTeam) {
        if (currentState !== MATCH_STATE.HALFTIME && currentState !== MATCH_STATE.GOAL_MOMENT) {
            currentState = MATCH_STATE.SET_PIECE;
            subStateSeconds = 1.2;
            setPieceWatchdogTimer = 0;
        }

        const gk = players.find(p => p.isHome === forHomeTeam && p.isGK);
        const kickX = forHomeTeam ? PITCH.left + 24 : PITCH.right - 24;
        const kickY = canvas.height / 2;

        ball.reset(kickX, kickY);
        if (gk) {
            gk.x = kickX;
            gk.y = kickY;
            takePossession(gk);
            eventManager.log(`🧤 Tiro de meta cobrado por ${gk.fullName}.`, 'info');
            trackTimeout(() => {
                distributeGoalKick(gk);
                exitSetPieceToOpenPlay();
            }, 650);
        }
    }

    // ========================================================
    // 21. GOAL SEQUENCE & CELEBRATION
    // ========================================================
    function triggerGoal(isHome) {
        currentState = MATCH_STATE.GOAL_MOMENT;
        subStateSeconds = 4.0;
        goalNetGlow = 45;
        screenShake = 22;

        ball.vx = 0; ball.vy = 0; ball.vz = 0; ball.z = 0; ball.carrier = null;

        let scorer = ball.lastTouchedBy;
        if (!scorer || scorer.isHome !== isHome || scorer.redCard) {
            scorer = players.find(p => p.isHome === isHome && !p.isGK && !p.redCard) || null;
        }

        let assister = (ball.shotAssister && ball.shotAssister !== scorer && ball.shotAssister.isHome === isHome)
            ? ball.shotAssister
            : null;

        if (scorer) {
            scorer.goals++;
            scorer.rating = Math.min(10.0, scorer.rating + 1.2);
        }
        if (assister) {
            assister.assists++;
            assister.rating = Math.min(10.0, assister.rating + 0.8);
        }

        goalScorerInfo = {
            isHome: isHome,
            scorer: scorer,
            assister: assister
        };

        if (isHome) {
            stats.home.goals++;
            if (scoreHomeDisplay) scoreHomeDisplay.innerText = stats.home.goals;
            const scorerName = scorer ? scorer.fullName : matchConfig.userTeamName;
            const assistText = assister ? ` (Assistência: ${assister.fullName})` : '';

            eventManager.log(`⚽ GOOOOOOL DO ${matchConfig.userTeamName}! Gol de ${scorerName}!${assistText}`, 'goal');
            addFloatText(`GOL DE ${scorer ? scorer.name.toUpperCase() : ''}! ⚽`, canvas.width / 2, canvas.height / 2 - 35, '#00ff66');
            createConfetti(PITCH.right, PITCH.goalCenterY);
        } else {
            stats.away.goals++;
            if (scoreAwayDisplay) scoreAwayDisplay.innerText = stats.away.goals;
            const scorerName = scorer ? scorer.fullName : matchConfig.opponentTeamName;
            const assistText = assister ? ` (Assistência: ${assister.fullName})` : '';

            eventManager.log(`⚽ Gol do ${matchConfig.opponentTeamName}! Finalização certeira de ${scorerName}.${assistText}`, 'goal');
            addFloatText(`GOL DE ${scorer ? scorer.name.toUpperCase() : ''}! ⚽`, canvas.width / 2, canvas.height / 2 - 35, '#ef4444');
            createConfetti(PITCH.left, PITCH.goalCenterY);
        }

        updateLineupDOM();
    }

    // ========================================================
    // 22. PENALTY SHOOTOUT (DISPUTA DE PÊNALTIS - ZERO FREEZE)
    // ========================================================
    let penaltyData = {
        round: 1,
        homeKicks: 0,
        awayKicks: 0,
        homeScore: 0,
        awayScore: 0,
        isHomeTurn: true,
        state: 'PREPARE', // 'PREPARE', 'WALK', 'AIM', 'SHOOT', 'RESULT'
        timer: 70,
        aimAngle: 0,
        aimDir: 1,
        kicker: null,
        gk: null,
        homeOrder: [],
        awayOrder: []
    };

    function startPenaltyShootout() {
        currentState = MATCH_STATE.PENALTY_SHOOTOUT;

        // Build sequential roster rotation (using all 11 players without repetition!)
        const hEligible = players.filter(p => p.isHome && !p.redCard);
        const aEligible = players.filter(p => !p.isHome && !p.redCard);

        penaltyData = {
            round: 1,
            homeKicks: 0,
            awayKicks: 0,
            homeScore: 0,
            awayScore: 0,
            isHomeTurn: true,
            state: 'PREPARE',
            timer: 70,
            aimAngle: 0,
            aimDir: 1,
            kicker: null,
            gk: null,
            homeOrder: hEligible,
            awayOrder: aEligible
        };

        if (phaseBadge) {
            phaseBadge.innerText = 'PÊNALTIS';
            phaseBadge.className = 'sb-phase-badge added-time';
        }
        if (penaltyScoreRow) penaltyScoreRow.style.display = 'flex';
        if (penScoreHome) penScoreHome.innerText = '0';
        if (penScoreAway) penScoreAway.innerText = '0';

        eventManager.log('⚽ --- INÍCIO DA DECISÃO POR PÊNALTIS --- ⚽', 'info');
        addFloatText('DISPUTA DE PÊNALTIS! ⚽', canvas.width / 2, canvas.height / 2 - 35, '#ffd700');
    }

    function updatePenaltyShootout(dt) {
        const fs = dt * 60;
        const spotX = PITCH.right - PITCH.penaltySpotDistance;
        const spotY = canvas.height / 2;

        if (penaltyData.state === 'PREPARE') {
            const isHome = penaltyData.isHomeTurn;
            const orderList = isHome ? penaltyData.homeOrder : penaltyData.awayOrder;
            const kickIdx = (isHome ? penaltyData.homeKicks : penaltyData.awayKicks) % orderList.length;

            const kicker = orderList[kickIdx];
            const gk = players.find(p => p.isHome !== isHome && p.isGK);

            penaltyData.kicker = kicker;
            penaltyData.gk = gk;

            // Outfield players gather respectfully around center line
            players.forEach((p, idx) => {
                if (p !== kicker && p !== gk) {
                    p.x = canvas.width / 2 - 40 + (p.isHome ? -1 : 1) * 35;
                    p.y = PITCH.top + 40 + (idx % 11) * 30;
                    p.vx = 0; p.vy = 0;
                    p.targetX = p.x; p.targetY = p.y;
                }
            });

            // Position GK on line and Striker walking up from center
            ball.reset(spotX, spotY);
            if (gk) { gk.x = PITCH.right - 14; gk.y = canvas.height / 2; gk.vx = 0; gk.vy = 0; }
            if (kicker) {
                kicker.x = canvas.width / 2 + 30;
                kicker.y = canvas.height / 2;
                kicker.targetX = spotX - 25;
                kicker.targetY = spotY;
            }

            penaltyData.state = 'WALK';
            penaltyData.timer = 60;
        }
        else if (penaltyData.state === 'WALK') {
            penaltyData.timer -= fs;
            if (penaltyData.kicker) {
                const walkLerp = 1 - Math.pow(1 - 0.12, fs);
                penaltyData.kicker.x += (spotX - 25 - penaltyData.kicker.x) * walkLerp;
                penaltyData.kicker.y += (spotY - penaltyData.kicker.y) * walkLerp;
            }
            if (penaltyData.timer <= 0) {
                penaltyData.state = 'AIM';
                penaltyData.timer = 75;
                penaltyData.aimAngle = 0;
                penaltyData.aimDir = 1;
                eventManager.log(`Cobrador: ${penaltyData.kicker.fullName} parte para a cobrança...`, 'info');
            }
        }
        else if (penaltyData.state === 'AIM') {
            penaltyData.aimAngle += 0.045 * penaltyData.aimDir * fs;
            if (Math.abs(penaltyData.aimAngle) > 0.48) penaltyData.aimDir *= -1;

            penaltyData.timer -= fs;
            if (penaltyData.timer <= 0) {
                penaltyData.state = 'SHOOT';
                const power = 11.5 + (penaltyData.kicker.attrs.shooting / 100) * 3.5;
                ball.vx = Math.cos(penaltyData.aimAngle) * power;
                ball.vy = Math.sin(penaltyData.aimAngle) * power;

                // Goalkeeper dive decision based on reflexes
                if (penaltyData.gk) {
                    const dive = Math.random();
                    let targetY = canvas.height / 2;
                    if (dive < 0.42) targetY = PITCH.goalTop + 14;
                    else if (dive < 0.84) targetY = PITCH.goalBottom - 14;

                    trackTimeout(() => {
                        if (penaltyData.gk) {
                            penaltyData.gk.vy = (targetY - penaltyData.gk.y) * 0.45;
                        }
                    }, 65);
                }

                penaltyData.timer = 85;
                penaltyData.saveRolled = false;
            }
        }
        else if (penaltyData.state === 'SHOOT') {
            penaltyData.timer -= fs;
            ball.x += ball.vx * fs;
            ball.y += ball.vy * fs;

            // Keeper reaches the ball (visual dive contact)
            if (penaltyData.gk) {
                penaltyData.gk.y += penaltyData.gk.vy * fs;
                if (distance(penaltyData.gk, ball) < penaltyData.gk.radius + ball.radius + 6) {
                    ball.vx = -ball.vx * 0.5;
                    ball.vy = (Math.random() - 0.5) * 5;
                    resolvePenaltyResult(false, 'DEFESA');
                    return;
                }

                // Skill-based save as the ball arrives at the line
                if (!penaltyData.saveRolled && ball.x > PITCH.right - 22) {
                    penaltyData.saveRolled = true;
                    const gkRating = penaltyData.gk.attrs.reflexes;
                    const kickerRating = penaltyData.kicker ? penaltyData.kicker.attrs.shooting : 78;
                    const saveChance = Math.min(0.72, Math.max(0.18, 0.34 + (gkRating - kickerRating) * 0.004));
                    if (Math.random() < saveChance) {
                        ball.vx = -Math.abs(ball.vx) * 0.5;
                        ball.vy = (Math.random() - 0.5) * 5;
                        resolvePenaltyResult(false, 'DEFESA');
                        return;
                    }
                }
            }

            // Goal detection inside net
            if (ball.x > PITCH.right + 6 && ball.y > PITCH.goalTop && ball.y < PITCH.goalBottom) {
                resolvePenaltyResult(true, 'GOL');
            } else if (penaltyData.timer <= 0 || ball.x > PITCH.right + 25 || ball.vx <= 0.2) {
                resolvePenaltyResult(false, 'FORA');
            }
        }
        else if (penaltyData.state === 'RESULT') {
            penaltyData.timer -= fs;
            if (penaltyData.timer <= 0) {
                advancePenaltyRound();
            }
        }
    }

    function resolvePenaltyResult(isGoal, outcomeType) {
        const isHome = penaltyData.isHomeTurn;
        if (isHome) penaltyData.homeKicks++;
        else penaltyData.awayKicks++;

        if (isGoal) {
            if (isHome) penaltyData.homeScore++;
            else penaltyData.awayScore++;

            screenShake = 16;
            createConfetti(PITCH.right, PITCH.goalCenterY);
            eventManager.log(`[PÊNALTIS] ⚽ GOL! ${penaltyData.kicker.fullName} converteu! (${penaltyData.homeScore} × ${penaltyData.awayScore})`, 'goal');
            addFloatText('GOL! ⚽', canvas.width / 2, canvas.height / 2 - 30, '#00ff66');
        } else {
            const reason = outcomeType === 'DEFESA' ? 'Defendido pelo goleiro!' : 'Para fora!';
            eventManager.log(`[PÊNALTIS] ❌ PERDEU! ${penaltyData.kicker.fullName} desperdiçou! ${reason} (${penaltyData.homeScore} × ${penaltyData.awayScore})`, 'info');
            addFloatText('PERDEU! ❌', canvas.width / 2, canvas.height / 2 - 30, '#ef4444');
        }

        if (penScoreHome) penScoreHome.innerText = penaltyData.homeScore;
        if (penScoreAway) penScoreAway.innerText = penaltyData.awayScore;

        penaltyData.state = 'RESULT';
        penaltyData.timer = 85;
    }

    function advancePenaltyRound() {
        const hk = penaltyData.homeKicks;
        const ak = penaltyData.awayKicks;
        const hs = penaltyData.homeScore;
        const as = penaltyData.awayScore;

        // 1. Check mathematical elimination during regular 5 rounds
        if (hk <= 5 && ak <= 5) {
            const remHome = 5 - hk;
            const remAway = 5 - ak;
            if (hs > as + remAway) { concludePenalties(true); return; }
            if (as > hs + remHome) { concludePenalties(false); return; }
        }

        // 2. If 5 kicks each completed -> Sudden death (morte súbita) check
        if (hk >= 5 && ak >= 5 && hk === ak) {
            if (hs !== as) {
                concludePenalties(hs > as);
                return;
            }
            // Safety valve: a long tied sudden death must never softlock the match.
            if (penaltyData.round >= 25) {
                concludePenalties(Math.random() < 0.5);
                return;
            }
        }

        // Next kicker turn
        if (penaltyData.isHomeTurn) {
            penaltyData.isHomeTurn = false;
        } else {
            penaltyData.isHomeTurn = true;
            penaltyData.round++;
        }
        penaltyData.state = 'PREPARE';
    }

    function concludePenalties(userWon) {
        currentState = MATCH_STATE.FULL_TIME;
        if (phaseBadge) { phaseBadge.innerText = 'FIM DE JOGO'; phaseBadge.className = 'sb-phase-badge'; }
        eventManager.log(`🏁 Fim da decisão nos pênaltis! Placar Oficial: ${penaltyData.homeScore} × ${penaltyData.awayScore}.`, 'info');

        trackTimeout(() => {
            if (userWon) {
                showPostMatchDialog('win', `Vitória heróica nos Pênaltis! Você venceu por ${penaltyData.homeScore} × ${penaltyData.awayScore} (tempo normal: ${stats.home.goals} × ${stats.away.goals}) e segue na Copa!`);
            } else {
                showPostMatchDialog('lose', `Derrota nas penalidades por ${penaltyData.homeScore} × ${penaltyData.awayScore} (tempo normal: ${stats.home.goals} × ${stats.away.goals}).`);
            }
        }, 1200);
    }

    // ========================================================
    // 23. VISUAL FX, PARTICLES & DEBUG HUD
    // ========================================================
    function updateVisualFX(dt) {
        const ballSpeed = Math.sqrt(ball.vx * ball.vx + ball.vy * ball.vy);
        if (ballSpeed > 3.0) {
            ballTrail.push({ x: ball.x, y: ball.y - ball.z, radius: ball.radius - 1, opacity: 0.65 });
            if (ballTrail.length > 7) ballTrail.shift();
        } else {
            if (ballTrail.length > 0) ballTrail.shift();
        }

        if (screenShake > 0) screenShake--;
        if (goalNetGlow > 0) goalNetGlow--;

        confetti.forEach((c, idx) => {
            c.x += c.vx;
            c.y += c.vy;
            c.vy += 0.08;
            c.opacity -= 0.015;
            if (c.opacity <= 0) confetti.splice(idx, 1);
        });

        for (let i = floatTexts.length - 1; i >= 0; i--) {
            floatTexts[i].y -= 0.55;
            floatTexts[i].timer -= 1;
            if (floatTexts[i].timer <= 0) floatTexts.splice(i, 1);
        }

        // Live Debug HUD (Item 55)
        if (DEBUG_SIMULATION && debugPanel) {
            const poss = ball.lastTeam === 'home' ? 'CASA' : 'VISITANTE';
            const carrierName = ball.carrier ? ball.carrier.name : 'Nenhum';
            const bSpeed = Math.sqrt(ball.vx * ball.vx + ball.vy * ball.vy).toFixed(1);
            const penInfo = (currentState === MATCH_STATE.PENALTY_SHOOTOUT)
                ? `<br>PENALTY: R${penaltyData.round} (${penaltyData.homeScore}x${penaltyData.awayScore}) [${penaltyData.state}]`
                : '';

            debugPanel.innerHTML =
                `STATE: <strong>${currentState}</strong><br>` +
                `MATCH_TIME: ${timerDisplay ? timerDisplay.innerText : '00:00'}<br>` +
                `POSSESSION: ${poss}<br>` +
                `BALL_STATE: ${ball.state}<br>` +
                `BALL_SPEED: ${bSpeed} px/f<br>` +
                `CARRIER: ${carrierName}<br>` +
                `AI_PHASE: ${teamAiPhase.home} / ${teamAiPhase.away}<br>` +
                `ACTION: ${currentActionDescription}` +
                penInfo;
        }
    }

    function createConfetti(x, y) {
        for (let i = 0; i < 45; i++) {
            confetti.push({
                x: x,
                y: y + (Math.random() - 0.5) * 80,
                vx: (Math.random() - 0.5) * 8,
                vy: (Math.random() - 0.5) * 8 - 3,
                color: `hsl(${Math.random() * 360}, 100%, 65%)`,
                radius: 3 + Math.random() * 3.5,
                opacity: 1.0
            });
        }
    }

    function addFloatText(text, x, y, color = '#ffffff') {
        floatTexts.push({
            text: text,
            x: x,
            y: y,
            color: color,
            timer: 45
        });
    }

    // ========================================================
    // 24. CANVAS 2D RENDERING
    // ========================================================
    function render() {
        ctx.save();

        if (screenShake > 0) {
            const dx = (Math.random() - 0.5) * 6;
            const dy = (Math.random() - 0.5) * 6;
            ctx.translate(dx, dy);
        }

        ctx.clearRect(0, 0, canvas.width, canvas.height);

        drawTacticalPitch();
        drawGoalNets();
        drawEntities();
        drawPlayerNameLabels();

        ctx.restore();
    }

    function drawPlayerNameLabels() {
        if (currentTab !== 'ratings') return;
        ctx.font = '700 9px Outfit, sans-serif';
        ctx.textAlign = 'center';
        players.forEach(p => {
            if (p.redCard && p.opacity <= 0) return;
            ctx.fillStyle = 'rgba(255,255,255,0.92)';
            ctx.shadowColor = 'rgba(0,0,0,0.85)';
            ctx.shadowBlur = 3;
            const label = `${p.name.split(' ')[0].toUpperCase()} ${p.rating.toFixed(1)}`;
            ctx.fillText(label, p.x, p.y - p.radius - 5);
            ctx.shadowBlur = 0;
        });
    }

    function drawTacticalPitch() {
        // Outer stadium floor
        ctx.fillStyle = '#07101e';
        ctx.fillRect(0, 0, canvas.width, canvas.height);

        // Mesa de botão — Verde escuro autêntico GOLAÇO
        ctx.fillStyle = '#0d4722';
        ctx.fillRect(PITCH.left, PITCH.top, PITCH.right - PITCH.left, PITCH.bottom - PITCH.top);

        // Vertical turf stripes
        ctx.fillStyle = '#0a3d1c';
        const stripeW = (PITCH.right - PITCH.left) / 12;
        for (let i = 0; i < 12; i += 2) {
            ctx.fillRect(PITCH.left + i * stripeW, PITCH.top, stripeW, PITCH.bottom - PITCH.top);
        }

        // Radial stadium light vignette
        const vig = ctx.createRadialGradient(canvas.width / 2, canvas.height / 2, 160, canvas.width / 2, canvas.height / 2, canvas.width / 2);
        vig.addColorStop(0, 'rgba(0, 255, 102, 0.05)');
        vig.addColorStop(1, 'rgba(3, 7, 18, 0.55)');
        ctx.fillStyle = vig;
        ctx.fillRect(PITCH.left, PITCH.top, PITCH.right - PITCH.left, PITCH.bottom - PITCH.top);

        // Glowing Chalk Lines
        ctx.strokeStyle = 'rgba(0, 255, 102, 0.4)';
        ctx.lineWidth = 2.0;

        // Outer boundaries
        ctx.strokeRect(PITCH.left, PITCH.top, PITCH.right - PITCH.left, PITCH.bottom - PITCH.top);

        // Halfway Line
        ctx.beginPath();
        ctx.moveTo(canvas.width / 2, PITCH.top);
        ctx.lineTo(canvas.width / 2, PITCH.bottom);
        ctx.stroke();

        // Center Circle & Spot
        ctx.beginPath();
        ctx.arc(canvas.width / 2, canvas.height / 2, PITCH.centerRadius, 0, Math.PI * 2);
        ctx.stroke();

        ctx.fillStyle = 'rgba(255, 234, 121, 0.85)';
        ctx.beginPath();
        ctx.arc(canvas.width / 2, canvas.height / 2, 3.5, 0, Math.PI * 2);
        ctx.fill();

        // Left Penalty Area, 6-yard box, penalty spot
        ctx.strokeRect(PITCH.left, canvas.height / 2 - PITCH.penaltyBoxHeight / 2, PITCH.penaltyBoxWidth, PITCH.penaltyBoxHeight);
        ctx.strokeRect(PITCH.left, PITCH.goalTop, PITCH.sixYardWidth, PITCH.goalBottom - PITCH.goalTop);
        ctx.beginPath(); ctx.arc(PITCH.left + PITCH.penaltySpotDistance, canvas.height / 2, 3, 0, Math.PI * 2); ctx.fill();
        ctx.beginPath(); ctx.arc(PITCH.left + PITCH.penaltySpotDistance, canvas.height / 2, 38, -0.65, 0.65); ctx.stroke();

        // Right Penalty Area, 6-yard box, penalty spot
        ctx.strokeRect(PITCH.right - PITCH.penaltyBoxWidth, canvas.height / 2 - PITCH.penaltyBoxHeight / 2, PITCH.penaltyBoxWidth, PITCH.penaltyBoxHeight);
        ctx.strokeRect(PITCH.right - PITCH.sixYardWidth, PITCH.goalTop, PITCH.sixYardWidth, PITCH.goalBottom - PITCH.goalTop);
        ctx.beginPath(); ctx.arc(PITCH.right - PITCH.penaltySpotDistance, canvas.height / 2, 3, 0, Math.PI * 2); ctx.fill();
        ctx.beginPath(); ctx.arc(PITCH.right - PITCH.penaltySpotDistance, canvas.height / 2, 38, Math.PI - 0.65, Math.PI + 0.65); ctx.stroke();

        // Corner Arcs
        ctx.beginPath(); ctx.arc(PITCH.left, PITCH.top, PITCH.cornerArcRadius, 0, Math.PI / 2); ctx.stroke();
        ctx.beginPath(); ctx.arc(PITCH.left, PITCH.bottom, PITCH.cornerArcRadius, -Math.PI / 2, 0); ctx.stroke();
        ctx.beginPath(); ctx.arc(PITCH.right, PITCH.top, PITCH.cornerArcRadius, Math.PI / 2, Math.PI); ctx.stroke();
        ctx.beginPath(); ctx.arc(PITCH.right, PITCH.bottom, PITCH.cornerArcRadius, Math.PI, Math.PI * 1.5); ctx.stroke();
    }

    function drawGoalNets() {
        ctx.strokeStyle = goalNetGlow > 0 ? 'rgba(0, 255, 102, 0.9)' : 'rgba(56, 189, 248, 0.35)';
        ctx.lineWidth = 1.2;

        const netDepth = 26;
        const goalH = PITCH.goalBottom - PITCH.goalTop;

        // Left Net
        ctx.strokeRect(PITCH.left - netDepth, PITCH.goalTop, netDepth, goalH);
        for (let y = PITCH.goalTop + 10; y < PITCH.goalBottom; y += 10) {
            ctx.beginPath(); ctx.moveTo(PITCH.left - netDepth, y); ctx.lineTo(PITCH.left, y); ctx.stroke();
        }
        for (let x = PITCH.left - netDepth; x <= PITCH.left; x += 7) {
            ctx.beginPath(); ctx.moveTo(x, PITCH.goalTop); ctx.lineTo(x, PITCH.goalBottom); ctx.stroke();
        }

        // Right Net
        ctx.strokeRect(PITCH.right, PITCH.goalTop, netDepth, goalH);
        for (let y = PITCH.goalTop + 10; y < PITCH.goalBottom; y += 10) {
            ctx.beginPath(); ctx.moveTo(PITCH.right, y); ctx.lineTo(PITCH.right + netDepth, y); ctx.stroke();
        }
        for (let x = PITCH.right; x <= PITCH.right + netDepth; x += 7) {
            ctx.beginPath(); ctx.moveTo(x, PITCH.goalTop); ctx.lineTo(x, PITCH.goalBottom); ctx.stroke();
        }

        // Goal Posts (White circles on line)
        ctx.fillStyle = '#ffffff';
        ctx.beginPath(); ctx.arc(PITCH.left, PITCH.goalTop, 3.5, 0, Math.PI * 2); ctx.fill();
        ctx.beginPath(); ctx.arc(PITCH.left, PITCH.goalBottom, 3.5, 0, Math.PI * 2); ctx.fill();
        ctx.beginPath(); ctx.arc(PITCH.right, PITCH.goalTop, 3.5, 0, Math.PI * 2); ctx.fill();
        ctx.beginPath(); ctx.arc(PITCH.right, PITCH.goalBottom, 3.5, 0, Math.PI * 2); ctx.fill();
    }

    function drawEntities() {
        // Penalty Aiming Guide Line during Shootout
        if (currentState === MATCH_STATE.PENALTY_SHOOTOUT && penaltyData.state === 'AIM' && penaltyData.kicker) {
            const startX = penaltyData.kicker.x;
            const startY = penaltyData.kicker.y;
            const len = 75;
            const endX = startX + Math.cos(penaltyData.aimAngle) * len;
            const endY = startY + Math.sin(penaltyData.aimAngle) * len;

            ctx.strokeStyle = '#00ff66';
            ctx.lineWidth = 2.5;
            ctx.setLineDash([5, 4]);
            ctx.beginPath(); ctx.moveTo(startX, startY); ctx.lineTo(endX, endY); ctx.stroke();
            ctx.setLineDash([]);
        }

        // 1. DRAW BUTTON DISCS WITH SHIRT NUMBERS
        players.forEach(p => {
            if (p.redCard && p.opacity <= 0) return;

            ctx.save();
            ctx.globalAlpha = p.opacity;

            // Drop Shadow
            ctx.fillStyle = 'rgba(0, 0, 0, 0.45)';
            ctx.beginPath();
            ctx.arc(p.x + 2, p.y + 3, p.radius, 0, Math.PI * 2);
            ctx.fill();

            // Possession Halo
            if (ball.carrier === p) {
                ctx.strokeStyle = p.isHome ? '#00ff66' : '#ef4444';
                ctx.lineWidth = 2.5;
                ctx.beginPath();
                ctx.arc(p.x, p.y, p.radius + 4.5, 0, Math.PI * 2);
                ctx.stroke();
            }

            // Outer Solid Acrylic Border
            ctx.fillStyle = darkenColor(p.color, 45);
            ctx.beginPath();
            ctx.arc(p.x, p.y, p.radius, 0, Math.PI * 2);
            ctx.fill();

            // 3D Disc Gradient Body
            const grad = ctx.createRadialGradient(p.x - 3, p.y - 3, 1, p.x, p.y, p.radius - 1.5);
            grad.addColorStop(0, '#ffffff');
            grad.addColorStop(0.35, p.color);
            grad.addColorStop(1, darkenColor(p.color, 35));

            ctx.fillStyle = grad;
            ctx.beginPath();
            ctx.arc(p.x, p.y, p.radius - 1.5, 0, Math.PI * 2);
            ctx.fill();

            // Inner Ring Accent
            ctx.strokeStyle = 'rgba(255, 255, 255, 0.4)';
            ctx.lineWidth = 0.8;
            ctx.beginPath();
            ctx.arc(p.x, p.y, p.radius - 4, 0, Math.PI * 2);
            ctx.stroke();

            // Bold Shirt Number in Center
            ctx.fillStyle = (p.isGK || isColorLight(p.color)) ? '#0a121d' : '#ffffff';
            ctx.font = '900 11px Outfit, sans-serif';
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            ctx.fillText(p.number.toString(), p.x, p.y + 0.5);

            ctx.restore();
        });

        // 2. BALL TRAIL
        ballTrail.forEach((dot, idx) => {
            ctx.fillStyle = `rgba(255, 255, 255, ${dot.opacity * (idx / ballTrail.length)})`;
            ctx.beginPath();
            ctx.arc(dot.x, dot.y, dot.radius * (idx / ballTrail.length), 0, Math.PI * 2);
            ctx.fill();
        });

        // 3. MINI 3D SOCCER BALL
        const ballRenderY = ball.y - ball.z;

        // Shadow below ball
        ctx.fillStyle = 'rgba(0, 0, 0, 0.45)';
        ctx.beginPath();
        ctx.arc(ball.x + 2, ball.y + 3, ball.radius * Math.max(0.4, 1 - ball.z * 0.03), 0, Math.PI * 2);
        ctx.fill();

        // Ball 3D Gradient
        const bGrad = ctx.createRadialGradient(ball.x - 2, ballRenderY - 2, 1, ball.x, ballRenderY, ball.radius);
        bGrad.addColorStop(0, '#ffffff');
        bGrad.addColorStop(0.75, '#f1f5f9');
        bGrad.addColorStop(1, '#94a3b8');

        ctx.fillStyle = bGrad;
        ctx.beginPath();
        ctx.arc(ball.x, ballRenderY, ball.radius, 0, Math.PI * 2);
        ctx.fill();

        ctx.strokeStyle = '#334155';
        ctx.lineWidth = 0.8;
        ctx.beginPath(); ctx.arc(ball.x, ballRenderY, ball.radius, 0, Math.PI * 2); ctx.stroke();

        // 4. CONFETTI PARTICLES
        confetti.forEach(c => {
            ctx.fillStyle = c.color;
            ctx.globalAlpha = c.opacity;
            ctx.beginPath();
            ctx.arc(c.x, c.y, c.radius, 0, Math.PI * 2);
            ctx.fill();
        });
        ctx.globalAlpha = 1.0;

        // 5. FLOATING TEXTS
        floatTexts.forEach(ft => {
            ctx.fillStyle = ft.color;
            ctx.font = 'bold 14px Outfit, sans-serif';
            ctx.textAlign = 'center';
            ctx.shadowColor = 'rgba(0, 0, 0, 0.9)';
            ctx.shadowBlur = 4;
            ctx.fillText(ft.text, ft.x, ft.y);
            ctx.shadowBlur = 0;
        });

        // 6. GOAL CELEBRATION BANNER
        if (currentState === MATCH_STATE.GOAL_MOMENT && goalScorerInfo) {
            const isHomeScored = goalScorerInfo.isHome;
            const teamColor = isHomeScored ? '#00ff66' : '#ef4444';
            const scorerName = goalScorerInfo.scorer ? goalScorerInfo.scorer.fullName.toUpperCase() : '';
            const assistName = goalScorerInfo.assister ? goalScorerInfo.assister.fullName : '';

            ctx.fillStyle = 'rgba(7, 13, 24, 0.88)';
            ctx.fillRect(0, canvas.height / 2 - 46, canvas.width, 92);

            ctx.fillStyle = teamColor;
            ctx.font = '900 28px Outfit, sans-serif';
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            ctx.shadowColor = teamColor;
            ctx.shadowBlur = 10;
            ctx.fillText(`⚽ GOOOOOL DO ${isHomeScored ? matchConfig.userTeamName.toUpperCase() : matchConfig.opponentTeamName.toUpperCase()}!`, canvas.width / 2, canvas.height / 2 - 10);
            ctx.shadowBlur = 0;

            ctx.fillStyle = '#ffffff';
            ctx.font = 'bold 14px Outfit, sans-serif';
            let detail = scorerName ? `Autor: ${scorerName}` : '';
            if (assistName) detail += `  |  Assistência: ${assistName}`;
            ctx.fillText(detail, canvas.width / 2, canvas.height / 2 + 20);
        }
    }

    // ========================================================
    // 25. STATS TABLE RENDERING FOR MODALS
    // ========================================================
    function renderStatsTable(containerElement) {
        if (!containerElement) return;

        const totalPoss = stats.home.possessionSeconds + stats.away.possessionSeconds || 1;
        const homePossPct = Math.round((stats.home.possessionSeconds / totalPoss) * 100);
        const awayPossPct = 100 - homePossPct;

        const rows = [
            { label: 'Finalizações', home: stats.home.shots, away: stats.away.shots },
            { label: 'Chutes no Alvo', home: stats.home.shotsOnTarget, away: stats.away.shotsOnTarget },
            { label: 'Posse de Bola', home: `${homePossPct}%`, away: `${awayPossPct}%`, homePct: homePossPct, awayPct: awayPossPct },
            { label: 'Passes Realizados', home: stats.home.passesCompleted, away: stats.away.passesCompleted },
            { label: 'Desarmes', home: stats.home.tackles, away: stats.away.tackles },
            { label: 'Escanteios', home: stats.home.corners, away: stats.away.corners },
            { label: 'Faltas', home: stats.home.fouls, away: stats.away.fouls },
            { label: 'Cartões Amarelos', home: stats.home.yellowCards, away: stats.away.yellowCards },
            { label: 'Cartões Vermelhos', home: stats.home.redCards, away: stats.away.redCards }
        ];

        let html = '';
        rows.forEach(r => {
            let hPct = 50;
            let aPct = 50;
            if (r.homePct !== undefined) {
                hPct = r.homePct;
                aPct = r.awayPct;
            } else {
                const total = (r.home + r.away) || 1;
                hPct = Math.round((r.home / total) * 100);
                aPct = 100 - hPct;
            }

            html += `
                <div class="stat-row">
                    <span class="stat-val home">${r.home}</span>
                    <div class="stat-center">
                        <span class="stat-name">${r.label}</span>
                        <div class="stat-bar-container">
                            <div class="stat-bar-home" style="width: ${hPct}%;"></div>
                            <div class="stat-bar-away" style="width: ${aPct}%;"></div>
                        </div>
                    </div>
                    <span class="stat-val away">${r.away}</span>
                </div>
            `;
        });

        containerElement.innerHTML = html;
    }

    // ========================================================
    // 26. POST-MATCH RESULTS & TOURNAMENT ADVANCEMENT
    // ========================================================
    function showPostMatchDialog(result, description) {
        let prize = 0;
        if (result === 'win') {
            const rewardMap = { 1: 500, 2: 1000, 3: 2000, 4: 5000 };
            prize = rewardMap[matchConfig.currentStage] || 500;
            if (mdTitle) {
                mdTitle.innerText = matchConfig.currentStage === 4 ? '🏆 CAMPEÃO!' : 'VITÓRIA!';
                mdTitle.className = 'md-result-title win';
            }
            if (mdRewardVal) mdRewardVal.innerText = prize;
            if (mdRewardContainer) mdRewardContainer.style.display = 'flex';
        } else {
            if (mdTitle) {
                mdTitle.innerText = 'ELIMINADO!';
                mdTitle.className = 'md-result-title lose';
            }
            if (mdRewardContainer) mdRewardContainer.style.display = 'none';
        }

        if (mdDesc) mdDesc.innerText = description;
        renderStatsTable(ftStatsTable);

        const mdCrests = document.getElementById('md-ft-crests');
        if (mdCrests && matchConfig.userCrestHtml) {
            mdCrests.innerHTML = `
                <div class="md-ft-team">${matchConfig.userCrestHtml}<span>${matchConfig.userTeamName}</span></div>
                <div class="md-ft-scoreline">${stats.home.goals} × ${stats.away.goals}</div>
                <div class="md-ft-team">${matchConfig.opponentCrestHtml || ''}<span>${matchConfig.opponentTeamName}</span></div>
            `;
        }

        if (dialogOverlay) dialogOverlay.classList.add('active');

        if (btnConfirm) {
            btnConfirm.onclick = function() {
                btnConfirm.innerText = 'PROCESSANDO...';
                btnConfirm.disabled = true;

                fetch('api/update_tournament.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        result: result,
                        tournament_id: matchConfig.tournamentId
                    })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.error) {
                        alert(data.error);
                        btnConfirm.innerText = 'Continuar Torneio';
                        btnConfirm.disabled = false;
                        return;
                    }
                    if (data.success && data.redirect) {
                        window.location.href = data.redirect;
                    }
                })
                .catch(err => {
                    alert('Erro ao salvar resultado da partida.');
                    btnConfirm.innerText = 'Continuar Torneio';
                    btnConfirm.disabled = false;
                    console.error(err);
                });
            };
        }
    }

    // ========================================================
    // 27. UTILITIES
    // ========================================================
    function distance(a, b) {
        const dx = b.x - a.x;
        const dy = b.y - a.y;
        return Math.sqrt(dx * dx + dy * dy);
    }

    function darkenColor(hex, percent) {
        hex = hex.replace(/^\s*#|\s*$/g, '');
        if (hex.length === 3) hex = hex.replace(/(.)/g, '$1$1');
        let r = parseInt(hex.substr(0, 2), 16),
            g = parseInt(hex.substr(2, 2), 16),
            b = parseInt(hex.substr(4, 2), 16);
        return '#' +
           ((0|(1<<8) + r * (100 - percent) / 100).toString(16)).substr(1) +
           ((0|(1<<8) + g * (100 - percent) / 100).toString(16)).substr(1) +
           ((0|(1<<8) + b * (100 - percent) / 100).toString(16)).substr(1);
    }

    function isColorLight(hex) {
        hex = hex.replace(/^\s*#|\s*$/g, '');
        if (hex.length === 3) hex = hex.replace(/(.)/g, '$1$1');
        let r = parseInt(hex.substr(0, 2), 16),
            g = parseInt(hex.substr(2, 2), 16),
            b = parseInt(hex.substr(4, 2), 16);
        const lum = (0.299 * r + 0.587 * g + 0.114 * b);
        return lum > 160;
    }

    // Initialize line-up preview on page load
    initSimulation();
});
