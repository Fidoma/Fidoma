<x-filament-widgets::widget>
    <x-filament::section icon="heroicon-o-clipboard-document-check" :heading="__('dashboard::messages.tasks.heading')" :description="__('dashboard::messages.tasks.description')">
        <div class="dashboard-task-list">
            @foreach ($tasks as $task)
                <a href="{{ $task['url'] }}" class="dashboard-task-row">
                    <div @class([
                        'dashboard-task-icon',
                        'is-danger' => $task['color'] === 'danger',
                        'is-warning' => $task['color'] === 'warning',
                        'is-info' => $task['color'] === 'info',
                    ])>
                        <x-filament::icon :icon="$task['icon']" />
                    </div>
                    <div class="dashboard-task-content">
                        <strong>{{ $task['label'] }}</strong>
                        <span>{{ $task['description'] }}</span>
                    </div>
                    <span class="dashboard-task-count">{{ $task['count'] ?? '–' }}</span>
                    <x-filament::icon icon="heroicon-o-chevron-right" class="dashboard-task-chevron" />
                </a>
            @endforeach
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
