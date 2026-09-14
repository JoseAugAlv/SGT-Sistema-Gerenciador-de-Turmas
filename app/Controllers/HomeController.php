<?php
// app/Controllers/HomeController.php
require_once __DIR__ . '/../Core/App.php';

class HomeController
{
    public function index()
    {
        $logado = !empty($_SESSION['usuario']);

        if ($logado) {
            $dados = [
                'tituloPagina' => 'Painel — ' . App::getName(),
                'usuario'      => $_SESSION['usuario'],
            ];
            extract($dados);
            require __DIR__ . '/../Views/home/index.php';
        } else {
            $dados = ['tituloPagina' => App::getName()];
            extract($dados);
            require __DIR__ . '/../Views/home/landing.php';
        }
    }
}