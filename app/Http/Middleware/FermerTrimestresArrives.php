<?php

namespace App\Http\Middleware;

use App\Models\Trimestre;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class FermerTrimestresArrives
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()) {
            Trimestre::fermerTrimestresArrives();
        }

        return $next($request);
    }
}
