<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Fidoma verbindet ERP, Dokumentenmanagement, KI-Unterstützung und automatisierte Geschäftsprozesse. Weniger Bürokratie für Hotellerie, Handwerk und Unternehmen.">
    <meta name="theme-color" content="#172033">
    <title>Fidoma – Open-Source-ERP mit intelligenter Automatisierung</title>
    <link rel="icon" href="{{ asset('images/brand/fidoma-mark.png') }}" type="image/png">
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="landing-page">
@include('welcome::partials.header')

<main id="top">
    <section class="hero-section"><div class="hero-glow" aria-hidden="true"></div><div class="site-container hero-grid">
        <div class="hero-copy">
            <span class="eyebrow"><span></span> Offenes ERP. Intelligent verbunden.</span>
            <h1>Mehr Zeit fürs Geschäft. <em>Weniger Bürokratie.</em></h1>
            <p>Fidoma verbindet ERP, Dokumentenmanagement und intelligente Automatisierung in einer zentralen Arbeitsumgebung. Rechnungen, Belege und Zahlungen werden mit offenen Schnittstellen verarbeitet – damit Sie sich auf Ihr Geschäft konzentrieren können.</p>
            <div class="hero-actions"><a class="button button-primary" href="#features">Fidoma entdecken <span aria-hidden="true">→</span></a><a class="button button-secondary" href="{{ route('pricing') }}">Preise ansehen</a></div>
            <div class="hero-notes"><span><i>✓</i> Open Source</span><span><i>✓</i> Prüfbarkeit statt Blackbox</span><span><i>✓</i> Für den Unternehmensalltag</span></div>
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

    <section class="trust-strip" aria-label="Fidoma Vorteile"><div class="site-container trust-grid"><article><span class="line-icon">↻</span><div><b>Weniger Routinearbeit</b><small>Abläufe sinnvoll verbinden</small></div></article><article><span class="line-icon">✓</span><div><b>Nachvollziehbar</b><small>Prüfung bleibt in Ihrer Hand</small></div></article><article><span class="line-icon">↗</span><div><b>Branchenübergreifend</b><small>Vom Hotel bis zum Bauprojekt</small></div></article><article><span class="line-icon">◇</span><div><b>Offen integriert</b><small>Bank, Paperless-ngx & Webmail</small></div></article></div></section>

    <section class="content-section" id="workflow"><div class="site-container">
        <div class="section-heading"><div><span class="section-kicker">Weniger manuelle Arbeit</span><h2>Weniger klicken. Mehr automatisch erledigen.</h2></div><p>Fidoma verbindet Informationen aus Rechnungen, Dokumenten und Bankumsätzen. Intelligente Zuordnung und automatisierte Abläufe reduzieren manuelle Arbeit und schaffen Übersicht.</p></div>
        <div class="workflow-grid"><article><span class="step">01</span><span class="feature-icon">⇩</span><h3>Bankumsätze übernehmen</h3><p>CSV-Dateien mit Vorschau und kontrollierter Spaltenzuordnung importieren.</p></article><article><span class="step">02</span><span class="feature-icon">€</span><h3>Zahlungen zuordnen</h3><p>Betrag, Rechnungsnummer und Gegenpartei mit offenen Vorgängen abgleichen.</p></article><article><span class="step">03</span><span class="feature-icon">⌕</span><h3>Belege erkennen</h3><p>OCR-Daten aus der angebundenen Paperless-ngx-Instanz in die Zuordnung einbeziehen.</p></article><article><span class="step">04</span><span class="feature-icon">✓</span><h3>Prüfen und abschließen</h3><p>Vorschläge nachvollziehen und nicht eindeutige Vorgänge kontrolliert bearbeiten.</p></article></div>
    </div></section>

    <section class="content-section intelligence-section" id="integrations"><div class="site-container">
        <div class="section-heading centered-heading"><div><span class="section-kicker">Offene Technologien. Intelligente Abläufe.</span><h2>Ihre Büroarbeit. Intelligent vernetzt.</h2></div><p>Fidoma verbindet bewährte Open-Source-Technologien mit intelligenten Automatisierungen. Dokumente, Geschäftsvorgänge und Zahlungen greifen ineinander – ohne unnötige doppelte Dateneingabe.</p></div>
        <div class="integration-grid">
            <article class="integration-card"><span class="feature-icon magenta">□</span><span class="availability available">Verfügbar</span><h3>Dokumentenmanagement</h3><p>Paperless-ngx bleibt ein eigenständiges Open-Source-Projekt und lässt sich als Dokumentenlösung anbinden.</p><ul><li>Zentrale Ablage und OCR-Texterkennung</li><li>Strukturierte Dokumentendaten übernehmen</li><li>Belege mit Vorgängen verknüpfen</li><li>Manuelle Erfassung reduzieren</li></ul></article>
            <article class="integration-card"><span class="feature-icon magenta">✦</span><span class="availability prepared">Integrationsfähig</span><h3>KI-Unterstützung</h3><p>Die offene Architektur schafft Raum für unterstützende KI-Dienste. Eine eigenständige KI-Engine ist derzeit nicht Bestandteil von Fidoma.</p><ul><li>Offene Schnittstellen als Grundlage</li><li>Dokumentdaten strukturiert weiterverwenden</li><li>Vorschläge statt unkontrollierter Entscheidungen</li><li>Menschliche Prüfung im Prozess</li></ul></article>
            <article class="integration-card"><span class="feature-icon magenta">↻</span><span class="availability available">Verfügbar</span><h3>Automatisierte Geschäftsprozesse</h3><p>ERP-Funktionen und Integrationen verbinden Informationen, ohne die Nachvollziehbarkeit zu verlieren.</p><ul><li>Bankumsätze importieren und abgleichen</li><li>Rechnungen und Zahlungen zuordnen</li><li>Offene Vorgänge erkennen</li><li>Belege kontrolliert verknüpfen</li></ul></article>
        </div>
    </div></section>

    <section class="content-section features-section" id="features"><div class="site-container">
        <div class="section-heading"><div><span class="section-kicker">Ein System für den Arbeitsalltag</span><h2>Kaufmännische Prozesse, passend zu Ihrem Betrieb.</h2></div><p>Fidoma bringt vorhandene Funktionen in einer konsistenten Oberfläche zusammen – für Hotels, Handwerks- und Baubetriebe sowie branchenübergreifende Teams.</p></div>
        <div class="industry-grid">
            <article class="industry-card industry-hotel"><span class="card-tag">Hotellerie</span><span class="feature-icon">◫</span><h3>Vom Messekalender bis zur Abrechnung</h3><p>Reservierungen, Belegungsplanung und Rechnungsstellung greifen ineinander.</p><ul><li>Zimmer und Reservierungen planen</li><li>Messeinformationen für die Vorbereitung</li><li>Leistungen abrechnen</li><li>Zahlungseingänge zuordnen</li></ul></article>
            <article class="industry-card"><span class="card-tag">Handwerk & Bau</span><span class="feature-icon">⌁</span><h3>Projekte verlässlich abrechnen</h3><p>Angebote, Aufträge und projektbezogene Rechnungen bleiben zusammen sichtbar.</p><ul><li>Angebote und Rechnungen</li><li>Projekte und Aufträge</li><li>Abschlagsrechnungen</li><li>Abschlagszahlungen verrechnen</li><li>Offene Forderungen im Blick</li></ul></article>
            <article class="industry-card"><span class="card-tag">Branchenübergreifend</span><span class="feature-icon">◇</span><h3>Eine gemeinsame Arbeitsgrundlage</h3><p>Stammdaten, Dokumente und Finanzen werden nicht länger getrennt gedacht.</p><ul><li>Kunden- und Produktverwaltung</li><li>Mietverträge und Vertragsvorlagen</li><li>Dokumentenmanagement</li><li>Zahlungsabgleich und Berichte</li><li>Automatisierte Abläufe</li></ul></article>
        </div>
        <div class="feature-grid compact-feature-grid">
            <article class="feature-card"><span class="feature-icon">▤</span><h3>Rechnungen & Angebote</h3><p>PDF, XRechnung, Gutschriften, Stornierungen und wiederkehrende Rechnungen.</p></article><article class="feature-card"><span class="feature-icon">▣</span><h3>Terminal-Zahlungen</h3><p>Kartenzahlungen über angebundene Zahlungsterminals starten, verfolgen und Rechnungen zuordnen.</p></article><article class="feature-card"><span class="feature-icon">↻</span><h3>FinTS-Banking</h3><p>Banktransaktionen im Cloud-Paket automatisiert über FinTS abrufen und für den Abgleich bereitstellen.</p></article>
        </div>
    </div></section>

    <section class="content-section hosting-section" id="self-hosting"><div class="site-container hosting-grid">
        <div class="hosting-copy"><span class="eyebrow light"><span></span> Offen und frei wählbar</span><h2>Ihre Software. Ihre Entscheidung.</h2><p>Nutzen Sie Fidoma auf Ihrer eigenen Infrastruktur oder entscheiden Sie sich für eine komfortable Cloud-Lösung. Die offenen Technologien und ERP-Funktionen bleiben die Grundlage.</p><ul><li>Self-Hosting ohne Lizenzgebühren</li><li>Cloud-Betrieb als komfortable Option</li><li>Eigene Datenbank und Zugriffsregeln beim Self-Hosting</li><li>Offene Anbindung an externe Dienste</li></ul><a class="button button-light" href="{{ route('pricing') }}">Modelle vergleichen <span aria-hidden="true">→</span></a></div>
        <div class="infrastructure-card"><div class="infra-heading"><b>Ihre Wahlfreiheit</b><span><i></i> Offene Grundlage</span></div><div class="choice-grid"><div class="server-card"><span>▦</span><b>Self-Hosting</b><small>Eigene Infrastruktur</small></div><div class="server-card cloud-card"><span>☁</span><b>Fidoma Cloud</b><small>Betrieb als Service</small></div></div><div class="connector-line"><i></i><i></i><i></i></div><div class="service-grid"><div><span>▣</span><b>Datenbank</b></div><div><span>□</span><b>Paperless-ngx</b></div><div><span>✉</span><b>Webmail</b></div></div></div>
    </div></section>

    <section class="cta-section"><div class="site-container cta-card"><div><span class="section-kicker">Weniger Bürokratie beginnt mit Übersicht</span><h2>Entdecken Sie das passende Fidoma-Modell.</h2></div><div class="hero-actions"><a class="button button-primary" href="{{ route('pricing') }}">Preise ansehen</a><a class="button button-secondary" href="{{ url('/admin/login') }}">Anmelden</a></div></div></section>
</main>

@include('welcome::partials.footer')
</body>
</html>
