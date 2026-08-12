<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\VerifyCsrfToken;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Asegurar que el grupo web incluye NUESTRA clase (además de lo que Laravel pone por defecto)
        $middleware->web(append: [
            VerifyCsrfToken::class,
        ]);
        
        $middleware->redirectUsersTo(function (\Illuminate\Http\Request $request) {
            $user = $request->user();
            if ($user && method_exists($user, 'inRole') && $user->inRole('client')) {
                return route('three.demo');
            }
            return config('platform.index') ? route(config('platform.index')) : '/admin';
        });
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();