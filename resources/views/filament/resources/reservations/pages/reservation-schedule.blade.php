@php($schedule = $this->schedule())

<x-filament-panels::page>
    <div class="reservation-scheduler-card" wire:ignore>
        <div id="reservations-scheduler" aria-label="Belegungsplan"></div>
    </div>

    <div class="flex flex-col gap-2 text-sm text-gray-600 dark:text-gray-400">
        <div class="flex flex-wrap items-center gap-2">
            <span>Senkrechter Rand:</span>
            <x-filament::badge color="gray">Offen</x-filament::badge>
            <x-filament::badge color="success">Bestätigt</x-filament::badge>
            <x-filament::badge color="danger">Storniert</x-filament::badge>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <span>Oberer Balken:</span>
            <x-filament::badge color="gray">Offen / nicht berechnet</x-filament::badge>
            <x-filament::badge color="success">Bezahlt</x-filament::badge>
        </div>
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
            const escapeHtml = value => String(value ?? '')
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;')
                .replaceAll("'", '&#039;');

            if (!container || typeof window.DayPilot === 'undefined') return;

            const scheduler = new DayPilot.Scheduler(container, {
                locale: 'de-de',
                startDate: config.startDate,
                days: config.days,
                scale: 'Day',
                cornerHtml: 'Zimmer',
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
        })();
    </script>
    @endscript
</x-filament-panels::page>
