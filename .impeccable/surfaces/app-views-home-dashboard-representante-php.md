---
version: 1
slug: "app-views-home-dashboard-representante-php"
primary_target: "app/Views/home/dashboard_representante.php"
related_targets: ["app/Views/layouts/header.php","app/Views/layouts/nav.php","public/css/base.css"]
---

## Escopo

Shell do app (cabeçalho, navegação inferior no celular, menu lateral no computador) e painel do representante (`app/Views/home/dashboard_representante.php`). Modo: Operate. Celular primeiro.

## Público e tarefa

Representante (aluno) coordenando a turma pelo celular: ver o que vence hoje, avaliar, validar atas, acompanhar etapas. Dados de exemplo nos protótipos são sintéticos.

## Decisões do usuário (2026-11-21)

- Direção escolhida: Camiseta de Turma (a rodada sorteada).
- Três temas, escolhidos por cada usuário: escuro roxo e preto; claro branco e azul; misto (topo preto, corpo claro, roxo).
- O representante escolhe a cor de destaque e envia um logo da turma; a cor da turma substitui a cor de marca no cabeçalho da turma.
- Sem a faixa tracejada sob o nome da turma; no lugar, um filete sólido na cor de acento.

## Direction contract

THESIS: a turma é um time com identidade própria; o painel abre com o "uniforme" da turma (cor, logo e sigla em numerais grandes) e recusa o painel genérico de cartões de estatística com menu lateral roxo.

OWN-WORLD: campos de cor chapada; numerais condensados (Barlow Condensed 800) para sigla, prazos e contagens; texto em Public Sans; grade modular rígida com células divididas por filetes de 1px; códigos sequenciais curtos (P02, E03, ATA 07) em etiquetas tingidas; nenhuma sombra decorativa, nenhum degradê, nenhum vidro.

STORY: o representante entende em segundos o que vence hoje, quantas pendências tem e qual a média; toca no item e resolve.

FIRST VIEWPORT: barra superior com marca Prumo, sino e menu; faixa da turma com logo 60px, sigla em numerais ~88px e nome/curso/papel; uma única placa invertida com o item mais urgente e o prazo em numerais; grade de três células (avaliações, atas, média); lista "Para fazer" com código, título, contexto e botão; barra inferior fixa com cinco destinos.

FORM: Camiseta de Turma, posição 4 da lista própria; seed:29b4a8ddbcf13f40. Raises: cor chapada em regiões inteiras (Mapa de Parque); só uma placa invertida por tela (Tabela de Horários); reflow por módulos inteiros (Grade Construída); códigos sequenciais (Catálogo Numerado); hierarquia por contraste de tamanho (Espécime Tipográfico).

FINISH: unreviewed and undocumented is unfinished; this build ends with the finish review, the verdict, DESIGN.md, and every shipping raster carrying its provenance

## Em aberto

- Contraste de texto branco sobre roxo #7C5CFF no tema escuro (verificar 4.5:1 em texto pequeno).
- Como a cor escolhida pela turma convive com os três temas (garantir contraste automático).
