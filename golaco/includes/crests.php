<?php
/**
 * GOLAÇO - Brazilian Soccer Team Badges & Copa do Brasil Logo (Vector SVGs)
 */

function getCopaDoBrasilLogoSvg($width = 54, $height = 54) {
    return <<<SVG
    <svg width="{$width}" height="{$height}" viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg" style="filter: drop-shadow(0 2px 8px rgba(0,255,102,0.3));">
        <!-- Golden Shield Base -->
        <path d="M40 4L66 14V38C66 54 40 74 40 74C40 74 14 54 14 38V14L40 4Z" fill="url(#copaGold)" stroke="#ffd700" stroke-width="2.5"/>
        <!-- Inner Emerald Green Field -->
        <path d="M40 9L61 17V37C61 50 40 67 40 67C40 67 19 50 19 37V17L40 9Z" fill="#0d4722" stroke="#4be07b" stroke-width="1.5"/>
        <!-- Trophy Silhouette -->
        <path d="M32 24H48V34C48 38.4 44.4 42 40 42C35.6 42 32 38.4 32 34V24Z" fill="#ffea79"/>
        <path d="M28 26C28 26 27 32 32 33M52 26C52 26 53 32 48 33" stroke="#ffea79" stroke-width="2" stroke-linecap="round"/>
        <rect x="37" y="42" width="6" height="8" fill="#ffd700"/>
        <path d="M33 50H47L45 56H35L33 50Z" fill="#ffd700"/>
        <!-- Brazilian Stars Ribbon -->
        <circle cx="40" cy="30" r="2.5" fill="#00ff66"/>
        <circle cx="35" cy="35" r="1.5" fill="#ffffff"/>
        <circle cx="45" cy="35" r="1.5" fill="#ffffff"/>
        <!-- Gradients -->
        <defs>
            <linearGradient id="copaGold" x1="14" y1="4" x2="66" y2="74" gradientUnits="userSpaceOnUse">
                <stop stop-color="#FFF275"/>
                <stop offset="0.5" stop-color="#E5A910"/>
                <stop offset="1" stop-color="#9C6B00"/>
            </linearGradient>
        </defs>
    </svg>
SVG;
}

function getTeamBadgeSvg($teamName, $kitColor = '#00ff66', $size = 46) {
    if (is_array($kitColor)) {
        $kitColor = $kitColor['primary'] ?? $kitColor[0] ?? '#00ff66';
    }
    if (!is_string($kitColor) || empty($kitColor)) {
        $kitColor = '#00ff66';
    }
    $normalized = mb_strtolower(trim($teamName), 'UTF-8');

    // 1. FLAMENGO
    if (str_contains($normalized, 'flamengo')) {
        return <<<SVG
        <svg width="{$size}" height="{$size}" viewBox="0 0 60 60" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M30 4L52 14V34C52 46 30 56 30 56C30 56 8 46 8 34V14L30 4Z" fill="#c00" stroke="#fff" stroke-width="2"/>
            <path d="M8 22H52M8 32H52M8 42H52" stroke="#111" stroke-width="5"/>
            <rect x="12" y="10" width="16" height="16" fill="#111" rx="2"/>
            <text x="20" y="22" font-family="Outfit, sans-serif" font-weight="900" font-size="10" fill="#fff" text-anchor="middle">CRF</text>
        </svg>
SVG;
    }

    // 2. PALMEIRAS
    if (str_contains($normalized, 'palmeiras')) {
        return <<<SVG
        <svg width="{$size}" height="{$size}" viewBox="0 0 60 60" fill="none" xmlns="http://www.w3.org/2000/svg">
            <circle cx="30" cy="30" r="26" fill="#006437" stroke="#fff" stroke-width="2.5"/>
            <circle cx="30" cy="30" r="21" fill="none" stroke="#fff" stroke-width="1.5" stroke-dasharray="2 3"/>
            <text x="30" y="38" font-family="serif" font-weight="900" font-size="24" fill="#fff" text-anchor="middle">P</text>
        </svg>
SVG;
    }

    // 3. SÃO PAULO
    if (str_contains($normalized, 'são paulo') || str_contains($normalized, 'sao paulo')) {
        return <<<SVG
        <svg width="{$size}" height="{$size}" viewBox="0 0 60 60" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M10 12H50L30 52L10 12Z" fill="#fff" stroke="#111" stroke-width="2"/>
            <path d="M10 12H50V22H10V12Z" fill="#111"/>
            <text x="30" y="20" font-family="Outfit, sans-serif" font-weight="900" font-size="9" fill="#fff" text-anchor="middle">SPFC</text>
            <path d="M12 24L30 50V24H12Z" fill="#c00"/>
            <path d="M48 24L30 50V24H48Z" fill="#111"/>
        </svg>
SVG;
    }

    // 4. CORINTHIANS
    if (str_contains($normalized, 'corinthians')) {
        return <<<SVG
        <svg width="{$size}" height="{$size}" viewBox="0 0 60 60" fill="none" xmlns="http://www.w3.org/2000/svg">
            <circle cx="30" cy="30" r="26" fill="#fff" stroke="#111" stroke-width="2.5"/>
            <circle cx="30" cy="30" r="21" fill="#111"/>
            <circle cx="30" cy="30" r="16" fill="#fff"/>
            <!-- Oars cross -->
            <line x1="12" y1="12" x2="48" y2="48" stroke="#c00" stroke-width="3"/>
            <line x1="48" y1="12" x2="12" y2="48" stroke="#c00" stroke-width="3"/>
            <!-- Anchor -->
            <path d="M30 18V42M22 36C22 41 38 41 38 36" stroke="#c00" stroke-width="2.5" fill="none"/>
            <text x="30" y="34" font-family="Outfit, sans-serif" font-weight="900" font-size="8" fill="#111" text-anchor="middle">SCCP</text>
        </svg>
SVG;
    }

    // 5. SANTOS
    if (str_contains($normalized, 'santos')) {
        return <<<SVG
        <svg width="{$size}" height="{$size}" viewBox="0 0 60 60" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M30 6L50 14V34C50 46 30 55 30 55C30 55 10 46 10 34V14L30 6Z" fill="#fff" stroke="#111" stroke-width="2"/>
            <line x1="10" y1="20" x2="50" y2="20" stroke="#111" stroke-width="2"/>
            <line x1="10" y1="20" x2="30" y2="54" stroke="#111" stroke-width="6"/>
            <line x1="30" y1="20" x2="45" y2="45" stroke="#111" stroke-width="5"/>
            <circle cx="20" cy="14" r="3.5" fill="#111"/>
            <text x="38" y="17" font-family="Outfit, sans-serif" font-weight="900" font-size="8" fill="#111" text-anchor="middle">SFC</text>
        </svg>
SVG;
    }

    // 6. GRÊMIO
    if (str_contains($normalized, 'grêmio') || str_contains($normalized, 'gremio')) {
        return <<<SVG
        <svg width="{$size}" height="{$size}" viewBox="0 0 60 60" fill="none" xmlns="http://www.w3.org/2000/svg">
            <circle cx="30" cy="30" r="26" fill="#0d80c7" stroke="#111" stroke-width="2.5"/>
            <ellipse cx="30" cy="30" rx="25" ry="14" fill="#fff" stroke="#111" stroke-width="1.5"/>
            <path d="M5 30H55" stroke="#111" stroke-width="7"/>
            <text x="30" y="33" font-family="Outfit, sans-serif" font-weight="900" font-size="8" fill="#fff" text-anchor="middle">GREMIO</text>
        </svg>
SVG;
    }

    // 7. INTERNACIONAL
    if (str_contains($normalized, 'internacional')) {
        return <<<SVG
        <svg width="{$size}" height="{$size}" viewBox="0 0 60 60" fill="none" xmlns="http://www.w3.org/2000/svg">
            <circle cx="30" cy="30" r="26" fill="#cc0000" stroke="#fff" stroke-width="2.5"/>
            <circle cx="30" cy="30" r="21" fill="none" stroke="#fff" stroke-width="1.5"/>
            <text x="30" y="36" font-family="serif" font-weight="900" font-size="16" fill="#fff" text-anchor="middle">SCI</text>
        </svg>
SVG;
    }

    // 8. VASCO
    if (str_contains($normalized, 'vasco')) {
        return <<<SVG
        <svg width="{$size}" height="{$size}" viewBox="0 0 60 60" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M30 6L50 14V34C50 46 30 55 30 55C30 55 10 46 10 34V14L30 6Z" fill="#111" stroke="#fff" stroke-width="2"/>
            <line x1="12" y1="16" x2="48" y2="52" stroke="#fff" stroke-width="7"/>
            <!-- Maltese Cross -->
            <path d="M26 26H34V34H26Z" fill="#c00"/>
            <path d="M23 23L27 27M37 23L33 27M23 37L27 33M37 37L33 33" stroke="#c00" stroke-width="3" stroke-linecap="square"/>
        </svg>
SVG;
    }

    // 9. FLUMINENSE
    if (str_contains($normalized, 'fluminense')) {
        return <<<SVG
        <svg width="{$size}" height="{$size}" viewBox="0 0 60 60" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M30 6L50 14V34C50 46 30 55 30 55C30 55 10 46 10 34V14L30 6Z" fill="#7a142c" stroke="#fff" stroke-width="2"/>
            <path d="M30 6L50 14V34C50 46 30 55 30 55V6Z" fill="#006437"/>
            <path d="M28 10V50H32V10H28Z" fill="#fff"/>
            <text x="30" y="35" font-family="serif" font-weight="900" font-size="12" fill="#fff" text-anchor="middle">FFC</text>
        </svg>
SVG;
    }

    // 10. BOTAFOGO
    if (str_contains($normalized, 'botafogo')) {
        return <<<SVG
        <svg width="{$size}" height="{$size}" viewBox="0 0 60 60" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M30 6L50 14V34C50 46 30 55 30 55C30 55 10 46 10 34V14L30 6Z" fill="#111" stroke="#fff" stroke-width="2"/>
            <!-- Lone Star -->
            <polygon points="30,18 33,26 42,26 35,31 38,40 30,34 22,40 25,31 18,26 27,26" fill="#fff"/>
        </svg>
SVG;
    }

    // 11. CRUZEIRO
    if (str_contains($normalized, 'cruzeiro')) {
        return <<<SVG
        <svg width="{$size}" height="{$size}" viewBox="0 0 60 60" fill="none" xmlns="http://www.w3.org/2000/svg">
            <circle cx="30" cy="30" r="26" fill="#003399" stroke="#fff" stroke-width="2"/>
            <!-- Southern Cross -->
            <polygon points="30,16 31.5,20 30,22 28.5,20" fill="#fff"/>
            <polygon points="30,38 31.5,42 30,44 28.5,42" fill="#fff"/>
            <polygon points="20,28 24,29.5 25,28 24,26.5" fill="#fff"/>
            <polygon points="40,28 36,29.5 35,28 36,26.5" fill="#fff"/>
            <polygon points="34,32 35,33 34,34 33,33" fill="#fff"/>
        </svg>
SVG;
    }

    // 12. ATLÉTICO-MG
    if (str_contains($normalized, 'atlético-mg') || str_contains($normalized, 'atletico-mg') || str_contains($normalized, 'atlético mineiro')) {
        return <<<SVG
        <svg width="{$size}" height="{$size}" viewBox="0 0 60 60" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M30 6L50 14V34C50 46 30 55 30 55C30 55 10 46 10 34V14L30 6Z" fill="#fff" stroke="#111" stroke-width="2"/>
            <path d="M18 10V48M26 8V53M34 8V53M42 10V48" stroke="#111" stroke-width="4"/>
            <rect x="18" y="14" width="24" height="14" fill="#111" rx="2"/>
            <text x="30" y="24" font-family="Outfit, sans-serif" font-weight="900" font-size="8" fill="#fff" text-anchor="middle">CAM</text>
        </svg>
SVG;
    }

    // 13. BAHIA
    if (str_contains($normalized, 'bahia')) {
        return <<<SVG
        <svg width="{$size}" height="{$size}" viewBox="0 0 60 60" fill="none" xmlns="http://www.w3.org/2000/svg">
            <circle cx="30" cy="30" r="26" fill="#fff" stroke="#003399" stroke-width="2.5"/>
            <circle cx="30" cy="30" r="21" fill="none" stroke="#c00" stroke-width="1.5"/>
            <rect x="20" y="20" width="20" height="14" fill="#003399"/>
            <rect x="20" y="24" width="20" height="6" fill="#fff"/>
            <rect x="20" y="26" width="20" height="2" fill="#c00"/>
        </svg>
SVG;
    }

    // 14. FORTALEZA
    if (str_contains($normalized, 'fortaleza')) {
        return <<<SVG
        <svg width="{$size}" height="{$size}" viewBox="0 0 60 60" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M30 6L50 14V34C50 46 30 55 30 55C30 55 10 46 10 34V14L30 6Z" fill="#003399" stroke="#fff" stroke-width="2"/>
            <path d="M30 6L50 14V34C50 46 30 55 30 55V6Z" fill="#c00"/>
            <line x1="28" y1="10" x2="28" y2="50" stroke="#fff" stroke-width="4"/>
            <text x="30" y="35" font-family="serif" font-weight="900" font-size="12" fill="#fff" text-anchor="middle">FEC</text>
        </svg>
SVG;
    }

    // 15. ATHLETICO-PR
    if (str_contains($normalized, 'athletico') || str_contains($normalized, 'atletico-pr') || str_contains($normalized, 'atlético-pr')) {
        return <<<SVG
        <svg width="{$size}" height="{$size}" viewBox="0 0 60 60" fill="none" xmlns="http://www.w3.org/2000/svg">
            <circle cx="30" cy="30" r="26" fill="#c00" stroke="#111" stroke-width="2.5"/>
            <path d="M30 8L48 16V44L30 52L12 44V16L30 8Z" fill="#111"/>
            <text x="30" y="36" font-family="Outfit, sans-serif" font-weight="900" font-size="9" fill="#fff" text-anchor="middle">CAP</text>
        </svg>
SVG;
    }

    // 16. SPORT RECIFE
    if (str_contains($normalized, 'sport')) {
        return <<<SVG
        <svg width="{$size}" height="{$size}" viewBox="0 0 60 60" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M30 6L50 14V34C50 46 30 55 30 55C30 55 10 46 10 34V14L30 6Z" fill="#c00" stroke="#ffd700" stroke-width="2"/>
            <path d="M10 20H50M10 28H50M10 36H50M10 44H50" stroke="#111" stroke-width="3"/>
            <!-- Lion Silhouette & Star -->
            <polygon points="30,12 32,15 35,15 33,17 34,20 30,18 26,20 27,17 25,15 28,15" fill="#ffd700"/>
            <text x="30" y="36" font-family="Outfit, sans-serif" font-weight="900" font-size="9" fill="#ffd700" text-anchor="middle">SCR</text>
        </svg>
SVG;
    }

    // 17. CEARÁ
    if (str_contains($normalized, 'ceará') || str_contains($normalized, 'ceara')) {
        return <<<SVG
        <svg width="{$size}" height="{$size}" viewBox="0 0 60 60" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M30 6L50 14V34C50 46 30 55 30 55C30 55 10 46 10 34V14L30 6Z" fill="#fff" stroke="#111" stroke-width="2"/>
            <path d="M10 14H50V24H10V14Z" fill="#111"/>
            <text x="30" y="21" font-family="Outfit, sans-serif" font-weight="900" font-size="8" fill="#fff" text-anchor="middle">CEARÁ</text>
            <path d="M10 24L30 54V24H10Z" fill="#111"/>
            <circle cx="24" cy="30" r="1.5" fill="#ffd700"/>
            <circle cx="30" cy="27" r="1.8" fill="#ffd700"/>
            <circle cx="36" cy="30" r="1.5" fill="#ffd700"/>
            <text x="38" y="42" font-family="serif" font-weight="900" font-size="12" fill="#111" text-anchor="middle">CSC</text>
        </svg>
SVG;
    }

    // 18. GOIÁS
    if (str_contains($normalized, 'goiás') || str_contains($normalized, 'goias')) {
        return <<<SVG
        <svg width="{$size}" height="{$size}" viewBox="0 0 60 60" fill="none" xmlns="http://www.w3.org/2000/svg">
            <circle cx="30" cy="30" r="26" fill="#006437" stroke="#fff" stroke-width="2.5"/>
            <circle cx="30" cy="30" r="20" fill="none" stroke="#fff" stroke-width="1.8"/>
            <text x="30" y="38" font-family="Outfit, sans-serif" font-weight="900" font-size="22" fill="#fff" text-anchor="middle">G</text>
        </svg>
SVG;
    }

    // 19. CORITIBA
    if (str_contains($normalized, 'coritiba')) {
        return <<<SVG
        <svg width="{$size}" height="{$size}" viewBox="0 0 60 60" fill="none" xmlns="http://www.w3.org/2000/svg">
            <circle cx="30" cy="30" r="26" fill="#005a36" stroke="#fff" stroke-width="2.5"/>
            <rect x="6" y="22" width="48" height="16" fill="#fff"/>
            <text x="30" y="34" font-family="Outfit, sans-serif" font-weight="900" font-size="10" fill="#005a36" text-anchor="middle">CFC</text>
            <circle cx="30" cy="14" r="2" fill="#ffd700"/>
        </svg>
SVG;
    }

    // 20. NOVORIZONTINO
    if (str_contains($normalized, 'novorizontino')) {
        return <<<SVG
        <svg width="{$size}" height="{$size}" viewBox="0 0 60 60" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M30 6L50 14V34C50 46 30 55 30 55C30 55 10 46 10 34V14L30 6Z" fill="#ffd700" stroke="#111" stroke-width="2.5"/>
            <path d="M12 24L30 52V24H12Z" fill="#111"/>
            <polygon points="30,12 32,16 36,16 33,19 34,23 30,20 26,23 27,19 24,16 28,16" fill="#111"/>
            <text x="30" y="36" font-family="Outfit, sans-serif" font-weight="900" font-size="9" fill="#ffd700" text-anchor="middle">GEN</text>
        </svg>
SVG;
    }

    // 21. DEFAULT FOR GENERIC / USER TEAM
    $initials = mb_strtoupper(mb_substr($teamName, 0, min(3, mb_strlen($teamName)), 'UTF-8'));
    $bgGradientStart = htmlspecialchars($kitColor);
    $bgGradientEnd = '#071810';

    return <<<SVG
    <svg width="{$size}" height="{$size}" viewBox="0 0 60 60" fill="none" xmlns="http://www.w3.org/2000/svg" style="filter: drop-shadow(0 2px 8px rgba(0,0,0,0.5));">
        <path d="M30 5L52 14V34C52 47 30 56 30 56C30 56 8 47 8 34V14L30 5Z" fill="url(#uTeamGrad_{$size})" stroke="#ffd700" stroke-width="2.5"/>
        <path d="M30 9L48 16V33C48 43 30 51 30 51C30 51 12 43 12 33V16L30 9Z" fill="none" stroke="rgba(255,255,255,0.4)" stroke-width="1.2"/>
        <!-- Star on top -->
        <polygon points="30,12 32,16 36,16 33,19 34,23 30,20 26,23 27,19 24,16 28,16" fill="#ffd700"/>
        <!-- Team Initials -->
        <text x="30" y="38" font-family="'Outfit', sans-serif" font-weight="900" font-size="13" fill="#ffffff" text-anchor="middle" letter-spacing="1">{$initials}</text>
        <defs>
            <linearGradient id="uTeamGrad_{$size}" x1="8" y1="5" x2="52" y2="56" gradientUnits="userSpaceOnUse">
                <stop stop-color="{$bgGradientStart}"/>
                <stop offset="1" stop-color="{$bgGradientEnd}"/>
            </linearGradient>
        </defs>
    </svg>
SVG;
}
