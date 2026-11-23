// public/js/landing-camadas.js
// Cena "Como funciona" da landing (#como-funciona). Usa o motor de cenas do kit (assets/scenes/lib.js):
// a seção fica presa por 3 telas e update(p) recebe o progresso suavizado da rolagem (0 a 1).
// Transições contínuas (a camada que sai e a que entra se cruzam) e "ímã": ao parar de rolar, a página
// desliza até o passo mais próximo, para cada passo ficar certinho na tela.
//
// Roteiro:
//   0.00-0.16  a tela do boletim, de frente, inclina para trás e se abre em 4 camadas no espaço
//   0.16-0.86  um passo por camada: a camada do passo sobe e acende, as de cima ficam translúcidas,
//              o passo correspondente acende na lista
//   0.86-1.00  as camadas se fundem de volta no boletim pronto, de frente
// O cursor gira a pilha alguns graus. No celular e no tablet a cena roda em coluna (passo atual em
// cima, pilha embaixo). Movimento reduzido ou tela muito baixa: composição parada (CSS).
import { scene, seg, ease, lerp, clamp, coarse } from '../assets/scenes/lib.js';

const raiz = document.querySelector('[data-camadas]');
if (raiz) iniciar(raiz);

function iniciar(root) {
    const pilha = root.querySelector('.cm__pilha');
    const telas = [...root.querySelectorAll('.cm__tela')];
    const passos = [...root.querySelectorAll('.cm__passos li')];
    const dica = root.querySelector('.cm__dica');
    const dicaTxt = dica && dica.querySelector('.cm__dica-txt');
    const dicaPts = dica ? [...dica.querySelectorAll('.cm__dica-pts i')] : [];
    const N = telas.length;
    const INI = 0.16, FIM = 0.86, LARG = (FIM - INI) / N;

    let ultimoP = 0, vivo = false, vh = 800, largura = 600, estreita = false;
    // cursor: alvo e valor suavizado, em -1..1
    const mouse = { x: 0, y: 0, ax: 0, ay: 0, raf: 0 };

    function desenhar(p) {
        ultimoP = p;
        const abre = seg(p, 0, INI, ease.inOut) * (1 - seg(p, FIM, 1, ease.inOut));
        const gap = Math.min(110, vh * 0.12, largura * 0.2) * abre;   // celular: camadas mais juntas

        // inclinação da pilha (vista isométrica) + giro do cursor
        const rx = lerp(0, 56, abre) - mouse.ay * 5;
        const rz = lerp(0, -36, abre);
        const ry = mouse.ax * 7;
        const esc = lerp(1, estreita ? 0.88 : 0.86, abre);
        pilha.style.transform = `rotateX(${rx.toFixed(2)}deg) rotateY(${ry.toFixed(2)}deg) rotateZ(${rz.toFixed(2)}deg) scale(${esc.toFixed(4)})`;

        // posição contínua nos passos: f = 0..N (centro do passo i em i + 0,5)
        const f = (p - INI) / LARG;
        const c = clamp(f - 0.5, 0, N - 1);                      // "camada atual" em número quebrado
        const ativo = p < INI ? -1 : Math.round(c);               // troca no meio da passagem entre dois passos
        const dentro = ativo < 0 ? 0 : clamp(f - ativo);

        telas.forEach((t, i) => {
            // peso da camada: platô no centro do seu passo e rampa suave até os vizinhos (as duas se cruzam)
            let w = 1 - Math.max(0, Math.abs(f - (i + 0.5)) - 0.22) / 0.56;
            if (i === N - 1 && f > i + 0.5) w = 1;                // o último passo segue aceso até as camadas se fundirem
            if (i === 0 && f < 0.5) w = 1 - Math.max(0, 0.28 - f) / 0.56;
            w = ease.inOut(clamp(w)) * abre;
            const z = (i - (N - 1) / 2) * gap + (estreita ? 110 : 170) * w;
            // destaque: a camada em foco sobe e cresce; as outras ficam quase transparentes (as de cima),
            // apagadas (as de baixo), desfocadas e sem cor — tudo proporcional, sem saltos
            const apaga = ativo >= 0 ? abre * (1 - w / Math.max(abre, 0.001)) : 0;
            const op = 1 - apaga * (i > c ? 0.93 : 0.68);
            t.style.transform = `translate3d(0, 0, ${z.toFixed(1)}px) scale(${(1 + 0.07 * w).toFixed(4)})`;
            t.style.opacity = op.toFixed(3);
            t.style.filter = apaga > 0.01 ? `blur(${(apaga * 2.2).toFixed(2)}px) grayscale(${(apaga * 0.85).toFixed(2)})` : '';
            t.style.setProperty('--brilho', w.toFixed(3));
            t.classList.toggle('is-ativa', i === ativo && abre > 0.5 && w > 0.5 * abre);
            const rot = t.querySelector('.cm__rotulo');
            if (rot) rot.style.opacity = (abre * (i === ativo ? 1 : 0.75)).toFixed(3);
        });

        // dica de rolagem (celular/tablet): diz quanto falta e some quando a cena acaba
        if (dica) {
            const txt = ativo < 0 ? 'Role para baixo para ver os 4 passos'
                : ativo < N - 1 ? `Passo ${ativo + 1} de ${N} · continue rolando` : 'Continue rolando para seguir';
            if (dicaTxt.textContent !== txt) dicaTxt.textContent = txt;
            dicaPts.forEach((pt, i) => pt.classList.toggle('on', i <= ativo));
            dica.classList.toggle('is-fim', p > 0.93);
        }

        passos.forEach((li, i) => {
            li.classList.toggle('is-on', i === ativo);
            li.classList.toggle('is-feito', ativo >= 0 && i < ativo);
            li.style.setProperty('--f', i === ativo ? dentro.toFixed(3) : (i < ativo ? '1' : '0'));
        });
    }

    function limpar() {
        pilha.style.transform = '';
        telas.forEach((t) => { t.style.transform = ''; t.style.opacity = ''; t.style.filter = ''; t.style.removeProperty('--brilho'); t.classList.remove('is-ativa');
            const rot = t.querySelector('.cm__rotulo'); if (rot) rot.style.opacity = ''; });
        passos.forEach((li) => { li.classList.remove('is-on', 'is-feito'); li.style.removeProperty('--f'); });
    }

    // giro do cursor: suaviza até o alvo e redesenha com o último progresso
    function passoMouse() {
        mouse.raf = 0;
        mouse.ax += (mouse.x - mouse.ax) * 0.08;
        mouse.ay += (mouse.y - mouse.ay) * 0.08;
        if (vivo) desenhar(ultimoP);
        if (Math.abs(mouse.x - mouse.ax) > 0.002 || Math.abs(mouse.y - mouse.ay) > 0.002) mouse.raf = requestAnimationFrame(passoMouse);
    }
    function aoMover(ev) {
        if (!vivo) return;
        mouse.x = clamp(ev.clientX / window.innerWidth, 0, 1) * 2 - 1;
        mouse.y = clamp(ev.clientY / window.innerHeight, 0, 1) * 2 - 1;
        if (!mouse.raf) mouse.raf = requestAnimationFrame(passoMouse);
    }
    if (!coarse()) window.addEventListener('pointermove', aoMover, { passive: true });

    // ---------- "ímã": ao parar de rolar dentro da cena, desliza até o passo mais próximo
    const paradas = [0, ...telas.map((_, i) => INI + LARG * (i + 0.5)), 1];
    let espera = 0, deslizando = false, toque = false;
    function geometria() {
        const r = root.getBoundingClientRect(), palco = root.querySelector('.sc__stage');
        const topo = r.top + window.scrollY, faixa = Math.max(1, root.offsetHeight - (palco ? palco.offsetHeight : window.innerHeight));
        return { topo, faixa };
    }
    function assentar() {
        if (!vivo || deslizando || toque || document.body.classList.contains('tour-aberto')) return;
        const { topo, faixa } = geometria();
        const p = (window.scrollY - topo) / faixa;
        if (p <= 0.003 || p >= 0.997) return;                   // fora da cena (ou saindo dela): não segura
        let alvo = paradas[0];
        for (const q of paradas) if (Math.abs(q - p) < Math.abs(alvo - p)) alvo = q;
        const y = Math.round(topo + alvo * faixa);
        if (Math.abs(y - window.scrollY) < 6) return;
        deslizando = true;
        window.scrollTo({ top: y, behavior: 'smooth' });
        setTimeout(() => { deslizando = false; }, 700);
    }
    function aoRolar() { if (deslizando) return; clearTimeout(espera); espera = setTimeout(assentar, 160); }
    if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        window.addEventListener('scroll', aoRolar, { passive: true });
        window.addEventListener('touchstart', () => { toque = true; clearTimeout(espera); }, { passive: true });
        window.addEventListener('touchend', () => { toque = false; aoRolar(); }, { passive: true });
    }

    scene(root, {
        hold: 3,                      // ~1 tela de rolagem por passo
        phone: { hold: 3 },           // celular também roda a cena (layout em coluna no CSS)
        tau: 0.12,                    // suavização um pouco maior (movimento mais macio)
        minWidth: 0,
        minHeight: 520,              // celular deitado: composição parada
        enter() { vivo = true; },
        measure(ctx) { vh = ctx.vh; largura = pilha.offsetWidth || 600; estreita = ctx.vw < 1024; },
        update(p) { desenhar(p); },
        rest() { vivo = false; limpar(); },
    });
}
