/**
 * Spring theme — floating blossom petals
 */

let animationFrame = null;
let canvas = null;

const particles = [];
const MAX_PARTICLES = 18;

function createParticle(width, height) {
    return {
        x: Math.random() * width,
        y: Math.random() * height,
        size: Math.random() * 4 + 2,
        speedY: Math.random() * 0.3 + 0.1,
        speedX: (Math.random() - 0.3) * 0.4,
        opacity: Math.random() * 0.5 + 0.2,
        rotation: Math.random() * 360,
        rotSpeed: (Math.random() - 0.5) * 0.5,
        life: Math.random() * 100,
    };
}

function animate(ctx, width, height) {
    ctx.clearRect(0, 0, width, height);

    for (let i = particles.length - 1; i >= 0; i--) {
        const p = particles[i];
        p.x += p.speedX;
        p.y += p.speedY;
        p.rotation += p.rotSpeed;
        p.life += 0.3;
        p.opacity = Math.sin(p.life * 0.03) * 0.4 + 0.2;

        if (p.y > height + 5 || p.opacity <= 0) {
            particles[i] = createParticle(width, height);
            particles[i].y = -5;
            continue;
        }

        ctx.save();
        ctx.translate(p.x, p.y);
        ctx.rotate((p.rotation * Math.PI) / 180);
        ctx.beginPath();
        ctx.ellipse(0, 0, p.size, p.size * 0.6, 0, 0, Math.PI * 2);
        ctx.fillStyle = `rgba(244, 143, 177, ${p.opacity})`;
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
