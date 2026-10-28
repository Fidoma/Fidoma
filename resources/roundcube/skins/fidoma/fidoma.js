(function () {
    'use strict';

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

    function receiveTheme(event) {
        if (event.origin !== window.location.origin || event.source !== window.parent) {
            return;
        }

        if (!event.data || event.data.type !== 'fidoma:theme' || typeof event.data.dark !== 'boolean') {
            return;
        }

        applyTheme(event.data.dark);
        document.cookie = 'colorMode=' + (event.data.dark ? 'dark' : 'light') + '; Path=/; SameSite=Lax';
    }

    window.addEventListener('message', receiveTheme);
    window.addEventListener('load', function () {
        preferDetailedMessageHeaders();

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
        }).observe(document.body, { childList: true, subtree: true });
    });
}());
