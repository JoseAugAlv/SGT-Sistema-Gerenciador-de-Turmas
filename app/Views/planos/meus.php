<?php
// app/Views/planos/meus.php
// Planos de todas as turmas do usuário: as que ele representa (com ações) e as que ele é aluno (só a situação).
$cssPagina = 'planos.css';

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';

$brl = fn($v) => 'R$ ' . number_format((float) $v, 2, ',', '.');
// código da situação (PlanoGuard) => [rótulo, classe]
$selo = [
    'isenta'          => ['Cortesia: sem cobrança', 'ok'],
    'ativa'           => ['Plano ativo', 'ok'],
    'vencendo'        => ['Vence em breve', 'aviso'],
    'tolerancia'      => ['Vencido: renove já', 'perigo'],
    'atraso'          => ['Parcela atrasada', 'perigo'],
    'sem_plano'       => ['Sem plano', 'perigo'],
    'aguardando'      => ['Aguardando pagamento', 'aviso'],
    'vencido'         => ['Vencido: turma bloqueada', 'perigo'],
    'atraso_bloqueio' => ['Bloqueada por atraso', 'perigo'],
];
?>

<section class="hero-section">
    <div>
        <div class="eyebrow"><span class="status-dot"></span> Planos</div>
        <h1>Meus planos</h1>
        <p class="hero-subtitle">O plano é da turma e quem cuida é o representante. Aqui estão todas as turmas em que você está.</p>
    </div>
    <div class="hero-actions">
        <a class="button button-primary" href="<?= $basePath ?>/turmas/nova"><i class="fas fa-plus"></i> Criar turma com plano</a>
        <a class="button button-secondary" href="<?= $basePath ?>/gremios/novo"><i class="fas fa-landmark"></i> Criar grêmio</a>
        <a class="button button-ghost" href="<?= $basePath ?>/precos"><i class="fas fa-tags"></i> Tabela de preços</a>
    </div>
</section>

<!-- ============ TURMAS QUE EU REPRESENTO ============ -->
<div class="bloco-titulo"><h2>Turmas que você representa</h2></div>
<?php if (!$comoRep): ?>
    <div class="panel meus-vazio">
        <i class="fas fa-crown"></i>
        <div>
            <strong>Você ainda não representa nenhuma turma</strong>
            <p>É o representante da sua sala? Crie a turma, escolha um plano e libere o Prumo para todos.</p>
            <a class="button button-primary" href="<?= $basePath ?>/turmas/nova">Criar minha turma</a>
        </div>
    </div>
<?php else: ?>
    <div class="meus-grade">
        <?php foreach ($comoRep as $t):
            $tid = (int) $t['id'];
            $sit = $t['situacao'];
            [$rotulo, $classe] = $selo[$sit['codigo']] ?? [$sit['codigo'], 'aviso'];
            $a = $t['atual'];
            $urlPlano = $basePath . '/turmas/' . $tid . '/plano';
        ?>
            <article class="panel meus-card">
                <div class="meus-topo">
                    <strong class="meus-turma"><?= h($t['nome']) ?></strong>
                    <span class="meus-selo <?= $classe ?>"><?= h($rotulo) ?></span>
                </div>
                <?php if ($a): ?>
                    <p class="meus-plano"><i class="fas fa-crown"></i> <?= h($a['plano_nome']) ?></p>
                    <ul class="meus-dados">
                        <li><?= $a['status'] === 'aguardando' ? 'Começa ao pagar a 1ª parcela' : ($a['fim'] ? 'Válido até ' . ViewHelper::data($a['fim']) : 'Sem vencimento') ?></li>
                        <li><?= (int) $t['projetos_usados'] ?>/<?= (int) $a['projetos_limite'] ?> projetos usados</li>
                        <li><?= (int) $t['total_alunos'] ?> pessoa(s) na turma</li>
                    </ul>
                <?php elseif ($sit['codigo'] === 'isenta'): ?>
                    <p class="meus-plano">Esta turma não paga plano.</p>
                <?php else: ?>
                    <p class="meus-plano">A turma está sem plano e fica bloqueada até você escolher um.</p>
                <?php endif; ?>

                <?php if ($sit['texto'] && $sit['codigo'] !== 'isenta'): ?><p class="field-help"><?= h($sit['texto']) ?></p><?php endif; ?>
                <?php if ($t['troca']): ?><p class="field-help">Troca para o plano <?= h($t['troca']['plano_nome']) ?> aguardando pagamento.</p><?php endif; ?>

                <div class="meus-acoes">
                    <?php if ($t['proxima']): ?>
                        <a class="button button-primary" href="<?= $basePath ?>/pagamentos/<?= (int) $t['proxima']['id'] ?>"><i class="fas fa-qrcode"></i> Pagar <?= $brl($t['proxima']['valor']) ?></a>
                    <?php elseif (!$a && $sit['codigo'] !== 'isenta'): ?>
                        <a class="button button-primary" href="<?= $urlPlano ?>"><i class="fas fa-crown"></i> Escolher plano</a>
                    <?php elseif ($a && $a['status'] === 'ativa'): ?>
                        <a class="button button-primary" href="<?= $urlPlano ?>#trocar"><i class="fas fa-arrow-up"></i> Fazer upgrade</a>
                    <?php endif; ?>
                    <a class="button button-ghost" href="<?= $urlPlano ?>">Detalhes do plano</a>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- ============ TURMAS EM QUE SOU ALUNO ============ -->
<?php if ($comoAluno): ?>
    <div class="bloco-titulo mt-24"><h2>Turmas em que você é aluno</h2></div>
    <p class="field-help">Nessas turmas o plano é cuidado pelo representante. Se algo estiver pendente, avise-o.</p>
    <ul class="lista-tarefas">
        <?php foreach ($comoAluno as $t):
            [$rotulo, $classe] = $selo[$t['situacao']['codigo']] ?? [$t['situacao']['codigo'], 'aviso'];
            $ass = $t['situacao']['assinatura'] ?? null;
        ?>
            <li>
                <span class="tarefa-icone"><i class="fas fa-users"></i></span>
                <div class="tarefa-info">
                    <strong><a class="mat-link" href="<?= $basePath ?>/turmas/<?= (int) $t['id'] ?>"><?= h($t['nome']) ?></a></strong>
                    <small><?= $ass ? 'Plano ' . h($ass['plano_nome']) . ($ass['fim'] ? ' · até ' . ViewHelper::data($ass['fim']) : '') : 'Plano do representante' ?></small>
                </div>
                <span class="meus-selo <?= $classe ?>"><?= h($rotulo) ?></span>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

<!-- ============ DADOS DE COBRANÇA ============ -->
<div class="bloco-titulo mt-24" id="cobranca"><h2>Dados de cobrança</h2></div>
<form method="POST" action="<?= $basePath ?>/planos/cobranca" class="meus-cobranca">
    <?= ViewHelper::csrfField() ?>
    <?php $cobrancaTitulo = 'CPF e endereço de quem paga'; require __DIR__ . '/_cobranca.php'; ?>
    <p><button type="submit" class="button button-primary">Salvar dados</button></p>
</form>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
