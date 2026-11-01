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

                    const createControl = document.createElement(action.modalAction ? 'button' : 'a');
                    createControl.className = 'fidoma-sidebar-create-action';
                    createControl.setAttribute('aria-label', action.label);
                    createControl.setAttribute('title', action.label);

                    if (action.modalAction) {
                        createControl.type = 'button';
                        createControl.addEventListener('click', () => {
                            Livewire.dispatch('open-sidebar-create-modal', {
                                action: action.modalAction,
                            });
                        });
                    } else {
                        createControl.href = action.createUrl;
                        createControl.setAttribute('wire:navigate', '');
                    }

                    createControl.append(iconTemplate.content.cloneNode(true));
                    navigationItem.append(createControl);
                });
            };

            queueMicrotask(addCreateActions);
            document.addEventListener('livewire:navigated', addCreateActions);
        })();
    </script>
@endif
