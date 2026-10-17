<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Fidoma verbindet Kunden, Rechnungen, Zahlungen, Bankumsätze und Belege in einer klaren ERP-Arbeitsumgebung.">
    <meta name="theme-color" content="#172033">
    <title>Fidoma – ERP, klar organisiert</title>
    <link rel="icon" href="{{ asset('images/brand/fidoma-mark.png') }}" type="image/png">
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="landing-page">
<header class="site-header"><div class="site-container nav-shell">
    <a class="brand" href="#top" aria-label="Fidoma Startseite"><img src="{{ asset('images/brand/fidoma-mark.png') }}" alt=""><span>Fidoma</span></a>
    <nav class="nav-links" aria-label="Hauptnavigation"><a href="#workflow">Automatisierung</a><a href="#features">Funktionen</a><a href="#self-hosting">Selbsthosting</a></nav>
    <a class="button button-primary button-small" href="{{ url('/admin/login') }}">Anmelden <span aria-hidden="true">→</span></a>
</div></header>

<main id="top">
<section class="hero-section"><div class="hero-glow" aria-hidden="true"></div><div class="site-container hero-grid">
    <div class="hero-copy">
        <span class="eyebrow"><span></span> ERP, klar organisiert</span>
        <h1>Mehr Zeit fürs Geschäft. <em>Weniger Aufwand</em> im Büro.</h1>
        <p>Fidoma verbindet Kunden, Angebote, Rechnungen, Zahlungen und Belege in einer gemeinsamen, selbst gehosteten Arbeitsumgebung.</p>
        <div class="hero-actions"><a class="button button-primary" href="{{ url('/admin/login') }}">Fidoma öffnen <span aria-hidden="true">→</span></a><a class="button button-secondary" href="#features">Funktionen ansehen</a></div>
        <div class="hero-notes"><span><i>✓</i> Selbst gehostet</span><span><i>✓</i> GoBD-orientierte Abläufe</span><span><i>✓</i> Für KMU entwickelt</span></div>
    </div>
    <div class="product-preview" aria-label="Vorschau des Fidoma Dashboards">
        <div class="browser-bar"><div class="browser-dots"><span></span><span></span><span></span></div><span class="browser-address">fidoma.local/admin</span><span class="browser-secure">●</span></div>
        <div class="filament-app">
            <aside class="mock-sidebar"><div class="mock-brand"><img src="{{ asset('images/brand/fidoma-mark.png') }}" alt=""><b>Fidoma</b></div><div class="mock-nav"><span class="active"><i>⌂</i> Übersicht</span><span><i>♙</i> Kunden</span><span><i>▱</i> Angebote</span><span><i>▤</i> Rechnungen</span><span><i>↔</i> Zahlungen</span><small>EXTERNE DIENSTE</small><span><i>✉</i> Webmail</span><span><i>□</i> Dokumente</span></div></aside>
            <div class="mock-main"><div class="mock-topbar"><span>⌕</span><span class="mock-avatar">AR</span></div><div class="mock-content">
                <div class="mock-heading"><div><span>ÜBERSICHT</span><h2>Guten Morgen</h2></div><button>+ Rechnung</button></div>
                <div class="metric-grid"><article><span>Rechnungssumme</span><strong>24.680,00 €</strong><small class="positive">↗ 8,4 %</small></article><article><span>Zahlungseingänge</span><strong>18.420,00 €</strong><small>12 Buchungen</small></article><article><span>Offener Betrag</span><strong>6.260,00 €</strong><small>4 Rechnungen</small></article></div>
                <div class="mock-panels"><article class="chart-panel"><div class="panel-heading"><b>Einnahmen & Ausgaben</b><span>2026⌄</span></div><div class="chart-bars"><i style="--h:37%"></i><i style="--h:54%"></i><i style="--h:46%"></i><i style="--h:67%"></i><i style="--h:79%"></i><i style="--h:71%"></i><i style="--h:91%"></i></div></article><article class="tasks-panel"><div class="panel-heading"><b>Aktuelle Vorgänge</b><span>•••</span></div><div><i class="status success"></i><span><b>RE-2026-041</b><small>Nordlicht Studio</small></span><strong>1.840 €</strong></div><div><i class="status pending"></i><span><b>RE-2026-040</b><small>Hafen & Co.</small></span><strong>960 €</strong></div><div><i class="status success"></i><span><b>RE-2026-039</b><small>Werkraum</small></span><strong>2.310 €</strong></div></article></div>
            </div></div>
        </div>
    </div>
</div></section>

<section class="trust-strip" aria-label="Fidoma Vorteile"><div class="site-container trust-grid"><article><span class="line-icon">⌂</span><div><b>Selbst gehostet</b><small>Auf Ihrer Infrastruktur</small></div></article><article><span class="line-icon">✓</span><div><b>Nachvollziehbar</b><small>Klare digitale Abläufe</small></div></article><article><span class="line-icon">↗</span><div><b>Exportbereit</b><small>Für die Steuerberatung</small></div></article><article><span class="line-icon">◇</span><div><b>Integriert</b><small>Bank, Paperless & Webmail</small></div></article></div></section>

<section class="content-section" id="workflow"><div class="site-container">
    <div class="section-heading"><div><span class="section-kicker">Weniger manuelle Zuordnung</span><h2>Vom Bankumsatz zum passenden Vorgang.</h2></div><p>Bankdaten, offene Rechnungen und erkannte Belege greifen ineinander. Eindeutige Treffer werden vorbereitet, offene Fälle bleiben transparent prüfbar.</p></div>
    <div class="workflow-grid"><article><span class="step">01</span><span class="feature-icon">⇩</span><h3>Bankumsätze übernehmen</h3><p>CSV-Dateien mit Vorschau und kontrollierter Spaltenzuordnung importieren.</p></article><article><span class="step">02</span><span class="feature-icon">€</span><h3>Zahlungen finden</h3><p>Betrag, Rechnungsnummer und Gegenpartei mit offenen Rechnungen abgleichen.</p></article><article><span class="step">03</span><span class="feature-icon">⌕</span><h3>Belege erkennen</h3><p>Paperless-Daten für den automatisierten Zuordnungsprozess einbeziehen.</p></article><article><span class="step">04</span><span class="feature-icon">✓</span><h3>Treffer prüfen</h3><p>Die Trefferquote nachvollziehen und nicht eindeutige Vorgänge gezielt bearbeiten.</p></article></div>
</div></section>

<section class="content-section features-section" id="features"><div class="site-container">
    <div class="section-heading"><div><span class="section-kicker">Ein System für den Arbeitsalltag</span><h2>Vertraute Filament-Oberflächen. Durchgängige Prozesse.</h2></div><p>Fidoma bringt die wichtigsten kaufmännischen Abläufe in einer übersichtlichen, konsistenten Oberfläche zusammen.</p></div>
    <div class="feature-grid">
        <article class="feature-card feature-large"><span class="feature-icon magenta">▤</span><span class="card-tag">Kernprozess</span><h3>Rechnungen & Angebote</h3><p>Dokumente professionell erstellen, versenden und mit Zahlungen verbinden – inklusive PDF, XRechnung und wiederkehrender Rechnungen.</p><div class="mini-table"><span><i class="dot paid"></i> RE-2026-041 <b>Bezahlt</b></span><span><i class="dot open"></i> RE-2026-042 <b>Offen</b></span><span><i class="dot draft"></i> AN-2026-018 <b>Entwurf</b></span></div></article>
        <article class="feature-card"><span class="feature-icon">♙</span><h3>Kunden & Produkte</h3><p>Stammdaten, Ansprechpartner, Preise und Vorgänge zentral organisieren.</p></article>
        <article class="feature-card"><span class="feature-icon">↔</span><h3>Bank & Zahlungen</h3><p>Transaktionen importieren, abgleichen und tatsächliche Zahlungseingänge sauber verfolgen.</p></article>
        <article class="feature-card"><span class="feature-icon">□</span><h3>Belege & Dokumente</h3><p>Mit automatisiertem Zuordnungsprozess und entsprechender Trefferquote.</p></article>
        <article class="feature-card"><span class="feature-icon">◫</span><h3>Reservierungen</h3><p>Planung, Leistungen und Abrechnung in einem durchgängigen Ablauf verwalten.</p></article>
        <article class="feature-card"><span class="feature-icon">⌁</span><h3>Auswertungen</h3><p>Umsatz, Zahlungseingänge und steuerrelevante Daten getrennt nachvollziehen.</p></article>
    </div>
</div></section>

<section class="content-section hosting-section" id="self-hosting"><div class="site-container hosting-grid">
    <div class="hosting-copy"><span class="eyebrow light"><span></span> Daten unter Ihrer Kontrolle</span><h2>Fidoma läuft dort, wo Sie es möchten.</h2><p>Anwendung und Datenbank werden selbst gehostet. Dokumente können über Ihre eigene Paperless-ngx-Instanz angebunden werden. Zugriffe und Integrationen bleiben in Ihrer Hand.</p><ul><li>Eigene Infrastruktur oder Hoster Ihrer Wahl</li><li>Eigene Datenbank und Zugriffsregeln</li><li>Offene Anbindung an externe Dienste</li></ul></div>
    <div class="infrastructure-card"><div class="infra-heading"><b>Ihre Infrastruktur</b><span><i></i> Unter eigener Kontrolle</span></div><div class="server-card"><span>▦</span><b>Fidoma ERP</b><small>Laravel · Filament · MySQL</small></div><div class="connector-line"><i></i><i></i><i></i></div><div class="service-grid"><div><span>▣</span><b>Datenbank</b></div><div><span>□</span><b>Paperless</b></div><div><span>✉</span><b>Webmail</b></div></div></div>
</div></section>

<section class="cta-section"><div class="site-container cta-card"><div><span class="section-kicker">Bereit für weniger Bürokratie?</span><h2>Öffnen Sie Ihren Fidoma-Arbeitsbereich.</h2></div><a class="button button-primary" href="{{ url('/admin/login') }}">Jetzt anmelden <span aria-hidden="true">→</span></a></div></section>
</main>

<footer class="site-footer"><div class="site-container footer-inner"><a class="brand" href="#top"><img src="{{ asset('images/brand/fidoma-mark.png') }}" alt=""><span>Fidoma</span></a><p>ERP, klar organisiert.</p><a href="{{ url('/admin/login') }}">Anmelden →</a></div></footer>
</body>
</html>
