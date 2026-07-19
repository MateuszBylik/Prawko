/**
 * Service worker aplikacji „Prawko".
 *
 * ŚWIADOMIE cache'uje TYLKO hashowane assety Vite i ikony — nigdy HTML.
 * Strony są za logowaniem i zawierają dane użytkownika: gdyby wpadły do cache'u,
 * pokazywałyby nieaktualne wyniki, a na współdzielonym urządzeniu wyciekłyby
 * do kolejnej osoby. Assety mają hash w nazwie, więc nowy build = nowy URL
 * i nie da się podać starego pliku.
 */

const CACHE = 'prawko-assets-v1';
const CACHEABLE = ['/build/assets/', '/icons/'];

self.addEventListener('install', () => self.skipWaiting());

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches
            .keys()
            .then((keys) => Promise.all(keys.filter((k) => k !== CACHE).map((k) => caches.delete(k))))
            .then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', (event) => {
    const { request } = event;

    if (request.method !== 'GET') return;

    const url = new URL(request.url);
    if (url.origin !== self.location.origin) return;
    if (!CACHEABLE.some((prefix) => url.pathname.startsWith(prefix))) return;

    // Cache-first: te pliki są niezmienne (hash w nazwie), więc trafienie w cache
    // jest zawsze poprawne, a offline nadal mamy style i skrypty.
    event.respondWith(
        caches.open(CACHE).then((cache) =>
            cache.match(request).then(
                (hit) =>
                    hit ||
                    fetch(request).then((response) => {
                        if (response.ok) cache.put(request, response.clone());

                        return response;
                    })
            )
        )
    );
});
