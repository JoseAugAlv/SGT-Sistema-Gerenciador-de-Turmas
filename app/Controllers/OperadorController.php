<?php
// app/Controllers/OperadorController.php

require_once __DIR__ . '/../Core/App.php';
require_once __DIR__ . '/../Models/Usuario.php';

class OperadorController
{
    private $usuario;

    public function __construct()
    {
        $this->usuario = new Usuario();
    }

    public function atividades()
    {
        if (!isset($_SESSION['usuario']) || $_SESSION['usuario']['role'] != 3) {
            header('Location: ' . App::getBasePath() . '/');
            exit;
        }

        $tituloPagina = 'Minhas Atividades - ' . App::getName();
        $cssPagina = 'home.css';
        
        require '../app/Views/operador/atividades.php';
    }
}