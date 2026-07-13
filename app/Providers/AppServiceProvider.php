<?php

namespace App\Providers;

use App\Events\AnnouncementPublished;
use App\Events\FavoriteCreatorAdded;
use App\Events\RequestCreated;
use App\Events\RequestPublished;
use App\Events\VoteAllocated;
use App\Listeners\DistributeAnnouncementNotifications;
use App\Listeners\EvaluateAccoladesAfterFavoriteAdded;
use App\Listeners\EvaluateAccoladesAfterRequestPublished;
use App\Listeners\EvaluateCreatorReachAfterRequestCreated;
use App\Listeners\EvaluateCreatorReachAfterVoteAllocated;
use App\Listeners\EvaluateGuideAccoladesAfterRequestCreated;
use App\Listeners\NotifyCreatorOfNewRequest;
use App\Listeners\NotifyRequestSubmitterOfPublication;
use App\Listeners\NotifyRequestSupportersOfPublication;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::define('access-video-tools', fn (User $user): bool => (bool) $user->can_access_video_tools);
        Event::listen(RequestCreated::class, NotifyCreatorOfNewRequest::class);
        Event::listen(RequestCreated::class, EvaluateGuideAccoladesAfterRequestCreated::class);
        Event::listen(RequestCreated::class, EvaluateCreatorReachAfterRequestCreated::class);
        Event::listen(RequestPublished::class, NotifyRequestSubmitterOfPublication::class);
        Event::listen(RequestPublished::class, NotifyRequestSupportersOfPublication::class);
        Event::listen(RequestPublished::class, EvaluateAccoladesAfterRequestPublished::class);
        Event::listen(FavoriteCreatorAdded::class, EvaluateAccoladesAfterFavoriteAdded::class);
        Event::listen(VoteAllocated::class, EvaluateCreatorReachAfterVoteAllocated::class);
        Event::listen(AnnouncementPublished::class, DistributeAnnouncementNotifications::class);
    }
}
