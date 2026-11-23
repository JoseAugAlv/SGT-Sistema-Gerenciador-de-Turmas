// public/js/landing-efeitos.js
// Efeitos de fundo e de cursor da landing (os ligados à rolagem estão em landing-rolagem.js):
//   1. grade de pontos em canvas atrás do topo: uma onda passa pela grade e, perto do cursor,
//      os pontos fogem, crescem e ficam azuis; no toque, um foco passeia sozinho (pausa fora da tela;
//      parada no movimento reduzido)
//   2. notificações em volta da janela do app acompanham o cursor em profundidade (--mx/--my)
//   3. luz azul que segue o cursor no fundo e na borda dos painéis (.ef-luz)
(function () {
    'use strict';

    const reduzido = matchMedia('(prefers-reduced-motion: reduce)').matches || document.documentElement.dataset.motion === 'off';
    const toque = matchMedia('(pointer: coarse)').matches;
    const clamp = (v, a, b) => Math.min(b, Math.max(a, v));

    // cor de um token CSS (ex.: --accent) como [r, g, b]
    function cor(nome) {
        const el = document.createElement('i');
        el.style.color = `var(${nome})`; document.body.appendChild(el);
        const m = getComputedStyle(el).color.match(/\d+(\.\d+)?/g) || [0, 0, 0];
        el.remove();
        return m.slice(0, 3).map(Number);
    }

    // ---------- 1. grade de pontos do topo
    const hero = document.querySelector('.hero');
    if (hero) {
        const cv = document.createElement('canvas');
        cv.className = 'ef-pontos'; cv.setAttribute('aria-hidden', 'true');
        hero.prepend(cv);
        const ctx = cv.getContext('2d');
        const PASSO = 30;
        let W = 0, H = 0, dpr = 1, tinta = [15, 18, 34], azul = [51, 85, 224], visivel = true, raf = 0, t0 = performance.now();
        const m = { x: -9999, y: -9999, ax: -9999, ay: -9999, forca: 0, alvoForca: 0 };

        function medir() {
            dpr = Math.min(2, devicePixelRatio || 1);
            const r = hero.getBoundingClientRect();
            W = r.width; H = r.height;
            cv.width = Math.round(W * dpr); cv.height = Math.round(H * dpr);
            ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
            tinta = cor('--ink'); azul = cor('--accent');
        }
        function desenhar(agora) {
            raf = 0;
            const t = (agora - t0) / 1000;
            // toque (sem cursor): um foco de luz passeia sozinho pela grade, em curva de Lissajous
            if (toque && !reduzido) {
                m.x = W * (0.5 + 0.38 * Math.sin(t * 0.55)); m.y = H * (0.55 + 0.3 * Math.sin(t * 0.9 + 1.2));
                m.alvoForca = 1; if (m.ax < -999) { m.ax = m.x; m.ay = m.y; }
            }
            m.ax += (m.x - m.ax) * 0.12; m.ay += (m.y - m.ay) * 0.12;
            m.forca += (m.alvoForca - m.forca) * 0.08;
            ctx.clearRect(0, 0, W, H);
            const R = 170;
            for (let y = PASSO / 2; y < H; y += PASSO) {
                for (let x = PASSO / 2; x < W; x += PASSO) {
                    // onda diagonal que atravessa a grade
                    const onda = reduzido ? 0 : Math.sin(x * 0.012 + y * 0.008 - t * 1.6) * 0.5 + 0.5;
                    let px = x, py = y, raio = 1 + onda * 0.7, alfa = 0.07 + onda * 0.08, k = 0;
                    const dx = x - m.ax, dy = y - m.ay, d = Math.hypot(dx, dy);
                    if (d < R && m.forca > 0.01) {
                        k = (1 - d / R) ** 2 * m.forca;
                        px += (dx / (d || 1)) * k * 26; py += (dy / (d || 1)) * k * 26;
                        raio += k * 2.4; alfa += k * 0.7;
                    }
                    const c = k > 0.02 ? azul : tinta;
                    ctx.fillStyle = `rgba(${c[0]},${c[1]},${c[2]},${Math.min(1, alfa).toFixed(3)})`;
                    ctx.beginPath(); ctx.arc(px, py, raio, 0, 6.2832); ctx.fill();
                }
            }
            if (visivel && !reduzido) raf = requestAnimationFrame(desenhar);
        }
        const iniciar = () => { if (!raf) raf = requestAnimationFrame(desenhar); };
        medir(); iniciar();
        addEventListener('resize', () => { medir(); iniciar(); });
        new IntersectionObserver((es) => { visivel = es[0].isIntersecting; if (visivel) iniciar(); }).observe(hero);
        // o tema muda as cores dos pontos
        new MutationObserver(() => { tinta = cor('--ink'); azul = cor('--accent'); iniciar(); })
            .observe(document.documentElement, { attributes: true, attributeFilter: ['data-theme'] });
        if (!toque) {
            hero.addEventListener('pointermove', (ev) => {
                const r = hero.getBoundingClientRect();
                m.x = ev.clientX - r.left; m.y = ev.clientY - r.top; m.alvoForca = 1;
                if (m.ax < -999) { m.ax = m.x; m.ay = m.y; }
                iniciar();
            });
            hero.addEventListener('pointerleave', () => { m.alvoForca = 0; iniciar(); });
        }
    }

    // ---------- 2. notificações acompanham o cursor (a saída na rolagem é de landing-rolagem.js)
    const notas = document.querySelector('.ef-notas');
    if (notas && hero && !toque && !reduzido) {
        const s = { x: 0, y: 0, ax: 0, ay: 0, raf: 0 };
        const passo = () => {
            s.raf = 0;
            s.ax += (s.x - s.ax) * 0.07; s.ay += (s.y - s.ay) * 0.07;
            notas.style.setProperty('--mx', s.ax.toFixed(3)); notas.style.setProperty('--my', s.ay.toFixed(3));
            if (Math.abs(s.x - s.ax) > 0.002 || Math.abs(s.y - s.ay) > 0.002) s.raf = requestAnimationFrame(passo);
        };
        hero.addEventListener('pointermove', (ev) => {
            s.x = clamp(ev.clientX / innerWidth * 2 - 1, -1, 1); s.y = clamp(ev.clientY / innerHeight * 2 - 1, -1, 1);
            if (!s.raf) s.raf = requestAnimationFrame(passo);
        });
        hero.addEventListener('pointerleave', () => { s.x = 0; s.y = 0; if (!s.raf) s.raf = requestAnimationFrame(passo); });
    }

    // ---------- 3. luz que segue o cursor nos painéis
    if (!toque) {
        document.querySelectorAll('#recursos .panel, #gremios .frame, #planos .frame').forEach((el) => {
            el.classList.add('ef-luz');
            el.insertAdjacentHTML('afterbegin', '<span class="ef-luz__f" aria-hidden="true"></span><span class="ef-luz__b" aria-hidden="true"></span>');
            el.addEventListener('pointermove', (ev) => {
                const r = el.getBoundingClientRect();
                el.style.setProperty('--lx', (ev.clientX - r.left).toFixed(0) + 'px');
                el.style.setProperty('--ly', (ev.clientY - r.top).toFixed(0) + 'px');
                el.style.setProperty('--lo', '1');
            });
            el.addEventListener('pointerleave', () => el.style.setProperty('--lo', '0'));
        });
    }
})();
