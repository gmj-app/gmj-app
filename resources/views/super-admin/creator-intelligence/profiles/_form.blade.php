@csrf
@if(isset($profile)) @method('PUT') @endif
<div class="grid gap-5 sm:grid-cols-2">
    @foreach(['display_name'=>'Display Name','slug'=>'Slug','timezone'=>'Timezone','default_currency'=>'Currency'] as $field=>$label)
        <div><label for="profile-{{ $field }}" class="ci-label">{{ $label }}</label><input id="profile-{{ $field }}" name="{{ $field }}" @if($field==='default_currency') maxlength="3" @endif value="{{ old($field, $profile->{$field} ?? ($field==='timezone'?'America/New_York':($field==='default_currency'?'USD':''))) }}" required aria-describedby="@error($field) profile-{{ $field }}-error @enderror" @error($field) aria-invalid="true" @enderror class="ci-control mt-1 w-full @if($field==='default_currency') uppercase @endif @error($field) !border-rose-500 @enderror">@error($field)<p id="profile-{{ $field }}-error" class="ci-error">{{ $message }}</p>@enderror</div>
    @endforeach
</div>
<button class="ci-button-primary mt-6">Save Profile</button>
