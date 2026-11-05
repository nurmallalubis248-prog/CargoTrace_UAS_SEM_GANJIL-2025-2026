<div class="inline-flex overflow-hidden rounded-lg border border-matcha-200 bg-white text-xs font-semibold" role="group" aria-label="{{ __('ui.common.language') }}">
    @foreach(['id' => 'ID', 'en' => 'EN'] as $code => $label)
        <a href="{{ route('lang.switch', $code) }}"
           class="px-2.5 py-1.5 transition-colors {{ app()->getLocale() === $code ? 'bg-matcha-700 text-white' : 'text-matcha-800 hover:bg-matcha-50' }}"
           @if(app()->getLocale() === $code) aria-current="true" @endif>{{ $label }}</a>
    @endforeach
</div>
