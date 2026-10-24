/*
 * Glance — a Shopclass public theme.
 * Copyright (c) 2026 Navjot Tomer (Mindstellar) and contributors
 * SPDX-License-Identifier: GPL-3.0-or-later
 *
 * Account pages. Deleting an ad and stopping an alert go through the REST API after a
 * confirm dialog; if a call fails, the original form or link runs instead. Without this
 * script every action still works through core's own forms and links.
 */
(() => {
    'use strict';

    const ready = (fn) => (document.readyState === 'loading' ? document.addEventListener('DOMContentLoaded', fn, { once: true }) : fn());
    const calm = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    const fill = (tpl, value) => String(tpl || '').replace('%s', value);

    function readApi() {
        const meta = document.querySelector('meta[name="shopclass-api"]');
        try {
            const api = meta ? JSON.parse(meta.content) : null;
            return api && api.url && api.token ? api : null;
        } catch (e) {
            return null;
        }
    }

    const api = readApi();

    // Every call carries the page token; one refresh is tried when the token has run out.
    async function call(method, path, retried) {
        const res = await fetch(api.url + path, {
            method,
            credentials: 'same-origin',
            headers: { Accept: 'application/json', [api.header]: api.token },
        });
        if (res.status === 401 && !retried) {
            const fresh = await fetch(api.url + '/auth/session', { credentials: 'same-origin', headers: { Accept: 'application/json', [api.header]: api.token } });
            if (fresh.ok) {
                const body = await fresh.json();
                if (body && body.data && body.data.token) {
                    api.token = body.data.token;
                    return call(method, path, true);
                }
            }
        }
        return res;
    }

    /* Scroll the current tab into view on the phone tab strip, without moving the page. The strip
       fades at each end that has hidden tabs (data-more-start, data-more-end). */
    function revealCurrentTab() {
        document.querySelectorAll('.oe-account-nav ul, .oe-tabs').forEach((list) => {
            const ends = () => {
                const max = list.scrollWidth - list.clientWidth;
                const at = Math.abs(list.scrollLeft);
                list.toggleAttribute('data-more-start', max > 1 && at > 1);
                list.toggleAttribute('data-more-end', max > 1 && at < max - 1);
            };
            list.addEventListener('scroll', ends, { passive: true });
            window.addEventListener('resize', ends);
            const here = list.querySelector('[aria-current]');
            if (here && list.scrollWidth > list.clientWidth) {
                const item = here.closest('li') || here;
                list.scrollLeft = item.offsetLeft - list.offsetLeft - (list.clientWidth - item.offsetWidth) / 2;
            }
            ends();
        });
    }

    ready(() => {
        revealCurrentTab();

        const dialog = document.querySelector('[data-account-confirm]');
        const live = document.querySelector('[data-account-live]');
        if (!dialog || typeof dialog.showModal !== 'function') {
            // No <dialog>: ask with the browser's own box before core's form or link runs.
            document.addEventListener('click', (e) => {
                const el = e.target.closest('[data-confirm], [data-ad-delete], [data-alert-stop]');
                if (!el) return;
                const box = el.closest('[data-confirm-title]');
                const text = el.dataset.confirm || (box && box.dataset.confirmTitle);
                if (text && !window.confirm(text)) e.preventDefault();
            });
            return;
        }

        const parts = {
            title: dialog.querySelector('[data-confirm-title]'),
            text: dialog.querySelector('[data-confirm-text]'),
            ok: dialog.querySelector('[data-confirm-ok]'),
        };

        function ask(o) {
            parts.title.textContent = o.title;
            parts.text.textContent = o.text;
            parts.ok.textContent = o.ok;
            dialog.returnValue = '';
            dialog.showModal();
            return new Promise((resolve) => dialog.addEventListener('close', () => resolve(dialog.returnValue === 'ok'), { once: true }));
        }

        function say(text) {
            if (!live) return;
            live.textContent = '';
            requestAnimationFrame(() => { live.textContent = text; });
        }

        function bump(key, by) {
            document.querySelectorAll(`[data-count="${key}"]`).forEach((el) => {
                const n = parseInt(el.textContent, 10);
                if (!Number.isNaN(n)) el.textContent = String(Math.max(0, n + by));
            });
        }

        // Fade the row, fold its height away, then remove it and put focus somewhere sensible.
        function removeRow(row, emptySel) {
            const list = row.parentElement;
            const next = row.nextElementSibling || row.previousElementSibling;
            const done = () => {
                row.remove();
                if (list && !list.querySelector('li')) {
                    const holder = list.closest('[data-ads]') || list;
                    holder.hidden = true;
                    const empty = document.querySelector(emptySel);
                    if (empty) empty.hidden = false;
                }
                const target = next && next.isConnected ? next.querySelector('h3 a, a') : document.querySelector('.oe-h1');
                if (target) {
                    if (!target.matches('a, button')) target.setAttribute('tabindex', '-1');
                    target.focus({ preventScroll: false });
                }
            };
            row.classList.add('is-leaving');
            if (calm() || !row.animate) {
                setTimeout(done, 200);
                return;
            }
            setTimeout(() => {
                const fold = row.animate(
                    [{ height: row.offsetHeight + 'px' }, { height: '0px', paddingTop: '0px', paddingBottom: '0px', borderBottomWidth: '0px' }],
                    { duration: 200, easing: 'cubic-bezier(0.16, 1, 0.3, 1)' }
                );
                fold.onfinish = done;
            }, 180);
        }

        // Run a destructive action: confirm, mark busy, call the API, fall back to the plain action.
        async function run(trigger, kind) {
            const row = trigger.closest(kind === 'ad' ? '[data-ad]' : '[data-alert]');
            const box = trigger.closest('[data-confirm-title]');
            if (!row || !box) return false;
            const name = row.dataset.title || '';
            const ok = await ask({
                title: box.dataset.confirmTitle,
                text: fill(box.dataset.confirmText, name),
                ok: box.dataset.confirmOk,
            });
            if (!ok) return true;

            const fallback = () => {
                say(box.dataset.msgFailed || '');
                if (kind === 'ad') HTMLFormElement.prototype.submit.call(trigger.form);
                else window.location.href = trigger.href;
            };

            if (!api) {
                fallback();
                return true;
            }

            row.setAttribute('aria-busy', 'true');
            say(box.dataset.msgWorking || '');
            try {
                const path = kind === 'ad' ? `/listings/${encodeURIComponent(row.dataset.id)}` : `/account/alerts/${encodeURIComponent(row.dataset.id)}`;
                const res = await call('DELETE', path);
                if (!res.ok) throw new Error(String(res.status));
            } catch (e) {
                fallback();
                return true;
            }

            row.removeAttribute('aria-busy');
            if (kind === 'ad') {
                bump('all', -1);
                const state = { live: 'active', pending: 'pending', expired: 'expired' }[row.dataset.state];
                if (state) bump(state, -1);
                say(fill(box.dataset.msgDeleted, name));
                removeRow(row, '[data-ads-empty]');
            } else {
                bump('alerts', -1);
                say(fill(box.dataset.msgAlertStopped, name));
                removeRow(row, '[data-alerts-empty]');
            }
            return true;
        }

        document.addEventListener('click', (e) => {
            const del = e.target.closest('[data-ad-delete]');
            const stop = !del && e.target.closest('[data-alert-stop]');
            const trigger = del || stop;
            if (!trigger || e.defaultPrevented || e.button > 0 || e.metaKey || e.ctrlKey || e.shiftKey) return;
            e.preventDefault();
            run(trigger, del ? 'ad' : 'alert');
        });

        // Plugin actions that ask first (listing_row_actions 'confirm') use the same dialog.
        document.addEventListener('click', async (e) => {
            const el = e.target.closest('[data-confirm]');
            if (!el || e.defaultPrevented || e.button > 0) return;
            e.preventDefault();
            const ok = await ask({ title: el.dataset.confirm, text: '', ok: el.textContent.trim() });
            if (!ok) return;
            if (el.form) HTMLFormElement.prototype.submit.call(el.form);
            else if (el.href) window.location.href = el.href;
        });
    });
})();
