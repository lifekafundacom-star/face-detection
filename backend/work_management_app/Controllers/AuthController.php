<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\View;

class AuthController
{
    public function showLogin(Request $request = null, array $params = []): void
    {
        View::render('auth/login', ['title' => 'Login', 'page' => 'login']);
    }

    public function login(Request $request, array $params = []): void
    {
        if (Auth::attempt((string) $request->input('email', ''), (string) $request->input('password', ''))) {
            Response::redirect('/dashboard');
        }

        View::render('auth/login', [
            'title' => 'Login',
            'page' => 'login',
            'error' => 'Invalid credentials.',
        ]);
    }

    public function logout(Request $request = null, array $params = []): void
    {
        Auth::logout();
        Response::redirect('/');
    }
}
