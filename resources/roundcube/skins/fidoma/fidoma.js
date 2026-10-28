(function () {
    'use strict';

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
