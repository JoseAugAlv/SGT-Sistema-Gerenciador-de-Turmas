<?php
require_once __DIR__ . '/../layouts/header.php';
require_once __DIR__ . '/../layouts/nav.php';
?>

<h1>Sobre o <?= h($appName) ?></h1>

<p>
    O <strong><?= h($appName) ?></strong> é um Sistema de Gestão de Turmas
    desenvolvido para organizar o dia a dia de professores e alunos: turmas,
    projetos, grupos, avaliações, atas e materiais.
</p>

<h2>Funcionalidades</h2>
<ul>
    <li>Autenticação com confirmação de email e primeiro acesso</li>
    <li>Turmas com código de acesso e bloqueio administrativo</li>
    <li>Projetos com etapas (modo etapa ou cronograma)</li>
    <li>Grupos com diretores (histórico completo de troca)</li>
    <li>Critérios com peso, tipo de avaliação e prazo</li>
    <li>Avaliações por diretor, pares, autoavaliação, coletiva e misto</li>
    <li>Atas com atividades, relatórios internos e validação</li>
    <li>Controle de materiais (uso, compra, preço médio ponderado)</li>
    <li>Alertas, notificações internas e por email</li>
    <li>Boletim em PDF, relatório geral e exportação em Excel</li>
    <li>Conformidade com a LGPD</li>
</ul>

<h2>Tecnologias</h2>
<ul>
    <li>PHP 8.0+ com arquitetura MVC</li>
    <li>MySQL / MariaDB com prepared statements (PDO)</li>
    <li>Composer para autoload PSR-4</li>
    <li>PHPMailer para envio de emails</li>
    <li>TCPDF para geração de PDFs</li>
    <li>FontAwesome para ícones</li>
</ul>

<h2>Contato</h2>
<p>
    Em caso de dúvidas, problemas técnicos ou questões relacionadas aos seus
    dados, consulte a <a href="<?= $basePath ?>/lgpd">Política de Privacidade</a>
    ou os <a href="<?= $basePath ?>/termos">Termos de Uso</a>.
</p>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>