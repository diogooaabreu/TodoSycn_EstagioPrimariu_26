<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ApiTokenAuth
{
    public function handle(Request $request, Closure $next)
    {
        // Verifica se existe token na sessão
        if (!session('api_token')) {
            return redirect('/login');
        }

        return $next($request);
    }
}
