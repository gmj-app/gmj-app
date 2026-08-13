@php
    $standaloneVoteState = ! request()->ajax();
    $standaloneReturnUrl = route('creator.queue', $creator, absolute: false).'#recommendation-'.$recommendation->id;
@endphp

@if ($standaloneVoteState)
<div x-data="creatorRequestVote({
    requestId: @js($recommendation->id),
    requestTitle: @js($recommendation->displayTitle()),
    detailsUrl: @js(route('requests.card-details', $recommendation)),
    voteUrl: @js(route('recommendations.vote', [$creator, $recommendation])),
    loginUrl: @js(route('login.required', ['return' => $standaloneReturnUrl])),
    authenticated: @js(auth()->check()),
    votable: @js($recommendation->isVotable()),
    hasVoted: @js($recommendation->votedBy(auth()->user())),
    votes: @js($recommendation->totalVotes()),
    csrfToken: @js(csrf_token()),
})">
@endif

<x-recommendation-card
    :recommendation="$recommendation"
    :creator="$creator"
    :usage="$usage"
    :top-requested="$recommendation->id === $topRequestedId"
    :owns-creator="$ownsCreator"
    :anchor="false"
/>

@if ($standaloneVoteState)
</div>
@endif
