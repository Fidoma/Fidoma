@php
    use App\Filament\Navigation\SidebarCreateActions;
    use Filament\Support\Enums\IconSize;
    use Filament\Support\Icons\Heroicon;

    $createActions = app(SidebarCreateActions::class)->all();
@endphp

@if ($createActions !== [])
    <template id="fidoma-sidebar-create-action-template">
        {{ \Filament\Support\generate_icon_html(Heroicon::Plus, size: IconSize::Medium) }}
    </template>

    <script data-navigate-once>
        (() => {
            const createActions = @js($createActions);

            const normalizeUrl = (value) => {
                const url = new URL(value, window.location.origin);

                return `${url.pathname.replace(/\/$/, '')}${url.search}`;
            };

            const addCreateActions = () => {
                const navigationLinks = Array.from(document.querySelectorAll(
                    '.fi-sidebar-item > .fi-sidebar-item-btn[href]',
                ));
                const iconTemplate = document.getElementById('fidoma-sidebar-create-action-template');

                if (! iconTemplate) {
                    return;
                }

                createActions.forEach((action) => {
                    const navigationLink = navigationLinks.find(
                        (link) => normalizeUrl(link.href) === normalizeUrl(action.navigationUrl),
                    );
                    const navigationItem = navigationLink?.closest('.fi-sidebar-item');

                    if (! navigationItem || navigationItem.querySelector(':scope > .fidoma-sidebar-create-action')) {
                        return;
                    }

                    const createLink = document.createElement('a');
                    createLink.className = 'fidoma-sidebar-create-action';
                    createLink.href = action.createUrl;
                    createLink.setAttribute('aria-label', action.label);
                    createLink.setAttribute('title', action.label);
                    createLink.setAttribute('wire:navigate', '');
                    createLink.append(iconTemplate.content.cloneNode(true));
                    navigationItem.append(createLink);
                });
            };

            queueMicrotask(addCreateActions);
            document.addEventListener('livewire:navigated', addCreateActions);
        })();
    </script>
@endif
