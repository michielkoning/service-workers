/* global self, $version, $assets, $offline, URL, Response */

'use strict'; // eslint-disable-line

const config = {
  version: $version,
  staticCacheItems: $assets,
  offlineImage: `
    <svg role="img" aria-labelledby="offline-title"' viewBox="0 0 400 300" xmlns="http://www.w3.org/2000/svg">
      <title id="offline-title">Offline</title>
      <g fill="none" fill-rule="evenodd"><path fill="#D8D8D8" d="M0 0h400v300H0z"/>
      <text fill="#9B9B9B" font-family="Times New Roman,Times,serif" font-size="72" font-weight="bold">
      <tspan x="93" y="172">offline</tspan></text></g>
    </svg>`,
  offlinePage: $offline,
};

/**
 * Generate prefixed cache keys
 *
 * @param {string} key
 */
const cacheName = key => `${config.version}-${key}`;

/**
 * Cache the response and return it
 * @param {string} cacheKey
 * @param {Object} request
 * @param {Object} response
 * @returns response
 */
const addToCache = (cacheKey, request, response) => {
  // Don’t cache bad responses.
  if (response.ok) {
    // Response objects may be used only once.
    // By cloning it, we are able to create a copy for the cache’s use:
    const copy = response.clone();
    caches.open(cacheKey)
      .then(cache => cache.put(request, copy));
  }
  return response;
};

const fetchFromCache = event =>
  caches.match(event.request)
    .then((response) => {
      if (!response) {
        throw Error(`${event.request.url} not found in cache`);
      }
      return response;
    });

const offlineResponse = (resourceType, event) => {
  if (resourceType === 'image') {
    return new Response(
      config.offlineImage,
      {
        headers: {
          'Content-Type': 'image/svg+xml',
        },
      },
    );
  } else if (resourceType === 'content') {
    return caches.match(config.offlinePage);
  }
  return undefined;
};

self.addEventListener('install', (event) => {
  function onInstall(event) {
    const cacheKey = cacheName('static');
    const { staticCacheItems } = config;

    staticCacheItems.push(config.offlinePage);

    return caches.open(cacheKey)
      .then(cache => cache.addAll(staticCacheItems));
  }

  event.waitUntil(onInstall(event)
    .then(() => self.skipWaiting()));
});

self.addEventListener('activate', (event) => {
  function onActivate(event) {
    return caches.keys()
      .then((cacheKeys) => {
        const oldCacheKeys = cacheKeys.filter(key => key.indexOf(config.version) !== 0);
        const deletePromises = oldCacheKeys.map(oldKey => caches.delete(oldKey));
        return Promise.all(deletePromises);
      });
  }

  event.waitUntil(onActivate(event)
    .then(() => self.clients.claim()));
});

self.addEventListener('fetch', (event) => {
  function shouldHandleFetch(event) {
    const { request } = event;
    const url = new URL(request.url);
    const criteria = {
      isGETRequest: request.method === 'GET',
      isFromMyOrigin: url.origin === self.location.origin,
      //isNotFromLocalhost: !url.origin.includes('localhost'),
      isNotTheServiceWorker: !url.pathname.includes('service-worker'),
    };
    const failingCriteria = Object.keys(criteria)
      .filter(criteriaKey => !criteria[criteriaKey]);
    return !failingCriteria.length;
  }

  function onFetch(event) {
    const { request } = event;
    const acceptHeader = request.headers.get('Accept');
    let resourceType = 'static';

    if (acceptHeader.indexOf('text/html') !== -1) {
      resourceType = 'content';
    } else if (acceptHeader.indexOf('image') !== -1) {
      resourceType = 'image';
    }

    const cacheKey = cacheName(resourceType);
    if (resourceType === 'content') {
      event.respondWith(fetch(request)
        .then(response => addToCache(cacheKey, request, response))
        .catch(() => fetchFromCache(event))
        .catch(() => offlineResponse(resourceType)));
    } else {
      event.respondWith(fetchFromCache(event)
        .catch(() => fetch(request))
        .then(response => addToCache(cacheKey, request, response))
        .catch(() => offlineResponse(resourceType)));
}
  }
  if (shouldHandleFetch(event)) {
    onFetch(event);
  }
});
