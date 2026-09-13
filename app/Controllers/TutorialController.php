<?php
// app/Controllers/TutorialController.php

require_once __DIR__ . '/../Core/App.php';

class TutorialController
{
    public function index()
    {
        $tituloPagina = 'Tutorial do Sistema' . App::getName();
        $cssPagina = 'tutorial.css';
        require_once __DIR__ . '/../Views/tutorial/index.php';
    }
}