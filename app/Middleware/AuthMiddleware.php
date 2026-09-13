<?php
// app/Middleware/AuthMiddleware.php

require_once __DIR__ . '/../Core/App.php';
require_once __DIR__ . '/../Core/Auth.php';

class AuthMiddleware
{
    public static function handle()
    {
        if (!Auth::check()) {
            header('Location: ' . App::getBasePath() . '/login');
            exit;
        }
    }
}