<?php
use App\Http\Controllers\ProjectController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ProjectController::class, 'home'])->name('home');
Route::post('/projects', [ProjectController::class, 'store'])->name('project.store')->middleware('throttle:20,1');
Route::get('/s/{slug}', [ProjectController::class, 'site'])->name('site');

// The unguessable project UUID is the owner's access token (no login, see DECISIONS.md).
Route::prefix('projects/{project:uuid}')->middleware('throttle:60,1')->scopeBindings()->group(function () {
    Route::get('/', [ProjectController::class, 'workspace'])->name('workspace');
    Route::get('/preview', [ProjectController::class, 'preview'])->name('preview');
    Route::post('/sources', [ProjectController::class, 'addSource'])->name('source.add');
    Route::post('/analyze', [ProjectController::class, 'analyze'])->name('analyze')->middleware('throttle:8,1');
    Route::post('/questions/{question}', [ProjectController::class, 'answer'])->name('question.answer');
    Route::post('/facts/{fact}/reject', [ProjectController::class, 'rejectFact'])->name('fact.reject');
    Route::post('/generate', [ProjectController::class, 'generate'])->name('generate')->middleware('throttle:8,1');
    Route::put('/sections/{section}', [ProjectController::class, 'updateSection'])->name('section.update');
    Route::post('/sections/{section}/regenerate', [ProjectController::class, 'regenerate'])->name('section.regenerate')->middleware('throttle:12,1');
    Route::post('/publish', [ProjectController::class, 'publish'])->name('publish');
});
