<?php

use App\Http\Controllers\BetaFeedbackController;
use App\Http\Controllers\CreatorDashboardController;
use App\Http\Controllers\CreatorRecommendationController;
use App\Http\Controllers\CreatorSettingsController;
use App\Http\Controllers\CreatorSetupController;
use App\Http\Controllers\CreatorStarterSuggestionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RecommendationController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::view('/about', 'pages.about')->name('about');
Route::view('/faq', 'pages.faq')->name('faq');
Route::view('/contact', 'pages.contact')->name('contact');

Route::post('/beta-feedback', [BetaFeedbackController::class, 'store'])
    ->name('beta-feedback.store');

Route::get('/dashboard', [DashboardController::class, '__invoke'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/creator/create', [CreatorSetupController::class, 'create'])
        ->name('creators.create');
    Route::post('/creator', [CreatorSetupController::class, 'store'])
        ->name('creators.store');

    Route::get('/creator-pages', [CreatorDashboardController::class, 'index'])
        ->name('creators.index');

    Route::prefix('creator/{creator:slug}')
        ->name('creators.')
        ->scopeBindings()
        ->group(function () {
            Route::get('/starter-suggestions', [CreatorStarterSuggestionController::class, 'create'])
                ->name('starter-suggestions.create');
            Route::post('/starter-suggestions', [CreatorStarterSuggestionController::class, 'store'])
                ->name('starter-suggestions.store');
            Route::post('/starter-suggestions/skip', [CreatorStarterSuggestionController::class, 'skip'])
                ->name('starter-suggestions.skip');
            Route::get('/dashboard', [CreatorDashboardController::class, 'show'])
                ->name('dashboard');
            Route::get('/recommendations', [CreatorRecommendationController::class, 'index'])
                ->name('recommendations.index');
            Route::patch('/recommendations/{recommendation}', [CreatorRecommendationController::class, 'update'])
                ->name('recommendations.update');
            Route::patch('/recommendations/{recommendation}/status', [CreatorRecommendationController::class, 'updateStatus'])
                ->name('recommendations.status');
            Route::delete('/recommendations/{recommendation}', [CreatorRecommendationController::class, 'destroy'])
                ->name('recommendations.destroy');
            Route::patch('/recommendations/{recommendation}/hide', [CreatorRecommendationController::class, 'hide'])
                ->name('recommendations.hide');
            Route::get('/followers', [CreatorDashboardController::class, 'followers'])
                ->name('followers');
            Route::get('/settings', [CreatorSettingsController::class, 'edit'])
                ->name('settings.edit');
            Route::patch('/settings', [CreatorSettingsController::class, 'update'])
                ->name('settings.update');
            Route::patch('/deactivate', [CreatorSettingsController::class, 'deactivate'])
                ->name('deactivate');
        });

    Route::get('/{creator:slug}/submit', [RecommendationController::class, 'create'])
        ->name('recommendations.create');
    Route::post('/{creator:slug}/submit', [RecommendationController::class, 'store'])
        ->name('recommendations.store');
    Route::get('/{creator:slug}/youtube-metadata', [RecommendationController::class, 'youtubeMetadata'])
        ->name('recommendations.youtube-metadata');
    Route::post('/{creator:slug}/favorite', [RecommendationController::class, 'toggleFavorite'])
        ->name('creator.favorite');
    Route::post('/{creator:slug}/recommendations/{recommendation}/vote', [RecommendationController::class, 'toggleVote'])
        ->scopeBindings()
        ->name('recommendations.vote');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

Route::get('/{creator:slug}', [RecommendationController::class, 'showCreatorQueue'])
    ->name('creator.queue');
