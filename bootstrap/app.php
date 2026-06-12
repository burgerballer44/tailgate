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
use Illuminate\Routing\Middleware\SubstituteBindings;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Central map of middleware aliases used by route definitions.
        $aliases = [
            'role' => CheckRole::class,
            'user.group.member' => EnsureUserIsGroupMember::class,
            'user.group.admin' => EnsureUserIsGroupAdmin::class,
            'group.member.belongs' => MemberMustBeInGroup::class,
            'group.member.approved' => MemberMustBeApproved::class,
            'group.follow.belongs' => FollowBelongsToGroup::class,
        ];

        $middleware->alias($aliases);

        // Keep only group/member aliases here by removing the 'role' entry.
        // We use array_diff_key because $aliases is keyed by alias name,
        // and only these middleware depend on bound route models.
        // 'role' can run independently and does not need binding priority.
        // Each remaining middleware is explicitly anchored after
        // SubstituteBindings so route params like {group} and {member}
        // are model instances before these checks execute.
        foreach (array_diff_key($aliases, ['role' => true]) as $customMiddleware) {
            $middleware->appendToPriorityList(SubstituteBindings::class, $customMiddleware);
        }
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
