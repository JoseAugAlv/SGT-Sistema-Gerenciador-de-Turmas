<?php
// app/Controllers/DashboardController.php

require_once __DIR__ . '/../Core/App.php';
require_once __DIR__ . '/../Models/Usuario.php';
require_once __DIR__ . '/../Models/Notificacao.php';

class DashboardController
{
    private $usuario;

    public function __construct()
    {
        $this->usuario = new Usuario();
    }

    public function index()
    {
        if (!isset($_SESSION['usuario'])) {
            header('Location: ' . App::getBasePath() . '/login');
            exit;
        }

        $appName = App::getName();
        $basePath = App::getBasePath();
        
        $totalUsuarios = $this->usuario->getTotal();
        $totalAtivos = $this->usuario->getTotalAtivos();
        $perfis = $this->usuario->getCountByPerfil();
        
        $tituloPagina = 'Dashboard - ' . $appName;
        $cssPagina = 'home.css';
        
        // CORRIGIDO: caminho absoluto
        require __DIR__ . '/../Views/home/dashboard.php';
    }
}