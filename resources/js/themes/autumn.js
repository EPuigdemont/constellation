/**
 * Autumn theme — falling leaves
 */

let animationFrame = null;
let canvas = null;

const particles = [];
const MAX_PARTICLES = 15;

const leafColors = [
    'rgba(230, 81, 0, ',
    'rgba(255, 109, 0, ',
    'rgba(191, 54, 12, ',
    'rgba(255, 143, 0, ',
];

function createParticle(width, height) {
    return {
        x: Math.random() * width,
        y: Math.random() * height,
        size: Math.random() * 5 + 3,
        speedY: Math.random() * 0.4 + 0.15,
        speedX: (Math.random() - 0.5) * 0.6,
        opacity: Math.random() * 0.45 + 0.15,
        rotation: Math.random() * 360,
        rotSpeed: (Math.random() - 0.5) * 0.8,
        sway: Math.random() * Math.PI * 2,
        swaySpeed: Math.random() * 0.01 + 0.005,
        colorIdx: Math.floor(Math.random() * leafColors.length),
    };
}

function animate(ctx, width, height) {
    ctx.clearRect(0, 0, width, height);

    for (let i = particles.length - 1; i >= 0; i--) {
        const p = particles[i];
        p.sway += p.swaySpeed;
        p.x += p.speedX + Math.sin(p.sway) * 0.3;
        p.y += p.speedY;
        p.rotation += p.rotSpeed;

        if (p.y > height + 10) {
            particles[i] = createParticle(width, height);
            particles[i].y = -10;
            continue;
        }

        ctx.save();
        ctx.translate(p.x, p.y);
        ctx.rotate((p.rotation * Math.PI) / 180);
        ctx.beginPath();
        // Leaf shape: two arcs
        ctx.moveTo(0, -p.size);
        ctx.quadraticCurveTo(p.size, 0, 0, p.size);
        ctx.quadraticCurveTo(-p.size, 0, 0, -p.size);
        ctx.fillStyle = leafColors[p.colorIdx] + p.opacity + ')';
        ctx.fill();
        ctx.restore();
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
    particles.length = 0;
    for (let i = 0; i < MAX_PARTICLES; i++) {
        particles.push(createParticle(canvas.width, canvas.height));
    }

    animate(ctx, canvas.width, canvas.height);
}

export function destroy() {
    if (animationFrame) {
        cancelAnimationFrame(animationFrame);
        animationFrame = null;
    }
    if (canvas) {
        canvas.remove();
        canvas = null;
    }
    particles.length = 0;
}
