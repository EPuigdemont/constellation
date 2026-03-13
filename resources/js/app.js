import * as spring from './themes/spring.js';
import * as summer from './themes/summer.js';
import * as autumn from './themes/autumn.js';
import * as winter from './themes/winter.js';
import * as love from './themes/love.js';
import * as breeze from './themes/breeze.js';
import * as night from './themes/night.js';
import * as cozy from './themes/cozy.js';

const themes = { spring, summer, autumn, winter, love, breeze, night, cozy };
let currentTheme = null;

function getActiveTheme() {
    const body = document.body;
    for (const name of Object.keys(themes)) {
        if (body.classList.contains(`theme-${name}`)) {
            return name;
        }
    }
    return null;
}

function activateTheme(force = false) {
    if (!window.matchMedia('(prefers-reduced-motion: no-preference)').matches) return;

    const name = getActiveTheme();

    // Always destroy old theme first (canvas may have been removed by navigation)
    if (currentTheme && themes[currentTheme]) {
        themes[currentTheme].destroy();
    }

    currentTheme = name;

    if (name && themes[name]) {
        themes[name].init();
    }
}

// Initial load
document.addEventListener('DOMContentLoaded', () => activateTheme());

// Livewire navigation (wire:navigate)
document.addEventListener('livewire:navigated', () => {
    // Small delay to let DOM settle after Livewire morph
    requestAnimationFrame(() => activateTheme(true));
});

// Manual theme change
document.addEventListener('theme-changed', () => activateTheme(true));
