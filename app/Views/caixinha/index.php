<?php
// app/Views/caixinha/index.php
// Caixinha anônima: enviar mensagem, acompanhar as próprias, perguntas publicadas e (representante) caixa de entrada.
$cssPagina = ['projetos.css', 'caixinha.css'];
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';
$tid = (int) $turma['id'];
$base = $basePath . '/turmas/' . $tid . '/caixinha';
$quando = fn($d) => date('d/m/Y H:i', strtotime($d));
$selo = function ($tipo) {
    [$nome, $ico] = Caixinha::TIPOS[$tipo] ?? Caixinha::TIPOS['duvida'];
    return '<span class="cx-tipo cx-' . $tipo . '"><i class="fas ' . $ico . '"></i> ' . $nome . '</span>';
};
?>

<section class="hero-section">
    <div>
        <div class="eyebrow"><span class="status-dot"></span> Caixinha</div>
        <h1>Caixinha anônima</h1>
        <p class="hero-subtitle">Dúvidas, sugestões, reclamações e elogios para o representante da <strong><?= h($turma['nome']) ?></strong>. Ninguém vê quem enviou.</p>
    </div>
    <div class="hero-actions">
        <a class="button button-ghost" href="<?= $basePath ?>/turmas/<?= $tid ?>"><i class="fas fa-arrow-left"></i> Voltar</a>
    </div>
</section>

<?php if ($gestor): ?>
    <!-- ============ CAIXA DE ENTRADA (representante) ============ -->
    <section class="panel mb-16" id="cx-entrada" data-atualizar="20">
        <div class="cx-abas" role="tablist">
            <?php foreach (['abertas' => ['Para responder', $contagem['nova'] + $contagem['lida']], 'respondidas' => ['Respondidas', $contagem['respondida']], 'arquivadas' => ['Arquivadas', $contagem['arquivada']]] as $k => [$rot, $n]): ?>
                <a href="<?= $base ?>?ver=<?= $k ?>" class="cx-aba<?= $filtro === $k ? ' ativa' : '' ?>"><?= $rot ?> <b><?= $n ?></b></a>
            <?php endforeach; ?>
            <?php if ($contagem['nova']): ?><span class="cx-novas"><?= $contagem['nova'] ?> nova<?= $contagem['nova'] > 1 ? 's' : '' ?></span><?php endif; ?>
        </div>

        <?php if (!$entrada): ?>
            <p class="cx-vazio"><?= $filtro === 'abertas' ? 'Nenhuma mensagem esperando resposta.' : 'Nada por aqui.' ?></p>
        <?php else: ?>
            <ul class="cx-lista">
                <?php foreach ($entrada as $m): ?>
                    <li class="cx-msg<?= $m['status'] === 'nova' ? ' nova' : '' ?>">
                        <div class="cx-msg-topo"><?= $selo($m['tipo']) ?><small><?= $quando($m['created_at']) ?> · anônima</small></div>
                        <p class="cx-texto"><?= nl2br(h($m['texto'])) ?></p>
                        <?php if ($m['resposta']): ?>
                            <div class="cx-resposta"><strong>Sua resposta<?= $m['publica'] ? ' (publicada para a turma)' : '' ?>:</strong> <?= nl2br(h($m['resposta'])) ?></div>
                        <?php endif; ?>
                        <?php if ($m['status'] !== 'arquivada'): ?>
                            <details class="cx-responder"<?= $m['resposta'] ? '' : ' open' ?>>
                                <summary><?= $m['resposta'] ? 'Editar resposta' : 'Responder' ?></summary>
                                <form method="POST" action="<?= $basePath ?>/caixinha/<?= (int) $m['id'] ?>/responder" data-parcial="#cx-entrada,#cx-grade">
                                    <?= ViewHelper::csrfField() ?>
                                    <textarea name="resposta" class="field" rows="3" maxlength="3000" required placeholder="Escreva a resposta"><?= h($m['resposta'] ?? '') ?></textarea>
                                    <label class="cx-check"><input type="checkbox" name="publica" value="1"<?= $m['publica'] ? ' checked' : '' ?>> Publicar pergunta e resposta para a turma (continua anônima)</label>
                                    <button type="submit" class="button button-primary"><i class="fas fa-reply"></i> Enviar resposta</button>
                                </form>
                            </details>
                        <?php endif; ?>
                        <form method="POST" action="<?= $basePath ?>/caixinha/<?= (int) $m['id'] ?>/arquivar" class="cx-arquivar" data-parcial="#cx-entrada">
                            <?= ViewHelper::csrfField() ?>
                            <input type="hidden" name="acao" value="<?= $m['status'] === 'arquivada' ? 'voltar' : 'arquivar' ?>">
                            <button type="submit" class="button button-ghost"><i class="fas <?= $m['status'] === 'arquivada' ? 'fa-box-open' : 'fa-box-archive' ?>"></i> <?= $m['status'] === 'arquivada' ? 'Tirar do arquivo' : 'Arquivar' ?></button>
                        </form>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
<?php endif; ?>

<div class="cx-grade" id="cx-grade" data-atualizar="30">
    <!-- ============ ENVIAR ============ -->
    <section class="panel">
        <h2 class="cx-titulo"><i class="fas fa-paper-plane"></i> Deixar uma mensagem</h2>
        <?php if ($restam > 0): ?>
            <form method="POST" action="<?= $base ?>" class="cx-form" data-parcial="#cx-grade" data-parcial-limpar>
                <?= ViewHelper::csrfField() ?>
                <div class="cx-tipos">
                    <?php foreach (Caixinha::TIPOS as $k => [$nome, $ico]): ?>
                        <label class="cx-opcao"><input type="radio" name="tipo" value="<?= $k ?>"<?= $k === 'duvida' ? ' checked' : '' ?>><span><i class="fas <?= $ico ?>"></i> <?= $nome ?></span></label>
                    <?php endforeach; ?>
                </div>
                <textarea name="texto" class="field" rows="4" maxlength="2000" required placeholder="Escreva aqui. O representante não vê o seu nome."></textarea>
                <div class="cx-form-pe">
                    <small><i class="fas fa-user-secret"></i> Anônima · você ainda pode enviar <?= $restam ?> hoje</small>
                    <button type="submit" class="button button-primary">Enviar</button>
                </div>
            </form>
        <?php else: ?>
            <p class="cx-vazio">Você já enviou <?= Caixinha::LIMITE_DIA ?> mensagens hoje. Amanhã libera de novo.</p>
        <?php endif; ?>

        <?php if ($minhas): ?>
            <h3 class="cx-sub" id="minhas">Suas mensagens</h3>
            <ul class="cx-lista compacta">
                <?php foreach ($minhas as $m): ?>
                    <li class="cx-msg">
                        <div class="cx-msg-topo"><?= $selo($m['tipo']) ?><small><?= $quando($m['created_at']) ?> · <?= $m['resposta'] ? 'respondida' : 'aguardando resposta' ?></small></div>
                        <p class="cx-texto"><?= nl2br(h($m['texto'])) ?></p>
                        <?php if ($m['resposta']): ?><div class="cx-resposta"><strong>Resposta:</strong> <?= nl2br(h($m['resposta'])) ?></div><?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>

    <!-- ============ PERGUNTAS PUBLICADAS ============ -->
    <section class="panel">
        <h2 class="cx-titulo"><i class="fas fa-comments"></i> Perguntas respondidas para a turma</h2>
        <?php if (!$publicas): ?>
            <p class="cx-vazio">Quando o representante publicar uma resposta, ela aparece aqui para todos.</p>
        <?php else: ?>
            <ul class="cx-lista compacta">
                <?php foreach ($publicas as $m): ?>
                    <li class="cx-msg">
                        <div class="cx-msg-topo"><?= $selo($m['tipo']) ?><small><?= $quando($m['respondida_em']) ?></small></div>
                        <p class="cx-texto"><?= nl2br(h($m['texto'])) ?></p>
                        <div class="cx-resposta"><strong>Representante:</strong> <?= nl2br(h($m['resposta'])) ?></div>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
