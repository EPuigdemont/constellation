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

/* ─── Diary Entry Glitter / Falling Particles ─── */

const glitterConfigs = {
    winter: {
        emoji: ['❄', '❅', '❆', '✧'],
        colors: ['#90caf9', '#bbdefb', '#e3f2fd', '#ffffff'],
        count: 12,
        speedY: [0.3, 0.8],
        size: [8, 14],
    },
    autumn: {
        emoji: ['🍂', '🍁', '🍃'],
        colors: ['#e65100', '#ff6d00', '#ff8f00', '#bf360c'],
        count: 10,
        speedY: [0.4, 1.0],
        size: [10, 16],
    },
    spring: {
        emoji: ['🌸', '✿', '❀', '🌼'],
        colors: ['#f8bbd0', '#f48fb1', '#81c784', '#c8e6c9'],
        count: 10,
        speedY: [0.2, 0.6],
        size: [8, 14],
    },
    summer: {
        emoji: ['❋', '✾', '❁'],
        colors: ['#ffe082', '#ffd54f', '#fff9c4', '#ffffff'],
        count: 8,
        speedY: [0.15, 0.5],
        size: [8, 12],
    },
    love: {
        emoji: ['♥', '♡', '❤'],
        colors: ['#f48fb1', '#e91e63', '#f8bbd0'],
        count: 10,
        speedY: [0.2, 0.5],
        size: [8, 14],
    },
    breeze: {
        emoji: ['~', '≈', '∿'],
        colors: ['#80deea', '#b2ebf2', '#e0f7fa'],
        count: 8,
        speedY: [0.15, 0.4],
        size: [10, 14],
    },
    night: {
        emoji: ['✦', '★', '✧', '⋆'],
        colors: ['#9fa8da', '#c5cae9', '#e8eaf6'],
        count: 10,
        speedY: [0.1, 0.35],
        size: [6, 12],
    },
    cozy: {
        emoji: ['✦', '·', '⸰'],
        colors: ['#bcaaa4', '#d7ccc8', '#ff8f00', '#ffab00'],
        count: 8,
        speedY: [0.1, 0.3],
        size: [6, 10],
    },
};

const glitterInstances = new Map();

function initDiaryGlitter(canvas) {
    if (glitterInstances.has(canvas)) return;

    const theme = canvas.dataset.glitterTheme || 'summer';
    const config = glitterConfigs[theme] || glitterConfigs.summer;
    const ctx = canvas.getContext('2d');
    const particles = [];
    let raf = null;
    let isHovered = false;

    function resize() {
        const parent = canvas.parentElement;
        if (!parent) return;
        canvas.width = parent.offsetWidth;
        canvas.height = parent.offsetHeight;
    }

    function spawn(initial) {
        const w = canvas.width;
        const h = canvas.height;
        const count = isHovered ? config.count * 2 : config.count;

        while (particles.length < count) {
            particles.push({
                x: Math.random() * w,
                y: initial ? Math.random() * h : Math.random() * h * -0.3,
                emoji: config.emoji[Math.floor(Math.random() * config.emoji.length)],
                size: config.size[0] + Math.random() * (config.size[1] - config.size[0]),
                speedY: config.speedY[0] + Math.random() * (config.speedY[1] - config.speedY[0]),
                speedX: (Math.random() - 0.5) * 0.4,
                opacity: 0.3 + Math.random() * 0.5,
                rotation: Math.random() * 360,
                rotSpeed: (Math.random() - 0.5) * 1.5,
            });
        }
    }

    function draw() {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        const targetCount = isHovered ? config.count * 2 : config.count;

        for (let i = particles.length - 1; i >= 0; i--) {
            const p = particles[i];
            p.y += p.speedY;
            p.x += p.speedX;
            p.rotation += p.rotSpeed;

            if (p.y > canvas.height + 20) {
                if (particles.length > targetCount) {
                    particles.splice(i, 1);
                    continue;
                }
                p.y = -20;
                p.x = Math.random() * canvas.width;
            }

            ctx.save();
            ctx.translate(p.x, p.y);
            ctx.rotate((p.rotation * Math.PI) / 180);
            ctx.globalAlpha = p.opacity;
            ctx.font = `${p.size}px serif`;
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            ctx.fillText(p.emoji, 0, 0);
            ctx.restore();
        }

        spawn();
        raf = requestAnimationFrame(draw);
    }

    const parent = canvas.parentElement;
    parent.addEventListener('mouseenter', () => { isHovered = true; });
    parent.addEventListener('mouseleave', () => { isHovered = false; });

    resize();
    spawn(true);
    draw();

    const ro = new ResizeObserver(() => resize());
    ro.observe(parent);

    glitterInstances.set(canvas, { raf, ro });
}

function destroyDiaryGlitter(canvas) {
    const instance = glitterInstances.get(canvas);
    if (instance) {
        cancelAnimationFrame(instance.raf);
        instance.ro.disconnect();
        glitterInstances.delete(canvas);
    }
}

function initAllDiaryGlitter() {
    // Destroy removed canvases
    for (const [canvas] of glitterInstances) {
        if (!document.contains(canvas)) {
            destroyDiaryGlitter(canvas);
        }
    }

    // Init new canvases
    document.querySelectorAll('.diary-entry-glitter').forEach(initDiaryGlitter);
    document.querySelectorAll('.page-glitter').forEach(initDiaryGlitter);
}

document.addEventListener('DOMContentLoaded', initAllDiaryGlitter);
document.addEventListener('livewire:navigated', () => requestAnimationFrame(initAllDiaryGlitter));
document.addEventListener('livewire:morph.updated', () => requestAnimationFrame(initAllDiaryGlitter));
