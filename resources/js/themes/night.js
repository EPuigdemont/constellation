/**
 * Night theme — twinkling star dots
 */

let animationFrame = null;
let canvas = null;

const particles = [];
const MAX_PARTICLES = 20;

function createParticle(width, height) {
    return {
        x: Math.random() * width,
        y: Math.random() * height,
        size: Math.random() * 2 + 0.5,
        baseOpacity: Math.random() * 0.4 + 0.2,
        twinkleSpeed: Math.random() * 0.03 + 0.01,
        phase: Math.random() * Math.PI * 2,
    };
}

function animate(ctx, width, height, time) {
    ctx.clearRect(0, 0, width, height);

    for (const p of particles) {
        const opacity = p.baseOpacity + Math.sin(time * p.twinkleSpeed + p.phase) * 0.3;
        if (opacity <= 0) continue;

        ctx.beginPath();
        ctx.arc(p.x, p.y, p.size, 0, Math.PI * 2);
        ctx.fillStyle = `rgba(159, 168, 218, ${Math.max(0, opacity)})`;
        ctx.fill();
    }

    animationFrame = requestAnimationFrame((t) => animate(ctx, width, height, t));
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
