<header class="site-header {{ $solid ?? false ? 'site-header-solid' : '' }}">
    <div class="site-container nav-shell">
        <a class="brand" href="{{ url('/') }}" aria-label="Fidoma Startseite">
            <img src="{{ asset('images/brand/fidoma-mark.png') }}" alt="">
            <span>Fidoma</span>
        </a>
        <nav class="nav-links" aria-label="Hauptnavigation">
            <a href="{{ url('/#workflow') }}">Automatisierung</a>
            <a href="{{ url('/#features') }}">Funktionen</a>
            <a href="{{ url('/#self-hosting') }}">Self-Hosting</a>
            <a href="{{ route('pricing') }}" @if(request()->routeIs('pricing')) aria-current="page" @endif>Preise</a>
        </nav>
        <a class="button button-primary button-small" href="{{ url('/admin/login') }}">Anmelden <span aria-hidden="true">→</span></a>
    </div>
</header>
