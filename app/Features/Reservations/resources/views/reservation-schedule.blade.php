@php($schedule = $this->schedule())

<x-filament-panels::page>
    <div class="flex justify-end">
        {{ $this->scheduleLegendAction }}
    </div>

    <div class="reservation-scheduler-card" wire:ignore>
        <div id="reservations-scheduler" aria-label="Belegungsplan"></div>
    </div>

    @assets
        <link rel="stylesheet" href="{{ asset('vendor/daypilot/reservations.css') }}">
        <script src="{{ asset('vendor/daypilot/daypilot-all.min.js') }}"></script>
    @endassets

    @script
    <script>
        (() => {
            const config = @js($schedule);
            const container = document.getElementById('reservations-scheduler');
            const rangeUrl = @js(route('trade-fairs.range'));
            const TRADE_FAIR_HEADER_COLOR = '#FFD966';
            let tradeFairs = [];
            const escapeHtml = value => String(value ?? '')
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;')
                .replaceAll("'", '&#039;');

            if (!container || typeof window.DayPilot === 'undefined') return;

            const fetchJson = async url => {
                const response = await fetch(url, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });

                if (!response.ok) throw new Error(`Trade fair API returned ${response.status}`);

                return (await response.json()).data ?? [];
            };

            const scheduler = new DayPilot.Scheduler(container, {
                locale: 'de-de',
                startDate: config.startDate,
                days: config.days,
                scale: 'Day',
                cornerHtml: '',
                cellWidthSpec: 'Fixed',
                cellWidth: 90,
                timeHeaders: [
                    { groupBy: 'Month', format: 'MMMM yyyy' },
                    { groupBy: 'Day', format: 'ddd d' },
                ],
                resources: config.resources,
                events: config.events,
                eventHeight: 56,
                eventTextWrappingEnabled: false,
                durationBarVisible: false,
                rowHeaderWidth: 220,
                useEventBoxes: 'Never',
                eventMoveHandling: 'Disabled',
                eventResizeHandling: 'Disabled',
                eventDeleteHandling: 'Disabled',
                timeRangeSelectedHandling: 'Enabled',
                onEventClicked: args => window.location.assign(args.e.data.editUrl),
                onBeforeCellRender: args => {
                    const day = args.cell.start.getDayOfWeek();

                    if (day === 0 || day === 6) {
                        args.cell.cssClass = 'reservation-scheduler-weekend';
                    }
                },
                onBeforeTimeHeaderRender: args => {
                    const isDayHeader = args.header.level === 1;
                    if (!isDayHeader) return;

                    const day = args.header.start.toString('yyyy-MM-dd');
                    const fairsForDay = tradeFairs.filter(fair => day >= fair.start_date && day <= fair.end_date);
                    const isToday = day === DayPilot.Date.today().toString('yyyy-MM-dd');

                    if (fairsForDay.length > 0) {
                        args.header.cssClass = 'reservation-scheduler-trade-fair-header';
                        args.header.backColor = TRADE_FAIR_HEADER_COLOR;
                        args.header.toolTip = fairsForDay.map(fair => fair.name).join('\n');
                    } else if (isToday) {
                        args.header.cssClass = 'reservation-scheduler-today-header';
                    }
                },
                onBeforeEventRender: args => {
                    const invoice = args.data.invoiceNumber ? ` #${args.data.invoiceNumber}` : '';
                    args.data.toolTip = `${args.data.text}\n${args.data.status}\n${args.data.billingStatus}${invoice}`;
                    args.data.html = `
                        <div class="reservation-event-content">
                            <strong>${escapeHtml(args.data.text)}</strong>
                            <span>${escapeHtml(args.data.status)} · ${escapeHtml(args.data.billingStatus + invoice)}</span>
                        </div>
                    `;
                    args.data.areas = [{
                        left: 0, right: 0, top: 0, height: 5,
                        cssClass: `reservation-billing-stripe reservation-billing-stripe-${args.data.billingClass}`,
                        toolTip: args.data.billingStatus + invoice,
                    }];
                },
                onTimeRangeSelected: args => {
                    $wire.mountAction('create', {
                        room_id: String(args.resource).replace(/^room-/, ''),
                        arrival_date: args.start.toString('yyyy-MM-dd'),
                        departure_date: args.end.toString('yyyy-MM-dd'),
                    });
                    scheduler.clearSelection();
                },
            });

            scheduler.init();
            scheduler.scrollTo(DayPilot.Date.today().addDays(-5));

            const loadRange = async () => {
                const end = new DayPilot.Date(config.startDate).addDays(config.days).toString('yyyy-MM-dd');
                const url = new URL(rangeUrl, window.location.origin);
                url.searchParams.set('start', config.startDate);
                url.searchParams.set('end', end);

                try {
                    tradeFairs = await fetchJson(url);
                } catch (error) {
                    tradeFairs = [];
                    console.warn('Messetermine für den Belegungsplan konnten nicht geladen werden.', error);
                }

                scheduler.update();
            };
            void loadRange();
        })();
    </script>
    @endscript
</x-filament-panels::page>
