<x-filament-widgets::widget>
    <x-filament::section
        :heading="__('clients::messages.dashboard.heading')"
        :description="__('clients::messages.dashboard.description', ['period' => $this->periodLabel()])"
        icon="heroicon-o-chart-bar-square"
        collapsible
        persist-collapsed
        collapse-id="client-analytics-dashboard"
        class="client-dashboard"
    >
        <x-slot name="afterHeader">
            <form wire:submit="applyCustomPeriod" class="client-dashboard-period" x-on:click.stop>
                <x-filament::dropdown placement="bottom-start">
                    <x-slot name="trigger">
                        <x-filament::button
                            type="button"
                            size="sm"
                            color="gray"
                            icon="heroicon-o-calendar-days"
                        >
                            {{ __('clients::messages.dashboard.period') }}
                        </x-filament::button>
                    </x-slot>

                    <x-filament::dropdown.list>
                        @foreach (['this_month', 'this_year'] as $period)
                            <x-filament::dropdown.list.item
                                :color="$selectedPeriod === $period ? 'primary' : 'gray'"
                                :icon="$selectedPeriod === $period ? 'heroicon-m-check' : null"
                                role="button"
                                x-on:click="close"
                                wire:click="selectPeriod('{{ $period }}')"
                            >
                                {{ __('clients::messages.dashboard.periods.'.$period) }}
                            </x-filament::dropdown.list.item>
                        @endforeach
                    </x-filament::dropdown.list>
                </x-filament::dropdown>

                <div class="client-dashboard-date-field">
                    <label for="client-dashboard-start">{{ __('clients::messages.dashboard.from') }}</label>
                    <x-filament::input.wrapper>
                        <x-filament::input id="client-dashboard-start" type="date" wire:model="startDate" />
                    </x-filament::input.wrapper>
                </div>

                <div class="client-dashboard-date-field">
                    <label for="client-dashboard-end">{{ __('clients::messages.dashboard.until') }}</label>
                    <x-filament::input.wrapper>
                        <x-filament::input id="client-dashboard-end" type="date" wire:model="endDate" />
                    </x-filament::input.wrapper>
                </div>

                <x-filament::button type="submit" size="sm" icon="heroicon-m-funnel">
                    {{ __('clients::messages.dashboard.apply') }}
                </x-filament::button>
            </form>
            @error('endDate')
                <p class="client-dashboard-error">{{ $message }}</p>
            @enderror
        </x-slot>

        <div wire:loading.class="client-dashboard-loading" wire:target="selectPeriod,applyCustomPeriod,selectRanking">
            <div class="client-dashboard-kpis">
                <a href="{{ $allClientsUrl }}" class="client-dashboard-kpi">
                    <span>{{ __('clients::messages.dashboard.kpis.total_clients') }}</span>
                    <strong>{{ number_format($overview['total_clients'], 0, ',', '.') }}</strong>
                    <small>{{ __('clients::messages.dashboard.kpis.open_list') }}</small>
                </a>
                <a href="{{ $activeClientsUrl }}" class="client-dashboard-kpi">
                    <span>{{ __('clients::messages.dashboard.kpis.active_clients') }}</span>
                    <strong>{{ number_format($overview['active_clients'], 0, ',', '.') }}</strong>
                    <small>{{ __('clients::messages.dashboard.kpis.open_active') }}</small>
                </a>
                <a href="{{ $invoicesUrl }}" class="client-dashboard-kpi">
                    <span>{{ __('clients::messages.dashboard.kpis.revenue') }}</span>
                    <strong>{{ number_format($overview['revenue'], 2, ',', '.') }} €</strong>
                    <small>{{ __('clients::messages.dashboard.kpis.issued_documents') }}</small>
                </a>
                <a href="{{ $receivablesUrl }}" class="client-dashboard-kpi is-warning">
                    <span>{{ __('clients::messages.dashboard.kpis.receivables') }}</span>
                    <strong>{{ number_format($overview['receivables'], 2, ',', '.') }} €</strong>
                    <small>{{ __('clients::messages.dashboard.kpis.open_invoices') }}</small>
                </a>
            </div>

            <div class="client-dashboard-content">
                <div class="client-dashboard-ranking">
                    <div class="client-dashboard-ranking-header">
                        <div>
                            <h3>{{ __('clients::messages.dashboard.ranking.heading') }}</h3>
                            <p>{{ __('clients::messages.dashboard.ranking.description') }}</p>
                        </div>
                        <div class="client-dashboard-ranking-actions" role="group" aria-label="{{ __('clients::messages.dashboard.ranking.heading') }}">
                            @foreach (['revenue', 'receivables', 'invoice_count'] as $metric)
                                <x-filament::button
                                    type="button"
                                    size="sm"
                                    :color="$rankingMetric === $metric ? 'primary' : 'gray'"
                                    wire:click="selectRanking('{{ $metric }}')"
                                >
                                    {{ __('clients::messages.dashboard.ranking.'.$metric) }}
                                </x-filament::button>
                            @endforeach
                        </div>
                    </div>

                    <div class="client-dashboard-bars">
                        @forelse ($ranking as $position => $client)
                            <a href="{{ $this->clientUrl($client['id']) }}" class="client-dashboard-bar-row">
                                <span class="client-dashboard-position">{{ $position + 1 }}</span>
                                <span class="client-dashboard-client" title="{{ $client['name'] }}">{{ $client['name'] }}</span>
                                <span class="client-dashboard-track" aria-hidden="true">
                                    <span style="width: {{ max(2, ($client['value'] / $maximum) * 100) }}%"></span>
                                </span>
                                <strong>
                                    @if ($rankingMetric === 'invoice_count')
                                        {{ trans_choice('clients::messages.dashboard.ranking.invoices', $client['value'], ['count' => $client['value']]) }}
                                    @else
                                        {{ number_format($client['value'], 2, ',', '.') }} €
                                    @endif
                                </strong>
                            </a>
                        @empty
                            <div class="client-dashboard-empty">
                                <x-filament::icon icon="heroicon-o-chart-bar" />
                                <span>{{ __('clients::messages.dashboard.ranking.empty') }}</span>
                            </div>
                        @endforelse
                    </div>
                </div>

                <div class="client-dashboard-secondary">
                    <a href="{{ $invoicesUrl }}">
                        <span>{{ __('clients::messages.dashboard.metrics.average_revenue') }}</span>
                        <strong>{{ number_format($overview['average_revenue'], 2, ',', '.') }} €</strong>
                        <small>{{ __('clients::messages.dashboard.metrics.average_hint') }}</small>
                    </a>
                    <a href="{{ $newClientsUrl }}">
                        <span>{{ __('clients::messages.dashboard.metrics.new_clients') }}</span>
                        <strong>{{ number_format($overview['new_clients'], 0, ',', '.') }}</strong>
                        <small>{{ $this->periodLabel() }}</small>
                    </a>
                    <a href="{{ $activeClientsUrl }}">
                        <span>{{ __('clients::messages.dashboard.metrics.without_revenue') }}</span>
                        <strong>{{ number_format($overview['without_revenue_90_days'], 0, ',', '.') }}</strong>
                        <small>{{ __('clients::messages.dashboard.metrics.without_revenue_hint') }}</small>
                    </a>
                </div>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
