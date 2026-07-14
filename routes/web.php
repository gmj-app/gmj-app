<?php

use App\Http\Controllers\AdvertisementClickController;
use App\Http\Controllers\BetaFeedbackController;
use App\Http\Controllers\CreatorAccoladeController;
use App\Http\Controllers\CreatorDashboardController;
use App\Http\Controllers\CreatorRecommendationController;
use App\Http\Controllers\CreatorSettingsController;
use App\Http\Controllers\CreatorSetupController;
use App\Http\Controllers\CreatorStarterSuggestionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GuideAccoladeController;
use App\Http\Controllers\GuideAccoladeIndexController;
use App\Http\Controllers\GuideRequestPresentationController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\InternalPlanTestingController;
use App\Http\Controllers\MyActivityController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicGuideProfileController;
use App\Http\Controllers\RecommendationAlternativeController;
use App\Http\Controllers\RecommendationController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SuperAdmin\AccoladeController as SuperAdminAccoladeController;
use App\Http\Controllers\SuperAdmin\AnnouncementController;
use App\Http\Controllers\SuperAdmin\CreatorController as SuperAdminCreatorController;
use App\Http\Controllers\SuperAdmin\CreatorRequestController as SuperAdminCreatorRequestController;
use App\Http\Controllers\SuperAdmin\DashboardController as SuperAdminDashboardController;
use App\Http\Controllers\SuperAdmin\HomepageAdvertisementController;
use App\Http\Controllers\SuperAdmin\TestNotificationController;
use App\Http\Controllers\ThemePreferenceController;
use App\Http\Controllers\ToolsAdminController;
use App\Http\Controllers\YoutubeToolsController;
use App\Http\Middleware\EnsurePublicProfileIsComplete;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/ads/{advertisement}/click', AdvertisementClickController::class)->name('ads.click');
Route::get('/search', SearchController::class)->name('search.index');
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
Route::post('/internal/beta-feedback/{feedback}/spam', [BetaFeedbackController::class, 'spam'])
    ->name('internal.beta-feedback.spam');
Route::post('/internal/beta-feedback/{feedback}/restore', [BetaFeedbackController::class, 'restore'])
    ->name('internal.beta-feedback.restore');

Route::get('/dashboard', [DashboardController::class, '__invoke'])
    ->middleware(['auth', 'verified', EnsurePublicProfileIsComplete::class])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::patch('/profile/theme', ThemePreferenceController::class)->name('profile.theme.update');
    Route::get('/accolades', GuideAccoladeIndexController::class)->name('accolades.index');
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
    Route::get('/notifications/{notification}/open', [NotificationController::class, 'open'])->name('notifications.open');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markRead'])->name('notifications.read');
    Route::post('/notifications/{notification}/unread', [NotificationController::class, 'markUnread'])->name('notifications.unread');
    Route::get('/my-activity', [MyActivityController::class, 'index'])->name('activity.index');
    Route::get('/profile/setup', [ProfileController::class, 'setup'])->name('profile.setup');
    Route::post('/profile/setup', [ProfileController::class, 'completeSetup'])->name('profile.setup.store');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::patch('/profile/public-identity', [ProfileController::class, 'updatePublicIdentity'])->name('profile.public-identity.update');
    Route::patch('/profile/display-name', [ProfileController::class, 'updateDisplayNamePrompt'])->name('profile.display-name.update');
    Route::post('/profile/display-name-prompt/dismiss', [ProfileController::class, 'dismissDisplayNamePrompt'])->name('profile.display-name-prompt.dismiss');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::patch('/profile/accolades/featured', [GuideAccoladeController::class, 'feature'])->name('profile.accolades.featured');
});

Route::middleware(['auth', EnsurePublicProfileIsComplete::class])->group(function () {
    Route::get('/requests/{recommendation}/presentation/edit', [GuideRequestPresentationController::class, 'edit'])->name('requests.presentation.edit');
    Route::patch('/requests/{recommendation}/presentation', [GuideRequestPresentationController::class, 'update'])->name('requests.presentation.update');
    Route::post('/requests/{recommendation}/corrections', [GuideRequestPresentationController::class, 'correction'])->name('requests.corrections.store');
    Route::post('/requests/{recommendation}/corrections/{correction}/cancel', [GuideRequestPresentationController::class, 'cancel'])->name('requests.corrections.cancel');
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
            Route::delete('/recommendations/{recommendation}/presentation', [CreatorRecommendationController::class, 'clearPresentation'])
                ->name('recommendations.presentation.clear');
            Route::post('/recommendations/{recommendation}/presentation/{revision}/revert', [CreatorRecommendationController::class, 'revertPresentation'])
                ->whereNumber('revision')
                ->name('recommendations.presentation.revert');
            Route::get('/followers', [CreatorDashboardController::class, 'followers'])
                ->name('followers');
            Route::get('/settings', [CreatorSettingsController::class, 'edit'])
                ->name('settings.edit');
            Route::patch('/settings', [CreatorSettingsController::class, 'update'])
                ->name('settings.update');
            Route::patch('/settings/accolades', [CreatorAccoladeController::class, 'update'])
                ->name('settings.accolades.update');
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

Route::prefix('super-admin')->name('super-admin.')->middleware(['auth', 'verified', 'super-admin'])->group(function () {
    Route::get('/', SuperAdminDashboardController::class)->name('dashboard');
    Route::get('/notifications/test', [TestNotificationController::class, 'index'])->name('notifications.test');
    Route::get('/accolades', [SuperAdminAccoladeController::class, 'index'])->name('accolades.index');
    Route::post('/accolades/guides/{user}/evaluate', [SuperAdminAccoladeController::class, 'evaluateGuide'])->name('accolades.guides.evaluate');
    Route::post('/accolades/creators/{creator}/evaluate', [SuperAdminAccoladeController::class, 'evaluateCreator'])->name('accolades.creators.evaluate');
    Route::post('/accolades/rebuild', [SuperAdminAccoladeController::class, 'rebuild'])->name('accolades.rebuild');
    Route::post('/notifications/test', [TestNotificationController::class, 'store'])->middleware('throttle:10,1')->name('notifications.test.store');
    Route::post('/announcements/{announcement}/publish', [AnnouncementController::class, 'publish'])->name('announcements.publish');
    Route::post('/announcements/{announcement}/cancel', [AnnouncementController::class, 'cancel'])->name('announcements.cancel');
    Route::post('/announcements/{announcement}/duplicate', [AnnouncementController::class, 'duplicate'])->name('announcements.duplicate');
    Route::resource('announcements', AnnouncementController::class)->except('show');
    Route::get('/creators', [SuperAdminCreatorController::class, 'index'])->name('creators.index');
    Route::get('/creators/{creator}/assist', [SuperAdminCreatorController::class, 'assist'])->name('creators.assist');
    Route::patch('/creators/{creator}/accolades', [CreatorAccoladeController::class, 'update'])->name('creators.accolades.update');
    Route::put('/creators/{creator}', [SuperAdminCreatorController::class, 'update'])->name('creators.update');
    Route::post('/creators/{creator}/starter-requests', [SuperAdminCreatorController::class, 'starter'])->name('creators.starter');
    Route::get('/creators/{creator}/preview', [SuperAdminCreatorController::class, 'preview'])->name('creators.preview');
    Route::patch('/creators/{creator}/disable', [SuperAdminCreatorController::class, 'disable'])->name('creators.disable');
    Route::patch('/creators/{creator}/enable', [SuperAdminCreatorController::class, 'enable'])->name('creators.enable');
    Route::delete('/creators/{creator}', [SuperAdminCreatorController::class, 'destroy'])->name('creators.destroy');
    Route::patch('/creators/{creator}/restore', [SuperAdminCreatorController::class, 'restore'])->whereNumber('creator')->name('creators.restore');
    Route::get('/creators/{creator}/requests', [SuperAdminCreatorRequestController::class, 'index'])->name('creators.requests.index');
    Route::get('/creators/{creator}/requests/{recommendation}/edit', [SuperAdminCreatorRequestController::class, 'edit'])->whereNumber('recommendation')->name('creators.requests.edit');
    Route::patch('/creators/{creator}/requests/{recommendation}', [SuperAdminCreatorRequestController::class, 'update'])->whereNumber('recommendation')->name('creators.requests.update');
    Route::post('/creators/{creator}/requests/{recommendation}/status', [SuperAdminCreatorRequestController::class, 'status'])->whereNumber('recommendation')->name('creators.requests.status');
    Route::delete('/creators/{creator}/requests/{recommendation}', [SuperAdminCreatorRequestController::class, 'destroy'])->whereNumber('recommendation')->name('creators.requests.destroy');
    Route::post('/creators/{creator}/requests/{recommendation}/restore', [SuperAdminCreatorRequestController::class, 'restore'])->whereNumber('recommendation')->name('creators.requests.restore');
    Route::delete('/creators/{creator}/requests/{recommendation}/presentation', [SuperAdminCreatorRequestController::class, 'clearPresentation'])->whereNumber('recommendation')->name('creators.requests.presentation.clear');
    Route::post('/creators/{creator}/requests/{recommendation}/presentation/{revision}/revert', [SuperAdminCreatorRequestController::class, 'revertPresentation'])->whereNumber(['recommendation', 'revision'])->name('creators.requests.presentation.revert');
    Route::patch('/ads/{advertisement}/toggle', [HomepageAdvertisementController::class, 'toggle'])->name('ads.toggle');
    Route::resource('ads', HomepageAdvertisementController::class)->parameters(['ads' => 'advertisement'])->except('show');
});

Route::get('/@{handle}', PublicGuideProfileController::class)
    ->where('handle', '[A-Za-z0-9_-]+')
    ->name('guides.show');

Route::get('/{creator:slug}/published', [RecommendationController::class, 'published'])
    ->name('creators.published');

Route::get('/{creator:slug}/closed', [RecommendationController::class, 'closed'])
    ->name('creators.closed');

Route::get('/requests/{recommendation}/card-details', [RecommendationController::class, 'cardDetails'])
    ->whereNumber('recommendation')
    ->name('requests.card-details');

Route::get('/{creator:slug}', [RecommendationController::class, 'showCreatorQueue'])
    ->name('creator.queue');
