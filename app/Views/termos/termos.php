<?php
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
?>

<h1>Termos de Uso</h1>
<p><small>Última atualização: <?= date('d/m/Y') ?></small></p>

<h2>1. Aceitação</h2>
<p>
    Ao criar uma conta e utilizar o <?= h($appName) ?>, você declara ter lido
    e aceito integralmente estes Termos de Uso e a Política de Privacidade.
</p>

<h2>2. Cadastro</h2>
<p>
    Você é responsável pela veracidade dos dados informados no cadastro e pela
    guarda de suas credenciais de acesso. O compartilhamento de credenciais é
    proibido.
</p>

<h2>3. Uso permitido</h2>
<p>
    O sistema deve ser utilizado apenas para finalidades educacionais
    relacionadas à gestão de turmas, projetos, grupos, avaliações e demais
    recursos disponíveis.
</p>

<h2>4. Condutas proibidas</h2>
<ul>
    <li>Tentar acessar dados de outras pessoas sem autorização.</li>
    <li>Inserir conteúdo ofensivo, ilegal ou que viole direitos de terceiros.</li>
    <li>Realizar engenharia reversa, burlar mecanismos de segurança ou explorar vulnerabilidades.</li>
    <li>Utilizar o sistema para envio de spam ou comunicações não relacionadas.</li>
</ul>

<h2>5. Propriedade intelectual</h2>
<p>
    Todo o conteúdo do sistema (código, layout, identidade visual) é protegido.
    O uso indevido pode gerar responsabilização civil e criminal.
</p>

<h2>6. Suspensão e exclusão</h2>
<p>
    Contas que violarem estes termos podem ser suspensas ou excluídas a
    critério da administração, sem prejuízo de outras medidas cabíveis.
</p>

<h2>7. Limitação de responsabilidade</h2>
<p>
    O sistema é fornecido "como está". Não garantimos disponibilidade
    ininterrupta nem nos responsabilizamos por perdas decorrentes do uso.
</p>

<h2>8. Alterações</h2>
<p>
    Estes termos podem ser atualizados a qualquer momento. O uso continuado
    após alterações implica concordância com a nova versão.
</p>

<h2>9. Foro</h2>
<p>
    Eventuais conflitos serão resolvidos no foro da comarca da instituição
    responsável pela operação do sistema.
</p>

<p><a href="<?= $basePath ?>/">Voltar à página inicial</a></p>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>