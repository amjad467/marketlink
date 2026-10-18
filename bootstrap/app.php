<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');
        $middleware->web(append: [
            \App\Http\Middleware\SetLocale::class,
        ]);
        $middleware->redirectGuestsTo(function ($request) {
            \App\Models\SecurityAuditLog::logViolation(
                'unauthenticated_access',
                'هەوڵی دەستگەیشتن بە ڕێڕەوی پارێزراو بەبێ چوونەژوورەوەی دروست.',
                $request,
                null,
                null,
                'critical',
                ['path' => $request->path()]
            );
            if ($request->is('/') || $request->routeIs('marketlink.index')) {
                return route('login');
            }
            abort(401, 'Unauthenticated.');
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\HttpException $e, $request) {
            if ($e->getStatusCode() === 419) {
                if ($request->expectsJson() || $request->wantsJson()) {
                    return response()->json(['message' => 'کاتی پەڕەکە بەسەرچووبوو، تکایە دووبارە تاقی بکەرەوە.'], 419);
                }
                return redirect()->back()
                    ->with('error', 'کاتی پەڕەکە بەسەرچووبوو (Session Expired). پەڕەکە نوێکرایەوە، تکایە دووبارە دوگمەکە دابگرە.')
                    ->withInput();
            }
        });

        $exceptions->render(function (\Illuminate\Session\TokenMismatchException $e, $request) {
            if ($request->expectsJson() || $request->wantsJson()) {
                return response()->json(['message' => 'کاتی پەڕەکە بەسەرچووبوو، تکایە دووبارە تاقی بکەرەوە.'], 419);
            }
            return redirect()->back()
                ->with('error', 'کاتی پەڕەکە بەسەرچووبوو (Session Expired). پەڕەکە نوێکرایەوە، تکایە دووبارە دوگمەکە دابگرە.')
                ->withInput();
        });
    })->create();
