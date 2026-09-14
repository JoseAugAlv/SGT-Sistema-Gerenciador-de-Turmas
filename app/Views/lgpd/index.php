<?php
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
?>

<h1>Política de Privacidade (LGPD)</h1>
<p><small>Última atualização: <?= date('d/m/Y') ?></small></p>

<h2>1. Quem somos</h2>
<p>
    O <strong><?= h($appName) ?></strong> é um sistema de gestão de turmas
    operado pela instituição responsável. Atuamos como <em>controladores</em>
    dos dados pessoais tratados na plataforma.
</p>

<h2>2. Dados coletados</h2>
<ul>
    <li><strong>Cadastro:</strong> nome, email, senha (hash bcrypt), telefone, data de nascimento.</li>
    <li><strong>Uso:</strong> turmas, grupos, avaliações, atas, materiais, alertas, notificações.</li>
    <li><strong>Técnicos:</strong> IP, user-agent, data e hora de acesso (para auditoria e segurança).</li>
</ul>

<h2>3. Finalidades</h2>
<ul>
    <li>Permitir o funcionamento do sistema de gestão de turmas.</li>
    <li>Cumprir obrigações legais e regulatórias.</li>
    <li>Prevenir fraudes e garantir a segurança das informações.</li>
    <li>Enviar comunicações relacionadas à sua participação (alertas, prazos, avaliações).</li>
</ul>

<h2>4. Base legal</h2>
<p>
    O tratamento é realizado com base no <strong>consentimento</strong>
    (fornecido no cadastro) e no <strong>legítimo interesse</strong> para
    segurança e auditoria, conforme art. 7º da LGPD.
</p>

<h2>5. Compartilhamento</h2>
<p>
    Seus dados <strong>não</strong> são vendidos nem compartilhados com
    terceiros para fins comerciais. O compartilhamento ocorre apenas com
    prestadores essenciais (hospedagem, envio de email) e por obrigação legal.
</p>

<h2>6. Retenção</h2>
<p>
    Os dados são mantidos enquanto a conta estiver ativa ou pelo prazo exigido
    por lei. Registros de auditoria são mantidos por no mínimo 12 meses.
</p>

<h2>7. Direitos do titular</h2>
<p>Você pode, a qualquer momento:</p>
<ul>
    <li>Confirmar a existência de tratamento.</li>
    <li>Acessar seus dados.</li>
    <li>Corrigir dados incompletos ou desatualizados.</li>
    <li>Solicitar anonimização, bloqueio ou eliminação de dados desnecessários.</li>
    <li>Exportar seus dados em formato estruturado.</li>
    <li>Solicitar a exclusão da conta.</li>
    <li>Revogar o consentimento.</li>
</ul>

<p>
    Para exercer seus direitos, acesse
    <a href="<?= $basePath ?>/lgpd/meus-direitos">Meus Direitos (LGPD)</a>
    ou entre em contato com o encarregado de dados da instituição.
</p>

<h2>8. Segurança</h2>
<p>
    Utilizamos criptografia de senhas (bcrypt cost 12), prepared statements,
    proteção CSRF, validação de entrada e registro de auditoria para proteger
    seus dados.
</p>

<h2>9. Cookies e sessões</h2>
<p>
    Usamos cookies de sessão estritamente necessários para manter você logado.
    Não utilizamos cookies de rastreamento ou publicidade.
</p>

<h2>10. Contato do encarregado</h2>
<p>
    Para questões de privacidade, entre em contato com a administração da
    instituição responsável pela operação deste sistema.
</p>

<p><a href="<?= $basePath ?>/">Voltar à página inicial</a></p>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>