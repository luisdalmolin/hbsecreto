<?php

use App\Http\Controllers\Api\V1\AssignmentController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\DrawConstraintController;
use App\Http\Controllers\Api\V1\DrawController;
use App\Http\Controllers\Api\V1\EditionController;
use App\Http\Controllers\Api\V1\EditionLifecycleController;
use App\Http\Controllers\Api\V1\EditionParticipantController;
use App\Http\Controllers\Api\V1\GroupController;
use App\Http\Controllers\Api\V1\GroupMemberController;
use App\Http\Controllers\Api\V1\InvitationController;
use App\Http\Middleware\SetUserLocale;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function (): void {
    Route::post('auth/login', [AuthController::class, 'login'])
        ->middleware('throttle:login')
        ->name('auth.login');
    Route::post('auth/register', [AuthController::class, 'register'])
        ->middleware('throttle:register')
        ->name('auth.register');
    Route::get('invitations/{token}', [InvitationController::class, 'show'])
        ->middleware('throttle:invitations')
        ->name('invitations.show');

    Route::middleware(['auth:sanctum', SetUserLocale::class])->group(function (): void {
        Route::post('auth/logout', [AuthController::class, 'logout'])->name('auth.logout');
        Route::get('me', [AuthController::class, 'me'])->name('me');
        Route::patch('me', [AuthController::class, 'updateMe'])->name('me.update');
        Route::get('groups', [GroupController::class, 'index'])->name('groups.index');
        Route::post('groups', [GroupController::class, 'store'])->name('groups.store');
        Route::get('groups/{group}', [GroupController::class, 'show'])->name('groups.show');
        Route::patch('groups/{group}', [GroupController::class, 'update'])->name('groups.update');
        Route::delete('groups/{group}', [GroupController::class, 'destroy'])->name('groups.destroy');
        Route::post('invitations/{token}/claim', [InvitationController::class, 'claim'])
            ->middleware('throttle:invitations')
            ->name('invitations.claim');

        Route::scopeBindings()->group(function (): void {
            Route::get('groups/{group}/members', [GroupMemberController::class, 'index'])->name('groups.members.index');
            Route::post('groups/{group}/members', [GroupMemberController::class, 'store'])->name('groups.members.store');
            Route::patch('groups/{group}/members/{member}', [GroupMemberController::class, 'update'])->name('groups.members.update');
            Route::put('groups/{group}/members/{member}/deactivate', [GroupMemberController::class, 'deactivate'])->name('groups.members.deactivate');
            Route::put('groups/{group}/members/{member}/reactivate', [GroupMemberController::class, 'reactivate'])->name('groups.members.reactivate');
            Route::post('groups/{group}/members/{member}/invite', [GroupMemberController::class, 'invite'])->name('groups.members.invite');

            Route::get('groups/{group}/editions', [EditionController::class, 'index'])->name('groups.editions.index');
            Route::post('groups/{group}/editions', [EditionController::class, 'store'])->name('groups.editions.store');
            Route::get('groups/{group}/editions/{edition}', [EditionController::class, 'show'])->name('groups.editions.show');
            Route::patch('groups/{group}/editions/{edition}', [EditionController::class, 'update'])->name('groups.editions.update');
            Route::delete('groups/{group}/editions/{edition}', [EditionController::class, 'destroy'])->name('groups.editions.destroy');
            Route::get('groups/{group}/editions/{edition}/participants', [EditionParticipantController::class, 'index'])->name('groups.editions.participants.index');
            Route::post('groups/{group}/editions/{edition}/participants', [EditionParticipantController::class, 'store'])->name('groups.editions.participants.store');
            Route::delete('groups/{group}/editions/{edition}/participants/{participant}', [EditionParticipantController::class, 'destroy'])->name('groups.editions.participants.destroy');
            Route::put('groups/{group}/editions/{edition}/open', [EditionLifecycleController::class, 'open'])->name('groups.editions.open');
            Route::put('groups/{group}/editions/{edition}/reveal', [EditionLifecycleController::class, 'reveal'])->name('groups.editions.reveal');
            Route::put('groups/{group}/editions/{edition}/archive', [EditionLifecycleController::class, 'archive'])->name('groups.editions.archive');
            Route::get('groups/{group}/editions/{edition}/draw-constraints', [DrawConstraintController::class, 'index'])->name('groups.editions.draw_constraints.index');
            Route::post('groups/{group}/editions/{edition}/draw-constraints', [DrawConstraintController::class, 'store'])->name('groups.editions.draw_constraints.store');
            Route::delete('groups/{group}/editions/{edition}/draw-constraints/{drawConstraint}', [DrawConstraintController::class, 'destroy'])->name('groups.editions.draw_constraints.destroy');
            Route::get('groups/{group}/editions/{edition}/draw/preflight', [DrawController::class, 'preflight'])->name('groups.editions.draw.preflight');
            Route::post('groups/{group}/editions/{edition}/draw', [DrawController::class, 'store'])->name('groups.editions.draw.store');
            Route::get('groups/{group}/editions/{edition}/my-assignment', [AssignmentController::class, 'mine'])->name('groups.editions.my_assignment.show');
            Route::get('groups/{group}/editions/{edition}/assignments', [AssignmentController::class, 'index'])->name('groups.editions.assignments.index');
        });
    });
});
