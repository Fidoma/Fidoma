<div
    role="group"
    aria-label="{{ __('authentication::messages.user_menu.language') }}"
    class="fi-theme-switcher fidoma-language-switcher"
>
    @foreach (['de' => __('authentication::messages.user_menu.german'), 'en' => __('authentication::messages.user_menu.english')] as $locale => $label)
        <form method="POST" action="{{ route('admin.locale.update', ['locale' => $locale]) }}">
            @csrf
            <button
                aria-label="{{ $label }}"
                aria-pressed="{{ app()->getLocale() === $locale ? 'true' : 'false' }}"
                type="submit"
                title="{{ $label }}"
                @class([
                    'fi-theme-switcher-btn fidoma-language-switcher-btn',
                    'fi-active' => app()->getLocale() === $locale,
                ])
            >
                {{ strtoupper($locale) }}
            </button>
        </form>
    @endforeach
</div>
