<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureNotClient
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if ($user && method_exists($user, 'inRole') && $user->inRole('client')) {
            return redirect()->route('three.demo');
        }

        return $next($request);
    }
}
