/**
 * Cozy theme — floating embers
 */

let animationFrame = null;
let canvas = null;

const particles = [];
const MAX_PARTICLES = 12;

function createParticle(width, height) {
    return {
        x: Math.random() * width,
        y: height + Math.random() * 10,
        size: Math.random() * 2.5 + 1,
        speedY: -(Math.random() * 0.3 + 0.1),
        speedX: (Math.random() - 0.5) * 0.5,
        opacity: Math.random() * 0.5 + 0.3,
        flicker: Math.random() * Math.PI * 2,
        flickerSpeed: Math.random() * 0.08 + 0.03,
    };
}

function animate(ctx, width, height, time) {
    ctx.clearRect(0, 0, width, height);

    for (let i = particles.length - 1; i >= 0; i--) {
        const p = particles[i];
        p.x += p.speedX + Math.sin(time * 0.001 + p.flicker) * 0.2;
        p.y += p.speedY;
        p.opacity -= 0.002;

        const flickerOpacity = p.opacity * (0.7 + Math.sin(time * p.flickerSpeed + p.flicker) * 0.3);

        if (p.y < -5 || flickerOpacity <= 0) {
            particles[i] = createParticle(width, height);
            continue;
        }

        const gradient = ctx.createRadialGradient(p.x, p.y, 0, p.x, p.y, p.size * 2);
        gradient.addColorStop(0, `rgba(255, 140, 50, ${flickerOpacity})`);
        gradient.addColorStop(1, `rgba(200, 80, 20, 0)`);

        ctx.beginPath();
        ctx.arc(p.x, p.y, p.size * 2, 0, Math.PI * 2);
        ctx.fillStyle = gradient;
        ctx.fill();
    }

    animationFrame = requestAnimationFrame((t) => animate(ctx, width, height, t));
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

    animate(ctx, canvas.width, canvas.height, 0);
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
