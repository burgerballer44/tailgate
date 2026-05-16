<?php

use App\Http\Middleware\CheckRole;
use App\Http\Middleware\EnsureUserIsGroupAdmin;
use App\Http\Middleware\EnsureUserIsGroupMember;
use App\Http\Middleware\FollowBelongsToGroup;
use App\Http\Middleware\MemberMustBeApproved;
use App\Http\Middleware\MemberMustBeInGroup;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'role' => CheckRole::class,
            'group.member' => EnsureUserIsGroupMember::class,
            'group.admin' => EnsureUserIsGroupAdmin::class,
            'group.member.belongs' => MemberMustBeInGroup::class,
            'group.member.approved' => MemberMustBeApproved::class,
            'group.follow.belongs' => FollowBelongsToGroup::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
