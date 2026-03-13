import * as summer from './themes/summer.js';
import * as love from './themes/love.js';
import * as breeze from './themes/breeze.js';
import * as night from './themes/night.js';
import * as cozy from './themes/cozy.js';

const themes = { summer, love, breeze, night, cozy };
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

function activateTheme() {
    const name = getActiveTheme();
    if (name === currentTheme) return;

    if (currentTheme && themes[currentTheme]) {
        themes[currentTheme].destroy();
    }

    currentTheme = name;

    if (name && themes[name]) {
        themes[name].init();
    }
}

document.addEventListener('DOMContentLoaded', () => {
    if (window.matchMedia('(prefers-reduced-motion: no-preference)').matches) {
        activateTheme();
    }
});

document.addEventListener('theme-changed', () => {
    if (window.matchMedia('(prefers-reduced-motion: no-preference)').matches) {
        activateTheme();
    }
});
