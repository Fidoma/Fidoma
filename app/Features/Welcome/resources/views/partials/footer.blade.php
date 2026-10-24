@php($links = config('website.links'))
<footer class="site-footer">
    <div class="site-container footer-grid">
        <div class="footer-brand">
            <a class="brand" href="{{ url('/') }}"><img src="{{ asset('images/brand/fidoma-mark.png') }}" alt=""><span>Fidoma</span></a>
            <p>Weniger Bürokratie. Mehr Automatisierung. Mehr Zeit fürs Geschäft.</p>
        </div>
        <nav aria-label="Produkt">
            <b>Produkt</b>
            <a href="{{ url('/#features') }}">Funktionen</a>
            <a href="{{ route('pricing') }}">Preise</a>
            <a href="{{ url('/#self-hosting') }}">Self-Hosting</a>
        </nav>
        <nav aria-label="Projekt">
            <b>Projekt</b>
            <a href="{{ $links['documentation'] }}">Dokumentation</a>
            <a href="{{ $links['repository'] }}">GitHub</a>
            <a href="{{ route('pricing') }}#unterstuetzen">Projekt unterstützen</a>
        </nav>
        <nav aria-label="Rechtliches und Kontakt">
            <b>Kontakt & Rechtliches</b>
            <a href="{{ route('pricing') }}#kontakt">Kontakt</a>
            @if($links['imprint'])<a href="{{ $links['imprint'] }}">Impressum</a>@endif
            @if($links['privacy'])<a href="{{ $links['privacy'] }}">Datenschutz</a>@endif
        </nav>
    </div>
    <div class="site-container footer-bottom"><span>Open-Source-ERP für den Unternehmensalltag.</span><a href="{{ url('/admin/login') }}">Anmelden →</a></div>
</footer>
