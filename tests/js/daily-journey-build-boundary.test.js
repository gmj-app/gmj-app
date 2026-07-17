import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import path from 'node:path';

const root = path.resolve(import.meta.dirname, '../..');
const read = relative => fs.readFileSync(path.join(root, relative), 'utf8');

test('Daily Journey keeps launcher, game, and Phaser behind separate dynamic boundaries', () => {
    const app = read('resources/js/app.js');
    const launcher = read('resources/js/daily-journey/index.js');
    const game = read('resources/js/daily-journey/game.js');

    assert.match(app, /import\('\.\/daily-journey\/index\.js'\)/);
    assert.doesNotMatch(app, /from ['"].*daily-journey/);
    assert.match(launcher, /import\('\.\/game\.js'\)/);
    assert.doesNotMatch(launcher, /from ['"]\.\/game(?:\.js)?['"]/);
    assert.doesNotMatch(launcher, /\bphaser\b/i);
    assert.match(game, /from 'phaser\/dist\/phaser-arcade-physics\.min\.js'/);
});

test('Phaser is imported once and production code contains no glob, fixture, or debug import', () => {
    const directory = path.join(root, 'resources/js/daily-journey');
    const files = [];
    const visit = current => {
        for (const entry of fs.readdirSync(current, { withFileTypes: true })) {
            const target = path.join(current, entry.name);
            if (entry.isDirectory()) visit(target);
            else if (entry.name.endsWith('.js')) files.push(target);
        }
    };
    visit(directory);
    const sources = files.map(file => fs.readFileSync(file, 'utf8'));

    assert.equal(sources.filter(source => /from ['"]phaser(?:\/|['"])/.test(source)).length, 1);
    for (const source of sources) {
        assert.doesNotMatch(source, /import\.meta\.glob/);
        assert.doesNotMatch(source, /from ['"].*(fixture|debug|test)/i);
    }
});

test('Vite production settings avoid source maps, compression reporting, and Terser', () => {
    const vite = read('vite.config.js');
    assert.match(vite, /reportCompressedSize:\s*false/);
    assert.match(vite, /sourcemap:\s*false/);
    assert.match(vite, /minify:\s*'esbuild'/);
    assert.match(vite, /cssCodeSplit:\s*true/);
    assert.match(vite, /daily-journey-phaser/);
    assert.doesNotMatch(vite, /terser/i);
});
