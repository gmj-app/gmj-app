@foreach ($supporters as $pick)
    @if ($pick->user)
        <x-requests.supporter-identity :user="$pick->user" directory />
    @endif
@endforeach
