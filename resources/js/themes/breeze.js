/**
 * Breeze theme — floating cloud wisps with varied speeds
 */

let animationFrame = null;
let canvas = null;

const clouds = [];
const MAX_CLOUDS = 14;

function createCloud(width, height, startRandom = false) {
    const speed = Math.random() * 0.5 + 0.1; // 0.1 to 0.6 — some much faster
    return {
        x: startRandom ? Math.random() * width : -60 - Math.random() * 100,
        y: Math.random() * height * 0.85,
        // Cloud = group of 2-3 overlapping ellipses
        parts: Array.from({ length: 2 + Math.floor(Math.random() * 2) }, () => ({
            offsetX: (Math.random() - 0.5) * 30,
            offsetY: (Math.random() - 0.5) * 8,
            rx: Math.random() * 40 + 25,
            ry: Math.random() * 12 + 6,
        })),
        speedX: speed,
        opacity: Math.random() * 0.12 + 0.04,
    };
}

function animate(ctx, width, height) {
    ctx.clearRect(0, 0, width, height);

    for (let i = clouds.length - 1; i >= 0; i--) {
        const c = clouds[i];
        c.x += c.speedX;

        // Reset when off-screen right
        const maxPartWidth = Math.max(...c.parts.map(p => p.rx));
        if (c.x - maxPartWidth > width + 60) {
            clouds[i] = createCloud(width, height);
            continue;
        }

        for (const part of c.parts) {
            ctx.beginPath();
            ctx.ellipse(c.x + part.offsetX, c.y + part.offsetY, part.rx, part.ry, 0, 0, Math.PI * 2);
            ctx.fillStyle = `rgba(128, 222, 234, ${c.opacity})`;
            ctx.fill();
        }
    }

    animationFrame = requestAnimationFrame(() => animate(ctx, width, height));
}

export function init() {
    const container = document.querySelector('[data-theme-particles]');
    if (!container) return;

    canvas = document.createElement('canvas');
    canvas.classList.add('theme-particles-canvas');
    container.appendChild(canvas);

    canvas.width = window.innerWidth;
    canvas.height = window.innerHeight;

    const ctx = canvas.getContext('2d');
    clouds.length = 0;
    for (let i = 0; i < MAX_CLOUDS; i++) {
        clouds.push(createCloud(canvas.width, canvas.height, true));
    }

    const resizeHandler = () => {
        canvas.width = window.innerWidth;
        canvas.height = window.innerHeight;
    };
    window.addEventListener('resize', resizeHandler);
    canvas._resizeHandler = resizeHandler;

    animate(ctx, canvas.width, canvas.height);
}

export function destroy() {
    if (animationFrame) {
        cancelAnimationFrame(animationFrame);
        animationFrame = null;
    }
    if (canvas) {
        if (canvas._resizeHandler) {
            window.removeEventListener('resize', canvas._resizeHandler);
        }
        canvas.remove();
        canvas = null;
    }
    clouds.length = 0;
}
