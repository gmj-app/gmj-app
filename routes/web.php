<?php

use App\Http\Controllers\BetaFeedbackController;
use App\Http\Controllers\CreatorDashboardController;
use App\Http\Controllers\CreatorRecommendationController;
use App\Http\Controllers\CreatorSettingsController;
use App\Http\Controllers\CreatorSetupController;
use App\Http\Controllers\CreatorStarterSuggestionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InternalPlanTestingController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RecommendationAlternativeController;
use App\Http\Controllers\RecommendationController;
use App\Http\Controllers\ToolsAdminController;
use App\Http\Controllers\YoutubeToolsController;
use App\Http\Middleware\EnsurePublicProfileIsComplete;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::view('/about', 'pages.about')->name('about');
Route::view('/faq', 'pages.faq')->name('faq');
Route::view('/contact', 'pages.contact')->name('contact');

Route::post('/beta-feedback', [BetaFeedbackController::class, 'store'])
    ->name('beta-feedback.store');
Route::get('/internal/beta-feedback', [BetaFeedbackController::class, 'index'])
    ->name('internal.beta-feedback.index');
Route::post('/internal/beta-feedback/{feedback}/read', [BetaFeedbackController::class, 'markRead'])
    ->name('internal.beta-feedback.mark-read');
Route::post('/internal/beta-feedback/{feedback}/unread', [BetaFeedbackController::class, 'markUnread'])
    ->name('internal.beta-feedback.mark-unread');

Route::get('/dashboard', [DashboardController::class, '__invoke'])
    ->middleware(['auth', 'verified', EnsurePublicProfileIsComplete::class])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile/setup', [ProfileController::class, 'setup'])->name('profile.setup');
    Route::post('/profile/setup', [ProfileController::class, 'completeSetup'])->name('profile.setup.store');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::patch('/profile/public-identity', [ProfileController::class, 'updatePublicIdentity'])->name('profile.public-identity.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', EnsurePublicProfileIsComplete::class])->group(function () {
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
    Route::post('/{creator:slug}/recommendations/{recommendation}/withdraw', [RecommendationController::class, 'withdraw'])
        ->scopeBindings()
        ->name('recommendations.withdraw');
    Route::post('/{creator:slug}/recommendations/{recommendation}/alternatives', [RecommendationAlternativeController::class, 'store'])
        ->scopeBindings()
        ->name('recommendations.alternatives.store');
    Route::patch('/{creator:slug}/recommendations/{recommendation}/alternatives/{alternative}/accept', [RecommendationAlternativeController::class, 'accept'])
        ->scopeBindings()
        ->name('recommendations.alternatives.accept');
    Route::patch('/{creator:slug}/recommendations/{recommendation}/alternatives/{alternative}/dismiss', [RecommendationAlternativeController::class, 'dismiss'])
        ->scopeBindings()
        ->name('recommendations.alternatives.dismiss');

    Route::get('/internal/plan-testing', [InternalPlanTestingController::class, 'edit'])
        ->name('internal.plan-testing');
    Route::post('/internal/plan-testing', [InternalPlanTestingController::class, 'update']);

    Route::prefix('tools/admin')
        ->name('tools.')
        ->middleware('can:access-video-tools')
        ->group(function () {
            Route::get('/', ToolsAdminController::class)->name('admin');
            Route::get('/youtube', [YoutubeToolsController::class, 'index'])->name('youtube.index');
            Route::get('/youtube/connect', [YoutubeToolsController::class, 'connect'])->name('youtube.connect');
            Route::get('/youtube/callback', [YoutubeToolsController::class, 'callback'])->name('youtube.callback');
            Route::post('/youtube/preview', [YoutubeToolsController::class, 'preview'])->name('youtube.preview');
            Route::post('/youtube/apply', [YoutubeToolsController::class, 'apply'])->middleware('throttle:3,1')->name('youtube.apply');
        });
});

require __DIR__.'/auth.php';

Route::get('/{creator:slug}/published', [RecommendationController::class, 'published'])
    ->name('creators.published');

Route::get('/{creator:slug}', [RecommendationController::class, 'showCreatorQueue'])
    ->name('creator.queue');
