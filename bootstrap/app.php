<?php

use App\Http\Middleware\DenyAdminLearning;
use App\Http\Middleware\EnsureCategoryChosen;
use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Middleware\PreventImpersonatedWrites;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Bez zaufanych proxy: prawdziwe IP klienta ustawia nginx (real_ip z CF-Connecting-IP, tylko
        // od adresów Cloudflare - /etc/nginx/conf.d/cloudflare.conf), a HTTPS (także adresy assetów
        // Vite) wynika z fastcgi_params. Dawne trustProxies('*') pozwalało podrobić IP nagłówkiem.

        $middleware->alias([
            'admin' => EnsureUserIsAdmin::class,
            'no-admin' => DenyAdminLearning::class,
            'category' => EnsureCategoryChosen::class,
        ]);

        // Globalnie w grupie `web`, a nie na wybranych trasach: gwarancja „podgląd
        // niczego nie zapisze" jest warta tyle, ile najsłabsze ogniwo, więc nie może
        // zależeć od pamiętania o niej przy dodawaniu każdej nowej trasy.
        $middleware->web(append: [
            SetLocale::class,
            PreventImpersonatedWrites::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
