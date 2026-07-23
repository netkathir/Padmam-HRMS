<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Console\Scheduling\Schedule;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'auth'       => \App\Http\Middleware\Authenticate::class,
            'role'       => \App\Http\Middleware\CheckRole::class,
            'permission' => \App\Http\Middleware\CheckPermission::class,
            'force.password.change' => \App\Http\Middleware\ForcePasswordChange::class,
            'require.branch' => \App\Http\Middleware\RequireBranchExists::class,
            'feature'    => \App\Http\Middleware\EnsureFeatureEnabled::class,
        ]);
    })
    ->withSchedule(function (Schedule $schedule): void {
        // Runs every 1 January at 00:00 — creates the new year's opening
        // leave balance for every active employee (see
        // ResetLeaveBalancesForNewYear for the no-carry-forward policy).
        $schedule->command('leave:reset-yearly-balances')
            ->yearlyOn(1, 1, '00:00')
            ->name('reset-leave-balances-yearly')
            ->onOneServer();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
