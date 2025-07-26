<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LogBroadcastAuth
{
    public function handle(Request $request, Closure $next)
    {
        Log::info('Broadcasting auth request received', [
            'url' => $request->url(),
            'method' => $request->method(),
            'headers' => [
                'csrf' => $request->header('X-CSRF-TOKEN'),
                'content-type' => $request->header('Content-Type'),
                'accept' => $request->header('Accept'),
            ],
            'body' => $request->all(),
            'user_id' => auth()->id(),
            'is_authenticated' => auth()->check()
        ]);

        return $next($request);
    }
} 