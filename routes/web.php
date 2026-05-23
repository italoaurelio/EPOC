<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AssignmentController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EventCandidateController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\EventFunctionController;
use App\Http\Controllers\EventEnvironmentSubmissionController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\GroupMembershipController;
use App\Http\Controllers\InviteLinkController;
use App\Http\Controllers\InviteRegistrationController;
use App\Http\Controllers\InsightsController;
use App\Http\Controllers\SubstitutionController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/convites/{token}', [InviteRegistrationController::class, 'show'])->name('invites.show');
Route::post('/convites/{token}/cadastro', [InviteRegistrationController::class, 'register'])->name('invites.register');

Route::get('/dashboard', [DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/grupos', [GroupController::class, 'index'])->name('groups.index');
    Route::get('/eventos', [EventController::class, 'index'])->name('events.index');
    Route::get('/insights', [InsightsController::class, 'index'])->name('insights.index');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::post('/groups', [GroupController::class, 'store'])->name('groups.store');
    Route::patch('/groups/{group}', [GroupController::class, 'update'])->name('groups.update');
    Route::get('/groups/{group}/memberships', [GroupMembershipController::class, 'index'])->name('groups.memberships.index');
    Route::patch('/groups/{group}/memberships/{membership}', [GroupMembershipController::class, 'update'])->name('groups.memberships.update');
    Route::post('/groups/{group}/invites', [InviteLinkController::class, 'store'])->name('groups.invites.store');
    Route::post('/groups/{group}/events', [EventController::class, 'store'])->name('groups.events.store');
    Route::patch('/events/{event}', [EventController::class, 'update'])->name('events.update');
    Route::get('/groups/{group}/functions', [EventFunctionController::class, 'index'])->name('groups.functions.index');
    Route::post('/groups/{group}/functions', [EventFunctionController::class, 'store'])->name('groups.functions.store');
    Route::post('/events/{event}/environments', [EventEnvironmentSubmissionController::class, 'store'])->name('events.environments.store');
    Route::post('/event-candidates', [EventCandidateController::class, 'store'])->name('event-candidates.store');
    Route::post('/event-slots/{slot}/decide-candidate', [EventCandidateController::class, 'decide'])->name('event-slots.decide-candidate');
    Route::post('/assignments', [AssignmentController::class, 'store'])->name('assignments.store');
    Route::post('/attendance/{attendance}/confirm', [AttendanceController::class, 'confirm'])->name('attendance.confirm');
    Route::patch('/attendance/{attendance}/manual', [AttendanceController::class, 'manualUpdate'])->name('attendance.manual');
    Route::post('/substitutions', [SubstitutionController::class, 'store'])->name('substitutions.store');
});

require __DIR__.'/auth.php';
