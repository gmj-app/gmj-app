import test from 'node:test';
import assert from 'node:assert/strict';
import fs from 'node:fs';
import {
    calculatePose, CHARACTER_CONFIG, CHARACTER_STATES, createPose, resolveCharacterState,
} from '../../resources/js/daily-journey/characters/procedural-runner-pose.js';
import { ProceduralRunnerCharacter } from '../../resources/js/daily-journey/characters/ProceduralRunnerCharacter.js';

class FakeGraphics {
    constructor() { this.calls = []; }
    call(name, ...args) { this.calls.push([name, ...args]); return this; }
    clear() { this.calls = []; return this; }
    lineStyle(...args) { return this.call('lineStyle', ...args); }
    fillStyle(...args) { return this.call('fillStyle', ...args); }
    fillEllipse(...args) { return this.call('fillEllipse', ...args); }
    strokeEllipse(...args) { return this.call('strokeEllipse', ...args); }
    fillCircle(...args) { return this.call('fillCircle', ...args); }
    strokeCircle(...args) { return this.call('strokeCircle', ...args); }
    fillRoundedRect(...args) { return this.call('fillRoundedRect', ...args); }
    beginPath() { return this.call('beginPath'); }
    moveTo(...args) { return this.call('moveTo', ...args); }
    lineTo(...args) { return this.call('lineTo', ...args); }
    strokePath() { return this.call('strokePath'); }
}

function fakeScene() {
    const objects = [];
    const scene = { add: {
        graphics() { const graphics = new FakeGraphics(); objects.push(graphics); return graphics; },
        container(x, y) {
            const container = {
                x, y, children: [], destroyed: false, visible: true,
                setDepth() { return this; }, add(children) { this.children.push(...children); return this; },
                setPosition(nextX, nextY) { this.x = nextX; this.y = nextY; return this; },
                setVisible(value) { this.visible = value; return this; }, destroy() { this.destroyed = true; },
            };
            objects.push(container); return container;
        },
    } };
    return { scene, objects };
}

const context = (overrides = {}) => ({ running: true, grounded: true, velocityY: 0, worldSpeed: 340, crouching: false, paused: false, gameOver: false, ...overrides });

test('all explicit visual states are registered and resolve by priority', () => {
    assert.deepEqual(CHARACTER_STATES, ['ready', 'running', 'jumping', 'falling', 'crouching', 'hit', 'game_over']);
    assert.equal(resolveCharacterState({ running: true, grounded: true }), 'running');
    assert.equal(resolveCharacterState({ running: true, grounded: false, velocityY: -2 }), 'jumping');
    assert.equal(resolveCharacterState({ running: true, grounded: false, velocityY: 2 }), 'falling');
    assert.equal(resolveCharacterState({ gameOver: true, crouching: true, hit: true }), 'hit');
    assert.equal(resolveCharacterState({ gameOver: true, crouching: true }), 'game_over');
});

test('running uses opposing limbs and remains stable at large phase values', () => {
    const ready = calculatePose(createPose(), 'running', 0);
    const stride = calculatePose(createPose(), 'running', Math.PI / 2);
    assert.ok(stride.leftFoot.x > ready.leftFoot.x);
    assert.ok(stride.rightHand.x > ready.rightHand.x);
    const fast = calculatePose(createPose(), 'running', Number.MAX_SAFE_INTEGER);
    for (const joint of Object.values(fast)) assert.ok(Number.isFinite(joint.x) && Number.isFinite(joint.y));
});

test('jump, fall, crouch, hit and game over poses are distinct', () => {
    const poses = ['jumping', 'falling', 'crouching', 'hit', 'game_over'].map(state => JSON.stringify(calculatePose(createPose(), state, 0, { velocityY: state === 'jumping' ? -500 : 500 })));
    assert.equal(new Set(poses).size, poses.length);
    const crouch = calculatePose(createPose(), 'crouching');
    assert.ok(crouch.head.y > -45);
    assert.ok(crouch.leftKnee.y > -15);
});

test('reduced motion lowers decorative run movement', () => {
    const full = calculatePose(createPose(), 'running', Math.PI / 2, { reducedMotion: false });
    const reduced = calculatePose(createPose(), 'running', Math.PI / 2, { reducedMotion: true });
    assert.ok(Math.abs(reduced.leftFoot.x - 7) < Math.abs(full.leftFoot.x - 7));
});

test('standing and crouching bodies preserve the same foot line', () => {
    const standing = CHARACTER_CONFIG.standingBody; const crouching = CHARACTER_CONFIG.crouchingBody;
    assert.deepEqual(standing, { width: 56, height: 82, offsetX: 12, offsetY: 8 });
    assert.deepEqual(crouching, { width: 56, height: 42, offsetX: 12, offsetY: 48 });
    assert.equal(standing.height + standing.offsetY, crouching.height + crouching.offsetY);
});

test('renderer owns a fixed object set, advances with delta time and freezes while paused', () => {
    const { scene, objects } = fakeScene(); const carrier = { x: 215, y: 620 };
    const character = new ProceduralRunnerCharacter(scene, carrier, { reducedMotion: false });
    assert.equal(objects.length, 4);
    character.update(20, context()); const first = character.phase;
    character.update(40, context()); assert.ok(character.phase > first);
    const paused = character.phase; character.update(50, context({ paused: true })); assert.equal(character.phase, paused);
    character.update(20, context()); assert.ok(character.phase > paused);
    assert.equal(objects.length, 4);
});

test('shield and break effects reuse graphics without changing body configuration', () => {
    const { scene } = fakeScene(); const character = new ProceduralRunnerCharacter(scene, { x: 0, y: 0 }, { reducedMotion: true });
    const bodyBefore = JSON.stringify(CHARACTER_CONFIG.standingBody);
    character.setShieldActive(true).update(16, context());
    assert.ok(character.shieldGraphics.calls.some(([name]) => name === 'strokeEllipse'));
    character.breakShield().update(16, context());
    assert.equal(character.shieldActive, false);
    assert.ok(character.effectGraphics.calls.some(([name]) => name === 'strokeEllipse'));
    for (let elapsed = 0; elapsed < CHARACTER_CONFIG.shieldBreakDurationMs + 50; elapsed += 50) character.update(50, context());
    assert.equal(character.breakRemaining, 0);
    assert.equal(JSON.stringify(CHARACTER_CONFIG.standingBody), bodyBefore);
});

test('hit overrides running, game over stops phase, reset and destroy clean up', () => {
    const { scene } = fakeScene(); const character = new ProceduralRunnerCharacter(scene, { x: 0, y: 0 });
    character.playHit().update(16, context()); assert.equal(character.state, 'hit');
    character.hitRemaining = 0; character.update(16, context({ gameOver: true })); assert.equal(character.state, 'game_over');
    const stopped = character.phase; character.update(40, context({ gameOver: true })); assert.equal(character.phase, stopped);
    character.reset(); assert.equal(character.state, 'ready'); assert.equal(character.phase, 0);
    character.destroy(); assert.equal(character.root.destroyed, true); character.destroy();
});

test('renderer interface is substitutable and performs no network work', () => {
    const methods = ['setState', 'setShieldActive', 'playHit', 'setPosition', 'setVisible', 'update', 'reset', 'destroy'];
    for (const method of methods) assert.equal(typeof ProceduralRunnerCharacter.prototype[method], 'function');
    const source = fs.readFileSync(new URL('../../resources/js/daily-journey/characters/ProceduralRunnerCharacter.js', import.meta.url), 'utf8');
    assert.doesNotMatch(source, /\b(fetch|XMLHttpRequest|WebSocket)\b/);
});

test('game keeps an invisible physics carrier and no visible square texture', () => {
    const source = fs.readFileSync(new URL('../../resources/js/daily-journey/game.js', import.meta.url), 'utf8');
    assert.match(source, /physics\.add\.sprite\(215, 550, 'runner-body'\)/);
    assert.match(source, /setVisible\(false\)/);
    assert.doesNotMatch(source, /fillRoundedRect\(0, 0, 80, 90/);
    assert.match(source, /collectible_count: this\.collectibles/);
    assert.match(source, /powerup_use_count: this\.shieldUses/);
});
