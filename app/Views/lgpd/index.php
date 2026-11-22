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
            <li><a href="#quem-somos">Quem somos</a></li>
            <li><a href="#dados">Dados coletados</a></li>
            <li><a href="#finalidades">Finalidades</a></li>
            <li><a href="#base-legal">Base legal</a></li>
            <li><a href="#compartilhamento">Compartilhamento</a></li>
            <li><a href="#retencao">Retenção</a></li>
            <li><a href="#direitos">Seus direitos</a></li>
            <li><a href="#seguranca">Segurança</a></li>
            <li><a href="#cookies">Cookies e sessões</a></li>
            <li><a href="#contato">Contato</a></li>
        </ol>
    </nav>

    <!-- ============================================================ -->
    <!-- CONTEÚDO                                                      -->
    <!-- ============================================================ -->
    <article>

        <header class="legal-hero">
            <div class="eyebrow">
                <i class="fas fa-shield-halved"></i>
                Política de Privacidade
            </div>
            <h1>Como tratamos seus dados pessoais</h1>
            <div class="legal-meta">
                <span><i class="far fa-calendar"></i> Última atualização: <?= date('d/m/Y') ?></span>
                <span><i class="fas fa-scale-balanced"></i> Conforme a Lei nº 13.709/2018 (LGPD)</span>
            </div>
        </header>

        <!-- ---------- 1. QUEM SOMOS ---------- -->
        <section class="legal-section" id="quem-somos">
            <div class="legal-section-header">
                <span class="legal-section-num">01</span>
                <h2>Quem somos</h2>
            </div>
            <p>
                O <strong><?= h($appName) ?></strong> é um sistema de gestão de turmas
                operado pela instituição responsável. Atuamos como <em>controladores</em>
                dos dados pessoais tratados na plataforma, conforme definido pela LGPD.
            </p>
        </section>

        <!-- ---------- 2. DADOS ---------- -->
        <section class="legal-section" id="dados">
            <div class="legal-section-header">
                <span class="legal-section-num">02</span>
                <h2>Dados coletados</h2>
            </div>
            <p>Coletamos apenas o mínimo necessário para o funcionamento do sistema:</p>
            <ul class="legal-list">
                <li><i class="fas fa-user"></i><span><strong>Cadastro:</strong> nome, email, senha (hash bcrypt), telefone, data de nascimento.</span></li>
                <li><i class="fas fa-gears"></i><span><strong>Uso:</strong> turmas, grupos, avaliações, atas, materiais, alertas, notificações.</span></li>
                <li><i class="fas fa-server"></i><span><strong>Técnicos:</strong> IP, user-agent, data e hora de acesso (auditoria e segurança).</span></li>
            </ul>
        </section>

        <!-- ---------- 3. FINALIDADES ---------- -->
        <section class="legal-section" id="finalidades">
            <div class="legal-section-header">
                <span class="legal-section-num">03</span>
                <h2>Finalidades do tratamento</h2>
            </div>
            <ul class="legal-list">
                <li><i class="fas fa-graduation-cap"></i><span>Permitir o funcionamento do sistema de gestão de turmas.</span></li>
                <li><i class="fas fa-scale-balanced"></i><span>Cumprir obrigações legais e regulatórias.</span></li>
                <li><i class="fas fa-shield-halved"></i><span>Prevenir fraudes e garantir a segurança das informações.</span></li>
                <li><i class="fas fa-paper-plane"></i><span>Enviar comunicações relacionadas à sua participação (alertas, prazos, avaliações).</span></li>
            </ul>
        </section>

        <!-- ---------- 4. BASE LEGAL ---------- -->
        <section class="legal-section" id="base-legal">
            <div class="legal-section-header">
                <span class="legal-section-num">04</span>
                <h2>Base legal</h2>
            </div>
            <p>
                O tratamento é realizado com base no <strong>consentimento</strong>
                (fornecido no cadastro) e no <strong>legítimo interesse</strong> para
                segurança e auditoria, conforme art. 7º da LGPD.
            </p>
            <div class="legal-note info">
                <i class="fas fa-circle-info"></i>
                <div>
                    <strong>Você pode revogar o consentimento a qualquer momento</strong>
                    solicitando a exclusão da sua conta pela página
                    <a href="<?= $basePath ?>/lgpd/meus-direitos">Meus Direitos (LGPD)</a>.
                </div>
            </div>
        </section>

        <!-- ---------- 5. COMPARTILHAMENTO ---------- -->
        <section class="legal-section" id="compartilhamento">
            <div class="legal-section-header">
                <span class="legal-section-num">05</span>
                <h2>Compartilhamento</h2>
            </div>
            <p>
                Seus dados <strong>não são vendidos</strong> nem compartilhados com
                terceiros para fins comerciais. O compartilhamento ocorre apenas com
                prestadores essenciais (hospedagem, envio de email) e por obrigação legal.
            </p>
        </section>

        <!-- ---------- 6. RETENÇÃO ---------- -->
        <section class="legal-section" id="retencao">
            <div class="legal-section-header">
                <span class="legal-section-num">06</span>
                <h2>Retenção</h2>
            </div>
            <p>
                Os dados são mantidos enquanto a conta estiver ativa ou pelo prazo
                exigido por lei. Registros de auditoria são mantidos por no mínimo
                <strong>12 meses</strong>, mesmo após a exclusão da conta, de forma
                anonimizada.
            </p>
        </section>

        <!-- ---------- 7. DIREITOS ---------- -->
        <section class="legal-section" id="direitos">
            <div class="legal-section-header">
                <span class="legal-section-num">07</span>
                <h2>Seus direitos como titular</h2>
            </div>
            <p>Conforme o art. 18 da LGPD, você pode, a qualquer momento:</p>

            <div class="direitos-grid">
                <div class="direito-card">
                    <span class="direito-icon"><i class="fas fa-check-circle"></i></span>
                    <div class="direito-info">
                        <strong>Confirmação</strong>
                        <small>Confirmar se tratamos seus dados.</small>
                    </div>
                </div>
                <div class="direito-card">
                    <span class="direito-icon"><i class="fas fa-eye"></i></span>
                    <div class="direito-info">
                        <strong>Acesso</strong>
                        <small>Consultar todos os dados que temos sobre você.</small>
                    </div>
                </div>
                <div class="direito-card">
                    <span class="direito-icon"><i class="fas fa-pen"></i></span>
                    <div class="direito-info">
                        <strong>Correção</strong>
                        <small>Corrigir dados incompletos ou desatualizados.</small>
                    </div>
                </div>
                <div class="direito-card">
                    <span class="direito-icon"><i class="fas fa-eraser"></i></span>
                    <div class="direito-info">
                        <strong>Anonimização ou exclusão</strong>
                        <small>De dados desnecessários ou excessivos.</small>
                    </div>
                </div>
                <div class="direito-card">
                    <span class="direito-icon"><i class="fas fa-download"></i></span>
                    <div class="direito-info">
                        <strong>Portabilidade</strong>
                        <small>Exportar seus dados em formato estruturado (JSON).</small>
                    </div>
                </div>
                <div class="direito-card">
                    <span class="direito-icon"><i class="fas fa-trash"></i></span>
                    <div class="direito-info">
                        <strong>Exclusão da conta</strong>
                        <small>Solicitar a remoção definitiva dos dados.</small>
                    </div>
                </div>
                <div class="direito-card">
                    <span class="direito-icon"><i class="fas fa-ban"></i></span>
                    <div class="direito-info">
                        <strong>Revogação</strong>
                        <small>Revogar o consentimento a qualquer momento.</small>
                    </div>
                </div>
                <div class="direito-card">
                    <span class="direito-icon"><i class="fas fa-flag"></i></span>
                    <div class="direito-info">
                        <strong>Oposição</strong>
                        <small>Opor-se a tratamentos específicos.</small>
                    </div>
                </div>
            </div>

            <p>
                Para exercer qualquer um destes direitos, acesse a página
                <a href="<?= $basePath ?>/lgpd/meus-direitos">Meus Direitos (LGPD)</a>
                ou entre em contato com o encarregado de dados da instituição.
            </p>
        </section>

        <!-- ---------- 8. SEGURANÇA ---------- -->
        <section class="legal-section" id="seguranca">
            <div class="legal-section-header">
                <span class="legal-section-num">08</span>
                <h2>Segurança</h2>
            </div>
            <p>Adotamos medidas técnicas para proteger seus dados:</p>
            <ul class="legal-list">
                <li><i class="fas fa-key"></i><span>Criptografia de senhas com <strong>bcrypt cost 12</strong>.</span></li>
                <li><i class="fas fa-database"></i><span>Prepared statements em todas as consultas (PDO).</span></li>
                <li><i class="fas fa-shield"></i><span>Proteção CSRF em todos os formulários.</span></li>
                <li><i class="fas fa-clipboard-list"></i><span>Registro de auditoria das ações administrativas.</span></li>
                <li><i class="fas fa-user-lock"></i><span>Controle de acesso por papel (master, representante, diretor, aluno).</span></li>
            </ul>
        </section>

        <!-- ---------- 9. COOKIES ---------- -->
        <section class="legal-section" id="cookies">
            <div class="legal-section-header">
                <span class="legal-section-num">09</span>
                <h2>Cookies e sessões</h2>
            </div>
            <p>
                Usamos apenas cookies de sessão <strong>estritamente necessários</strong>
                para manter você logado. <strong>Não utilizamos</strong> cookies de
                rastreamento, publicidade ou analytics de terceiros.
            </p>
        </section>

        <!-- ---------- 10. CONTATO ---------- -->
        <section class="legal-section" id="contato">
            <div class="legal-section-header">
                <span class="legal-section-num">10</span>
                <h2>Contato do encarregado</h2>
            </div>
            <p>
                Para questões de privacidade, entre em contato com a administração da
                instituição responsável pela operação deste sistema.
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