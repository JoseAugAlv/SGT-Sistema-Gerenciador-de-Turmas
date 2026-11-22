<?php
// app/Controllers/TermosController.php

require_once __DIR__ . '/../Core/App.php';

class TermosController
{
    public function index()
    {
        $tituloPagina = 'Termos de Uso — ' . App::getName();
        $cssPagina    = 'lgpd.css';

        require __DIR__ . '/../Views/termos/termos.php';
    }
}