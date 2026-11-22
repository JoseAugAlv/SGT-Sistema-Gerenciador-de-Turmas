<?php
$cssPagina = 'lgpd.css';

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
require_once __DIR__ . '/../layouts/flashes.php';

$badgeStatus = function (string $s): string {
    return match ($s) {
        'pendente'    => '<span class="sol-badge pendente"><i class="fas fa-hourglass-half"></i> Pendente</span>',
        'processando' => '<span class="sol-badge processando"><i class="fas fa-gear"></i> Processando</span>',
        'concluida'   => '<span class="sol-badge concluida"><i class="fas fa-check"></i> Concluída</span>',
        'negada'      => '<span class="sol-badge negada"><i class="fas fa-xmark"></i> Negada</span>',
        default       => '<span class="sol-badge">' . h($s) . '</span>',
    };
};

$tipoLabel = fn(string $t) => match ($t) {
    'exportacao' => 'Exportação de dados',
    'exclusao'   => 'Exclusão da conta',
    default      => ucfirst($t),
};
?>

<div class="legal-layout">

    <!-- ============================================================ -->
    <!-- ÍNDICE LATERAL                                                -->
    <!-- ============================================================ -->
    <nav class="legal-toc" aria-label="Índice">
        <div class="legal-toc-title">
            <i class="fas fa-list-ul"></i> Nesta página
        </div>
        <ol>
            <li><a href="#excluir">Solicitar exclusão</a></li>
            <li><a href="#historico">Histórico</a></li>
            <li><a href="#duvidas">Dúvidas</a></li>
        </ol>
    </nav>

    <!-- ============================================================ -->
    <!-- CONTEÚDO                                                      -->
    <!-- ============================================================ -->
    <article>

        <header class="legal-hero">
            <div class="eyebrow">
                <i class="fas fa-user-shield"></i>
                Meus Direitos (LGPD)
            </div>
            <h1>Controle seus dados pessoais</h1>
            <div class="legal-meta">
                <span><i class="fas fa-scale-balanced"></i> Conforme a Lei nº 13.709/2018</span>
                <span><i class="fas fa-circle-info"></i> Consulte também a <a href="<?= $basePath ?>/lgpd">Política de Privacidade</a></span>
            </div>
        </header>

        <!-- ---------- 2. EXCLUIR ---------- -->
        <section class="legal-section" id="excluir">
            <div class="acao-bloco excluir">
                <div class="acao-bloco-header">
                    <span class="acao-bloco-icon"><i class="fas fa-trash"></i></span>
                    <div>
                        <h3>Solicitar exclusão da conta</h3>
                        <p>
                            A exclusão é <strong>permanente</strong>: nome, email e telefone
                            são anonimizados. Os registros de auditoria são mantidos de forma
                            anonimizada pelo prazo legal mínimo de 12 meses.
                        </p>
                    </div>
                </div>

                <div class="legal-note warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    <div>
                        <strong>Atenção antes de prosseguir</strong>
                        <p class="mb-0">
                            A solicitação passa por análise antes de ser executada.
                            Você receberá uma notificação quando for aprovada ou negada.
                        </p>
                    </div>
                </div>

                <div class="acao-bloco-body">
                    <form method="POST" action="<?= $basePath ?>/lgpd/solicitar-exclusao">
                        <?= ViewHelper::csrfField() ?>

                        <div style="flex: 1; min-width: 220px;">
                            <label class="field-label">Motivo (opcional)</label>
                            <textarea name="motivo" class="field" rows="2" maxlength="500"
                                      placeholder="Conte-nos brevemente o motivo..."></textarea>
                        </div>

                        <button type="submit" class="button btn-outline-danger"
                                onclick="return confirm('Confirma a solicitação de exclusão? Esta ação é irreversível após aprovação.')">
                            <i class="fas fa-trash"></i> Solicitar exclusão
                        </button>
                    </form>
                </div>
            </div>
        </section>
        <!-- ---------- 4. DÚVIDAS ---------- -->
        <section class="legal-section" id="duvidas">
            <div class="legal-section-header">
                <span class="legal-section-num">04</span>
                <h2>Perguntas frequentes</h2>
            </div>

            <div class="legal-note info">
                <i class="fas fa-clock"></i>
                <div>
                    <strong>Quanto tempo leva para processar?</strong>
                    <p class="mb-0">
                        Exportações são concluídas imediatamente. Solicitações de exclusão
                        passam por análise e costumam ser processadas em até <strong>15 dias</strong>.
                    </p>
                </div>
            </div>

            <div class="legal-note info">
                <i class="fas fa-database"></i>
                <div>
                    <strong>Depois da exclusão, meus dados somem completamente?</strong>
                    <p class="mb-0">
                        Sim. Seus dados pessoais (nome, email, telefone) são anonimizados.
                        Registros de auditoria permanecem sem identificação pelo prazo legal.
                    </p>
                </div>
            </div>

            <div class="legal-note info">
                <i class="fas fa-undo"></i>
                <div>
                    <strong>Posso cancelar uma solicitação de exclusão?</strong>
                    <p class="mb-0">
                        Enquanto o status estiver como <strong>Pendente</strong>, entre em
                        contato com a administração. Após aprovada, não é possível reverter.
                    </p>
                </div>
            </div>
        </section>

        <!-- ---------- FOOTER ---------- -->
        <footer class="legal-footer">
            <span>&copy; <?= date('Y') ?> <?= h($appName) ?> — Todos os direitos reservados.</span>
            <a class="voltar-link" href="<?= $basePath ?>/lgpd">
                <i class="fas fa-book"></i> Ver Política de Privacidade completa
            </a>
        </footer>

    </article>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>