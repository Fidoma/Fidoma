(function () {
    'use strict';

    var aiTemplates = [];
    var aiActionsLabel = 'KI-Aktionen';

    function preferDetailedMessageHeaders() {
        if (!window.rcmail || typeof window.rcmail.local_storage_get_item !== 'function') {
            return;
        }

        var preferences = window.rcmail.local_storage_get_item('prefs.elastic', {});

        if (preferences['mail.show.envelope'] === true) {
            return;
        }

        preferences['mail.show.envelope'] = true;

        if (!window.rcmail.local_storage_set_item('prefs.elastic', preferences)) {
            window.rcmail.set_cookie('mail.show.envelope', true, false);
        }
    }

    // Elastic reads this preference during its page initialization. The skin
    // script is deferred, so this runs before its DOM-ready handlers.
    preferDetailedMessageHeaders();

    function applyTheme(dark, root) {
        var documentRoot = root || document;
        var html = documentRoot.documentElement;

        if (!html) {
            return;
        }

        html.classList.toggle('dark-mode', dark);
        html.dataset.fidomaTheme = dark ? 'dark' : 'light';

        var themeColor = documentRoot.querySelector('meta[name="theme-color"]');
        if (themeColor) {
            themeColor.setAttribute('content', dark ? '#18181b' : '#f8fafc');
        }

        documentRoot.querySelectorAll('iframe').forEach(function (frame) {
            try {
                if (frame.contentDocument) {
                    applyTheme(dark, frame.contentDocument);
                }
            } catch (error) {
                // Message contents can deliberately contain cross-origin frames.
            }
        });
    }

    function messageContext() {
        if (!window.rcmail || typeof window.rcmail.get_single_uid !== 'function') {
            return null;
        }

        var rawUid = window.rcmail.get_single_uid();
        var uidMatch = /^(\d+)(?:-.*)?$/.exec(String(rawUid || ''));
        var mailbox = rawUid && typeof window.rcmail.get_message_mailbox === 'function'
            ? window.rcmail.get_message_mailbox(rawUid)
            : window.rcmail.env && window.rcmail.env.mailbox;

        if (!uidMatch || typeof mailbox !== 'string' || mailbox.length === 0) {
            return null;
        }

        return {
            uid: uidMatch[1],
            mailbox: mailbox,
        };
    }

    function closeAiActionsMenu() {
        var button = document.getElementById('fidoma-ai-actions-button');

        if (button && window.jQuery && typeof window.jQuery(button).popover === 'function') {
            window.jQuery(button).popover('hide');
        } else if (window.rcmail && typeof window.rcmail.hide_menu === 'function') {
            window.rcmail.hide_menu('fidoma-ai-actions-menu');
        }

        if (button) {
            button.setAttribute('aria-expanded', 'false');
        }
    }

    function updateAiActionsState() {
        var button = document.getElementById('fidoma-ai-actions-button');
        var enabled = Boolean(messageContext()) && aiTemplates.length > 0;

        if (!button) {
            return;
        }

        button.classList.toggle('disabled', !enabled);
        button.setAttribute('aria-disabled', enabled ? 'false' : 'true');
        button.tabIndex = enabled ? 0 : -1;

        if (!enabled) {
            closeAiActionsMenu();
        }
    }

    function selectAiTemplate(templateId) {
        var context = messageContext();

        if (!context) {
            return;
        }

        window.parent.postMessage({
            type: 'fidoma:ai-template-selected',
            templateId: templateId,
            context: context,
        }, window.location.origin);

        closeAiActionsMenu();
    }

    function renderAiActions() {
        var current = document.getElementById('fidoma-ai-actions');
        var replyButtons = Array.from(document.querySelectorAll('a.reply'));
        var reply = replyButtons.find(function (button) {
            return button.offsetParent !== null;
        }) || replyButtons[0];

        if (!reply || aiTemplates.length === 0) {
            if (current) {
                var currentItem = current.closest('.fidoma-ai-actions-item');
                var currentButton = current.querySelector('#fidoma-ai-actions-button');

                if (currentButton && window.jQuery && typeof window.jQuery(currentButton).popover === 'function') {
                    window.jQuery(currentButton).popover('dispose');
                }
                currentItem ? currentItem.remove() : current.remove();
            }

            var unusedMenu = document.getElementById('fidoma-ai-actions-menu');
            if (unusedMenu) {
                unusedMenu.remove();
            }

            return;
        }

        if (!current) {
            current = document.createElement('span');
            current.id = 'fidoma-ai-actions';
            current.className = 'fidoma-ai-actions';

            var button = document.createElement('a');
            button.id = 'fidoma-ai-actions-button';
            button.className = 'button toolbar-button fidoma-ai-actions-button disabled';
            button.href = '#fidoma-ai-actions-menu';
            button.dataset.popup = 'fidoma-ai-actions-menu';
            button.dataset.popupPos = 'bottom';
            button.setAttribute('role', 'button');
            button.addEventListener('click', function (event) {
                if (!messageContext()) {
                    event.preventDefault();
                    event.stopImmediatePropagation();

                    return;
                }
            });

            var icon = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
            icon.classList.add('fidoma-ai-actions-icon');
            icon.setAttribute('viewBox', '0 0 20 20');
            icon.setAttribute('aria-hidden', 'true');
            icon.setAttribute('focusable', 'false');

            var iconPath = document.createElementNS('http://www.w3.org/2000/svg', 'path');
            iconPath.setAttribute('d', 'M10 1.5 11.4 6 16 7.5 11.4 9 10 13.5 8.6 9 4 7.5 8.6 6 10 1.5ZM4.5 11l.9 2.6L8 14.5l-2.6.9L4.5 18l-.9-2.6-2.6-.9 2.6-.9.9-2.6Zm11.5 1 .7 2.3 2.3.7-2.3.7L16 18l-.7-2.3-2.3-.7 2.3-.7L16 12Z');
            icon.appendChild(iconPath);
            button.appendChild(icon);

            var label = document.createElement('span');
            label.className = 'inner fidoma-ai-actions-label';
            button.appendChild(label);

            var oldMenu = document.getElementById('fidoma-ai-actions-menu');
            if (oldMenu) {
                oldMenu.remove();
            }

            var menu = document.createElement('div');
            menu.id = 'fidoma-ai-actions-menu';
            menu.className = 'popupmenu fidoma-ai-actions-menu hidden';
            menu.setAttribute('aria-hidden', 'true');

            var menuHeading = document.createElement('h3');
            menuHeading.className = 'voice';
            menuHeading.textContent = aiActionsLabel;
            menu.appendChild(menuHeading);

            var list = document.createElement('ul');
            list.className = 'menu listing';
            list.setAttribute('role', 'menu');
            menu.appendChild(list);
            current.appendChild(button);
            document.body.appendChild(menu);

            if (reply.parentElement && reply.parentElement.tagName === 'LI') {
                var item = document.createElement('li');
                item.className = 'fidoma-ai-actions-item';
                item.appendChild(current);
                reply.parentElement.parentElement.insertBefore(item, reply.parentElement);
            } else {
                reply.parentElement.insertBefore(current, reply);
            }

            if (window.UI && typeof window.UI.popup_init === 'function') {
                window.UI.popup_init(button);
            }
        }

        current.querySelector('.fidoma-ai-actions-label').textContent = aiActionsLabel;
        current.querySelector('#fidoma-ai-actions-button').title = aiActionsLabel;

        var menuList = document.querySelector('#fidoma-ai-actions-menu .listing');
        menuList.replaceChildren();

        aiTemplates.forEach(function (template) {
            var item = document.createElement('li');
            item.setAttribute('role', 'menuitem');

            var link = document.createElement('a');
            link.href = '#';
            link.className = 'active';
            link.textContent = template.title;
            link.addEventListener('click', function (event) {
                event.preventDefault();
                selectAiTemplate(template.id);
            });

            item.appendChild(link);
            menuList.appendChild(item);
        });

        updateAiActionsState();
    }

    function setAiTemplates(templates, label) {
        if (!Array.isArray(templates)) {
            return;
        }

        aiTemplates = templates.filter(function (template) {
            return template
                && Number.isInteger(template.id)
                && typeof template.title === 'string'
                && template.title.length > 0;
        }).map(function (template) {
            return { id: template.id, title: template.title };
        });
        aiActionsLabel = typeof label === 'string' && label.length > 0
            ? label
            : aiActionsLabel;

        renderAiActions();
    }

    function receiveParentMessage(event) {
        if (event.origin !== window.location.origin || event.source !== window.parent) {
            return;
        }

        if (!event.data || typeof event.data.type !== 'string') {
            return;
        }

        if (event.data.type === 'fidoma:theme' && typeof event.data.dark === 'boolean') {
            applyTheme(event.data.dark);
            document.cookie = 'colorMode=' + (event.data.dark ? 'dark' : 'light') + '; Path=/; SameSite=Lax';

            return;
        }

        if (event.data.type !== 'fidoma:ai-templates' || !Array.isArray(event.data.templates)) {
            return;
        }

        setAiTemplates(event.data.templates, event.data.label);
    }

    window.addEventListener('message', receiveParentMessage);
    window.addEventListener('load', function () {
        preferDetailedMessageHeaders();

        if (window.rcmail && window.rcmail.env) {
            setAiTemplates(
                window.rcmail.env.fidoma_ai_templates || [],
                window.rcmail.env.fidoma_ai_actions_label,
            );
        } else {
            renderAiActions();
        }

        window.parent.postMessage({ type: 'fidoma:ai-templates-request' }, window.location.origin);

        if (window.rcmail && typeof window.rcmail.addEventListener === 'function') {
            window.rcmail.addEventListener('enable-command', function (event) {
                if (event && event.command === 'reply') {
                    updateAiActionsState();
                }
            });
        }

        var dark = document.documentElement.classList.contains('dark-mode');

        document.querySelectorAll('iframe').forEach(function (frame) {
            frame.addEventListener('load', function () {
                applyTheme(dark);
            });
        });

        new MutationObserver(function (mutations) {
            mutations.forEach(function (mutation) {
                mutation.addedNodes.forEach(function (node) {
                    if (node.nodeType !== 1) {
                        return;
                    }

                    var frames = node.matches && node.matches('iframe')
                        ? [node]
                        : Array.from(node.querySelectorAll ? node.querySelectorAll('iframe') : []);

                    frames.forEach(function (frame) {
                        frame.addEventListener('load', function () {
                            applyTheme(document.documentElement.classList.contains('dark-mode'));
                        });
                    });
                });
            });

            if (aiTemplates.length > 0 && !document.getElementById('fidoma-ai-actions')) {
                window.setTimeout(renderAiActions, 0);
            }
        }).observe(document.body, { childList: true, subtree: true });
    });
}());
