/**
 * GOLAÇO — Match simulation global configuration & state constants
 */
window.GolacoMatch = window.GolacoMatch || {};

// Enable simulation debug HUD by default (toggleable via UI button or 'D' key)
window.GolacoMatch.DEBUG_SIMULATION = true;

/** Real seconds for a full 90-minute match (plus stoppage handled separately) */
window.GolacoMatch.BASE_MATCH_REAL_SECONDS = 70;

/** Default match speed multiplier (UI speed buttons multiply this) */
window.GolacoMatch.MATCH_SPEED = 1.0;

window.GolacoMatch.MATCH_STATE = {
    PRE_MATCH: 'PRE_MATCH',
    FIRST_HALF: 'FIRST_HALF',
    FIRST_HALF_ADDED: 'FIRST_HALF_ADDED',
    HALFTIME: 'HALFTIME',
    SECOND_HALF: 'SECOND_HALF',
    SECOND_HALF_ADDED: 'SECOND_HALF_ADDED',
    SET_PIECE: 'SET_PIECE',
    GOAL_MOMENT: 'GOAL_MOMENT',
    PENALTY_SHOOTOUT: 'PENALTY_SHOOTOUT',
    FULL_TIME: 'FULL_TIME'
};

window.GolacoMatch.AI_PHASE = {
    BUILD_UP: 'BUILD_UP',
    PROGRESSION: 'PROGRESSION',
    FINAL_THIRD: 'FINAL_THIRD',
    CHANCE_CREATION: 'CHANCE_CREATION',
    SHOT: 'SHOT',
    RECOVERY: 'RECOVERY',
    PRESSING: 'PRESSING',
    DEFENDING: 'DEFENDING',
    COUNTER_ATTACK: 'COUNTER_ATTACK'
};
