import { defineConfig } from 'vitepress'

const base = process.env.DOCS_BASE ?? '/Fidoma/'

export default defineConfig({
  lang: 'de-DE',
  title: 'Fidoma',
  description: 'Dokumentation für das Open-Source-ERP Fidoma',
  base,
  cleanUrls: true,
  lastUpdated: true,
  head: [
    ['link', { rel: 'icon', href: `${base}logo.svg` }],
    ['meta', { name: 'theme-color', content: '#ff0092' }],
  ],
  themeConfig: {
    logo: `${base}logo.svg`,
    siteTitle: 'Fidoma Docs',
    search: { provider: 'local' },
    outline: { level: [2, 3], label: 'Auf dieser Seite' },
    returnToTopLabel: 'Nach oben',
    sidebarMenuLabel: 'Menü',
    darkModeSwitchLabel: 'Darstellung',
    lightModeSwitchTitle: 'Helles Design',
    darkModeSwitchTitle: 'Dunkles Design',
    lastUpdated: { text: 'Zuletzt aktualisiert', formatOptions: { dateStyle: 'medium' } },
    nav: [
      { text: 'Anleitung', link: '/getting-started/introduction' },
      { text: 'Module', link: '/user-guide/overview' },
      { text: 'Entwicklung', link: '/developers/architecture' },
      { text: 'GitHub', link: 'https://github.com/Fidoma/Fidoma' },
    ],
    sidebar: [
      {
        text: 'Erste Schritte',
        items: [
          { text: 'Einführung', link: '/getting-started/introduction' },
          { text: 'Installation', link: '/getting-started/installation' },
          { text: 'Konfiguration', link: '/getting-started/configuration' },
          { text: 'Aktualisierung', link: '/getting-started/updating' },
        ],
      },
      {
        text: 'Benutzerhandbuch',
        items: [
          { text: 'Modulübersicht', link: '/user-guide/overview' },
          { text: 'Dashboard', link: '/user-guide/dashboard' },
          { text: 'Kunden & Projekte', link: '/user-guide/customers-projects' },
          { text: 'Angebote & Rechnungen', link: '/user-guide/invoices' },
          { text: 'Zahlungen', link: '/user-guide/payments' },
          { text: 'Transaktionszuordnung', link: '/user-guide/transaction-mapping' },
          { text: 'ZVT-Terminal', link: '/user-guide/zvt-terminal' },
          { text: 'Weitere Module', link: '/user-guide/additional-modules' },
        ],
      },
      {
        text: 'Integrationen',
        items: [
          { text: 'Überblick', link: '/integrations/overview' },
          { text: 'Paperless-ngx', link: '/integrations/paperless' },
        ],
      },
      {
        text: 'Entwicklung',
        items: [
          { text: 'Architektur', link: '/developers/architecture' },
          { text: 'Features & Module', link: '/developers/modules' },
          { text: 'Schnittstellen', link: '/developers/api' },
          { text: 'Mitwirken', link: '/developers/contributing' },
        ],
      },
    ],
    socialLinks: [{ icon: 'github', link: 'https://github.com/Fidoma/Fidoma' }],
    editLink: {
      pattern: 'https://github.com/Fidoma/Fidoma/edit/main/docs/:path',
      text: 'Diese Seite auf GitHub bearbeiten',
    },
    footer: {
      message: 'Open-Source-Dokumentation für Fidoma',
      copyright: 'Veröffentlicht unter der MIT-Lizenz.',
    },
  },
})
