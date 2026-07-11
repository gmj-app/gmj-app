@props(['size' => 'sm', 'limit' => 4])
@php
    $demoGuides = collect([
        ['name' => 'Maya', 'email' => 'maya@example.test', 'guide_number' => 18],
        ['name' => 'Noah', 'email' => 'noah@example.test', 'guide_number' => 102],
        ['name' => 'Lina', 'email' => 'lina@example.test', 'guide_number' => 250],
        ['name' => 'Ari', 'email' => 'ari@example.test', 'guide_number' => 500],
    ])->take($limit)->map(fn (array $data) => new \App\Models\User($data));
@endphp
<span {{ $attributes->class('inline-flex items-center overflow-visible py-1') }} aria-label="Example community Guides">
    @foreach ($demoGuides as $guide)
        <span class="relative inline-flex overflow-visible {{ $loop->first ? '' : '-ml-1' }}">
            <x-guide-avatar :user="$guide" :size="$size" />
        </span>
    @endforeach
</span>
