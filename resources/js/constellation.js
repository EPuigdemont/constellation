/**
 * Constellation — D3.js force-directed graph + starry background
 *
 * All graph/node visualization logic lives here.
 * Registered as Alpine.data('constellationApp').
 */

import * as d3 from 'd3';

/* ─── Mood colour map (matches CSS custom properties) ─── */
const moodColors = {
    spring: '#81c784',
    summer: '#ffd54f',
    autumn: '#ff8a65',
    winter: '#90caf9',
    love:   '#f48fb1',
    breeze: '#80deea',
    night:  '#9fa8da',
    cozy:   '#bcaaa4',
    plain:  '#a3a3a3',
    custom: '#a3a3a3',
};

/* ─── Node shape helpers ─── */
function nodeSymbol(type) {
    switch (type) {
        case 'diary':  return d3.symbolCircle;
        case 'note':   return d3.symbolSquare;
        case 'postit': return d3.symbolDiamond;
        case 'image':  return d3.symbolSquare2;
        default:       return d3.symbolCircle;
    }
}

function nodeSize(type) {
    switch (type) {
        case 'diary':  return 220;
        case 'note':   return 180;
        case 'postit': return 140;
        case 'image':  return 200;
        default:       return 160;
    }
}

/* ─── Starry background ─── */
function createStars(container) {
    const canvas = document.createElement('canvas');
    canvas.classList.add('constellation-stars-canvas');
    canvas.style.cssText = 'position:absolute;inset:0;width:100%;height:100%;';
    container.appendChild(canvas);

    const ctx = canvas.getContext('2d');
    const stars = [];
    let raf = null;

    function resize() {
        canvas.width = container.offsetWidth;
        canvas.height = container.offsetHeight;
        if (stars.length === 0) {
            for (let i = 0; i < 120; i++) {
                stars.push({
                    x: Math.random() * canvas.width,
                    y: Math.random() * canvas.height,
                    r: Math.random() * 1.5 + 0.3,
                    baseOpacity: Math.random() * 0.6 + 0.2,
                    speed: Math.random() * 0.003 + 0.001,
                    phase: Math.random() * Math.PI * 2,
                    fourPoint: Math.random() < 0.2,
                });
            }
        }
    }

    function drawFourPointStar(ctx, x, y, outer, inner) {
        ctx.beginPath();
        for (let i = 0; i < 4; i++) {
            const a = (i * Math.PI) / 2 - Math.PI / 2;
            const m = a + Math.PI / 4;
            ctx.lineTo(x + Math.cos(a) * outer, y + Math.sin(a) * outer);
            ctx.lineTo(x + Math.cos(m) * inner, y + Math.sin(m) * inner);
        }
        ctx.closePath();
        ctx.fill();
    }

    function animate(time) {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        for (const s of stars) {
            const opacity = s.baseOpacity + Math.sin(time * s.speed + s.phase) * 0.3;
            if (opacity <= 0) continue;
            ctx.fillStyle = `rgba(200, 210, 240, ${Math.max(0, opacity)})`;
            if (s.fourPoint) {
                const pulse = 1 + Math.sin(time * s.speed * 1.5 + s.phase) * 0.3;
                drawFourPointStar(ctx, s.x, s.y, s.r * 3 * pulse, s.r * 0.6);
            } else {
                ctx.beginPath();
                ctx.arc(s.x, s.y, s.r, 0, Math.PI * 2);
                ctx.fill();
            }
        }
        raf = requestAnimationFrame(animate);
    }

    resize();
    raf = requestAnimationFrame(animate);

    const ro = new ResizeObserver(resize);
    ro.observe(container);

    return {
        parallax(offsetX, offsetY) {
            // Gentle parallax on stars
            canvas.style.transform = `translate(${offsetX * 0.02}px, ${offsetY * 0.02}px)`;
        },
        destroy() {
            cancelAnimationFrame(raf);
            ro.disconnect();
            canvas.remove();
        },
    };
}

/* ─── Edge styling ─── */
function edgeStroke(type) {
    switch (type) {
        case 'parent_child': return { dash: null,     opacity: 0.7, width: 2   };
        case 'sibling':      return { dash: '6,3',    opacity: 0.6, width: 1.5 };
        case 'tag':          return { dash: null,      opacity: 0.3, width: 1   };
        case 'date':         return { dash: '2,4',    opacity: 0.15, width: 0.8 };
        default:             return { dash: null,      opacity: 0.2, width: 0.8 };
    }
}

/* ─── Alpine component ─── */
window.constellationApp = function () {
    return {
        selectedNode: null,
        connectedCount: 0,
        _starsCtrl: null,
        _simulation: null,
        _wire: null,

        init(wire) {
            this._wire = wire;

            // Stars background
            const starsEl = this.$refs.starsCanvas;
            if (starsEl) {
                this._starsCtrl = createStars(starsEl);
            }

            // Build graph from embedded JSON
            this.$nextTick(() => this.buildGraph());

            // Rebuild when Livewire re-renders (filter changes)
            this.$watch('$wire.filterType', () => this.$nextTick(() => this.buildGraph()));
            this.$watch('$wire.filterTag', () => this.$nextTick(() => this.buildGraph()));
            this.$watch('$wire.filterMonth', () => this.$nextTick(() => this.buildGraph()));
            this.$watch('$wire.filterWeekday', () => this.$nextTick(() => this.buildGraph()));
            this.$watch('$wire.filterDateFrom', () => this.$nextTick(() => this.buildGraph()));
            this.$watch('$wire.filterDateTo', () => this.$nextTick(() => this.buildGraph()));
        },

        parallax(event) {
            if (!this._starsCtrl) return;
            const cx = window.innerWidth / 2;
            const cy = window.innerHeight / 2;
            this._starsCtrl.parallax(event.clientX - cx, event.clientY - cy);
        },

        buildGraph() {
            const jsonEl = this.$refs.graphData;
            if (!jsonEl) return;

            let data;
            try {
                data = JSON.parse(jsonEl.textContent);
            } catch {
                return;
            }

            const svg = d3.select(this.$refs.graphSvg);
            svg.selectAll('*').remove();

            if (this._simulation) {
                this._simulation.stop();
            }

            const container = this.$refs.graphContainer;
            const width = container.offsetWidth;
            const height = container.offsetHeight;

            svg.attr('viewBox', [0, 0, width, height]);

            // Zoom + pan
            const g = svg.append('g');
            const zoom = d3.zoom()
                .scaleExtent([0.2, 5])
                .on('zoom', (event) => g.attr('transform', event.transform));
            svg.call(zoom);

            // Center initial view
            svg.call(zoom.transform, d3.zoomIdentity.translate(width / 2, height / 2).scale(0.8));

            const nodes = data.nodes.map(d => ({ ...d }));
            const edges = data.edges.map(d => ({ ...d }));

            // Force simulation
            const simulation = d3.forceSimulation(nodes)
                .force('link', d3.forceLink(edges).id(d => d.id).distance(d => {
                    if (d.type === 'parent_child') return 80;
                    if (d.type === 'sibling') return 100;
                    if (d.type === 'tag') return 150;
                    return 200;
                }).strength(d => d.strength * 0.5))
                .force('charge', d3.forceManyBody().strength(-200))
                .force('center', d3.forceCenter(0, 0))
                .force('collision', d3.forceCollide().radius(20))
                .alphaDecay(0.02);

            this._simulation = simulation;

            // Draw edges
            const link = g.append('g')
                .selectAll('line')
                .data(edges)
                .join('line')
                .each(function (d) {
                    const style = edgeStroke(d.type);
                    const el = d3.select(this);
                    el.attr('stroke', 'var(--theme-accent, #9fa8da)')
                      .attr('stroke-opacity', style.opacity)
                      .attr('stroke-width', style.width);
                    if (style.dash) el.attr('stroke-dasharray', style.dash);
                });

            // Draw nodes
            const node = g.append('g')
                .selectAll('g')
                .data(nodes)
                .join('g')
                .attr('cursor', 'pointer')
                .call(d3.drag()
                    .on('start', (event, d) => {
                        if (!event.active) simulation.alphaTarget(0.3).restart();
                        d.fx = d.x;
                        d.fy = d.y;
                    })
                    .on('drag', (event, d) => {
                        d.fx = event.x;
                        d.fy = event.y;
                    })
                    .on('end', (event, d) => {
                        if (!event.active) simulation.alphaTarget(0);
                        d.fx = null;
                        d.fy = null;
                    })
                );

            // Node shapes
            node.append('path')
                .attr('d', d => d3.symbol().type(nodeSymbol(d.type)).size(nodeSize(d.type))())
                .attr('fill', d => d.color_override || moodColors[d.mood] || moodColors.plain)
                .attr('stroke', 'var(--theme-bg, #1a1a2e)')
                .attr('stroke-width', 1.5)
                .attr('opacity', 0.9);

            // Node labels
            node.append('text')
                .text(d => d.title.length > 18 ? d.title.slice(0, 16) + '…' : d.title)
                .attr('dy', 22)
                .attr('text-anchor', 'middle')
                .attr('fill', 'var(--theme-text-muted, #888)')
                .attr('font-size', '9px')
                .attr('pointer-events', 'none');

            // Node glow on hover
            node.on('mouseenter', function (event, d) {
                d3.select(this).select('path')
                    .transition().duration(150)
                    .attr('filter', 'drop-shadow(0 0 6px ' + (d.color_override || moodColors[d.mood] || '#fff') + ')')
                    .attr('opacity', 1);
            }).on('mouseleave', function () {
                d3.select(this).select('path')
                    .transition().duration(150)
                    .attr('filter', null)
                    .attr('opacity', 0.9);
            });

            // Alpine ref for click handling
            const self = this;

            node.on('click', function (event, d) {
                event.stopPropagation();

                // Highlight connected
                const connectedIds = new Set();
                edges.forEach(e => {
                    const srcId = typeof e.source === 'object' ? e.source.id : e.source;
                    const tgtId = typeof e.target === 'object' ? e.target.id : e.target;
                    if (srcId === d.id) connectedIds.add(tgtId);
                    if (tgtId === d.id) connectedIds.add(srcId);
                });

                // Dim non-connected
                node.select('path').transition().duration(200)
                    .attr('opacity', n => n.id === d.id || connectedIds.has(n.id) ? 1 : 0.15);
                node.select('text').transition().duration(200)
                    .attr('opacity', n => n.id === d.id || connectedIds.has(n.id) ? 1 : 0.15);
                link.transition().duration(200)
                    .attr('stroke-opacity', e => {
                        const srcId = typeof e.source === 'object' ? e.source.id : e.source;
                        const tgtId = typeof e.target === 'object' ? e.target.id : e.target;
                        return (srcId === d.id || tgtId === d.id) ? edgeStroke(e.type).opacity : 0.03;
                    });

                self.selectedNode = d;
                self.connectedCount = connectedIds.size;
            });

            // Click background to deselect
            svg.on('click', () => {
                self.selectedNode = null;
                node.select('path').transition().duration(200).attr('opacity', 0.9);
                node.select('text').transition().duration(200).attr('opacity', 1);
                link.each(function (d) {
                    d3.select(this).transition().duration(200)
                        .attr('stroke-opacity', edgeStroke(d.type).opacity);
                });
            });

            // Tick
            simulation.on('tick', () => {
                link
                    .attr('x1', d => d.source.x)
                    .attr('y1', d => d.source.y)
                    .attr('x2', d => d.target.x)
                    .attr('y2', d => d.target.y);

                node.attr('transform', d => `translate(${d.x},${d.y})`);
            });
        },

        destroy() {
            if (this._starsCtrl) this._starsCtrl.destroy();
            if (this._simulation) this._simulation.stop();
        },
    };
};
