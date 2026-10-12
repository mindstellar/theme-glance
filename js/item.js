/*
 * Glance — a Shopclass public theme.
 * Copyright (c) 2026 Navjot Tomer (Mindstellar) and contributors
 * SPDX-License-Identifier: GPL-3.0-or-later
 *
 * The ad: gallery, lightbox, phone reveal, share, contact and comment dialogs, map. Runs on the page and on every ad
 * the side panel loads. Without it the ad is still a full page with a plain list of photos.
 */
(() => {
    'use strict';

    const isRtl = (el) => getComputedStyle(el).direction === 'rtl';

    /* ---- Lightbox (one per page) ---------------------------------------- */

    let box = null;

    const buildBox = (t) => {
        const dlg = document.createElement('dialog');
        dlg.className = 'glance-lightbox';
        dlg.setAttribute('aria-label', t.photos || '');
        dlg.innerHTML = `
            <div class="lb-stage" data-lb-stage></div>
            <p class="lb-count" aria-live="polite" data-lb-count></p>
            <button type="button" class="lb-btn lb-close" data-lb-close></button>
            <button type="button" class="lb-btn lb-nav lb-prev" data-lb-prev></button>
            <button type="button" class="lb-btn lb-nav lb-next" data-lb-next></button>`;
        dlg.querySelector('[data-lb-close]').setAttribute('aria-label', t.close || '');
        dlg.querySelector('[data-lb-prev]').setAttribute('aria-label', t.prev || '');
        dlg.querySelector('[data-lb-next]').setAttribute('aria-label', t.next || '');
        document.body.appendChild(dlg);

        const state = { items: [], i: 0, onClose: null };
        const img = document.createElement('img');
        img.alt = '';
        img.draggable = false;
        dlg.querySelector('[data-lb-stage]').append(img);
        const count = dlg.querySelector('[data-lb-count]');
        const prev = dlg.querySelector('[data-lb-prev]');
        const next = dlg.querySelector('[data-lb-next]');
        const stage = dlg.querySelector('[data-lb-stage]');

        const paths = { x: '<path d="M18 6 6 18"/><path d="m6 6 12 12"/>', 'chevron-left': '<path d="m15 18-6-6 6-6"/>', 'chevron-right': '<path d="m9 18 6-6-6-6"/>' };
        const icon = (name) => `<svg class="gi gi-${name}" aria-hidden="true" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">${paths[name]}</svg>`;
        dlg.querySelector('[data-lb-close]').innerHTML = icon('x');
        prev.innerHTML = icon('chevron-left');
        next.innerHTML = icon('chevron-right');

        const show = (i) => {
            const n = state.items.length;
            state.i = Math.max(0, Math.min(n - 1, i));
            const it = state.items[state.i];
            img.src = it.src;
            img.alt = it.alt;
            count.textContent = n > 1 ? `${state.i + 1} / ${n}` : '';
            prev.hidden = next.hidden = n < 2;
            [state.i - 1, state.i + 1].forEach((k) => {
                if (state.items[k]) new Image().src = state.items[k].src;
            });
        };
        const step = (d) => show(state.i + d);
        const rtlStep = (d) => step(isRtl(dlg) ? -d : d);

        prev.addEventListener('click', () => step(-1));
        next.addEventListener('click', () => step(1));
        dlg.querySelector('[data-lb-close]').addEventListener('click', () => dlg.close());
        dlg.addEventListener('keydown', (e) => {
            if (e.key === 'ArrowLeft') { e.preventDefault(); rtlStep(-1); }
            if (e.key === 'ArrowRight') { e.preventDefault(); rtlStep(1); }
        });
        dlg.addEventListener('close', () => {
            img.removeAttribute('src');
            if (state.onClose) state.onClose(state.i);
        });
        stage.addEventListener('click', (e) => {
            if (e.target === stage) dlg.close();
        });

        let startX = null;
        stage.addEventListener('pointerdown', (e) => { startX = e.clientX; });
        stage.addEventListener('pointerup', (e) => {
            if (startX === null) return;
            const dx = e.clientX - startX;
            startX = null;
            if (Math.abs(dx) > 50) rtlStep(dx < 0 ? 1 : -1);
        });
        stage.addEventListener('pointercancel', () => { startX = null; });

        return {
            open(items, i, onClose) {
                state.items = items;
                state.onClose = onClose;
                show(i);
                dlg.showModal();
            },
        };
    };

    /* ---- Gallery --------------------------------------------------------- */

    const initGallery = (gal, t) => {
        const track = gal.querySelector('[data-gal-track]');
        if (!track) return;
        const slides = [...track.children];
        const links = slides.map((s) => s.querySelector('[data-gal-open]'));
        const thumbsWrap = gal.querySelector('[data-gal-thumbs]');
        const thumbs = [...gal.querySelectorAll('[data-gal-thumb]')];
        const prev = gal.querySelector('[data-gal-prev]');
        const next = gal.querySelector('[data-gal-next]');
        const cur = gal.querySelector('[data-gal-current]');
        let index = 0;

        if (thumbsWrap) thumbsWrap.hidden = false;
        if (prev) prev.hidden = false;
        if (next) next.hidden = false;

        const goto = (i, instant) => {
            const slide = slides[Math.max(0, Math.min(slides.length - 1, i))];
            const delta = slide.getBoundingClientRect().left - track.getBoundingClientRect().left;
            track.scrollBy({ left: delta, behavior: instant ? 'instant' : 'auto' });
        };

        const mark = (i) => {
            index = i;
            if (cur) cur.textContent = String(i + 1);
            thumbs.forEach((t, k) => {
                if (k === i) t.setAttribute('aria-current', 'true');
                else t.removeAttribute('aria-current');
            });
            if (prev) prev.disabled = i === 0;
            if (next) next.disabled = i === slides.length - 1;
            const t = thumbs[i];
            if (t && thumbsWrap) {
                const a = t.getBoundingClientRect();
                const b = thumbsWrap.getBoundingClientRect();
                thumbsWrap.scrollBy({ left: a.left + a.width / 2 - (b.left + b.width / 2) });
            }
        };
        mark(0);

        if ('IntersectionObserver' in window) {
            const io = new IntersectionObserver((entries) => {
                entries.forEach((en) => {
                    if (en.isIntersecting) mark(slides.indexOf(en.target));
                });
            }, { root: track, threshold: 0.6 });
            slides.forEach((s) => io.observe(s));
        }

        prev?.addEventListener('click', () => goto(index - 1));
        next?.addEventListener('click', () => goto(index + 1));
        thumbs.forEach((t, k) => t.addEventListener('click', () => goto(k)));

        track.addEventListener('keydown', (e) => {
            if (e.key !== 'ArrowLeft' && e.key !== 'ArrowRight') return;
            e.preventDefault();
            const d = e.key === 'ArrowRight' ? 1 : -1;
            goto(index + (isRtl(track) ? -d : d));
        });

        links.forEach((a, k) => {
            a.addEventListener('click', (e) => {
                if (e.metaKey || e.ctrlKey || e.shiftKey || typeof HTMLDialogElement === 'undefined') return;
                e.preventDefault();
                box = box || buildBox(t);
                const items = links.map((l) => ({ src: l.href, alt: l.querySelector('img')?.alt || '' }));
                box.open(items, k, (last) => goto(last, true));
            });
        });
    };

    /* ---- Form dialogs (contact, comment) ----------------------------------- */

    /* The page ships each form inline. Here it moves into a modal dialog that its buttons open.
       The dialog stays inside the ad, so it leaves with it when the panel swaps ads. */
    const formDialog = (ad, host, body, headNode, id, labelId, fallback) => {
        const dlg = document.createElement('dialog');
        dlg.className = 'cdlg';
        dlg.id = id;
        dlg.setAttribute('aria-labelledby', labelId);
        const scroll = document.createElement('div');
        scroll.className = 'cdlg-scroll';
        dlg.append(headNode, scroll);
        scroll.append(body);
        host.append(dlg);

        let opener = null;
        let downOnBackdrop = false;

        const firstField = () => {
            const fields = [...body.querySelectorAll('input:not([type=hidden]):not([type=file]):not([disabled]), textarea, select')];
            return fields.find((f) => f.required && !f.value)
                || fields.find((f) => f.tagName === 'TEXTAREA')
                || fields[0];
        };

        dlg.querySelector('[data-dialog-close]')?.addEventListener('click', () => dlg.close());
        dlg.addEventListener('pointerdown', (e) => { downOnBackdrop = e.target === dlg; });
        dlg.addEventListener('click', (e) => {
            if (e.target !== dlg || !downOnBackdrop) return;
            const r = dlg.getBoundingClientRect();
            const inside = e.clientX >= r.left && e.clientX <= r.right && e.clientY >= r.top && e.clientY <= r.bottom;
            if (!inside) dlg.close();
        });
        dlg.addEventListener('close', () => {
            const back = opener && opener.isConnected && opener.offsetParent !== null
                ? opener
                : [...ad.querySelectorAll(fallback)].find((a) => a.offsetParent !== null);
            opener = null;
            back?.focus({ preventScroll: true });
        });

        return {
            open(from) {
                if (dlg.open) return;
                opener = from;
                dlg.showModal();
                firstField()?.focus();
            },
        };
    };

    const canDialog = () => document.documentElement.classList.contains('can-dialog');

    const upgradeContact = (ad) => {
        const section = ad.querySelector('[data-contact-form]');
        const body = section && section.querySelector('[data-contact-body]');
        const head = section && section.querySelector('template[data-contact-head]');
        if (!body || !head || !canDialog()) return null;

        const failed = Boolean(body.querySelector('.oe-form-error'));
        const d = formDialog(ad, section, body, head.content.cloneNode(true), 'ad-contact-dialog', 'ad-contact-dlg-h', '[data-contact]');
        d.failed = failed;
        return d;
    };

    /* The comment form folds in a <details> without JS; with JS its summary becomes a button. */
    const upgradeComment = (ad) => {
        const fold = ad.querySelector('[data-comment-fold]');
        const form = fold && fold.querySelector('form');
        const head = ad.querySelector('template[data-comment-head]');
        const summary = fold && fold.querySelector('summary');
        if (!form || !head || !summary || !canDialog()) return null;

        const failed = fold.open || Boolean(form.querySelector('.oe-form-error'));
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = summary.className;
        btn.innerHTML = summary.innerHTML;
        btn.dataset.commentOpen = '';
        fold.replaceWith(btn);

        const d = formDialog(ad, btn.parentElement, form, head.content.cloneNode(true), 'ad-comment-dialog', 'ad-comment-dlg-h', '[data-comment-open]');
        btn.addEventListener('click', () => d.open(btn));
        d.failed = failed;
        return d;
    };

    /* ---- Location map ------------------------------------------------------ */

    /* Nothing from the map provider loads until "Show map" is pressed. Each library loads once
       per page. A map whose ad has left the page (the panel swapped ads) is torn down. */
    const libs = {};
    const loadOnce = (name, make) => {
        if (!libs[name]) {
            libs[name] = make().catch((err) => {
                delete libs[name];
                throw err;
            });
        }
        return libs[name];
    };

    const addScript = (src, attrs) => new Promise((resolve, reject) => {
        const s = document.createElement('script');
        s.src = src;
        s.async = true;
        Object.assign(s, attrs || {});
        s.onload = resolve;
        s.onerror = () => {
            s.remove();
            reject(new Error('map'));
        };
        document.head.append(s);
    });

    const LEAFLET = 'https://unpkg.com/leaflet@1.9.4/dist/';
    const loadLeaflet = () => loadOnce('leaflet', () => {
        if (!document.querySelector('link[data-leaflet]')) {
            const css = document.createElement('link');
            css.rel = 'stylesheet';
            css.href = `${LEAFLET}leaflet.css`;
            css.integrity = 'sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=';
            css.crossOrigin = '';
            css.dataset.leaflet = '';
            document.head.append(css);
        }
        return addScript(`${LEAFLET}leaflet.js`, { integrity: 'sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=', crossOrigin: '' })
            .then(() => window.L);
    });

    const loadGoogle = (key) => loadOnce('google', () => new Promise((resolve, reject) => {
        window.glanceMapsReady = () => {
            delete window.glanceMapsReady;
            resolve(window.google);
        };
        addScript(`https://maps.googleapis.com/maps/api/js?key=${encodeURIComponent(key)}&callback=glanceMapsReady`).catch(reject);
    }));

    const geocodeOsm = (address) => fetch(`https://nominatim.openstreetmap.org/search?format=json&limit=1&q=${encodeURIComponent(address)}`,
        { headers: { Accept: 'application/json' } })
        .then((r) => (r.ok ? r.json() : []))
        .then((list) => (list && list.length ? [parseFloat(list[0].lat), parseFloat(list[0].lon)] : null));

    const liveMaps = new Set();
    let mapWatch = null;
    const watchMaps = () => {
        if (mapWatch || !('MutationObserver' in window)) return;
        mapWatch = new MutationObserver(() => {
            liveMaps.forEach((m) => {
                if (m.el.isConnected) return;
                m.destroy();
                liveMaps.delete(m);
            });
            if (!liveMaps.size) {
                mapWatch.disconnect();
                mapWatch = null;
            }
        });
        mapWatch.observe(document.body, { childList: true, subtree: true });
    };

    const initMap = (box, t) => {
        const button = box.querySelector('[data-map-load]');
        const canvas = box.querySelector('[data-map-canvas]');
        if (!button || !canvas) return;

        const d = box.dataset;
        const lat = parseFloat(d.mapLat);
        const lng = parseFloat(d.mapLng);
        const hasCoords = Number.isFinite(lat) && Number.isFinite(lng) && (lat !== 0 || lng !== 0);
        const address = d.mapAddress || '';
        const google = d.mapProvider === 'google' && Boolean(d.mapKey);

        const fail = () => {
            if (!box.isConnected) return;
            const p = document.createElement('p');
            p.className = 'ad-map-error';
            p.setAttribute('role', 'status');
            p.textContent = t.mapError || '';
            box.classList.remove('is-loading', 'is-live');
            box.replaceChildren(p);
        };

        const accent = () => {
            const probe = document.createElement('span');
            probe.style.color = 'var(--accent)';
            box.append(probe);
            const c = getComputedStyle(probe).color;
            probe.remove();
            return c || '#2F5BFF';
        };

        const renderOsm = async (L) => {
            const at = hasCoords ? [lat, lng] : (address ? await geocodeOsm(address) : null);
            if (!at) throw new Error('map');
            if (!box.isConnected) return;
            canvas.hidden = false;
            const map = L.map(canvas, { scrollWheelZoom: false, attributionControl: true });
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a>',
            }).addTo(map);
            map.setView(at, hasCoords ? 15 : 13);
            const c = accent();
            L.circleMarker(at, { radius: 8, weight: 3, color: '#fff', fillColor: c, fillOpacity: 1 }).addTo(map);
            liveMaps.add({ el: box, destroy: () => map.remove() });
            watchMaps();
        };

        const renderGoogle = (g) => new Promise((resolve, reject) => {
            const draw = (center) => {
                if (!box.isConnected) { resolve(); return; }
                canvas.hidden = false;
                const map = new g.maps.Map(canvas, {
                    zoom: hasCoords ? 15 : 13, center, mapTypeControl: false, streetViewControl: false, fullscreenControl: false,
                });
                const marker = new g.maps.Marker({ map, position: center });
                liveMaps.add({ el: box, destroy: () => { marker.setMap(null); g.maps.event.clearInstanceListeners(map); } });
                watchMaps();
                resolve();
            };
            if (hasCoords) { draw({ lat, lng }); return; }
            if (!address) { reject(new Error('map')); return; }
            new g.maps.Geocoder().geocode({ address }, (res, status) => {
                if (status !== 'OK' || !res || !res.length) reject(new Error('map'));
                else draw(res[0].geometry.location);
            });
        });

        button.addEventListener('click', async () => {
            if (button.disabled) return;
            button.disabled = true;
            button.setAttribute('aria-busy', 'true');
            box.classList.add('is-loading');
            try {
                if (google) await renderGoogle(await loadGoogle(d.mapKey));
                else await renderOsm(await loadLeaflet());
                if (!box.isConnected) return;
                box.classList.remove('is-loading');
                box.classList.add('is-live');
                const hadFocus = document.activeElement === button;
                button.remove();
                if (hadFocus) {
                    if (!canvas.hasAttribute('tabindex')) canvas.tabIndex = -1;
                    canvas.focus({ preventScroll: true });
                }
            } catch (err) {
                fail();
            }
        });
    };

    /* ---- Per-ad behaviour ------------------------------------------------ */

    const initAd = (ad, onPage) => {
        if (ad.dataset.adReady) return;
        ad.dataset.adReady = '1';

        let t = {};
        try { t = JSON.parse(ad.dataset.i18n || '{}'); } catch (err) { t = {}; }

        ad.querySelectorAll('[data-gallery]').forEach((g) => initGallery(g, t));

        if (!onPage) ad.querySelectorAll('[data-collapse-in-panel]').forEach((d) => d.removeAttribute('open'));

        ad.querySelectorAll('[data-phone-reveal]').forEach((a) => {
            a.addEventListener('click', (e) => {
                if (a.dataset.revealed) return;
                e.preventDefault();
                a.dataset.revealed = '1';
                const label = a.querySelector('[data-phone-label]');
                if (label) label.textContent = a.dataset.phone || '';
            });
        });

        ad.querySelectorAll('[data-share]').forEach((btn) => {
            const url = btn.dataset.url || location.href;
            const title = btn.dataset.title || document.title;
            if (!navigator.share && !navigator.clipboard) return;
            btn.hidden = false;
            btn.addEventListener('click', async () => {
                try {
                    if (navigator.share) {
                        await navigator.share({ title, url });
                    } else {
                        await navigator.clipboard.writeText(url);
                        const old = btn.getAttribute('aria-label');
                        btn.setAttribute('aria-label', t.copied || '');
                        btn.title = t.copied || '';
                        setTimeout(() => {
                            btn.setAttribute('aria-label', old);
                            btn.removeAttribute('title');
                        }, 2000);
                    }
                } catch (err) {
                    if (err && err.name === 'AbortError') return;
                    window.prompt('', url);
                }
            });
        });

        const contact = upgradeContact(ad);
        ad.querySelectorAll('[data-contact]').forEach((a) => {
            a.addEventListener('click', (e) => {
                if (!contact || e.metaKey || e.ctrlKey || e.shiftKey) return;
                e.preventDefault();
                contact.open(a);
            });
        });

        ad.querySelectorAll('[data-confirm]').forEach((a) => {
            a.addEventListener('click', (e) => {
                if (!window.confirm(a.dataset.confirm)) e.preventDefault();
            });
        });

        ad.querySelectorAll('[data-map]').forEach((m) => initMap(m, t));

        const comment = upgradeComment(ad);
        ad.querySelectorAll('[data-comment-bar]').forEach((a) => {
            a.addEventListener('click', (e) => {
                if (e.metaKey || e.ctrlKey || e.shiftKey) return;
                if (comment) {
                    e.preventDefault();
                    comment.open(a);
                    return;
                }
                ad.querySelectorAll('[data-collapse-in-panel]').forEach((f) => { f.open = true; });
            });
        });
        if (comment && comment.failed) {
            ad.querySelectorAll('[data-collapse-in-panel]').forEach((d) => { d.open = true; });
        }

        if (onPage && contact && contact.failed) contact.open(null);
        else if (onPage && comment && comment.failed) comment.open(null);
    };

    const init = (root, onPage) => {
        const ads = [];
        if (root.matches && root.matches('[data-glance-panel]')) ads.push(root);
        root.querySelectorAll('[data-glance-panel]').forEach((a) => ads.push(a));
        ads.forEach((a) => initAd(a, onPage));
    };

    const start = () => init(document, true);
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start);
    else start();

    document.addEventListener('glance:content', (e) => {
        const root = e.detail && e.detail.root;
        if (root) init(root, false);
    });
})();
