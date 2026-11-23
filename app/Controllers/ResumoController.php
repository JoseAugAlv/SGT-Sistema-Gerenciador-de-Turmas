<?php
// app/Controllers/ResumoController.php
// Resumo semanal: prévia do próprio resumo (qualquer pessoa) e envio imediato para todos (master).
require_once __DIR__ . '/../Core/App.php';
require_once __DIR__ . '/../Services/ResumoSemanalService.php';
require_once __DIR__ . '/../Middleware/CsrfMiddleware.php';

class ResumoController
{
    /** Mostra o email que a pessoa receberia nesta semana. */
    public function previa()
    {
        $u = $_SESSION['usuario'];
        $s = new ResumoSemanalService();
        $d = $s->montar((int) $u['id']);
        header('Content-Type: text/html; charset=utf-8');
        if (!$d['tem']) {
            echo '<!doctype html><meta charset="utf-8"><body style="font-family:system-ui;padding:40px;text-align:center;color:#334155">'
               . '<h2>Nada para resumir agora</h2><p>Quando houver agenda, tarefas, enquetes ou avisos nas suas turmas, o resumo de segunda mostra aqui.</p>'
               . '<p><a href="' . App::getBasePath() . '/notificacoes/preferencias">Voltar</a></p></body>';
            exit;
        }
        echo $s->html($u, $d);
        exit;
    }

    /** Master: envia agora para quem ainda não recebeu nesta semana. */
    public function enviar()
    {
        CsrfMiddleware::validate();
        if (($_SESSION['usuario']['tipo'] ?? '') !== 'master') { http_response_code(403); exit('Apenas o master.'); }
        $r = (new ResumoSemanalService())->enviarLote(1000);
        $_SESSION['flash'] = ['tipo' => 'sucesso', 'mensagem' => "Resumo da semana: {$r['enviados']} enviado(s), {$r['vazios']} sem nada para resumir. Quem já recebeu nesta semana não recebe de novo."];
        header('Location: ' . App::getBasePath() . '/notificacoes/preferencias');
        exit;
    }
}
