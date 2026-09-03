(function () {
    const body = document.body;
    if (!body || !body.classList.contains('gopass-auth')) {
        return;
    }

    const SEARCH_URL = 'https://www.pexels.com/search/4k%20wallpaper/';
    const FALLBACK_IDS = [
        1366919, 1624496, 1287145, 933054, 417074, 2662116, 3225517, 2387873,
        624015, 3408744, 167699, 1486974, 1770809, 1834399, 325185, 531880,
        355465, 1323550, 1261728, 1438761, 346529, 414612, 459225, 547115,
        572897, 709552, 807598, 1054218, 235621, 1166209, 1509582, 2113566,
        3283186, 3493777, 3509971, 3617500, 466685, 1103970, 268533, 2739013,
        3310694, 3338505, 3480494, 1903702, 2559941, 2835436, 1146134, 1292115,
        1591447, 2437299, 688660, 691668, 917494, 1485894, 1761279, 1420440,
        210186, 2649403, 3374210, 3385046, 3560044, 462162, 189349, 1535162
    ];

    function imageUrl(id) {
        return 'https://images.pexels.com/photos/' + id + '/pexels-photo-' + id
            + '.jpeg?auto=compress&cs=tinysrgb&w=1920';
    }

    function pickId(ids) {
        const pool = ids && ids.length ? ids : FALLBACK_IDS;
        return pool[Math.floor(Math.random() * pool.length)];
    }

    function ensureLayer() {
        let layer = document.querySelector('.gopass-auth-wallpaper');
        if (!layer) {
            layer = document.createElement('div');
            layer.className = 'gopass-auth-wallpaper';
            body.insertBefore(layer, body.firstChild);
        }
        body.classList.add('gopass-auth--wallpaper');
        return layer;
    }

    function setCredit(pageUrl, credit) {
        let link = document.querySelector('.gopass-auth-pexels');
        if (!link) {
            link = document.createElement('a');
            link.className = 'gopass-auth-pexels';
            link.target = '_blank';
            link.rel = 'noopener noreferrer';
            body.appendChild(link);
        }
        link.href = pageUrl || SEARCH_URL;
        link.textContent = credit ? ('Ảnh: ' + credit) : 'Ảnh: Pexels';
    }

    function show(url, pageUrl, credit) {
        const layer = ensureLayer();
        const img = new Image();
        img.onload = function () {
            layer.style.backgroundImage = 'url("' + url + '")';
            layer.classList.add('is-ready');
        };
        img.onerror = function () {
            layer.classList.add('is-ready');
        };
        img.src = url;
        setCredit(pageUrl, credit);
    }

    const preset = document.querySelector('.gopass-auth-wallpaper[data-wallpaper-url]');
    if (preset && preset.getAttribute('data-wallpaper-url')) {
        show(
            preset.getAttribute('data-wallpaper-url'),
            preset.getAttribute('data-wallpaper-page') || SEARCH_URL,
            preset.getAttribute('data-wallpaper-credit') || 'Pexels'
        );
        return;
    }

    fetch('/assets/data/pexels-4k-wallpapers.json', { credentials: 'same-origin' })
        .then(function (res) { return res.ok ? res.json() : null; })
        .then(function (data) {
            const id = pickId(data && data.ids);
            show(imageUrl(id), SEARCH_URL, 'Pexels');
        })
        .catch(function () {
            show(imageUrl(pickId()), SEARCH_URL, 'Pexels');
        });
})();
