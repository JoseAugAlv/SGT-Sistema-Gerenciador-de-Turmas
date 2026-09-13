<?php
// app/Models/Exemplo.php

require_once __DIR__ . '/../Core/Model.php';

class Exemplo extends Model
{
    public function __construct()
    {
        parent::__construct();
        $this->table = 'exemplo';
    }

    // Métodos customizados podem ser adicionados aqui
}