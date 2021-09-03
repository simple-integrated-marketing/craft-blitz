var Blitz = {};

// The event name will be replaced with the `injectScriptEvent` config setting.
document.addEventListener('{injectScriptEvent}', blitzInject);

function blitzInject() {
    'use strict';

    Blitz = {
        elements: document.querySelectorAll('.blitz-inject:not(.blitz-inject--injected)'),
        inject: {
            cacheable: {},
            unique: {},
        },
        processed: 0,
    };

    // Use IE compatible events (https://caniuse.com/#feat=customevent)
    var beforeBlitzInjectAll = document.createEvent('CustomEvent');
    beforeBlitzInjectAll.initCustomEvent('beforeBlitzInjectAll', false, true, null);

    if (!document.dispatchEvent(beforeBlitzInjectAll)) {
        processed++;

        return;
    }

    // Use IE compatible for loop
    for (var i = 0; i < Blitz.elements.length; i++) {
        var data = Blitz.elements[i];

        var values = {
            id: data.getAttribute('data-blitz-id'),
            uri: data.getAttribute('data-blitz-uri'),
            params: data.getAttribute('data-blitz-params'),
            unique: data.getAttribute('data-blitz-unique'),
        };

        var beforeBlitzInject = document.createEvent('CustomEvent');
        beforeBlitzInject.initCustomEvent('beforeBlitzInject', false, true, values);

        if (!document.dispatchEvent(beforeBlitzInject)) {
            return;
        }

        var url = values.uri + (values.params && '?' + values.params);

        if (values.unique) {
            Blitz.unique.push(values);
        }
        else {
            Blitz.cacheable[url] = Blitz.cacheable[url] ?? [];
            Blitz.cacheable[url].push(values);
        }
    }

    for (var i = 0; i < Blitz.cacheable.length; i++) {
        for (var j = 0; j < Blitz.inject[i].cacheable; j++) {
            var data = Blitz.inject[i];

            if (i == 0) {

            }
        }
    }

    console.log(Blitz);
}

function blitzReplace(values) {
    'use strict';

    var url = values.uri + (values.params && '?' + values.params);

    if (values.cache) {
        if (Blitz.cache[url] != undefined) {
            Blitz.cache[url].push(values);
        }
        else {
            Blitz.cache[url] = [values];
        }
    }

    var xhr = new XMLHttpRequest();
    xhr.onload = function() {
        if (this.status >= 200 && this.status < 300) {
            var element = document.getElementById('blitz-inject-' + values.id);

            if (element) {
                values.element = element;
                values.responseText = this.responseText;

                element.innerHTML = this.responseText;
                element.classList.add('blitz-inject--injected');
            }

            if (values.cache) {
                Blitz.cache.push(url);
            }

            var afterBlitzInject = document.createEvent('CustomEvent');
            afterBlitzInject.initCustomEvent('afterBlitzInject', false, false, values);
            document.dispatchEvent(afterBlitzInject);
        }

        Blitz.processed++;

        if (Blitz.processed >= Blitz.elements.length) {
            var afterBlitzInjectAll = document.createEvent('CustomEvent');
            afterBlitzInjectAll.initCustomEvent('afterBlitzInjectAll', false, false, null);
            document.dispatchEvent(afterBlitzInjectAll);
        }
    };

    xhr.open('GET', url);
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    xhr.send();
}
