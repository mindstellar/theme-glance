/*
 * Glance — a Shopclass public theme.
 * Copyright (c) 2026 Navjot Tomer (Mindstellar) and contributors
 * SPDX-License-Identifier: GPL-3.0-or-later
 *
 * The ad list (search, home): opens each ad in the reader beside the list, or in a bottom sheet
 * on phones, by fetching the ad's own page and taking its [data-glance-panel] part. Every ad
 * link stays a real link; without this file it simply navigates.
 */
(() => {
    'use strict';

    const root = document.documentElement;
    const reader = document.querySelector('[data-reader]');
    const inner = reader && reader.querySelector('[data-reader-inner]');

    const wide = matchMedia('(min-width: 64rem)');
    const phone = matchMedia('(max-width: 47.999rem)');
    const still = matchMedia('(prefers-reduced-motion: reduce)');
    const isRtl = () => getComputedStyle(root).direction === 'rtl';

    /* ---- Sticky header height, for the reader's top edge ------------------- */

    const header = document.querySelector('.site-header');
    if (header) {
        const setTop = () => root.style.setProperty('--sticky-top', `${header.offsetHeight}px`);
        setTop();
        if ('ResizeObserver' in window) new ResizeObserver(setTop).observe(header);
    }

    /* ---- Sort: the select navigates straight to the chosen order ----------- */

    document.querySelectorAll('select[data-sort]').forEach((s) => {
        s.addEventListener('change', () => {
            if (s.value) location.href = s.value;
        });
    });

    /* ---- Filters: a bar when wide, a bottom sheet below ------------------- */

    const filters = document.querySelector('[data-filters]');
    const filterSheet = document.querySelector('[data-filter-sheet]');
    const filterForm = filters && filters.querySelector('[data-filters-form]');

    if (filters && filterSheet && filterForm && typeof filterSheet.showModal === 'function') {
        const syncFilters = () => {
            if (wide.matches) {
                if (filterSheet.open) filterSheet.close();
                if (filterForm.parentNode !== filters) filters.append(filterForm);
                filters.open = true;
            } else {
                filters.open = false;
            }
        };
        filters.querySelector('[data-filters-toggle]').addEventListener('click', (e) => {
            if (wide.matches) return;
            e.preventDefault();
            filterSheet.append(filterForm);
            filterSheet.showModal();
        });
        filterForm.querySelector('[data-filters-close]').addEventListener('click', () => filterSheet.close());
        filterSheet.addEventListener('close', () => {
            setTimeout(() => {
                if (!filterSheet.open) filters.append(filterForm);
            }, 250);
        });
        filterSheet.addEventListener('click', (e) => {
            if (e.target === filterSheet) filterSheet.close();
        });
        wide.addEventListener('change', syncFilters);
        syncFilters();
    }

    if (!reader || !inner) return;

    root.classList.add('glance-js');
    reader.dataset.ready = '';

    /* ---- State --------------------------------------------------------------- */

    const sheet = document.querySelector('[data-reader-sheet]');
    const bar = inner.querySelector('[data-reader-bar]');
    bar.hidden = true;
    const body = inner.querySelector('[data-reader-body]');
    const empty = inner.querySelector('[data-reader-empty]');
    const loading = inner.querySelector('[data-reader-loading]');
    const loadingTitle = loading && loading.querySelector('[data-reader-loading-title]');
    const idleBody = () => body.replaceChildren(...[empty, loading].filter(Boolean));
    const pos = inner.querySelector('[data-reader-pos]');
    const prevBtn = inner.querySelector('[data-reader-prev]');
    const nextBtn = inner.querySelector('[data-reader-next]');
    const fullLink = inner.querySelector('[data-reader-full]');
    const nextPage = inner.querySelector('[data-reader-nextpage]');
    const status = document.querySelector('[data-reader-status]');
    const posFormat = (pos && pos.dataset.format) || '%1$s / %2$s';
    const posOffset = pos ? Number(pos.dataset.offset) || 0 : 0;

    const rows = Array.from(document.querySelectorAll('[data-rows] [data-row]'));
    const linkOf = (row) => row.querySelector('[data-item-link]');
    const posTotal = (pos && Number(pos.dataset.total)) || rows.length;

    const listUrl = location.href;
    const listTitle = document.title;
    const cache = new Map();
    const inflight = new Map();
    const beaconed = new Set();
    const CACHE_MAX = 20;

    /* Ad styles may still be loading (non-blocking on list pages); show an ad only once they apply. */
    const lateCss = document.querySelector('link[data-glance-late-css]');
    const cssReady = !lateCss || lateCss.media !== 'print' ? Promise.resolve() : new Promise((done) => {
        lateCss.addEventListener('load', done, { once: true });
        lateCss.addEventListener('error', done, { once: true });
    });

    let current = null;      // {url, id, row}
    let isOpen = false;
    let pushed = false;      // our history entry sits on top of the list entry
    let controller = null;
    let token = 0;
    let returnFocus = null;
    let closing = false;     // the visitor closed the reader; do not open the first row again

    /* The wide reader fills what is left of the viewport below its top edge, so the ad's
       sticky action bar is on screen before the page has scrolled. */
    let fitQueued = false;
    const fit = () => {
        fitQueued = false;
        if (!wide.matches) return;
        const top = Math.max(0, reader.getBoundingClientRect().top);
        root.style.setProperty('--reader-h', `${Math.max(320, Math.round(innerHeight - top))}px`);
    };
    const queueFit = () => {
        if (!fitQueued) {
            fitQueued = true;
            requestAnimationFrame(fit);
        }
    };
    addEventListener('scroll', queueFit, { passive: true });
    addEventListener('resize', queueFit);
    fit();

    const mode = () => (wide.matches ? 'wide' : phone.matches ? 'phone' : 'tablet');

    try {
        history.replaceState({ glanceList: true }, '', location.href);
    } catch (e) { /* history blocked */ }

    /* ---- Seen marks ---------------------------------------------------------- */

    const paintSeen = (row) => {
        row.dataset.seen = '';
        const mark = row.querySelector('[data-seen-mark]');
        if (mark) mark.hidden = false;
    };
    if (window.Glance && typeof window.Glance.seen === 'function') {
        const seen = window.Glance.seen();
        rows.forEach((row) => {
            if (seen.has(String(row.dataset.id))) paintSeen(row);
        });
    }

    /* ---- Roving focus through the rows -------------------------------------- */

    let rove = 0;
    const setRove = (i) => {
        rove = Math.max(0, Math.min(rows.length - 1, i));
        rows.forEach((row, n) => {
            row.querySelectorAll('a, button').forEach((el) => {
                el.tabIndex = n === rove ? 0 : -1;
            });
        });
    };
    if (rows.length) setRove(0);

    const focusRow = (i) => {
        if (!rows[i]) return;
        setRove(i);
        const link = linkOf(rows[i]);
        link.focus({ preventScroll: true });
        rows[i].scrollIntoView({ block: 'nearest' });
    };

    document.querySelectorAll('[data-rows]').forEach((list) => {
        list.addEventListener('focusin', (e) => {
            const row = e.target.closest('[data-row]');
            const i = rows.indexOf(row);
            if (i > -1 && i !== rove) setRove(i);
        });
        list.addEventListener('keydown', (e) => {
            if (e.altKey || e.ctrlKey || e.metaKey) return;
            const row = e.target.closest('[data-row]');
            const i = rows.indexOf(row);
            if (i < 0) return;
            let to = -1;
            if (e.key === 'ArrowDown') to = i + 1;
            else if (e.key === 'ArrowUp') to = i - 1;
            else if (e.key === 'Home') to = 0;
            else if (e.key === 'End') to = rows.length - 1;
            if (to < 0 || to >= rows.length) return;
            e.preventDefault();
            focusRow(to);
        });
    });

    /* ---- Fetching ads -------------------------------------------------------- */

    const remember = (url, entry) => {
        cache.delete(url);
        cache.set(url, entry);
        while (cache.size > CACHE_MAX) cache.delete(cache.keys().next().value);
    };

    const load = (url, signal) => {
        if (cache.has(url)) return Promise.resolve(cache.get(url));
        if (inflight.has(url)) return inflight.get(url);
        const p = fetch(url, {
            credentials: 'same-origin',
            headers: { 'X-Glance-Panel': '1' },
            signal,
        }).then((res) => {
            if (!res.ok) throw new Error(`HTTP ${res.status}`);
            return res.text();
        }).then((html) => {
            const doc = new DOMParser().parseFromString(html, 'text/html');
            const art = doc.querySelector('[data-glance-panel]');
            if (!art) throw new Error('No panel part');
            const entry = { node: art, title: doc.title };
            remember(url, entry);
            return entry;
        }).finally(() => inflight.delete(url));
        inflight.set(url, p);
        return p;
    };

    const prefetch = (row) => {
        if (!row) return;
        const url = linkOf(row).href;
        if (cache.has(url) || inflight.has(url)) return;
        load(url).catch(() => { /* the click will retry */ });
    };
    const idle = window.requestIdleCallback || ((fn) => setTimeout(fn, 300));

    /* One h1 per document: the ad's heading becomes an h2 inside the list page. */
    const demote = (art) => {
        art.querySelectorAll('h1').forEach((h1) => {
            const h2 = document.createElement('h2');
            for (const a of h1.attributes) h2.setAttribute(a.name, a.value);
            h2.append(...h1.childNodes);
            h1.replaceWith(h2);
        });
        return art;
    };

    /* ---- Placing the reader for the screen size ---------------------------- */

    const place = () => {
        const m = mode();
        if (m === 'phone' && sheet && typeof sheet.showModal === 'function') {
            if (isOpen) {
                if (inner.parentNode !== sheet) sheet.append(inner);
                if (!sheet.open) sheet.showModal();
            } else if (sheet.open) {
                sheet.close();
                setTimeout(() => {
                    if (!isOpen && inner.parentNode !== reader) reader.append(inner);
                }, 250);
            }
            reader.classList.remove('is-open');
        } else {
            if (inner.parentNode !== reader) reader.append(inner);
            if (sheet && sheet.open) sheet.close();
            reader.classList.toggle('is-open', isOpen && m === 'tablet');
        }
    };

    const setActive = (row) => {
        rows.forEach((r) => {
            const on = r === row;
            r.toggleAttribute('data-active', on);
            const link = linkOf(r);
            if (on) link.setAttribute('aria-current', 'true');
            else link.removeAttribute('aria-current');
        });
    };

    const paintBar = () => {
        const i = current ? rows.indexOf(current.row) : -1;
        bar.hidden = false;
        const last = i < 0 || i >= rows.length - 1;
        prevBtn.disabled = i <= 0;
        nextBtn.disabled = last;
        /* On the last row of a page with more results, Next becomes a link to that page. */
        const toPage = Boolean(nextPage) && i > -1 && last;
        if (nextPage) nextPage.hidden = !toPage;
        nextBtn.hidden = toPage;
        pos.textContent = i > -1
            ? posFormat.replace('%1$s', (posOffset + i + 1).toLocaleString()).replace('%2$s', posTotal.toLocaleString())
            : '';
        fullLink.href = current ? current.url : '#';
    };

    const swap = (node) => {
        const run = () => {
            body.replaceChildren(node);
            body.scrollTop = 0;
        };
        if (document.startViewTransition && !still.matches && mode() === 'wide' && isOpen) {
            return document.startViewTransition(run).updateCallbackDone;
        }
        run();
        return Promise.resolve();
    };

    /* ---- Open, step, close --------------------------------------------------- */

    const open = async (url, opts = {}) => {
        const row = opts.row || rows.find((r) => linkOf(r).href === url) || null;
        const id = row ? row.dataset.id : (opts.id || '');
        const my = ++token;

        if (controller) controller.abort();
        controller = new AbortController();

        if (!isOpen) returnFocus = opts.auto ? null : (opts.from || (row && linkOf(row)) || document.activeElement);
        current = { url, id, row, auto: Boolean(opts.auto), beacon: '' };
        setActive(row);
        if (row) setRove(rows.indexOf(row));

        const wasOpen = isOpen;
        isOpen = true;
        paintBar();
        place();

        const busy = (on) => {
            if (on) {
                reader.setAttribute('aria-busy', 'true');
                if (sheet) sheet.setAttribute('aria-busy', 'true');
            } else {
                reader.removeAttribute('aria-busy');
                if (sheet) sheet.removeAttribute('aria-busy');
            }
        };

        let entry = cache.get(url);
        let slow = 0;
        if (!entry) {
            busy(true);
            if (status) status.textContent = status.dataset.loading || '';
            /* The outline of the ad shows at once when nothing is open, or after a beat while
               the last ad is still on screen, so a quick step does not flash. */
            if (loadingTitle) {
                const t = row && row.querySelector('.row-title');
                loadingTitle.textContent = t ? t.textContent.trim() : '';
            }
            if (!body.querySelector('[data-glance-panel]')) idleBody();
            else slow = setTimeout(() => { if (my === token) idleBody(); }, 250);
            try {
                entry = await load(url, controller.signal);
                await cssReady;
            } catch (err) {
                if (err && err.name === 'AbortError') return;
                if (my === token) location.href = url;
                return;
            } finally {
                clearTimeout(slow);
            }
        } else if (!body.querySelector('[data-glance-panel]')) {
            // A cached ad may still wait on the late ad styles; keep the outline up, never the empty hint.
            busy(true);
        }
        await cssReady;
        if (my !== token) return;

        const art = demote(document.importNode(entry.node, true));
        await swap(art);
        if (my === token) busy(false);

        if (opts.history !== false) {
            const state = { glanceItem: id || true, url };
            try {
                if (wasOpen && history.state && history.state.glanceItem) {
                    history.replaceState(state, '', url);
                } else {
                    history.pushState(state, '', url);
                    pushed = true;
                }
            } catch (e) { /* history blocked */ }
        }
        if (entry.title && !opts.auto) document.title = entry.title;
        if (status) status.textContent = '';

        current.beacon = art.dataset.viewBeacon || '';
        if (!opts.auto) engage();

        document.dispatchEvent(new CustomEvent('glance:content', { detail: { root: body } }));

        if (opts.focus !== false) {
            const heading = art.querySelector('#ad-title, h2, h1');
            const target = heading || body;
            if (heading && !heading.hasAttribute('tabindex')) heading.tabIndex = -1;
            target.focus({ preventScroll: true });
        }

        if (row && mode() === 'wide' && !opts.auto) row.scrollIntoView({ block: 'nearest' });

        const i = row ? rows.indexOf(row) : -1;
        if (i > -1) idle(() => prefetch(rows[i + 1]));
    };

    /* A read counts (seen mark, view beacon) once the visitor opened the ad, or touched the
       one the page opened for them. */
    const engage = () => {
        if (!current) return;
        current.auto = false;
        if (current.id && window.Glance && typeof window.Glance.markSeen === 'function') window.Glance.markSeen(current.id);
        if (current.row) paintSeen(current.row);
        const beacon = current.beacon;
        if (beacon && !beaconed.has(beacon) && navigator.sendBeacon) {
            beaconed.add(beacon);
            navigator.sendBeacon(beacon);
        }
    };
    ['pointerdown', 'wheel', 'focusin', 'keydown'].forEach((type) => {
        body.addEventListener(type, () => {
            if (current && current.auto && isOpen) engage();
        }, { passive: true });
    });

    /* Wide screens open the first row on load so the reader is never empty. It stays the list
       page: no history entry, no focus move, no seen mark or view until the visitor engages. */
    const autoOpen = () => {
        if (closing || isOpen || !wide.matches || !rows.length) return;
        open(linkOf(rows[0]).href, { row: rows[0], auto: true, history: false, focus: false });
    };

    const hide = () => {
        if (!isOpen) return;
        token++;
        if (controller) controller.abort();
        reader.removeAttribute('aria-busy');
        isOpen = false;
        pushed = false;
        setActive(null);
        bar.hidden = true;
        const wasSheet = sheet && sheet.open;
        place();
        if (wasSheet) {
            setTimeout(() => {
                if (!isOpen) idleBody();
            }, 250);
        } else {
            idleBody();
        }
        document.title = listTitle;
        const back = current && current.auto ? null : (returnFocus && returnFocus.isConnected ? returnFocus : (current && current.row && linkOf(current.row)));
        current = null;
        if (back && typeof back.focus === 'function') back.focus({ preventScroll: mode() !== 'wide' });
    };

    const close = () => {
        if (!isOpen) return;
        closing = true;
        if (pushed && history.state && history.state.glanceItem) {
            history.back();
            return;
        }
        try {
            history.replaceState({ glanceList: true }, '', listUrl);
        } catch (e) { /* history blocked */ }
        hide();
    };

    const step = (dir) => {
        if (!current || !current.row) return;
        const i = rows.indexOf(current.row) + dir;
        if (!rows[i]) return;
        open(linkOf(rows[i]).href, { row: rows[i], focus: false });
    };

    window.addEventListener('popstate', (e) => {
        const s = e.state;
        if (s && s.glanceItem && s.url) {
            pushed = true;
            open(s.url, { history: false, focus: false });
        } else if (isOpen && wide.matches && !closing && current && current.row) {
            /* Back to the list page: keep the row being read in the reader, as on load. */
            pushed = false;
            document.title = listTitle;
            open(linkOf(current.row).href, { row: current.row, auto: true, history: false, focus: false });
        } else if (isOpen) {
            hide();
        }
    });

    /* ---- Wiring --------------------------------------------------------------- */

    document.addEventListener('click', (e) => {
        if (e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
        const link = e.target.closest('a[data-item-link]');
        if (!link || link.target === '_blank' || link.hasAttribute('download')) return;
        if (link.origin !== location.origin) return;
        if (link.closest('[data-glance-panel]')) return;
        e.preventDefault();
        const pop = link.closest('[popover]');
        if (pop && typeof pop.hidePopover === 'function') pop.hidePopover();
        const row = link.closest('[data-row]');
        open(link.href, { row: rows.includes(row) ? row : null, id: link.dataset.itemLink, from: link });
    });

    /* A pin on the Map search plugin's map opens its ad here instead of the map's own card. */
    document.addEventListener('map-search:pin', (e) => {
        const url = e.detail && e.detail.url;
        if (!url || new URL(url, location.href).origin !== location.origin) return;
        e.preventDefault();
        open(url, { id: String(e.detail.id || '') });
    });

    inner.querySelector('[data-reader-close]').addEventListener('click', close);
    prevBtn.addEventListener('click', () => step(-1));
    nextBtn.addEventListener('click', () => step(1));
    prevBtn.setAttribute('aria-keyshortcuts', isRtl() ? 'ArrowRight K' : 'ArrowLeft K');
    nextBtn.setAttribute('aria-keyshortcuts', isRtl() ? 'ArrowLeft J' : 'ArrowRight J');

    /* While the reader is open, Left/Right (and J/K) step through the ads from anywhere on the
       page, except inside a field or a gallery that scrolls sideways. */
    const typing = (el) => el && (el.isContentEditable || /^(INPUT|TEXTAREA|SELECT)$/.test(el.tagName));
    const sideways = (el, stop = document.body) => {
        for (let n = el; n && n !== stop; n = n.parentElement) {
            if (n.scrollWidth > n.clientWidth + 2) {
                const ox = getComputedStyle(n).overflowX;
                if (ox === 'auto' || ox === 'scroll') return true;
            }
        }
        return false;
    };
    document.addEventListener('keydown', (e) => {
        if (!isOpen || e.defaultPrevented || e.altKey || e.ctrlKey || e.metaKey || e.shiftKey) return;
        let dir = 0;
        if (e.key === 'ArrowRight') dir = isRtl() ? -1 : 1;
        else if (e.key === 'ArrowLeft') dir = isRtl() ? 1 : -1;
        else if (e.key === 'j' || e.key === 'J') dir = 1;
        else if (e.key === 'k' || e.key === 'K') dir = -1;
        if (!dir) return;
        const t = document.activeElement;
        if (typing(t) || (e.key.startsWith('Arrow') && sideways(t))) return;
        if (document.querySelector(':popover-open, dialog[open]:not([data-reader-sheet])')) return;
        e.preventDefault();
        step(dir);
    });

    document.addEventListener('keydown', (e) => {
        if (e.key !== 'Escape' || !isOpen || e.defaultPrevented) return;
        if (document.querySelector(':popover-open, details.account-menu[open]')) return;
        if (sheet && sheet.open) return;
        const lb = document.querySelector('dialog[open]:not([data-reader-sheet]):not([data-filter-sheet])');
        if (lb) return;
        e.preventDefault();
        close();
    });

    if (sheet) {
        sheet.addEventListener('cancel', (e) => {
            e.preventDefault();
            close();
        });
        sheet.addEventListener('click', (e) => {
            if (e.target === sheet) close();
        });
    }

    /* The ad the page opened by itself belongs to the wide layout; it does not follow into a sheet. */
    wide.addEventListener('change', () => {
        if (!wide.matches && current && current.auto) hide();
        place();
        autoOpen();
    });
    phone.addEventListener('change', place);

    autoOpen();

    /* ---- Phone sheet gestures -------------------------------------------------- */

    if (sheet) {
        let drag = null;
        bar.addEventListener('pointerdown', (e) => {
            if (!sheet.open || e.button !== 0 || e.target.closest('button, a')) return;
            drag = { y: e.clientY, t: e.timeStamp, dy: 0 };
            bar.setPointerCapture(e.pointerId);
            sheet.classList.add('is-dragging');
        });
        bar.addEventListener('pointermove', (e) => {
            if (!drag) return;
            drag.dy = Math.max(0, e.clientY - drag.y);
            sheet.style.translate = `0 ${drag.dy}px`;
        });
        const endDrag = (e) => {
            if (!drag) return;
            const fast = drag.dy / Math.max(1, e.timeStamp - drag.t) > 0.6;
            const far = drag.dy > Math.min(160, sheet.offsetHeight * 0.25);
            sheet.classList.remove('is-dragging');
            sheet.style.translate = '';
            drag = null;
            if (fast || far) close();
        };
        bar.addEventListener('pointerup', endDrag);
        bar.addEventListener('pointercancel', endDrag);

        let swipe = null;
        body.addEventListener('pointerdown', (e) => {
            if (!sheet.open || e.pointerType === 'mouse' || sideways(e.target, sheet)) return;
            swipe = { x: e.clientX, y: e.clientY, t: e.timeStamp };
        });
        body.addEventListener('pointercancel', () => { swipe = null; });
        body.addEventListener('pointerup', (e) => {
            if (!swipe) return;
            const dx = e.clientX - swipe.x;
            const dy = e.clientY - swipe.y;
            const dt = e.timeStamp - swipe.t;
            swipe = null;
            if (Math.abs(dx) < 64 || Math.abs(dx) < Math.abs(dy) * 1.6 || dt > 700) return;
            const forward = isRtl() ? dx > 0 : dx < 0;
            step(forward ? 1 : -1);
        });
    }
})();
