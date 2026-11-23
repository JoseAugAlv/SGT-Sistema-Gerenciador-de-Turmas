// public/js/landing-rolagem.js
// Animações ligadas à rolagem ao longo da landing (fora a cena "Como funciona", que tem o seu
// próprio arquivo). Cada efeito é uma função de t (0 a 1) calculado pela posição do elemento na
// tela: rola para baixo, a peça monta; rola para cima, ela volta. Só transform, opacity, filter
// e clip-path. Movimento reduzido ou <html data-motion="off">: tudo no estado final, sem animar.
//
//   barra de progresso de leitura  ·  topo: janela do app inclina ao sair e gira com o cursor
//   títulos: palavras sobem uma a uma  ·  recursos: cartões em 3D, linhas em sequência, relógio conta
//   frase grande: o texto ao lado acende palavra por palavra  ·  grêmio: diretorias se encaixam
//   planos: linhas deslizam, tela do Pix gira, total conta, QR se desenha  ·  rodapé: letras sobem

(function () {
    'use strict';

    const html = document.documentElement;
    const reduzido = matchMedia('(prefers-reduced-motion: reduce)').matches || html.dataset.motion === 'off';
    const clamp = (v, a = 0, b = 1) => Math.min(b, Math.max(a, v));
    const lerp = (a, b, t) => a + (b - a) * t;
    const saida = (t) => 1 - Math.pow(1 - t, 3);                  // ease-out cúbico
    // trecho [a, b] de t, reescalado para 0..1 e suavizado
    const faixa = (t, a, b) => saida(clamp((t - a) / (b - a)));
    const $ = (s, r = document) => r.querySelector(s);
    const $$ = (s, r = document) => [...r.querySelectorAll(s)];

    let vh = innerHeight;
    const efeitos = [];

    // t de entrada: 0 com o topo do elemento na base da tela, 1 com ele a `fim` da altura da tela
    function tEntrada(el, fim = 0.42) {
        const r = el.getBoundingClientRect();
        return clamp((vh - r.top) / (vh * (1 - fim)));
    }
    // t de saída: 0 com o elemento no topo, 1 quando ele inteiro saiu por cima
    function tSaida(el) {
        const r = el.getBoundingClientRect();
        return clamp(-r.top / Math.max(1, r.height));
    }

    function registrar(el, calc, desenha) {
        if (!el) return;
        efeitos.push({ el, calc, desenha, atual: -1, alvo: 0 });
    }

    // ---------- quebra em palavras / letras (o texto continua o mesmo para leitores de tela)
    function quebrarPalavras(el, classe) {
        const nos = [];
        const walker = document.createTreeWalker(el, NodeFilter.SHOW_TEXT);
        while (walker.nextNode()) nos.push(walker.currentNode);
        const palavras = [];
        nos.forEach((no) => {
            const frag = document.createDocumentFragment();
            no.textContent.split(/(\s+)/).forEach((parte) => {
                if (!parte) return;
                if (/^\s+$/.test(parte)) { frag.appendChild(document.createTextNode(parte)); return; }
                const fora = document.createElement('span'); fora.className = classe;
                const dentro = document.createElement('span'); dentro.className = classe + 'i'; dentro.textContent = parte;
                fora.appendChild(dentro); frag.appendChild(fora); palavras.push(dentro);
            });
            no.parentNode.replaceChild(frag, no);
        });
        return palavras;
    }
    function quebrarLetras(el) {
        const texto = el.textContent;
        el.setAttribute('aria-label', texto);
        el.textContent = '';
        return [...texto].map((c) => {
            const s = document.createElement('span'); s.className = 'rl-l'; s.textContent = c; s.setAttribute('aria-hidden', 'true');
            el.appendChild(s); return s;
        });
    }

    // ---------- 1. barra de progresso de leitura
    const barra = document.createElement('div');
    barra.className = 'rl-progresso'; barra.setAttribute('aria-hidden', 'true');
    document.body.appendChild(barra);
    function desenharBarra() {
        const max = Math.max(1, document.documentElement.scrollHeight - vh);
        barra.style.transform = `scaleX(${clamp(scrollY / max).toFixed(4)})`;
    }

    // ---------- 2. topo: a janela inclina ao sair e gira com o cursor
    const hero = $('.hero');
    const heroMidia = $('.hero__media');
    const heroTexto = $('.hero__text');
    const mouse = { x: 0, y: 0, ax: 0, ay: 0 };
    if (hero && heroMidia) {
        registrar(hero, () => tSaida(hero), (t) => {
            const rx = t * 18 - mouse.ay * 4, ry = mouse.ax * 6;
            heroMidia.style.transform = `perspective(1600px) translateY(${(-t * 50).toFixed(1)}px) rotateX(${rx.toFixed(2)}deg) rotateY(${ry.toFixed(2)}deg) scale(${(1 - t * 0.08).toFixed(4)})`;
            heroMidia.style.opacity = (1 - t * 0.5).toFixed(3);
            if (heroTexto) { heroTexto.style.transform = `translateY(${(-t * 90).toFixed(1)}px)`; heroTexto.style.opacity = (1 - t * 0.9).toFixed(3); }
            // as notificações flutuantes se espalham para fora e somem
            const notas = heroMidia.querySelector('.ef-notas');
            if (notas) { notas.style.setProperty('--sair', faixa(t, 0, 0.6).toFixed(3)); notas.style.opacity = (1 - faixa(t, 0.1, 0.6)).toFixed(3); }
        });
        if (!matchMedia('(pointer: coarse)').matches) {
            let raf = 0;
            const passo = () => {
                raf = 0;
                mouse.ax += (mouse.x - mouse.ax) * 0.08; mouse.ay += (mouse.y - mouse.ay) * 0.08;
                const ef = efeitos.find((e) => e.el === hero); if (ef) ef.desenha(Math.max(0, ef.atual));
                if (Math.abs(mouse.x - mouse.ax) > 0.002 || Math.abs(mouse.y - mouse.ay) > 0.002) raf = requestAnimationFrame(passo);
            };
            hero.addEventListener('pointermove', (ev) => {
                const r = heroMidia.getBoundingClientRect();
                mouse.x = clamp((ev.clientX - r.left) / r.width, -0.5, 1.5) * 2 - 1;
                mouse.y = clamp((ev.clientY - r.top) / r.height, -0.5, 1.5) * 2 - 1;
                mouse.x = clamp(mouse.x, -1, 1); mouse.y = clamp(mouse.y, -1, 1);
                if (!raf) raf = requestAnimationFrame(passo);
            });
            hero.addEventListener('pointerleave', () => { mouse.x = 0; mouse.y = 0; if (!raf) raf = requestAnimationFrame(passo); });
        }
    }

    // ---------- 3. títulos: palavras sobem uma a uma
    $$('#recursos-h, #gremios-h, #planos-h, #close-h').forEach((h) => {
        const ps = quebrarPalavras(h, 'rl-p');
        registrar(h, () => tEntrada(h, 0.55), (t) => {
            ps.forEach((p, i) => {
                const k = faixa(t, i / (ps.length + 3), (i + 4) / (ps.length + 3));
                p.style.transform = `translateY(${((1 - k) * 110).toFixed(1)}%) rotate(${((1 - k) * 6).toFixed(2)}deg)`;
                p.style.opacity = (0.1 + 0.9 * k).toFixed(3);
                p.style.filter = k < 0.99 ? `blur(${((1 - k) * 10).toFixed(2)}px)` : '';
            });
        });
    });

    // ---------- 4. recursos: cartões em 3D e o conteúdo das telas em sequência
    $$('#recursos article.card').forEach((card, n) => {
        const itens = $$('.pr-row, .pr-ev, .pr-pres span, .pr-clock span', card);
        const numeros = $$('.pr-clock b', card).map((b) => ({ b, fim: parseInt(b.textContent, 10) || 0 }));
        const painel = $('.panel', card);
        const lado = (n % 2 ? 1 : -1) * (innerWidth < 640 ? 0.3 : 1);   // no celular, entrada lateral menor
        registrar(card, () => tEntrada(card, 0.38), (t) => {
            const k = faixa(t, 0, 0.55 + (n % 2) * 0.1), m = 1 - k;
            // o cartão vem de lado, girado em 3D e afundado, e se endireita
            card.style.transform = `perspective(1400px) translate3d(${(lado * m * 120).toFixed(1)}px, ${(m * 70).toFixed(1)}px, ${(-m * 260).toFixed(1)}px) rotateY(${(-lado * m * 32).toFixed(2)}deg) rotateX(${(m * 12).toFixed(2)}deg)`;
            card.style.opacity = k.toFixed(3);
            // o painel se revela de dentro para fora, como uma janela abrindo
            if (painel) painel.style.clipPath = k < 0.999 ? `inset(${(m * 22).toFixed(1)}% ${(m * 30).toFixed(1)}% round 24px)` : '';
            itens.forEach((it, i) => {
                const ki = faixa(t, 0.35 + i * 0.07, 0.6 + i * 0.07);
                it.style.transform = `translateX(${((1 - ki) * -22).toFixed(1)}px)`;
                it.style.opacity = ki.toFixed(3);
            });
            const kn = faixa(t, 0.4, 0.95);
            numeros.forEach(({ b, fim }) => { b.textContent = String(Math.round(fim * kn)).padStart(2, '0'); });
        });
    });

    // ---------- 4b. faixa de selos: as duas linhas correm em sentidos opostos com a rolagem
    //            e inclinam conforme a velocidade (rolagem rápida, inclinação maior)
    const faixaSelos = $('.ef-faixa');
    if (faixaSelos) {
        const linhas = $$('.ef-faixa__linha', faixaSelos);
        let antes = -1, incl = 0;
        registrar(faixaSelos, () => {
            const r = faixaSelos.getBoundingClientRect();
            return clamp((vh - r.top) / (vh + r.height));
        }, (t) => {
            if (antes >= 0) incl += (clamp((t - antes) * 900, -9, 9) - incl) * 0.35;
            antes = t;
            linhas.forEach((l) => {
                const dir = Number(l.dataset.dir) || -1;
                const x = dir < 0 ? -t * 900 : -1100 + t * 900;
                l.style.transform = `translate3d(${x.toFixed(1)}px, 0, 0) skewX(${(-dir * incl).toFixed(2)}deg)`;
            });
        });
    }

    // ---------- 5. frase grande: o texto ao lado acende palavra por palavra; os fatos sobem
    const sp = $('#sem-planilha p');
    if (sp) {
        const ws = quebrarPalavras(sp, 'rl-w');
        registrar(sp, () => tEntrada(sp, 0.3), (t) => {
            ws.forEach((w, i) => { w.style.opacity = (0.18 + 0.82 * faixa(t, i / ws.length * 0.8, i / ws.length * 0.8 + 0.2)).toFixed(3); });
        });
    }
    $$('#sem-planilha .fatos > div').forEach((d, i) => {
        registrar(d, () => tEntrada(d, 0.35), (t) => {
            const k = faixa(t, i * 0.08, 0.6 + i * 0.08);
            d.style.transform = `translateY(${((1 - k) * 30).toFixed(1)}px)`;
            d.style.opacity = k.toFixed(3);
            d.style.setProperty('--rl-linha', k.toFixed(3));
        });
    });

    // ---------- 6. grêmio: as diretorias chegam espalhadas em 3D e se encaixam no quadro
    const quadro = $('.pr-board');
    if (quadro) {
        const dirs = $$('.pr-dir', quadro);
        const pres = $('.ef-pres', quadro);
        const svg = $('.ef-linhas', quadro);
        const espalha = [[-160, -110, -14, 28], [170, -90, 12, -24], [-150, 120, 10, -20], [160, 130, -12, 26]];
        // linhas da presidente até o topo de cada diretoria (posições sem transform, pelo layout)
        const NS = 'http://www.w3.org/2000/svg';
        let caminhos = [];
        function tracar() {
            if (!svg || !pres) return;
            svg.innerHTML = ''; caminhos = [];
            const x0 = pres.offsetLeft + pres.offsetWidth / 2, y0 = pres.offsetTop + pres.offsetHeight;
            const visiveis = dirs.filter((d) => d.offsetParent);   // no celular algumas ficam escondidas
            const topo1 = visiveis.length ? visiveis[0].offsetTop : 0;
            const duasColunas = visiveis.length > 1 && visiveis[1].offsetTop === topo1;
            visiveis.forEach((d, i) => {
                const x1 = d.offsetLeft + d.offsetWidth / 2, y1 = d.offsetTop, ym = (y0 + y1) / 2;
                let dd;
                if (d.offsetTop === topo1) dd = `M${x0},${y0} C${x0},${ym} ${x1},${ym} ${x1},${y1}`;
                else if (duasColunas) {
                    // segunda fileira: desce pelo vão do meio e entra pela lateral do cartão
                    const yMeio = d.offsetTop + d.offsetHeight / 2;
                    const xBorda = d.offsetLeft + d.offsetWidth / 2 < x0 ? d.offsetLeft + d.offsetWidth : d.offsetLeft;
                    dd = `M${x0},${y0} L${x0},${yMeio - 10} Q${x0},${yMeio} ${xBorda},${yMeio}`;
                } else return;                                      // uma coluna: só a primeira
                if (i > 3) return;
                const base = document.createElementNS(NS, 'path'); base.setAttribute('d', dd); base.setAttribute('pathLength', '1'); base.setAttribute('class', 'base');
                const fluxo = document.createElementNS(NS, 'path'); fluxo.setAttribute('d', dd); fluxo.setAttribute('pathLength', '1'); fluxo.setAttribute('class', 'fluxo');
                svg.append(base, fluxo); caminhos.push({ base, fluxo });
            });
        }
        tracar();
        addEventListener('resize', tracar);
        addEventListener('load', tracar);
        registrar(quadro, () => tEntrada(quadro, 0.3), (t) => {
            if (pres) {
                const kp = faixa(t, 0, 0.3);
                pres.style.transform = `translateY(${((1 - kp) * -30).toFixed(1)}px) scale(${(0.6 + 0.4 * kp).toFixed(3)})`;
                pres.style.opacity = kp.toFixed(3);
            }
            caminhos.forEach((c, i) => {
                const kl = faixa(t, 0.45 + i * 0.07, 0.75 + i * 0.07);
                c.base.style.setProperty('--k', (1 - kl).toFixed(3));
                c.fluxo.style.setProperty('--fo', faixa(t, 0.85, 1).toFixed(3));
            });
            dirs.forEach((d, i) => {
                const [x, y, rz, ry] = espalha[i % espalha.length];
                const k = faixa(t, i * 0.09, 0.62 + i * 0.09), m = 1 - k;
                d.style.transform = `perspective(1200px) translate3d(${(x * m).toFixed(1)}px, ${(y * m).toFixed(1)}px, ${(-220 * m).toFixed(1)}px) rotateZ(${(rz * m).toFixed(2)}deg) rotateY(${(ry * m).toFixed(2)}deg)`;
                d.style.opacity = (0.1 + 0.9 * k).toFixed(3);
                $$('.pr-pessoa', d).forEach((p, j) => { const kp = faixa(t, 0.5 + i * 0.06 + j * 0.06, 0.8 + i * 0.06 + j * 0.06); p.style.opacity = kp.toFixed(3); p.style.transform = `translateX(${((1 - kp) * 14).toFixed(1)}px)`; });
            });
        });
    }

    // ---------- 7. planos: linhas deslizam, a tela do Pix gira, o total conta e o QR se desenha
    $$('#planos .rows li').forEach((li, i) => {
        registrar(li, () => tEntrada(li, 0.4), (t) => {
            const k = faixa(t, i * 0.05, 0.6 + i * 0.05);
            li.style.transform = `translateX(${((1 - k) * -50).toFixed(1)}px)`;
            li.style.opacity = k.toFixed(3);
        });
    });
    const pix = $('#planos .frame .pr-mini');
    if (pix) {
        const linhas = $$('.pr-row, .pr-pix, .pr-k', pix);
        const qr = $('.pr-qr', pix);
        const total = $$('.pr-row b.pr-mono', pix).pop();
        const valor = total ? parseFloat(total.textContent.replace(/[^\d,]/g, '').replace(',', '.')) || 0 : 0;
        const fmt = (v) => 'R$ ' + v.toFixed(2).replace('.', ',');
        registrar(pix, () => tEntrada(pix, 0.3), (t) => {
            const k = faixa(t, 0, 0.6), m = 1 - k;
            pix.style.transform = `perspective(1400px) translateX(${(m * 80).toFixed(1)}px) rotateY(${(m * -32).toFixed(2)}deg) rotateX(${(m * 10).toFixed(2)}deg)`;
            pix.style.opacity = (0.1 + 0.9 * k).toFixed(3);
            linhas.forEach((l, i) => { const kl = faixa(t, 0.25 + i * 0.07, 0.5 + i * 0.07); l.style.opacity = kl.toFixed(3); l.style.transform = `translateY(${((1 - kl) * 10).toFixed(1)}px)`; });
            if (qr) qr.style.clipPath = `inset(0 0 ${((1 - faixa(t, 0.5, 0.95)) * 100).toFixed(1)}% 0)`;
            if (total) total.textContent = fmt(valor * faixa(t, 0.4, 0.9));
            // no fim: o Pix é confirmado (selo "Pago", check verde sobre o QR, anel pulsando)
            pix.classList.toggle('ef-pago', t >= 0.97);
        });
    }

    // ---------- 8. rodapé: as letras do nome gigante sobem uma a uma
    const nome = $('.close__word');
    if (nome) {
        const ls = quebrarLetras(nome);
        registrar(nome, () => tEntrada(nome, 0.25), (t) => {
            ls.forEach((l, i) => {
                const k = faixa(t, i * 0.09, 0.55 + i * 0.09);
                l.style.transform = `perspective(600px) translateY(${((1 - k) * 100).toFixed(1)}%) rotateX(${((1 - k) * -85).toFixed(1)}deg) scale(${(0.7 + 0.3 * k).toFixed(3)})`;
                l.style.filter = k < 0.99 ? `blur(${((1 - k) * 12).toFixed(2)}px)` : '';
                l.style.opacity = (0.15 + 0.85 * k).toFixed(3);
            });
        });
    }

    // ---------- laço: calcula o alvo de cada efeito na rolagem e suaviza até ele
    let raf = 0;
    function quadroAnim() {
        raf = 0;
        desenharBarra();
        let falta = false;
        efeitos.forEach((e) => {
            e.alvo = e.calc();
            const novo = e.atual < 0 ? e.alvo : e.atual + (e.alvo - e.atual) * 0.2;
            const chegou = Math.abs(e.alvo - novo) < 0.001;
            const v = chegou ? e.alvo : novo;
            if (v !== e.atual) { e.atual = v; e.desenha(v); }
            if (!chegou) falta = true;
        });
        if (falta) raf = requestAnimationFrame(quadroAnim);
    }
    const pedir = () => { if (!raf) raf = requestAnimationFrame(quadroAnim); };

    if (reduzido) {
        // estado final, sem movimento (o topo fica como está)
        efeitos.forEach((e) => e.desenha(e.el === hero ? 0 : 1));
        desenharBarra();
        addEventListener('scroll', desenharBarra, { passive: true });
        return;
    }
    html.classList.add('rl-vivo');
    addEventListener('scroll', pedir, { passive: true });
    addEventListener('resize', () => { vh = innerHeight; pedir(); });
    addEventListener('load', pedir);
    pedir();
})();
