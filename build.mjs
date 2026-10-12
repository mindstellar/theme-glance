/*
 * Minify every css/*.css and js/*.js into a .min.* sibling. Files already
 * named .min.* are skipped. Also writes one CSS bundle per page type
 * (css/bundle-<surface>.min.css) so each page loads a single theme stylesheet.
 */
import { build, transform } from 'esbuild';
import { readdirSync, readFileSync, writeFileSync, statSync } from 'node:fs';
import { join, extname } from 'node:path';

// Target esnext so modern CSS (light-dark(), color-mix(), nesting, @starting-style)
// is kept as written instead of being lowered.
const common = { minify: true, bundle: false, legalComments: 'none', charset: 'utf8', target: 'esnext' };

const sources = (dir, ext) =>
    readdirSync(dir)
        .filter((f) => extname(f) === ext && !f.includes('.min.') && !f.startsWith('bundle-'))
        .map((f) => join(dir, f));

const files = [...sources('css', '.css'), ...sources('js', '.js')];

for (const entry of files) {
    const outfile = entry.replace(/\.(css|js)$/, '.min.$1');
    await build({ ...common, entryPoints: [entry], outfile, logLevel: 'silent' });
    const before = statSync(entry).size;
    const after = statSync(outfile).size;
    console.log(`${entry} -> ${outfile}  ${before} B -> ${after} B`);
}

// Must match the CSS lists in glance_enqueue_assets() (functions.php), in the same order.
const bundles = {
    list: ['glance', 'list'],
    item: ['glance', 'item'],
    account: ['glance', 'account'],
    post: ['glance', 'account', 'post'],
};

for (const [name, parts] of Object.entries(bundles)) {
    const css = parts.map((p) => readFileSync(join('css', p + '.css'), 'utf8')).join('\n');
    const { code } = await transform(css, { minify: true, legalComments: 'none', charset: 'utf8', target: 'esnext', loader: 'css' });
    const outfile = join('css', `bundle-${name}.min.css`);
    writeFileSync(outfile, code);
    console.log(`${parts.join(' + ')} -> ${outfile}  ${Buffer.byteLength(code)} B`);
}
