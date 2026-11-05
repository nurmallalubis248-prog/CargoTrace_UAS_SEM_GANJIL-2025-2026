{{-- Password input with show/hide button. Vars: $id, $name, $label --}}
<div>
    <label class="label" for="{{ $id }}">{{ $label }}</label>
    <div class="relative">
        <input class="input pr-10" id="{{ $id }}" type="password" name="{{ $name }}" required autocomplete="{{ $autocomplete ?? 'current-password' }}">
        <button type="button" data-toggle-password="#{{ $id }}" data-label-show="{{ __('ui.auth.show') }}" data-label-hide="{{ __('ui.auth.hide') }}"
                class="absolute inset-y-0 right-0 grid w-10 place-items-center text-ink-500 hover:text-matcha-700" aria-label="{{ __('ui.auth.show') }}">
            <span class="icon-show">@include('partials.icon', ['name' => 'eye', 'class' => 'h-5 w-5'])</span>
            <span class="icon-hide hidden">@include('partials.icon', ['name' => 'eye-off', 'class' => 'h-5 w-5'])</span>
        </button>
    </div>
</div>
