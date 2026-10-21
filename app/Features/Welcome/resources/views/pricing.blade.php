@php
    $plans = config('website.plans');
    $links = config('website.links');
    $paypal = config('website.paypal');
@endphp
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Fidoma kostenlos selbst hosten oder mit Cloud-Hosting und persönlichem Support nutzen. Transparente Angebote für Ihr Open-Source-ERP.">
    <meta name="theme-color" content="#172033">
    <title>Fidoma Preise – Community, Cloud und Support</title>
    <link rel="icon" href="{{ asset('images/brand/fidoma-mark.png') }}" type="image/png">
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="landing-page pricing-page">
@include('welcome::partials.header', ['solid' => true])

<main id="top">
    <section class="pricing-hero"><div class="hero-glow" aria-hidden="true"></div><div class="site-container pricing-hero-copy">
        <span class="eyebrow"><span></span> Preise & Möglichkeiten</span>
        <h1>Transparent. Fair. <em>Ohne versteckte Kosten.</em></h1>
        <p>Fidoma bleibt Open Source. Entscheiden Sie selbst, ob Sie die Software eigenständig betreiben, bequem in der Cloud nutzen oder persönliche Unterstützung erhalten möchten.</p>
    </div></section>

    <section class="pricing-content"><div class="site-container">
        <div class="pricing-grid">
            <article class="pricing-card">
                <div><span class="plan-kicker">Open Source</span><h2>{{ $plans['community']['name'] }}</h2><p>Die volle Freiheit von Open Source. Installieren und betreiben Sie Fidoma auf Ihrer eigenen Infrastruktur.</p></div>
                <div class="price"><span>{{ $plans['community']['suffix'] }}</span></div>
                <ul><li>Open-Source-Software</li><li>Eigenständiges Hosting</li><li>ERP-Kernfunktionen</li><li>Verfügbare Automatisierungsfunktionen</li><li>Zugriff auf den Quellcode</li><li>Community-Unterstützung</li><li>Keine Lizenzgebühren</li></ul>
                <a class="button button-secondary button-wide" href="{{ $links['repository'] }}">Kostenlos starten <span aria-hidden="true">→</span></a>
            </article>
            <article class="pricing-card pricing-featured">
                <span class="popular-badge">Bequem starten</span>
                <div><span class="plan-kicker">Gehostet</span><h2>{{ $plans['cloud']['name'] }}</h2><p>Fidoma nutzen, ohne sich selbst um Server, Installation und Wartung kümmern zu müssen.</p></div>
                <div class="price"><span>{{ $plans['cloud']['suffix'] }}</span></div>
                <ul><li>Gehostete Fidoma-Instanz</li><li>Serverbetrieb und Wartung</li><li>Regelmäßige Backups und Software-Updates</li><li>HTTPS-Verschlüsselung</li><li>Terminal-Zahlungen</li><li>Automatisierter Banktransaktionsabruf über FinTS</li><li>ERP-Kernfunktionen</li><li>Kein eigener Server erforderlich</li></ul>
                <a class="button button-primary button-wide" href="{{ $links['cloud'] ?: '#kontakt' }}">Cloud anfragen <span aria-hidden="true">→</span></a>
            </article>
            <article class="pricing-card">
                <div><span class="plan-kicker">Persönlich</span><h2>{{ $plans['support']['name'] }}</h2><p>Persönliche Unterstützung für Unternehmen, die Fidoma produktiv einsetzen.</p></div>
                <div class="price"><span>{{ $plans['support']['suffix'] }}</span></div>
                <ul><li>Technische Unterstützung</li><li>Hilfe bei Einrichtung und Konfiguration</li><li>Unterstützung bei Fragen und Problemen</li><li>Hilfe bei Integrationen</li><li>Für Self-Hosting und Cloud geeignet</li><li>Individuelle Vereinbarungen möglich</li></ul>
                <a class="button button-secondary button-wide" href="{{ $links['support'] ?: '#kontakt' }}">Support anfragen <span aria-hidden="true">→</span></a>
            </article>
        </div>
        <p class="pricing-note">Cloud und Support werden individuell angeboten. Leistungsumfang, Verfügbarkeit und Preis erhalten Sie auf Anfrage unter info@fidoma.de.</p>
    </div></section>

    <section class="support-project-section" id="unterstuetzen"><div class="site-container support-project-card"><span class="heart-icon" aria-hidden="true">♡</span><div><span class="section-kicker">Freiwillige Projektunterstützung</span><h2>Fidoma gefällt Ihnen? Unterstützen Sie das Projekt.</h2><p>Fidoma wird unabhängig entwickelt und bleibt kostenlos als Open-Source-Software verfügbar. Mit einer freiwilligen Unterstützung helfen Sie dabei, neue Funktionen zu entwickeln, bestehende Funktionen zu verbessern und das Projekt langfristig weiterzuführen.</p></div><a class="button button-primary" href="{{ $paypal['donation'] }}">Spenden <span aria-hidden="true">→</span></a></div></section>

    <section class="faq-section"><div class="site-container faq-layout"><div><span class="section-kicker">Häufige Fragen</span><h2>Gut zu wissen.</h2><p>Die wichtigsten Unterschiede zwischen freier Software, Hosting und persönlicher Unterstützung.</p></div><div class="faq-list">
        <details open><summary>Ist Fidoma wirklich kostenlos?</summary><p>Ja. Fidoma ist als Open-Source-Software kostenlos nutzbar. Kosten entstehen nur, wenn Sie zusätzliche Dienstleistungen wie Hosting oder persönlichen Support buchen.</p></details>
        <details><summary>Was ist der Unterschied zwischen Community und Cloud?</summary><p>Bei Community betreiben Sie Fidoma selbst. Bei Cloud übernehmen wir den technischen Betrieb, die Wartung und die Bereitstellung.</p></details>
        <details><summary>Kann ich Fidoma selbst hosten und trotzdem Support buchen?</summary><p>Ja. Unser Supportangebot kann unabhängig vom Hosting genutzt werden.</p></details>
        <details><summary>Sind die ERP-Funktionen in der Cloud anders?</summary><p>Die grundlegenden ERP-Funktionen basieren auf derselben Software. Der Unterschied liegt hauptsächlich im Hosting und den angebotenen Dienstleistungen.</p></details>
        <details><summary>Kann ich später von Self-Hosting zur Cloud wechseln?</summary><p>Ein standardisierter Migrationsprozess ist derzeit nicht veröffentlicht. Sprechen Sie uns an, damit wir die vorhandene Installation und einen möglichen Wechsel individuell prüfen können.</p></details>
    </div></div></section>

    <section class="contact-section" id="kontakt"><div class="site-container cta-card"><div><span class="section-kicker">Cloud & Support</span><h2>Welches Modell passt zu Ihrem Betrieb?</h2><p>Cloud- und Supportleistungen werden anfragebasiert vereinbart. Leistungsumfang, Supportzeiten, Reaktionszeiten und mögliche Kontingente werden nicht pauschal vorausgesetzt.</p></div>@if($links['contact'])<a class="button button-primary" href="{{ $links['contact'] }}">Kontakt aufnehmen <span aria-hidden="true">→</span></a>@else<div class="configuration-note">Der direkte Kontaktweg ist noch nicht veröffentlicht und muss vor dem öffentlichen Angebot zentral hinterlegt werden.</div>@endif</div></section>
</main>

@include('welcome::partials.footer')
</body>
</html>
