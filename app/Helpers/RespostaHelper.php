<?php
// app/Helpers/RespostaHelper.php
// Responde ao navegador (redireciona) e continua rodando: para tarefas lentas como notificar a turma toda por email.
require_once __DIR__ . '/../Core/App.php';

class RespostaHelper
{
    public static function redirecionarEContinuar(string $path): void
    {
        ignore_user_abort(true);
        @set_time_limit(300);
        session_write_close();
        header('Location: ' . App::getBasePath() . $path);
        header('Connection: close');
        header('Content-Length: 0');
        while (ob_get_level() > 0) ob_end_flush();
        flush();
    }

    /** Responde um JSON e continua rodando (ex.: avisar a turma por email depois de salvar). */
    public static function jsonEContinuar(array $dados): void
    {
        ignore_user_abort(true);
        @set_time_limit(300);
        session_write_close();
        $corpo = json_encode($dados, JSON_UNESCAPED_UNICODE);
        header('Content-Type: application/json; charset=utf-8');
        header('Connection: close');
        header('Content-Length: ' . strlen($corpo));
        echo $corpo;
        while (ob_get_level() > 0) ob_end_flush();
        flush();
    }
}
