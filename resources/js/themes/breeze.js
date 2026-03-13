/**
 * Breeze theme — floating cloud wisps
 */

let animationFrame = null;
let canvas = null;

const particles = [];
const MAX_PARTICLES = 10;

function createParticle(width, height) {
    return {
        x: -30 + Math.random() * 10,
        y: Math.random() * height,
        width: Math.random() * 30 + 20,
        height: Math.random() * 10 + 5,
        speedX: Math.random() * 0.3 + 0.1,
        opacity: Math.random() * 0.15 + 0.05,
    };
}

function animate(ctx, width, height) {
    ctx.clearRect(0, 0, width, height);

    for (let i = particles.length - 1; i >= 0; i--) {
        const p = particles[i];
        p.x += p.speedX;

        if (p.x > width + 40) {
            particles[i] = createParticle(width, height);
            continue;
        }

        ctx.beginPath();
        ctx.ellipse(p.x, p.y, p.width / 2, p.height / 2, 0, 0, Math.PI * 2);
        ctx.fillStyle = `rgba(128, 222, 234, ${p.opacity})`;
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
        const p = createParticle(canvas.width, canvas.height);
        p.x = Math.random() * canvas.width;
        particles.push(p);
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
