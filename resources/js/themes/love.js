/**
 * Love theme — floating heart particles
 */

let animationFrame = null;
let canvas = null;

const particles = [];
const MAX_PARTICLES = 12;

function createParticle(width, height) {
    return {
        x: Math.random() * width,
        y: height + Math.random() * 20,
        size: Math.random() * 6 + 4,
        speedY: -(Math.random() * 0.4 + 0.15),
        speedX: (Math.random() - 0.5) * 0.4,
        opacity: Math.random() * 0.5 + 0.2,
        rotation: Math.random() * Math.PI * 2,
        rotationSpeed: (Math.random() - 0.5) * 0.02,
    };
}

function drawHeart(ctx, x, y, size, rotation) {
    ctx.save();
    ctx.translate(x, y);
    ctx.rotate(rotation);
    ctx.beginPath();
    const s = size / 10;
    ctx.moveTo(0, -s * 3);
    ctx.bezierCurveTo(-s * 5, -s * 8, -s * 10, -s * 1, 0, s * 5);
    ctx.bezierCurveTo(s * 10, -s * 1, s * 5, -s * 8, 0, -s * 3);
    ctx.closePath();
    ctx.fill();
    ctx.restore();
}

function animate(ctx, width, height) {
    ctx.clearRect(0, 0, width, height);

    for (let i = particles.length - 1; i >= 0; i--) {
        const p = particles[i];
        p.x += p.speedX;
        p.y += p.speedY;
        p.rotation += p.rotationSpeed;
        p.opacity -= 0.001;

        if (p.y < -10 || p.opacity <= 0) {
            particles[i] = createParticle(width, height);
            continue;
        }

        ctx.fillStyle = `rgba(233, 30, 99, ${p.opacity})`;
        drawHeart(ctx, p.x, p.y, p.size, p.rotation);
    }

    animationFrame = requestAnimationFrame(() => animate(ctx, width, height));
}

export function init() {
    const container = document.querySelector('[data-flux-sidebar-header], flux\\:sidebar\\.header, [data-theme-particles]');
    if (!container) return;

    canvas = document.createElement('canvas');
    canvas.classList.add('theme-particles-canvas');
    canvas.style.cssText = 'position:absolute;inset:0;width:100%;height:100%;pointer-events:none;z-index:0;';
    container.style.position = 'relative';
    container.appendChild(canvas);

    const rect = container.getBoundingClientRect();
    canvas.width = rect.width;
    canvas.height = rect.height;

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
