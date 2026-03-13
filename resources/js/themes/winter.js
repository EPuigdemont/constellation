/**
 * Winter theme — gentle snowfall
 */

let animationFrame = null;
let canvas = null;

const particles = [];
const MAX_PARTICLES = 25;

function createParticle(width, height) {
    return {
        x: Math.random() * width,
        y: Math.random() * height,
        size: Math.random() * 3 + 1,
        speedY: Math.random() * 0.3 + 0.1,
        speedX: (Math.random() - 0.5) * 0.2,
        opacity: Math.random() * 0.5 + 0.15,
        sway: Math.random() * Math.PI * 2,
        swaySpeed: Math.random() * 0.008 + 0.003,
    };
}

function animate(ctx, width, height) {
    ctx.clearRect(0, 0, width, height);

    for (let i = particles.length - 1; i >= 0; i--) {
        const p = particles[i];
        p.sway += p.swaySpeed;
        p.x += p.speedX + Math.sin(p.sway) * 0.2;
        p.y += p.speedY;

        if (p.y > height + 5) {
            particles[i] = createParticle(width, height);
            particles[i].y = -5;
            continue;
        }

        ctx.beginPath();
        ctx.arc(p.x, p.y, p.size, 0, Math.PI * 2);
        ctx.fillStyle = `rgba(200, 220, 255, ${p.opacity})`;
        ctx.fill();
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
