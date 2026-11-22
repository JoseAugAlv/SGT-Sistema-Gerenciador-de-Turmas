<?php
$cssPagina = 'lgpd.css';

require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
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
            <li><a href="#aceitacao">Aceitação</a></li>
            <li><a href="#cadastro">Cadastro</a></li>
            <li><a href="#uso">Uso permitido</a></li>
            <li><a href="#proibicoes">Condutas proibidas</a></li>
            <li><a href="#propriedade">Propriedade intelectual</a></li>
            <li><a href="#suspensao">Suspensão e exclusão</a></li>
            <li><a href="#responsabilidade">Limitação de responsabilidade</a></li>
            <li><a href="#alteracoes">Alterações</a></li>
            <li><a href="#foro">Foro</a></li>
        </ol>
    </nav>

    <!-- ============================================================ -->
    <!-- CONTEÚDO                                                      -->
    <!-- ============================================================ -->
    <article>

        <header class="legal-hero">
            <div class="eyebrow">
                <i class="fas fa-file-contract"></i>
                Termos de Uso
            </div>
            <h1>Regras para uso da plataforma</h1>
            <div class="legal-meta">
                <span><i class="far fa-calendar"></i> Última atualização: <?= date('d/m/Y') ?></span>
                <span><i class="fas fa-circle-info"></i> Leia também a <a href="<?= $basePath ?>/lgpd">Política de Privacidade</a></span>
            </div>
        </header>

        <!-- ---------- 1. ACEITAÇÃO ---------- -->
        <section class="legal-section" id="aceitacao">
            <div class="legal-section-header">
                <span class="legal-section-num">01</span>
                <h2>Aceitação dos termos</h2>
            </div>
            <p>
                Ao criar uma conta e utilizar o <strong><?= h($appName) ?></strong>,
                você declara ter lido e aceito integralmente estes Termos de Uso
                e a Política de Privacidade.
            </p>
            <div class="legal-note info">
                <i class="fas fa-circle-check"></i>
                <div>
                    <strong>Concordância explícita</strong>
                    A aceitação é registrada com data e hora no seu cadastro e pode ser
                    consultada a qualquer momento pela área <em>Minha Conta</em>.
                </div>
            </div>
        </section>

        <!-- ---------- 2. CADASTRO ---------- -->
        <section class="legal-section" id="cadastro">
            <div class="legal-section-header">
                <span class="legal-section-num">02</span>
                <h2>Cadastro e credenciais</h2>
            </div>
            <p>
                Você é responsável pela <strong>veracidade</strong> dos dados informados
                no cadastro e pela <strong>guarda das suas credenciais</strong>. O
                compartilhamento de credenciais é proibido.
            </p>
            <ul class="legal-list">
                <li><i class="fas fa-user-check"></i><span>Você declara ter no mínimo 12 anos de idade.</span></li>
                <li><i class="fas fa-envelope-circle-check"></i><span>Seu email precisa ser verificado para liberar o uso completo.</span></li>
                <li><i class="fas fa-key"></i><span>A senha é pessoal e intransferível.</span></li>
                <li><i class="fas fa-bell"></i><span>Notifique a administração imediatamente em caso de acesso não autorizado.</span></li>
            </ul>
        </section>

        <!-- ---------- 3. USO PERMITIDO ---------- -->
        <section class="legal-section" id="uso">
            <div class="legal-section-header">
                <span class="legal-section-num">03</span>
                <h2>Uso permitido</h2>
            </div>
            <p>
                O sistema deve ser utilizado apenas para finalidades <strong>educacionais</strong>
                relacionadas à gestão de turmas, projetos, grupos, avaliações e demais
                recursos disponíveis.
            </p>
        </section>

        <!-- ---------- 4. PROIBIÇÕES ---------- -->
        <section class="legal-section" id="proibicoes">
            <div class="legal-section-header">
                <span class="legal-section-num">04</span>
                <h2>Condutas proibidas</h2>
            </div>
            <p>São expressamente vedadas as seguintes condutas:</p>
            <ul class="legal-list">
                <li><i class="fas fa-ban"></i><span>Tentar acessar dados de outras pessoas sem autorização.</span></li>
                <li><i class="fas fa-triangle-exclamation"></i><span>Inserir conteúdo ofensivo, ilegal ou que viole direitos de terceiros.</span></li>
                <li><i class="fas fa-bug"></i><span>Realizar engenharia reversa, burlar mecanismos de segurança ou explorar vulnerabilidades.</span></li>
                <li><i class="fas fa-envelope-circle-xmark"></i><span>Utilizar o sistema para envio de spam ou comunicações não relacionadas.</span></li>
                <li><i class="fas fa-users-slash"></i><span>Compartilhar credenciais de acesso com terceiros.</span></li>
            </ul>
            <div class="legal-note danger">
                <i class="fas fa-shield-halved"></i>
                <div>
                    <strong>Consequências</strong>
                    O descumprimento pode resultar em suspensão ou exclusão da conta,
                    sem prejuízo das medidas legais cabíveis.
                </div>
            </div>
        </section>

        <!-- ---------- 5. PROPRIEDADE ---------- -->
        <section class="legal-section" id="propriedade">
            <div class="legal-section-header">
                <span class="legal-section-num">05</span>
                <h2>Propriedade intelectual</h2>
            </div>
            <p>
                Todo o conteúdo do sistema — código-fonte, layout, identidade visual,
                textos e imagens — é protegido por direitos autorais e demais leis de
                propriedade intelectual. O uso indevido pode gerar responsabilização
                civil e criminal.
            </p>
        </section>

        <!-- ---------- 6. SUSPENSÃO ---------- -->
        <section class="legal-section" id="suspensao">
            <div class="legal-section-header">
                <span class="legal-section-num">06</span>
                <h2>Suspensão e exclusão</h2>
            </div>
            <p>
                Contas que violarem estes termos podem ser <strong>suspensas</strong> ou
                <strong>excluídas</strong> a critério da administração, sem prejuízo
                de outras medidas cabíveis.
            </p>
            <p>
                Você também pode solicitar a exclusão voluntária a qualquer momento
                em <a href="<?= $basePath ?>/lgpd/meus-direitos">Meus Direitos (LGPD)</a>.
            </p>
        </section>

        <!-- ---------- 7. RESPONSABILIDADE ---------- -->
        <section class="legal-section" id="responsabilidade">
            <div class="legal-section-header">
                <span class="legal-section-num">07</span>
                <h2>Limitação de responsabilidade</h2>
            </div>
            <p>
                O sistema é fornecido <strong>"como está"</strong>. Não garantimos
                disponibilidade ininterrupta nem nos responsabilizamos por perdas
                decorrentes do uso, na máxima extensão permitida pela legislação
                aplicável.
            </p>
        </section>

        <!-- ---------- 8. ALTERAÇÕES ---------- -->
        <section class="legal-section" id="alteracoes">
            <div class="legal-section-header">
                <span class="legal-section-num">08</span>
                <h2>Alterações destes termos</h2>
            </div>
            <p>
                Estes termos podem ser atualizados a qualquer momento. O uso continuado
                após alterações implica concordância com a nova versão.
            </p>
            <div class="legal-note warning">
                <i class="fas fa-bell"></i>
                <div>
                    <strong>Você será notificado</strong>
                    Alterações relevantes geram uma notificação na área de
                    <a href="<?= $basePath ?>/notificacoes">Notificações</a>.
                </div>
            </div>
        </section>

        <!-- ---------- 9. FORO ---------- -->
        <section class="legal-section" id="foro">
            <div class="legal-section-header">
                <span class="legal-section-num">09</span>
                <h2>Foro competente</h2>
            </div>
            <p>
                Eventuais conflitos serão resolvidos no foro da comarca da instituição
                responsável pela operação do sistema.
            </p>
        </section>

        <!-- ---------- FOOTER ---------- -->
        <footer class="legal-footer">
            <span>&copy; <?= date('Y') ?> <?= h($appName) ?> — Todos os direitos reservados.</span>
            <a class="voltar-link" href="<?= $basePath ?>/">
                <i class="fas fa-arrow-left"></i> Voltar à página inicial
            </a>
        </footer>

    </article>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>