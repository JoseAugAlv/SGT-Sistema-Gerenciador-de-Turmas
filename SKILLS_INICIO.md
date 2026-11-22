# Skills e plugins — como começar um projeto

Todas as skills abaixo estão instaladas **globalmente** (`~/.claude/skills/` e `~/.claude/agents/`), sem hooks automáticos. Nenhuma roda sozinha: só são usadas quando o projeto começar.

---

## Para começar

Digite no Claude Code:

```
Vamos começar o projeto. Siga o SKILLS_INICIO.md.
```

O Claude então executa a sequência abaixo, uma etapa por vez, mostrando o resultado de cada uma antes de seguir.

---

## Sequência de início

### 1. Mapear o código: `graphify`
```
/graphify . --update
```
- Atualiza o grafo em `graphify-out/` com o que mudou desde a última vez.
- A partir daí, perguntas sobre o código usam o grafo, o que **economiza tokens**:
  - `/graphify query "como funciona X?"`
  - `graphify explain "NomeDaClasse"`
  - `graphify path "A" "B"`
- Rode `--update` de novo sempre que fizer mudanças grandes.

### 2. Definir o produto: `impeccable`
```
/impeccable init
```
- Cria o `PRODUCT.md`, que descreve o sistema, os usuários e o objetivo. Todas as outras etapas de design partem dele.
- Opcional: `/impeccable document` cria um `DESIGN.md` com as cores, fontes e componentes que já existem.

### 3. Diagnóstico da interface
```
/impeccable critique   ← revisão de UX com nota
/impeccable audit      ← relatório técnico (acessibilidade, responsividade, performance)
```
- **Só gera relatórios, não altera nada.** Você escolhe o que corrigir.

### 4. Melhorias (uma tela ou um setor por vez)
| Objetivo | Comando |
|---|---|
| Acabamento geral | `/impeccable polish <tela>` |
| Organizar layout | `/impeccable layout <tela>` |
| Tipografia | `/impeccable typeset <tela>` |
| Cores | `/impeccable colorize <tela>` |
| Simplificar | `/impeccable distill <tela>` |
| Textos e mensagens mais claros | `/impeccable clarify <tela>` |
| Celular e tablet | `/impeccable adapt <tela>` |
| Robustez (erros, estados vazios) | `/impeccable harden <tela>` |
| Animações | `/impeccable animate <tela>` |

Digite `/impeccable` sozinho para ver todos os comandos.

### 4a. Sistema visual: `ui-ux-pro-max`
| Objetivo | Skill |
|---|---|
| Consultar estilos, paletas, fontes e regras de UX (base de dados local) | `/ui-ux-pro-max:ui-ux-pro-max` |
| Criar tokens (cores, espaçamentos, tipografia) em variáveis CSS | `/ui-ux-pro-max:design-system` |
| Estilizar componentes, tema escuro, responsividade | `/ui-ux-pro-max:ui-styling` |
| Logo, ícones, identidade visual | `/ui-ux-pro-max:design` · `/ui-ux-pro-max:brand` |

Atenção: o `ui-styling` usa Tailwind e shadcn (React). No SGT (PHP puro), peça para aplicar só as **ideias** em CSS normal, sem trocar de framework.

### 4a+. Página pública: `taste-skill`
- Use **só** para a página inicial pública (`landing.php`), o login e o cadastro. É feita para sites de empresa, não para telas internas.
- Exemplo: *"use a taste-skill para melhorar a landing.php sem mudar a estrutura PHP"*.

### 4b. Detalhes e animações: skills do Emil Kowalski
| Objetivo | Skill |
|---|---|
| Não sabe qual usar? | `/ask-emil` (indica a skill certa) |
| Filosofia de acabamento de UI | `/emil-design-eng` |
| Testar a tela com dados extremos (nomes longos, listas vazias…) | `/break-ui <tela>` |
| Achar onde faltam animações (só sugere) | `/find-animation-opportunities` |
| Revisar as animações existentes | `/review-animations` · `/improve-animations` |
| Tirar animações desnecessárias | `/remove-animations` |
| Criar uma animação | `/animate` · `/choreograph` (vários elementos juntos) |
| Performance das animações | `/animation-performance` |
| Experiência de app no celular | `/mobile-native` |
| Várias versões de um componente para escolher | `/prototype` |
| Escolher uma biblioteca de front-end | `/pick-ui-library` |
| Transformar correções em regras no DESIGN.md | `/learn-taste` |

Dica: `/break-ui` é ótimo para o SGT (nomes de alunos longos, turmas sem grupos, notas vazias).

### 5. Ver funcionando no navegador
- **playwright-cli** (principal): o Claude abre o SGT sozinho, navega, preenche formulários, clica e tira prints para conferir as mudanças.
  - **Sempre com `--browser=msedge`**, porque o Google Chrome não está instalado:
    `playwright-cli open http://localhost/SGT --browser=msedge`
  - Exemplo de pedido: *"use o playwright-cli para logar como representante e tirar print da tela de avaliações"*.
  - Ele cria uma pasta `.playwright-cli/` com os prints e snapshots (já está no `.gitignore`).
- **Claude in Chrome** (alternativa): extensão do Chrome, usa o seu navegador com os logins que já estão nele.
- Ou use `/run` para iniciar e verificar o app.

### 6. Revisar antes de salvar
```
/web-interface-guidelines app/Views/<pasta>/*.php   ← checa acessibilidade, formulários, foco e textos (regras da Vercel)
/code-review      ← procura bugs nas mudanças
/simplify         ← limpa código duplicado
```

---

## Regras do projeto
- **Mudanças pequenas.** Nada de grandes refatorações. Manter a organização **por setor**: um controller, um model e uma pasta de views por área.
- Uma tela ou um setor por vez, sempre mostrando o resultado antes de seguir.
- Hooks automáticos ficam desligados, a menos que você peça.

---

## Status das instalações

| Skill / plugin | Status | Observação |
|---|---|---|
| graphify | ✅ instalado | Grafo do SGT já gerado em `graphify-out/` |
| impeccable (v4.10.0) | ✅ instalado | Skill e 5 subagentes; hooks não instalados |
| emilkowalski/skills | ✅ instalado | 17 skills; de fora: animate-expo, write-swift, ask-sonner (React Native/Swift/React) |
| ui-ux-pro-max | ✅ instalado (plugin) | 7 skills: ui-ux-pro-max, design-system, ui-styling, design, brand, banner-design, slides |
| taste-skill (v2.5.1) | ✅ instalado | Focada em sites/landing pages; no SGT serve para `landing.php` e telas públicas |
| web-interface-guidelines | ✅ instalado | Comando `/web-interface-guidelines` em `~/.claude/commands/`; só revisa, não altera |
| playwright-cli (v0.1.25) | ✅ instalado | `npm -g @playwright/cli` + skill em `~/.claude/skills/playwright-cli`; testado no SGT com Edge |
| dev-browser | ❌ não suportado | Não roda no Windows; substituído pelo playwright-cli |

Novas skills instaladas serão adicionadas a esta lista e à sequência acima.
Vamos começar o projeto. Siga o SKILLS_INICIO.md.
