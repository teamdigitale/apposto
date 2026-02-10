<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\OtherController;
use App\Http\Controllers\PresenceController;
use App\Http\Controllers\LeaveRequestController;
use App\Http\Controllers\ProjectMembershipController;
use App\Http\Controllers\AbsenceDashboardController;

Route::get('/', function () {
    return view('welcome');
});

Route::post('/contatti/send', [OtherController::class, 'send'])->name('contact.send');
Route::get('/contatti', [OtherController::class, 'showForm'])->name('contact.form');


Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/presences/overview', [\App\Http\Controllers\PresenceOverviewController::class, 'index'])->name('presences.overview');
    Route::get('/contacts', [ContactController::class, 'index'])->name('contacts.index');
    Route::post('/bookings/multi-cancel', [BookingController::class, 'multiCancel'])->name('bookings.multiCancel');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/booking', [BookingController::class, 'stepOne'])->name('booking.step.one');
    
    Route::get('/booking/step-two', [BookingController::class, 'stepOne']);
    Route::get('/booking/step-three', [BookingController::class, 'stepOne']);
    Route::get('/booking/complete', [BookingController::class, 'myBookings']);

    Route::post('/booking/step-two', [BookingController::class, 'stepTwo'])->name('booking.step.two');
    Route::post('/booking/step-three', [BookingController::class, 'stepThree'])->name('booking.step.three');

    Route::get('/api/desks/{plan}', [BookingController::class, 'getDesks']);
    
    Route::get('/booking/get-plans/{workplace_id}', [BookingController::class, 'getPlans']);
    Route::post('/booking/complete', [BookingController::class, 'complete'])->name('booking.complete');

    Route::get('/my-bookings', [BookingController::class, 'myBookings'])->name('booking.my');
    
    Route::post('/override-desk/{desk}', [BookingController::class, 'getOtherDesk']);

    Route::get('/booking/history', [BookingController::class, 'history'])->name('bookings.history');
    Route::get('/booking/current', [BookingController::class, 'current'])->name('bookings.current');

    Route::get('/presences', [PresenceController::class, 'index'])->name('presences.index');
    Route::post('/presences', [PresenceController::class, 'store'])->name('presences.store');
    Route::get('/api/presences/stats', [PresenceController::class, 'getStats'])->name('presences.stats');

    Route::post('/booking/cancel/{id}', [BookingController::class, 'cancel'])->name('bookings.cancel');

    Route::post('/check-workstation-availability', [ BookingController::class, 'checkWorkstationAvailability'])->name('booking.checkWorkstationAvailability');
    Route::post('/check-desk-availability', [BookingController::class, 'checkAvailability'])->name('desk.checkAvailability');
    Route::get('/booking/check-desk', function () { return view('booking.check-desk');   })->name('desk.check');

    Route::get('/projects', [ProjectMembershipController::class, 'index'])->name('projects.index');
    Route::get('/projects/{project}', [ProjectMembershipController::class, 'show'])->name('projects.show');
    Route::post('/projects/{project}/join', [ProjectMembershipController::class, 'join'])->name('projects.join');
    Route::delete('/projects/{project}/leave', [ProjectMembershipController::class, 'leave'])->name('projects.leave');
    Route::patch('/projects/{project}/update-role', [ProjectMembershipController::class, 'updateRole'])->name('projects.update-role');
    
    Route::get('/absences/dashboard', [AbsenceDashboardController::class, 'index'])->name('absences.dashboard');
    Route::get('/absences/project/{project}', [AbsenceDashboardController::class, 'projectAbsences'])->name('absences.project');
    Route::get('/absences/export', [AbsenceDashboardController::class, 'export'])->name('absences.export');
    Route::get('/api/absences/chart-data', [AbsenceDashboardController::class, 'getChartData'])->name('absences.chart-data');
});

require __DIR__.'/auth.php';