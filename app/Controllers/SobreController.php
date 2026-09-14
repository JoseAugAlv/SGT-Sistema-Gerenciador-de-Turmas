<?php
// app/Controllers/SobreController.php
require_once __DIR__ . '/../Core/App.php';

class SobreController
{
    public function index()
    {
        $tituloPagina = 'Sobre — ' . App::getName();
        require __DIR__ . '/../Views/sobre/index.php';
    }
}