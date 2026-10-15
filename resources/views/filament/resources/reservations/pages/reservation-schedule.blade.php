@php($schedule = $this->schedule())

<x-filament-panels::page>
    <x-filament::actions :actions="[$this->createAction, $this->listAction]" />

    <div class="reservation-scheduler-card">
        <div id="reservations-scheduler" aria-label="Belegungsplan"></div>
    </div>

    <div class="reservation-billing-legend">
        <span>Abrechnung:</span>
        <span class="reservation-legend reservation-billing-none">Nicht berechnet</span>
        <span class="reservation-legend reservation-billing-draft">Entwurf</span>
        <span class="reservation-legend reservation-billing-sent">Versendet</span>
        <span class="reservation-legend reservation-billing-paid">Bezahlt</span>
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

            if (!container || typeof window.DayPilot === 'undefined') return;

            const scheduler = new DayPilot.Scheduler(container, {
                locale: 'de-de',
                startDate: config.startDate,
                days: config.days,
                scale: 'Day',
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
                useEventBoxes: 'Never',
                eventMoveHandling: 'Disabled',
                eventResizeHandling: 'Disabled',
                eventDeleteHandling: 'Disabled',
                timeRangeSelectedHandling: 'Enabled',
                onEventClicked: args => window.location.assign(args.e.data.editUrl),
                onBeforeEventRender: args => {
                    const invoice = args.data.invoiceNumber ? ` #${args.data.invoiceNumber}` : '';
                    args.data.toolTip = `${args.data.text}\n${args.data.status}\n${args.data.billingStatus}${invoice}`;
                    args.data.areas = [{
                        left: 0, right: 0, top: 0, height: 5,
                        cssClass: `reservation-billing-stripe reservation-billing-stripe-${args.data.billingClass}`,
                        toolTip: args.data.billingStatus + invoice,
                    }];
                },
                onTimeRangeSelected: args => {
                    const params = new URLSearchParams({
                        room_id: String(args.resource).replace(/^room-/, ''),
                        arrival_date: args.start.toString('yyyy-MM-dd'),
                        departure_date: args.end.toString('yyyy-MM-dd'),
                    });
                    scheduler.clearSelection();
                    window.location.assign(`${config.createUrl}?${params}`);
                },
            });

            scheduler.init();
            scheduler.scrollTo(DayPilot.Date.today().addDays(-5));
        })();
    </script>
    @endscript
</x-filament-panels::page>
