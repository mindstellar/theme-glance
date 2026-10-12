/*
 * Glance — a Shopclass public theme.
 * Copyright (c) 2026 Navjot Tomer (Mindstellar) and contributors
 * SPDX-License-Identifier: GPL-3.0-or-later
 *
 * Post and edit ad form: category steps, inline validation, character counters, and keyboard
 * access for core's uploader. Core's server checks stay the final word; nothing here is needed to post.
 */
(function () {
    'use strict';

    var form;
    var T = {};

    function controlOf(field) {
        var label = field.querySelector('label[for]');
        return label ? document.getElementById(label.getAttribute('for')) : null;
    }

    function addDescribedBy(el, id) {
        var ids = (el.getAttribute('aria-describedby') || '').split(/\s+/).filter(Boolean);
        if (ids.indexOf(id) === -1) { ids.push(id); }
        el.setAttribute('aria-describedby', ids.join(' '));
    }

    function errorBox(field, el) {
        var box = field.querySelector('.post-error');
        if (box) { return box; }
        box = document.createElement('p');
        box.className = 'post-error';
        box.id = el.id + '-error';
        box.hidden = true;
        field.appendChild(box);
        addDescribedBy(el, box.id);
        return box;
    }

    function messageFor(el) {
        var v = el.validity;
        if (v.customError) { return el.validationMessage; }
        if (v.valueMissing) { return T.required; }
        if (v.typeMismatch) { return el.type === 'email' ? T.email : el.validationMessage; }
        if (v.tooShort) { return T.short; }
        if (v.tooLong) { return T.over; }
        if (v.patternMismatch) { return el.id === 'price' ? T.number : el.validationMessage; }
        return el.validationMessage;
    }

    var busy = false;

    function show(field, el) {
        if (busy) { return false; }
        busy = true;
        try { return paint(field, el); } finally { busy = false; }
    }

    function paint(field, el) {
        var box = errorBox(field, el);
        var ok = el.checkValidity();
        el.setAttribute('aria-invalid', ok ? 'false' : 'true');
        if (ok) {
            box.hidden = true;
            box.textContent = '';
        } else {
            box.textContent = messageFor(el);
            box.hidden = false;
        }
        return ok;
    }

    function wireField(field) {
        var el = controlOf(field);
        if (!el || el.dataset.postWired) { return; }
        el.dataset.postWired = '1';

        var hint = field.querySelector('.post-hint');
        if (hint) { addDescribedBy(el, hint.id); }

        var max = parseInt(field.getAttribute('data-max'), 10) || 0;
        var count = null;
        if (max > 0 && !window.tinymce) {
            count = document.createElement('span');
            count.className = 'post-count';
            count.id = el.id + '-count';
            field.appendChild(count);
            addDescribedBy(el, count.id);
        }

        function hiddenEditor() {
            return el.tagName === 'TEXTAREA' && window.getComputedStyle(el).display === 'none';
        }

        function update() {
            if (count && hiddenEditor()) {
                count.hidden = true;
                el.required = false;
                el.setCustomValidity('');
                return;
            }
            if (count) {
                var n = el.value.length;
                var left = max - n;
                count.textContent = n + ' / ' + max;
                count.classList.toggle('is-near', left >= 0 && left <= Math.max(20, max * 0.1));
                count.classList.toggle('is-over', left < 0);
                if (el.tagName === 'TEXTAREA') {
                    el.setCustomValidity(left < 0 ? T.over : '');
                }
            }
            if (el.getAttribute('aria-invalid') === 'true') { show(field, el); }
        }

        el.addEventListener('input', update);
        el.addEventListener('change', function () { show(field, el); });
        el.addEventListener('blur', function () {
            if (el.value !== '' || el.required) { show(field, el); }
        });
        el.addEventListener('invalid', function (e) {
            e.preventDefault();
            show(field, el);
        });
        update();
        if (count) { window.addEventListener('load', function () { setTimeout(update, 600); }); }
    }

    function setup() {
        var cat = document.getElementById('catId');
        var price = document.getElementById('price');
        var email = document.getElementById('contactEmail');
        var title = form.querySelector('input[name^="title["]');
        var desc = form.querySelector('textarea[name^="description["]');
        var currency = document.getElementById('currency');

        if (cat) { cat.required = true; }
        if (title) { title.required = true; }
        if (desc) { desc.required = true; desc.minLength = 3; }
        if (email) { email.type = 'email'; email.required = true; email.autocomplete = 'email'; }
        var name = document.getElementById('contactName');
        if (name) { name.autocomplete = 'name'; name.minLength = 3; name.maxLength = 35; }
        var addr = document.getElementById('address');
        if (addr) { addr.minLength = 3; addr.maxLength = 100; }
        ['regionId', 'cityId'].forEach(function (id) {
            var sel = document.getElementById(id);
            if (sel && sel.tagName === 'SELECT') { sel.required = true; }
        });
        var phone = document.getElementById('contactPhone');
        if (phone) { phone.type = 'tel'; phone.autocomplete = 'tel'; }
        if (price) {
            price.inputMode = 'decimal';
            price.pattern = '[0-9]+([.,][0-9]+)?|[0-9][0-9 ,.\']*';
            price.autocomplete = 'off';
        }
        if (currency && currency.tagName === 'SELECT') { currency.setAttribute('aria-label', T.currency); }

        form.querySelectorAll('.post-field').forEach(wireField);
        form.noValidate = true;

        form.addEventListener('submit', function (e) {
            var first = null;
            if (window.tinymce && window.tinymce.triggerSave) { window.tinymce.triggerSave(); }
            form.querySelectorAll('.post-field').forEach(function (field) {
                var el = controlOf(field);
                if (!el || el.disabled || el.closest('[hidden]')) { return; }
                if (el.tagName === 'TEXTAREA' && window.getComputedStyle(el).display === 'none') {
                    el.required = false;
                    el.minLength = 0;
                    el.setCustomValidity('');
                    return;
                }
                if (!show(field, el) && !first) { first = el; }
            });
            if (!first) {
                var hook = document.getElementById('plugin-hook');
                var bad = hook ? Array.prototype.find.call(hook.querySelectorAll('input, select, textarea'), function (c) {
                    return !c.disabled && !c.checkValidity();
                }) : null;
                if (bad) {
                    e.preventDefault();
                    bad.reportValidity();
                    return;
                }
            }
            if (first) {
                e.preventDefault();
                first.focus();
                first.scrollIntoView({ block: 'center', behavior: 'smooth' });
            }
        }, true);
    }

    // Category in steps: one native select per level. They drive core's catId select, which stays in
    // the form (hidden) so core's change handlers and the custom-field reload still run.
    function depthOf(option) {
        var m = option.textContent.match(/^ */);
        return Math.floor(m[0].length / 2);
    }

    function categoryTree(select) {
        var roots = [];
        var stack = [];
        function add(node, depth) {
            depth = Math.min(depth, stack.length);
            stack.length = depth;
            node.parent = depth > 0 ? stack[depth - 1] : null;
            (node.parent ? node.parent.children : roots).push(node);
            stack.push(node);
        }
        function leaf(o) {
            return { value: o.value, label: o.textContent.trim(), children: [] };
        }
        Array.prototype.forEach.call(select.children, function (el) {
            if (el.tagName === 'OPTGROUP') {
                stack.length = 0;
                add({ value: '', label: el.label, children: [] }, 0);
                Array.prototype.forEach.call(el.children, function (o) { add(leaf(o), Math.max(1, depthOf(o))); });
            } else if (el.value !== '') {
                add(leaf(el), depthOf(el));
            }
        });
        return roots.filter(function (n) { return n.value !== '' || n.children.length; });
    }

    function findNode(nodes, value) {
        for (var i = 0; i < nodes.length; i++) {
            if (nodes[i].value === value) { return nodes[i]; }
            var hit = findNode(nodes[i].children, value);
            if (hit) { return hit; }
        }
        return null;
    }

    function wireCategory() {
        var cat = document.getElementById('catId');
        if (!cat || cat.tagName !== 'SELECT') { return; }
        var native = cat.closest('.post-field');
        var tree = categoryTree(cat);
        if (!native || !tree.some(function (n) { return n.children.length; })) { return; }

        var label = native.querySelector('label');
        var steps = document.createElement('div');
        steps.className = 'post-cat-steps';
        native.parentNode.insertBefore(steps, native);
        native.classList.add('post-cat-native');
        native.setAttribute('aria-hidden', 'true');
        cat.tabIndex = -1;
        cat.required = false;

        var levels = [];
        var hook = document.getElementById('plugin-hook');

        function setCat(value) {
            if (cat.value === value) { return; }
            if (hook) { hook.style.minHeight = hook.offsetHeight + 'px'; }
            cat.value = value;
            cat.dispatchEvent(new Event('change', { bubbles: true }));
        }

        function addLevel(nodes, parent) {
            var i = levels.length;
            var field = document.createElement('div');
            field.className = 'post-field';
            var lab = document.createElement('label');
            var sel = document.createElement('select');
            sel.id = 'post-cat-' + i;
            lab.htmlFor = sel.id;
            lab.textContent = i === 0 ? (label ? label.textContent : '') : T.subcat;
            var first = new Option(i === 0 ? cat.options[0].textContent : T.subcatPick, '');
            sel.add(first);
            nodes.forEach(function (n, k) { sel.add(new Option(n.label, String(k))); });
            sel.required = i === 0 || !parent.value;
            field.appendChild(lab);
            field.appendChild(sel);
            steps.appendChild(field);
            var level = { field: field, select: sel, nodes: nodes, parent: parent };
            levels.push(level);
            sel.addEventListener('change', function () { pick(i); });
            wireField(field);
            return level;
        }

        function pick(i) {
            var level = levels[i];
            levels.splice(i + 1).forEach(function (l) { l.field.remove(); });
            var node = level.select.value === '' ? null : level.nodes[level.select.value];
            if (node && node.children.length) { addLevel(node.children, node); }
            setCat(node ? node.value : (level.parent ? level.parent.value : ''));
        }

        var level = addLevel(tree, null);
        var path = [];
        for (var n = cat.value === '' ? null : findNode(tree, cat.value); n; n = n.parent) { path.unshift(n); }
        path.forEach(function (node) {
            level.select.value = String(level.nodes.indexOf(node));
            level = node.children.length ? addLevel(node.children, node) : level;
        });
    }

    // Price, then the fields core loads for the chosen category. Hold the height while they swap so
    // the page does not jump, and say when they have arrived.
    function wireDetails() {
        var hook = document.getElementById('plugin-hook');
        var cat = document.getElementById('catId');
        var section = hook ? hook.closest('.post-details') : null;
        if (!section || !cat) { return; }
        var note = section.querySelector('[data-post-details-note]');
        var live = section.querySelector('[data-post-details-live]');
        var price = document.getElementById('price');
        var changed = false;

        function hasFields() {
            return !!hook.querySelector('input:not([type="hidden"]), select, textarea');
        }

        function sync() {
            var picked = cat.value !== '';
            var priceShown = price && price.type !== 'hidden' && !price.closest('.osc-price-hidden');
            if (note) { note.hidden = picked; }
            section.classList.toggle('is-empty', picked && !priceShown && !hasFields());
        }

        cat.addEventListener('change', function () { changed = true; });
        hook.addEventListener('osc:item-fields-loaded', function () {
            hook.style.minHeight = '';
            sync();
            if (!changed || !live || cat.value === '') { return; }
            var name = cat.options[cat.selectedIndex].textContent.trim();
            live.textContent = (hasFields() ? T.fields : T.nofields).replace('%s', name);
        });
        sync();
    }

    // Core hides the real file input inside the drop label; make the label a keyboard control.
    function wireUploader() {
        var root = document.getElementById('osc-uploader');
        if (!root) { return; }
        var drop = root.querySelector('.osc-uploader-drop');
        var input = root.querySelector('.osc-uploader-input');
        var errors = root.querySelector('.osc-uploader-errors');
        if (errors) {
            errors.setAttribute('role', 'alert');
        }
        if (!drop || !input) { return; }
        drop.tabIndex = 0;
        drop.setAttribute('role', 'button');
        drop.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                input.click();
            }
        });
    }

    function init() {
        form = document.querySelector('[data-post-form]');
        if (!form) { return; }
        var d = form.dataset;
        T = {
            required: d.msgRequired || 'This is required.',
            email: d.msgEmail || 'Enter a valid email address.',
            short: d.msgShort || 'Write a little more.',
            over: d.msgOver || 'This is too long.',
            number: d.msgNumber || 'Enter a number, like 1200 or 1200.50.',
            currency: d.msgCurrency || '',
            subcat: d.msgSubcat || 'Subcategory',
            subcatPick: d.msgSubcatPick || 'Choose a subcategory',
            fields: d.msgFields || '%s',
            nofields: d.msgNofields || '%s'
        };
        setup();
        wireCategory();
        wireDetails();
        wireUploader();
        wireEditor();
    }

    /* Core's description editor writes in a frame of its own; give it the page's colours and scheme. */
    function paintEditor(ed) {
        var doc = ed.getDoc && ed.getDoc();
        if (!doc || !doc.body) { return; }
        var root = document.documentElement;
        var dark = root.dataset.theme === 'dark' ||
            (root.dataset.theme !== 'light' && window.matchMedia('(prefers-color-scheme: dark)').matches);
        var page = getComputedStyle(document.body);
        doc.documentElement.style.colorScheme = dark ? 'dark' : 'light';
        doc.documentElement.style.background = 'transparent';
        doc.body.style.background = 'transparent';
        doc.body.style.color = page.color;
        doc.body.style.fontFamily = page.fontFamily;
    }

    function wireEditor() {
        var mce = window.tinymce;
        if (!mce || !mce.on) {
            // Core loads the editor after this file; try again once the page has loaded.
            if (document.readyState !== 'complete') { window.addEventListener('load', wireEditor, { once: true }); }
            return;
        }
        var all = function () { (mce.get() || []).forEach(paintEditor); };
        var watch = function (ed) {
            if (ed.initialized) { paintEditor(ed); } else { ed.on('init', function () { paintEditor(ed); }); }
        };
        (mce.get() || []).forEach(watch);
        mce.on('AddEditor', function (e) { watch(e.editor); });
        new MutationObserver(all).observe(document.documentElement, { attributes: true, attributeFilter: ['data-theme'] });
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', all);
        all();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
}());
