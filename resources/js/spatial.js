/* MARIMOI 2.0 - Spatial Intelligence homepage.
 * All visual state is driven by data-* attributes so the styling stays in Tailwind (see the Blade views). */

const DATA = window.MARIMOI_HOME || { points: [], layers: [] };
const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
let isMobile = window.matchMedia('(max-width: 900px)').matches;
let vh = window.innerHeight;
const scrollHandlers = [];

const clamp = (v, a, b) => Math.min(b, Math.max(a, v));
const ease = (t) => 1 - Math.pow(1 - t, 3);
const $ = (sel, root = document) => root.querySelector(sel);
const $$ = (sel, root = document) => Array.from(root.querySelectorAll(sel));
const fmt = (n) => Math.round(n).toLocaleString('id-ID');

/* ---------- Navbar + mobile panel ---------- */
const nav = $('#nav');
const burger = $('#burger');
const panel = $('#mobileMenu');

function setMenu(open) {
    if (!panel) { return; }
    panel.dataset.open = String(open);
    panel.setAttribute('aria-hidden', String(!open));
    burger?.setAttribute('aria-expanded', String(open));
    document.body.style.overflow = open ? 'hidden' : '';
}
burger?.addEventListener('click', () => setMenu(true));
$('#burgerClose')?.addEventListener('click', () => setMenu(false));
$$('a', panel || document.createElement('div')).forEach((a) => a.addEventListener('click', () => setMenu(false)));
document.addEventListener('keydown', (e) => { if (e.key === 'Escape') { setMenu(false); } });

/* ---------- Reveal on scroll ---------- */
const revealEls = $$('[data-reveal]');
if ('IntersectionObserver' in window && !reduceMotion) {
    const revealObs = new IntersectionObserver((entries) => {
        entries.forEach((e) => {
            if (e.isIntersecting) { e.target.dataset.in = 'true'; revealObs.unobserve(e.target); }
        });
    }, { threshold: 0.15, rootMargin: '0px 0px -6% 0px' });
    revealEls.forEach((el) => revealObs.observe(el));
} else {
    revealEls.forEach((el) => { el.dataset.in = 'true'; });
}

/* ---------- Counters ---------- */
$$('[data-count]').forEach((el) => {
    const target = parseInt(el.dataset.count, 10) || 0;
    const pad = parseInt(el.dataset.pad, 10) || 0;
    const show = (n) => { const s = fmt(n); el.textContent = pad ? s.padStart(pad, '0') : s; };
    if (reduceMotion || !('IntersectionObserver' in window)) { show(target); return; }
    show(0);
    const obs = new IntersectionObserver((entries) => {
        if (!entries[0].isIntersecting) { return; }
        obs.disconnect();
        const t0 = performance.now();
        const tick = (now) => {
            const p = clamp((now - t0) / 1600, 0, 1);
            show(target * ease(p));
            if (p < 1) { requestAnimationFrame(tick); }
        };
        requestAnimationFrame(tick);
    }, { threshold: 0.5 });
    obs.observe(el);
});

/* ---------- Parallax ----------
 * Browser modern: CSS scroll-driven animations (jalan di compositor, tanpa JS per frame).
 * Fallback (mis. Firefox): translate3d lewat rAF, pembacaan layout dibatch dulu baru penulisan. */
const parallaxEls = $$('[data-parallax]');
const heroLayers = $$('[data-hero-speed]');
const hero = $('#beranda');
const cssParallax = !reduceMotion && typeof CSS !== 'undefined' && CSS.supports('animation-timeline: view()');

if (cssParallax) {
    parallaxEls.forEach((el) => {
        el.style.setProperty('--s', el.dataset.parallax || '0.1');
        el.classList.add('parallax-view');
    });
    heroLayers.forEach((el) => {
        el.style.setProperty('--s', el.dataset.heroSpeed || '0');
        if (el.hasAttribute('data-hero-fade')) { el.style.setProperty('--end-o', '0'); }
        el.classList.add('parallax-hero');
    });
}

function updateParallax() {
    if (reduceMotion || cssParallax) { return; }
    const factor = isMobile ? 0.45 : 1;
    const y = window.scrollY;
    const reads = [];
    parallaxEls.forEach((el) => {
        const box = (el.parentElement || el).getBoundingClientRect();
        if (box.bottom < -300 || box.top > vh + 300) { return; }
        reads.push([el, (box.top + box.height / 2 - vh / 2) * (parseFloat(el.dataset.parallax) || 0.1) * factor * -1]);
    });
    if (hero && y < hero.offsetHeight + 200) {
        heroLayers.forEach((el) => {
            const speed = parseFloat(el.dataset.heroSpeed) || 0;
            el.style.transform = `translate3d(0, ${(y * speed * factor).toFixed(1)}px, 0)`;
            if (el.hasAttribute('data-hero-fade')) { el.style.opacity = String(clamp(1 - y / (vh * 0.85), 0, 1)); }
        });
    }
    reads.forEach(([el, offset]) => { el.style.transform = `translate3d(0, ${offset.toFixed(1)}px, 0)`; });
}

/* ---------- Procedural topographic contours ---------- */
function makeContours(svg, seed, centers, rings) {
    const W = 1600, H = 900;
    let rnd = seed, paths = '';
    const r = () => { rnd = (rnd * 16807) % 2147483647; return rnd / 2147483647; };
    for (let c = 0; c < centers; c++) {
        const cx = r() * W, cy = r() * H, base = 70 + r() * 90;
        const ph = [r() * 6.28, r() * 6.28, r() * 6.28];
        const amp = [0.16 + r() * 0.1, 0.09 + r() * 0.06, 0.05 + r() * 0.04];
        for (let k = 1; k <= rings; k++) {
            const rad = base * k * 0.62;
            let d = '';
            for (let a = 0; a <= 72; a++) {
                const th = (a / 72) * Math.PI * 2;
                const n = 1 + amp[0] * Math.sin(2 * th + ph[0] + k * 0.18) + amp[1] * Math.sin(3 * th + ph[1]) + amp[2] * Math.sin(5 * th + ph[2] - k * 0.3);
                d += `${a ? 'L' : 'M'}${(cx + Math.cos(th) * rad * n * 1.35).toFixed(1)} ${(cy + Math.sin(th) * rad * n * 0.85).toFixed(1)}`;
            }
            paths += `<path d="${d}Z"/>`;
        }
    }
    svg.setAttribute('viewBox', `0 0 ${W} ${H}`);
    svg.setAttribute('preserveAspectRatio', 'xMidYMid slice');
    svg.innerHTML = paths;
}
$$('svg.contours').forEach((svg, i) => makeContours(svg, 7919 + i * 131, isMobile ? 3 : 5, isMobile ? 6 : 9));

/* ---------- Constellation: real MARIMOI points drawn as a network ---------- */
const BOUNDS = { x0: 124.3, x1: 129.6, y0: -2.5, y1: 2.7 };
let nodes = [];
const edges = [];

(function build() {
    let pts = DATA.points || [];
    if (isMobile && pts.length > 170) { pts = pts.filter((_, i) => i % 2 === 0); }
    nodes = pts.map((p, i) => ({
        nx: (p.x - BOUNDS.x0) / (BOUNDS.x1 - BOUNDS.x0),
        ny: 1 - (p.y - BOUNDS.y0) / (BOUNDS.y1 - BOUNDS.y0),
        d: 0.3 + ((i * 37) % 70) / 100,
        ph: (i * 1.7) % 6.28,
        big: i % 9 === 0,
    })).sort((a, b) => a.nx - b.nx);
    const seen = new Set();
    for (let i = 0; i < nodes.length; i++) {
        const near = [];
        for (let j = 0; j < nodes.length; j++) {
            if (i === j) { continue; }
            const dx = nodes[i].nx - nodes[j].nx, dy = nodes[i].ny - nodes[j].ny, dist = dx * dx + dy * dy;
            if (dist > 0 && dist < 0.0075) { near.push({ j, dist }); }
        }
        near.sort((a, b) => a.dist - b.dist).slice(0, 2).forEach((n) => {
            const key = i < n.j ? `${i}-${n.j}` : `${n.j}-${i}`;
            if (!seen.has(key)) { seen.add(key); edges.push({ a: i, b: n.j }); }
        });
    }
})();

class Scene {
    constructor(canvas, opts = {}) {
        this.c = canvas;
        this.ctx = canvas.getContext('2d');
        this.opts = opts;
        this.reveal = opts.reveal === undefined ? 1 : opts.reveal;
        this.revealTarget = this.reveal;
        this.mouse = { x: 0, y: 0, tx: 0, ty: 0 };
        this.signals = [];
        this.particles = [];
        this.running = false;
        this.visible = false;
        this.frame = this.frame.bind(this);
        this.resize = this.resize.bind(this);
        this.resize();
        window.addEventListener('resize', this.resize);

        if (opts.only === 'particles') {
            for (let i = 0; i < (isMobile ? 26 : 60); i++) {
                this.particles.push({ x: Math.random(), y: Math.random(), s: 0.2 + Math.random() * 0.8, v: 0.00004 + Math.random() * 0.00009 });
            }
        }
        if ('IntersectionObserver' in window) {
            new IntersectionObserver((en) => { this.visible = en[0].isIntersecting; this.toggle(); }).observe(canvas);
        } else { this.visible = true; }
        document.addEventListener('visibilitychange', () => this.toggle());
        if (opts.mouse && !reduceMotion && !isMobile) {
            window.addEventListener('mousemove', (e) => {
                this.mouse.tx = e.clientX / window.innerWidth - 0.5;
                this.mouse.ty = e.clientY / window.innerHeight - 0.5;
            }, { passive: true });
        }
        this.toggle();
    }

    resize() {
        const dpr = Math.min(window.devicePixelRatio || 1, 1.5);
        this.w = this.c.clientWidth;
        this.h = this.c.clientHeight;
        this.c.width = this.w * dpr;
        this.c.height = this.h * dpr;
        this.ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
        vh = window.innerHeight;
        isMobile = window.matchMedia('(max-width: 900px)').matches;
        this.draw(performance.now());
    }

    toggle() {
        const should = this.visible && !document.hidden && !reduceMotion;
        if (should && !this.running) { this.running = true; requestAnimationFrame(this.frame); }
        if (!should) { this.running = false; }
    }

    frame(t) {
        if (!this.running) { return; }
        this.tick = (this.tick || 0) + 1;
        if (this.opts.only !== 'particles' || this.tick % 2 === 0) { this.draw(t); }
        requestAnimationFrame(this.frame);
    }

    draw(t) {
        const { ctx, w, h, opts: o, mouse: m } = this;
        ctx.clearRect(0, 0, w, h);

        if (o.only === 'particles') {
            this.particles.forEach((p) => {
                p.y -= p.v * 16;
                if (p.y < 0) { p.y = 1; p.x = Math.random(); }
                ctx.fillStyle = `rgba(160,230,255,${0.12 + p.s * 0.18})`;
                ctx.fillRect(p.x * w, p.y * h, p.s * 1.6, p.s * 1.6);
            });
            return;
        }

        m.x += (m.tx - m.x) * 0.05;
        m.y += (m.ty - m.y) * 0.05;
        const size = Math.min(w * (o.scale || 0.9), h * (o.scale || 0.9));
        const cx = o.anchor === 'right' && w > 900 ? w * 0.68 : w * 0.5;
        const cy = h * 0.52;
        if (o.smooth) { this.reveal += (this.revealTarget - this.reveal) * 0.12; }
        const count = nodes.length;
        const shown = this.reveal * count;
        const px = new Array(count), py = new Array(count);

        for (let i = 0; i < count; i++) {
            const n = nodes[i];
            px[i] = cx - size / 2 + n.nx * size + m.x * 34 * n.d;
            py[i] = cy - size / 2 + n.ny * size + m.y * 24 * n.d;
        }

        ctx.lineWidth = 1;
        edges.forEach((e) => {
            const top = Math.max(e.a, e.b);
            if (top > shown) { return; }
            ctx.strokeStyle = `rgba(32,217,255,${0.16 * clamp((shown - top) / 12, 0, 1)})`;
            ctx.beginPath(); ctx.moveTo(px[e.a], py[e.a]); ctx.lineTo(px[e.b], py[e.b]); ctx.stroke();
        });

        if (!reduceMotion && edges.length && this.signals.length < (isMobile ? 3 : 7) && Math.random() < 0.05) {
            const pick = edges[(Math.random() * edges.length) | 0];
            if (Math.max(pick.a, pick.b) < shown) { this.signals.push({ e: pick, p: 0, s: 0.008 + Math.random() * 0.01 }); }
        }
        for (let i = this.signals.length - 1; i >= 0; i--) {
            const sg = this.signals[i];
            sg.p += sg.s;
            if (sg.p >= 1) { this.signals.splice(i, 1); continue; }
            const sx = px[sg.e.a] + (px[sg.e.b] - px[sg.e.a]) * sg.p, sy = py[sg.e.a] + (py[sg.e.b] - py[sg.e.a]) * sg.p;
            ctx.fillStyle = 'rgba(77,225,193,.95)'; ctx.beginPath(); ctx.arc(sx, sy, 2, 0, 6.283); ctx.fill();
            ctx.fillStyle = 'rgba(77,225,193,.18)'; ctx.beginPath(); ctx.arc(sx, sy, 6, 0, 6.283); ctx.fill();
        }

        const time = t / 1000;
        for (let i = 0; i < count && i <= shown; i++) {
            const n = nodes[i];
            const a = clamp((shown - i) / 10, 0, 1);
            const pulse = reduceMotion ? 0.7 : 0.6 + 0.4 * Math.sin(time * 1.6 + n.ph);
            ctx.fillStyle = `rgba(32,217,255,${0.18 * a * pulse})`;
            ctx.beginPath(); ctx.arc(px[i], py[i], n.big ? 7 : 4.5, 0, 6.283); ctx.fill();
            ctx.fillStyle = `rgba(190,245,255,${0.95 * a})`;
            ctx.beginPath(); ctx.arc(px[i], py[i], n.big ? 2.4 : 1.6, 0, 6.283); ctx.fill();
            if (n.big && !reduceMotion) {
                const ring = (time * 0.5 + n.ph) % 1;
                ctx.strokeStyle = `rgba(32,217,255,${0.5 * (1 - ring) * a})`;
                ctx.beginPath(); ctx.arc(px[i], py[i], 3 + ring * 16, 0, 6.283); ctx.stroke();
            }
        }
    }
}

const heroParticles = $('#heroParticles');
const heroNodes = $('#heroNodes');
if (heroParticles) { new Scene(heroParticles, { only: 'particles' }); }
if (heroNodes) { new Scene(heroNodes, { anchor: 'right', mouse: true, scale: 0.95 }); }

/* ---------- 01 pinned scroll scene ---------- */
const pin = $('#perspektif');
const pinCanvas = $('#pinCanvas');
const captions = $$('[data-caption]');
const meter = $('#pinMeter');
if (pin && pinCanvas) {
    const pinScene = new Scene(pinCanvas, { anchor: 'right', scale: 1, reveal: reduceMotion ? 1 : 0, mouse: true, smooth: !reduceMotion });
    if (captions[0]) { captions[0].dataset.on = 'true'; }
    if (reduceMotion && meter) { meter.textContent = fmt(nodes.length); }
    scrollHandlers.push(() => {
        if (reduceMotion) { return; }
        const box = pin.getBoundingClientRect();
        const p = clamp(-box.top / (box.height - vh), 0, 1);
        pinScene.revealTarget = ease(clamp(p * 1.15, 0, 1));
        const idx = p < 0.34 ? 0 : (p < 0.7 ? 1 : 2);
        captions.forEach((c, i) => { c.dataset.on = String(i === idx); });
        if (meter) { meter.textContent = fmt(pinScene.revealTarget * nodes.length); }
    });
}

/* ---------- 03 alur data: jalur kanvas + langkah aktif berdasarkan scroll ---------- */
const flowSection = $('#alur');
const flow = $('#flow');
const flowCanvas = $('#flowCanvas');
if (flowSection && flow && flowCanvas) {
    const stepEls = $$('[data-step]', flow);
    const panels = $$('[data-panel]', flowSection);
    const fctx = flowCanvas.getContext('2d');
    const state = { t: reduceMotion ? 5 : 0, target: reduceMotion ? 5 : 0, active: -1 };
    let centers = [];
    let fw = 0, fh = 0;
    let running = false;
    let visible = false;
    const flowDots = [];
    for (let i = 0; i < 16; i++) { flowDots.push({ u: Math.random(), s: 0.0006 + Math.random() * 0.0012, o: (Math.random() - 0.5) * 2 }); }

    function layoutFlow() {
        const dpr = Math.min(window.devicePixelRatio || 1, 2);
        const box = flow.getBoundingClientRect();
        fw = box.width; fh = box.height;
        flowCanvas.width = fw * dpr; flowCanvas.height = fh * dpr;
        fctx.setTransform(dpr, 0, 0, dpr, 0, 0);
        centers = stepEls.map((el) => {
            const r = $('[data-dot]', el).getBoundingClientRect();
            return { x: r.left - box.left + r.width / 2, y: r.top - box.top + r.height / 2 };
        });
        drawFlow(performance.now());
    }

    function drawFlow(time) {
        if (!centers.length) { return; }
        state.t += (state.target - state.t) * (reduceMotion ? 1 : 0.08);
        fctx.clearRect(0, 0, fw, fh);
        const x0 = centers[0].x, x1 = centers[centers.length - 1].x, y = centers[0].y;
        const headX = x0 + ((x1 - x0) * state.t) / 5;
        const wave = (x, amp) => (reduceMotion ? 0 : Math.sin(x * 0.035 + time / 500) * amp);

        // jalur dasar
        fctx.lineWidth = 2;
        fctx.strokeStyle = 'rgba(255,255,255,.12)';
        fctx.beginPath(); fctx.moveTo(x0, y); fctx.lineTo(x1, y); fctx.stroke();

        // jalur terisi + cahaya
        if (headX > x0) {
            const grad = fctx.createLinearGradient(x0, 0, headX, 0);
            grad.addColorStop(0, 'rgba(10,132,255,.9)');
            grad.addColorStop(1, 'rgba(32,217,255,1)');
            fctx.lineWidth = 3;
            fctx.strokeStyle = grad;
            fctx.shadowColor = 'rgba(32,217,255,.8)';
            fctx.shadowBlur = 14;
            fctx.beginPath();
            for (let x = x0; x <= headX; x += 4) { const yy = y + wave(x, 2.2); if (x === x0) { fctx.moveTo(x, yy); } else { fctx.lineTo(x, yy); } }
            fctx.stroke();
            fctx.shadowBlur = 0;
        }

        // partikel data mengalir sepanjang jalur
        flowDots.forEach((d) => {
            if (!reduceMotion) { d.u += d.s; if (d.u > 1) { d.u = 0; } }
            const x = x0 + (x1 - x0) * d.u;
            const lit = x <= headX;
            const yy = y + wave(x, 2.2) + d.o * 6;
            fctx.fillStyle = lit ? 'rgba(190,245,255,.95)' : 'rgba(255,255,255,.22)';
            fctx.beginPath(); fctx.arc(x, yy, lit ? 2 : 1.5, 0, 6.283); fctx.fill();
        });

        // komet di ujung jalur
        if (state.t > 0.02 && state.t < 4.98) {
            const g = fctx.createRadialGradient(headX, y, 0, headX, y, 26);
            g.addColorStop(0, 'rgba(255,255,255,.95)');
            g.addColorStop(0.25, 'rgba(32,217,255,.6)');
            g.addColorStop(1, 'rgba(32,217,255,0)');
            fctx.fillStyle = g;
            fctx.beginPath(); fctx.arc(headX, y, 26, 0, 6.283); fctx.fill();
        }
    }

    function loopFlow(t) {
        if (!running) { return; }
        drawFlow(t);
        requestAnimationFrame(loopFlow);
    }
    function toggleFlow() {
        const should = visible && !document.hidden && !reduceMotion;
        if (should && !running) { running = true; requestAnimationFrame(loopFlow); }
        if (!should) { running = false; }
    }
    if ('IntersectionObserver' in window) {
        new IntersectionObserver((en) => { visible = en[0].isIntersecting; toggleFlow(); }).observe(flowSection);
    }
    document.addEventListener('visibilitychange', toggleFlow);

    function setActive(idx) {
        if (idx === state.active) { return; }
        state.active = idx;
        stepEls.forEach((el, i) => { el.dataset.on = String(i <= idx); el.dataset.active = String(i === idx); });
        panels.forEach((el, i) => { el.dataset.on = String(i === idx); el.setAttribute('aria-hidden', String(i !== idx)); });
    }

    function updateFlow() {
        if (reduceMotion) { setActive(5); stepEls.forEach((el) => { el.dataset.on = 'true'; }); panels.forEach((el) => { el.dataset.on = 'true'; el.setAttribute('aria-hidden', 'false'); }); return; }
        const box = flowSection.getBoundingClientRect();
        const p = clamp(-box.top / (box.height - vh), 0, 1);
        state.target = clamp(p * 1.12, 0, 1) * 5;
        setActive(clamp(Math.round(state.target), 0, 5));
    }
    scrollHandlers.push(updateFlow);

    stepEls.forEach((el, i) => $('[data-dot]', el).addEventListener('click', () => {
        const top = flowSection.getBoundingClientRect().top + window.scrollY;
        const p = i / 5.6 + 0.001;
        window.scrollTo({ top: top + p * (flowSection.offsetHeight - vh), behavior: 'smooth' });
    }));

    window.addEventListener('resize', layoutFlow);
    window.addEventListener('load', layoutFlow);
    layoutFlow();
    updateFlow();
}

/* ---------- Mockup tablet: miring 3D mengikuti kursor (hanya perangkat dengan pointer halus) ---------- */
const tabletStage = $('#tabletStage');
const tabletTilt = $('#tabletTilt');
if (tabletStage && tabletTilt && !reduceMotion && window.matchMedia('(pointer: fine)').matches) {
    const tilt = { rx: 0, ry: 0, trx: 0, try: 0 };
    let raf = 0;
    const step = () => {
        tilt.rx += (tilt.trx - tilt.rx) * 0.12;
        tilt.ry += (tilt.try - tilt.ry) * 0.12;
        tabletTilt.style.transform = `perspective(900px) rotateX(${tilt.rx.toFixed(2)}deg) rotateY(${tilt.ry.toFixed(2)}deg)`;
        if (Math.abs(tilt.trx - tilt.rx) > 0.02 || Math.abs(tilt.try - tilt.ry) > 0.02) { raf = requestAnimationFrame(step); } else { raf = 0; }
    };
    const kick = () => { if (!raf) { raf = requestAnimationFrame(step); } };
    tabletStage.addEventListener('pointermove', (e) => {
        const r = tabletStage.getBoundingClientRect();
        const x = (e.clientX - r.left) / r.width;
        const y = (e.clientY - r.top) / r.height;
        tilt.try = (x - 0.5) * 16;
        tilt.trx = (0.5 - y) * 12;
        tabletTilt.style.setProperty('--mx', `${(x * 100).toFixed(1)}%`);
        tabletTilt.style.setProperty('--my', `${(y * 100).toFixed(1)}%`);
        kick();
    });
    tabletStage.addEventListener('pointerleave', () => { tilt.trx = 0; tilt.try = 0; kick(); });
}

/* ---------- Video modal (testimonial) ---------- */
const videoModal = $('#videoModal');
if (videoModal) {
    const frame = $('#videoFrame');
    const caption = $('#videoCaption');
    let lastTrigger = null;
    const closeVideo = () => {
        videoModal.dataset.open = 'false';
        videoModal.setAttribute('aria-hidden', 'true');
        frame.src = '';
        document.body.style.overflow = '';
        lastTrigger?.focus();
    };
    $$('[data-video-id]').forEach((btn) => btn.addEventListener('click', () => {
        lastTrigger = btn;
        frame.src = `https://www.youtube-nocookie.com/embed/${btn.dataset.videoId}?autoplay=1&rel=0`;
        caption.textContent = btn.dataset.videoTitle || '';
        videoModal.dataset.open = 'true';
        videoModal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
        $('#videoClose').focus();
    }));
    $('#videoClose').addEventListener('click', closeVideo);
    videoModal.addEventListener('click', (e) => { if (e.target === videoModal) { closeVideo(); } });
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && videoModal.dataset.open === 'true') { closeVideo(); } });
}

/* ---------- CV viewer (Profil Reformer) ---------- */
const cvModal = $('#cvModal');
if (cvModal && Array.isArray(window.MARIMOI_CV) && window.MARIMOI_CV.length) {
    const docs = window.MARIMOI_CV;
    const img = $('#cvImage');
    let idx = 0;
    let opener = null;
    const show = (i) => {
        idx = (i + docs.length) % docs.length;
        img.src = docs[idx].src;
        img.alt = `CV - ${docs[idx].label}`;
        $('#cvCaption').textContent = docs[idx].label;
        $('#cvCounter').textContent = `${idx + 1} / ${docs.length}`;
    };
    const closeCv = () => {
        cvModal.dataset.open = 'false';
        cvModal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
        opener?.focus();
    };
    $$('[data-cv-open]').forEach((btn) => btn.addEventListener('click', () => {
        opener = btn;
        show(parseInt(btn.dataset.cvOpen, 10) || 0);
        cvModal.dataset.open = 'true';
        cvModal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
        $('#cvClose').focus();
    }));
    $('#cvClose').addEventListener('click', closeCv);
    $('#cvPrev').addEventListener('click', () => show(idx - 1));
    $('#cvNext').addEventListener('click', () => show(idx + 1));
    cvModal.addEventListener('click', (e) => { if (e.target === cvModal) { closeCv(); } });
    document.addEventListener('keydown', (e) => {
        if (cvModal.dataset.open !== 'true') { return; }
        if (e.key === 'Escape') { closeCv(); }
        if (e.key === 'ArrowLeft') { show(idx - 1); }
        if (e.key === 'ArrowRight') { show(idx + 1); }
    });
}

/* ---------- Floating actions ---------- */
const floatActions = $('#floatActions');
$('#backToTop')?.addEventListener('click', () => window.scrollTo({ top: 0, behavior: reduceMotion ? 'auto' : 'smooth' }));

/* ---------- Scroll loop ---------- */
let ticking = false;
function onScroll() {
    if (ticking) { return; }
    ticking = true;
    requestAnimationFrame(() => {
        if (nav && nav.dataset.solid !== 'true') { nav.dataset.scrolled = String(window.scrollY > 40); }
        if (floatActions) { floatActions.dataset.show = String(window.scrollY > 320); }
        updateParallax();
        scrollHandlers.forEach((fn) => fn());
        ticking = false;
    });
}
window.addEventListener('scroll', onScroll, { passive: true });
window.addEventListener('resize', () => { vh = window.innerHeight; onScroll(); });
onScroll();

/* ---------- 04 integrated map (Leaflet loaded lazily) ---------- */
const mapEl = $('#homeMap');

function loadLeaflet(done) {
    if (window.L) { done(); return; }
    const css = document.createElement('link');
    css.rel = 'stylesheet';
    css.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
    document.head.appendChild(css);
    const js = document.createElement('script');
    js.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
    js.async = true;
    js.onload = done;
    js.onerror = () => { const l = $('#mapLoading'); if (l) { l.textContent = 'Peta tidak dapat dimuat. Periksa koneksi Anda.'; } };
    document.head.appendChild(js);
}

function fromTemplate(id) { return $(`#${id}`).content.firstElementChild.cloneNode(true); }

function initMap() {
    const L = window.L;
    const layers = {};
    const active = {};
    const markers = [];
    let query = '';
    (DATA.layers || []).forEach((l) => { layers[l.id] = l; active[l.id] = true; });

    const map = L.map(mapEl, { zoomControl: false, attributionControl: false, preferCanvas: true, minZoom: 5, maxZoom: 16, scrollWheelZoom: false });
    L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/Canvas/World_Dark_Gray_Base/MapServer/tile/{z}/{y}/{x}', {
        attribution: 'Tiles &copy; Esri &mdash; Esri, HERE, Garmin, OpenStreetMap contributors', maxNativeZoom: 16, maxZoom: 16,
    }).addTo(map);
    const group = L.layerGroup().addTo(map);
    let pulse = null;

    const pts = DATA.points || [];
    const bounds = pts.length ? L.latLngBounds(pts.map((p) => [p.y, p.x])) : L.latLngBounds([[-2.5, 124.3], [2.7, 129.6]]);
    map.fitBounds(bounds, isMobile ? { padding: [30, 30] } : { paddingTopLeft: [330, 40], paddingBottomRight: [80, 40] });

    const info = $('#mapInfo');
    function select(p, layer) {
        $('#infoDot').style.backgroundColor = layer.warna;
        $('#infoLayer').textContent = layer.nama;
        $('#infoTitle').textContent = p.n || layer.nama;
        $('#infoYear').textContent = p.t || '-';
        $('#infoCoord').textContent = `${p.y.toFixed(4)}, ${p.x.toFixed(4)}`;
        info.dataset.open = 'true';
        if (pulse) { map.removeLayer(pulse); }
        pulse = L.marker([p.y, p.x], {
            interactive: false,
            icon: L.divIcon({ className: '', html: fromTemplate('tplPulse').outerHTML, iconSize: [22, 22], iconAnchor: [11, 11] }),
        }).addTo(map);
        map.flyTo([p.y, p.x], Math.max(map.getZoom(), 10), { duration: reduceMotion ? 0 : 1.1 });
    }
    $('#infoClose').addEventListener('click', () => {
        info.dataset.open = 'false';
        if (pulse) { map.removeLayer(pulse); pulse = null; }
    });

    pts.forEach((p) => {
        const layer = layers[p.k];
        if (!layer) { return; }
        const color = layer.warna || '#20d9ff';
        const mk = L.circleMarker([p.y, p.x], { radius: 6, color, weight: 2, opacity: 0.95, fillColor: color, fillOpacity: 0.55 });
        mk.on('mouseover', () => mk.setStyle({ radius: 9, fillOpacity: 0.9 }));
        mk.on('mouseout', () => mk.setStyle({ radius: 6, fillOpacity: 0.55 }));
        mk.on('click', () => select(p, layer));
        markers.push({ mk, p, hay: `${p.n || ''} ${layer.nama}`.toLowerCase() });
    });

    function apply() {
        let shown = 0;
        markers.forEach((m) => {
            const on = active[m.p.k] && (!query || m.hay.includes(query));
            if (on) { shown++; if (!group.hasLayer(m.mk)) { group.addLayer(m.mk); } }
            else if (group.hasLayer(m.mk)) { group.removeLayer(m.mk); }
        });
        $('#mapCount').textContent = fmt(shown);
    }

    const list = $('#layerList');
    list.replaceChildren(...(DATA.layers || []).map((l) => {
        const el = fromTemplate('tplLayerItem');
        el.dataset.id = l.id;
        const dot = $('.js-dot', el);
        dot.style.backgroundColor = l.warna;
        dot.style.boxShadow = `0 0 10px ${l.warna}`;
        $('.js-name', el).textContent = l.nama;
        $('.js-count', el).textContent = l.total;
        return el;
    }));
    list.addEventListener('click', (e) => {
        const b = e.target.closest('[data-id]');
        if (!b) { return; }
        const id = b.dataset.id;
        active[id] = !active[id];
        b.dataset.off = String(!active[id]);
        apply();
    });
    $('#layerAll').addEventListener('click', () => {
        const anyOff = Object.keys(active).some((k) => !active[k]);
        Object.keys(active).forEach((k) => { active[k] = anyOff; });
        $$('[data-id]', list).forEach((b) => { b.dataset.off = String(!anyOff); });
        apply();
    });
    $('#mapSearch').addEventListener('input', (e) => { query = e.target.value.trim().toLowerCase(); apply(); });
    $('#zoomIn').addEventListener('click', () => map.zoomIn());
    $('#zoomOut').addEventListener('click', () => map.zoomOut());
    const tools = $('#mapTools');
    $('#toolsToggle').addEventListener('click', () => { tools.dataset.collapsed = String(tools.dataset.collapsed !== 'true'); });
    if (isMobile) { tools.dataset.collapsed = 'true'; }
    map.on('click focus', () => map.scrollWheelZoom.enable());
    $('#mapLoading')?.remove();
    apply();
    setTimeout(() => map.invalidateSize(), 200);
}

if (mapEl) {
    if ('IntersectionObserver' in window) {
        const mo = new IntersectionObserver((en) => {
            if (en[0].isIntersecting) { mo.disconnect(); loadLeaflet(initMap); }
        }, { rootMargin: '300px 0px' });
        mo.observe(mapEl);
    } else { loadLeaflet(initMap); }
}
