/*
 * Glance — a Shopclass public theme.
 * Copyright (c) 2026 Navjot Tomer (Mindstellar) and contributors
 * SPDX-License-Identifier: GPL-3.0-or-later
 *
 * Site chrome on every page: colour theme, saved ads, menus.
 */
(() => {
    'use strict';

    const root = document.documentElement;

    const store = {
        get(key, fallback) {
            try {
                const v = localStorage.getItem(key);
                return v === null ? fallback : JSON.parse(v);
            } catch (e) {
                return fallback;
            }
        },
        set(key, value) {
            try {
                localStorage.setItem(key, JSON.stringify(value));
                return true;
            } catch (e) {
                return false;
            }
        },
        ok: (() => {
            try {
                localStorage.setItem('glance-probe', '1');
                localStorage.removeItem('glance-probe');
                return true;
            } catch (e) {
                return false;
            }
        })(),
    };

    /* ---- Colour theme ---------------------------------------------------- */

    const themeSwitch = document.querySelector('[data-theme-switch]');
    if (themeSwitch && store.ok) {
        const paint = (mode) => {
            if (mode === 'light' || mode === 'dark') {
                root.dataset.theme = mode;
            } else {
                delete root.dataset.theme;
            }
            themeSwitch.querySelectorAll('[data-theme-set]').forEach((b) => {
                b.setAttribute('aria-pressed', String(b.dataset.themeSet === mode));
            });
        };
        let current = 'auto';
        try {
            current = localStorage.getItem('glance-theme') || 'auto';
        } catch (e) { /* storage blocked */ }
        paint(current);
        themeSwitch.hidden = false;
        themeSwitch.addEventListener('click', (e) => {
            const btn = e.target.closest('[data-theme-set]');
            if (!btn) return;
            const mode = btn.dataset.themeSet;
            try {
                if (mode === 'auto') localStorage.removeItem('glance-theme');
                else localStorage.setItem('glance-theme', mode);
            } catch (e2) { /* storage blocked */ }
            paint(mode);
        });
    }

    /* ---- Saved ads -------------------------------------------------------- */

    const SAVED = 'glance-saved';
    const savedList = () => store.get(SAVED, []).filter((x) => x && x.id);
    const isSaved = (id) => savedList().some((x) => String(x.id) === String(id));

    const savedCount = document.querySelector('[data-saved-count]');
    const savedUl = document.querySelector('[data-saved-list]');
    const savedEmpty = document.querySelector('[data-saved-empty]');

    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

    const renderSaved = () => {
        const list = savedList();
        if (savedCount) {
            savedCount.textContent = String(list.length);
            savedCount.hidden = list.length === 0;
        }
        if (savedUl) {
            savedUl.innerHTML = list.map((x) => `
                <li>
                    <a href="${esc(x.url)}" data-item-link="${esc(x.id)}">
                        ${x.img ? `<img src="${esc(x.img)}" alt="" width="64" height="48" loading="lazy">` : '<span class="saved-noimg"></span>'}
                        <span class="saved-text"><strong>${esc(x.title)}</strong><span>${esc(x.price)}</span></span>
                    </a>
                    <button type="button" class="btn btn-quiet btn-icon" data-unsave="${esc(x.id)}" aria-label="${esc(savedUl.dataset.removeLabel)}">${savedUl.dataset.removeIcon || ''}</button>
                </li>`).join('');
        }
        if (savedEmpty) savedEmpty.hidden = list.length > 0;
        document.querySelectorAll('[data-save]').forEach((btn) => {
            const on = isSaved(btn.dataset.save);
            btn.setAttribute('aria-pressed', String(on));
            const label = btn.querySelector('[data-save-label]');
            if (label) label.textContent = (on ? btn.dataset.labelOn : btn.dataset.labelOff) || label.textContent;
        });
    };

    const toggleSaved = (btn) => {
        const id = btn.dataset.save;
        let list = savedList();
        if (list.some((x) => String(x.id) === String(id))) {
            list = list.filter((x) => String(x.id) !== String(id));
        } else {
            list.unshift({
                id,
                title: btn.dataset.title || '',
                url: btn.dataset.url || '',
                price: btn.dataset.price || '',
                img: btn.dataset.img || '',
            });
            list = list.slice(0, 100);
        }
        store.set(SAVED, list);
        renderSaved();
    };

    if (store.ok) {
        document.documentElement.classList.add('can-save');
        renderSaved();

        document.addEventListener('click', (e) => {
            const save = e.target.closest('[data-save]');
            if (save) {
                e.preventDefault();
                toggleSaved(save);
                return;
            }
            const unsave = e.target.closest('[data-unsave]');
            if (unsave) {
                store.set(SAVED, savedList().filter((x) => String(x.id) !== String(unsave.dataset.unsave)));
                renderSaved();
            }
        });

        window.addEventListener('storage', (e) => {
            if (e.key === SAVED) renderSaved();
        });
        document.addEventListener('glance:content', renderSaved);
    } else {
        document.documentElement.classList.remove('can-save');
    }

    /* ---- Seen ads (rows already read) ------------------------------------ */

    const SEEN = 'glance-seen';
    window.Glance = Object.assign(window.Glance || {}, {
        store,
        markSeen(id) {
            if (!store.ok || !id) return;
            const seen = store.get(SEEN, []);
            if (!seen.includes(String(id))) {
                seen.unshift(String(id));
                store.set(SEEN, seen.slice(0, 500));
            }
        },
        seen() {
            return new Set(store.get(SEEN, []));
        },
    });

    /* ---- Menus ------------------------------------------------------------ */

    document.addEventListener('click', (e) => {
        document.querySelectorAll('details.account-menu[open]').forEach((d) => {
            if (!d.contains(e.target)) d.open = false;
        });
    });
    document.addEventListener('keydown', (e) => {
        if (e.key !== 'Escape') return;
        document.querySelectorAll('details.account-menu[open]').forEach((d) => {
            d.open = false;
            d.querySelector('summary')?.focus();
        });
    });

    // The saved header menu scrolls sideways: show the current link, and mark each end with more links past it.
    document.querySelectorAll('.glance-menu .wrap').forEach((strip) => {
        const current = strip.querySelector('[aria-current]');
        if (current && strip.scrollWidth > strip.clientWidth) {
            const a = current.getBoundingClientRect();
            const s = strip.getBoundingClientRect();
            strip.scrollLeft += (a.left - s.left) - (s.width - a.width) / 2;
        }
        const mark = () => {
            const left = Math.abs(strip.scrollLeft);
            strip.toggleAttribute('data-more', strip.scrollWidth - strip.clientWidth - left > 4);
            strip.toggleAttribute('data-more-start', left > 4);
        };
        mark();
        strip.addEventListener('scroll', mark, { passive: true });
        window.addEventListener('resize', mark);
    });

    // After a reload on a panel URL, Back lands on a list state this document never drew.
    window.addEventListener('popstate', (e) => {
        if (e.state && e.state.glanceList && !document.querySelector('[data-reader]')) location.reload();
    });

    document.querySelectorAll('select[data-autosubmit]').forEach((s) => {
        s.addEventListener('change', () => s.form?.requestSubmit());
    });
})();
