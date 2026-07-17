import Phaser from 'phaser';
import { ProceduralRunnerCharacter } from './characters/ProceduralRunnerCharacter.js';
import { CHARACTER_CONFIG } from './characters/procedural-runner-pose.js';

class SeededRandom {
    constructor(seed) { this.state = Number(BigInt(seed) & 0xffffffffn) || 1; }
    next() { let x = this.state; x ^= x << 13; x ^= x >>> 17; x ^= x << 5; this.state = x >>> 0; return this.state / 4294967296; }
    between(min, max) { return min + this.next() * (max - min); }
}

class JourneyScene extends Phaser.Scene {
    constructor(context) { super('journey'); this.ctx = context; }

    create() {
        this.cfg = this.ctx.session.config;
        this.rng = new SeededRandom(this.ctx.session.seed);
        this.running = false; this.finished = false; this.manualPaused = false; this.started = false;
        this.activeMs = 0; this.distance = 0; this.collectibles = 0; this.shieldPickups = 0; this.shieldUses = 0;
        this.shield = false; this.tier = 1; this.eventLog = []; this.events.toJSON = () => this.eventLog; this.nextSpawn = 650;
        this.lastHudAt = 0; this.lastMilestone = 0; this.personalBestShown = false; this.leaderShown = false; this.currentWorldSpeed = 0; this.deathKind = null;

        this.makeArt(); this.cameras.main.setBackgroundColor('#70d6ff');
        this.add.rectangle(640, 250, 1280, 500, 0x70d6ff); this.add.circle(1040, 110, 55, 0xffe66d);
        for (let i = 0; i < 6; i++) this.add.triangle(i * 260, 510, 0, 150, 130, 0, 260, 150, i % 2 ? 0x6cc58f : 0x54b878).setOrigin(.5, 1);
        this.add.rectangle(640, 660, 1280, 120, 0x61412e); this.add.rectangle(640, 606, 1280, 18, 0x7bd267);

        this.physics.world.setBounds(0, 0, 1280, 720);
        this.player = this.physics.add.sprite(215, 550, 'runner-body').setOrigin(.5, 1).setVisible(false).setCollideWorldBounds(true);
        this.player.body.setSize(CHARACTER_CONFIG.standingBody.width, CHARACTER_CONFIG.standingBody.height).setOffset(CHARACTER_CONFIG.standingBody.offsetX, CHARACTER_CONFIG.standingBody.offsetY); this.player.body.setGravityY(1800);
        this.character = new ProceduralRunnerCharacter(this, this.player);
        this.characterContext = { running: false, grounded: true, velocityY: 0, worldSpeed: 0, crouching: false, paused: false, gameOver: false };
        this.ground = this.physics.add.staticImage(640, 620, 'ground').setVisible(false); this.physics.add.collider(this.player, this.ground);
        this.hazards = this.physics.add.group({ allowGravity: false, immovable: true });
        this.items = this.physics.add.group({ allowGravity: false, immovable: true });
        this.physics.add.overlap(this.player, this.hazards, (_, hazard) => this.hit(hazard));
        this.physics.add.overlap(this.player, this.items, (_, item) => this.collect(item));

        this.keys = this.input.keyboard.addKeys({ jump: 'SPACE', up: 'UP', duck: 'DOWN', pause: 'P', escape: 'ESC', mute: 'M' });
        this.keys.jump.on('down', () => this.jump()); this.keys.up.on('down', () => this.jump());
        this.keys.duck.on('down', () => this.setDuck(true)); this.keys.duck.on('up', () => this.setDuck(false));
        this.keys.pause.on('down', () => this.togglePause()); this.keys.escape.on('down', () => this.togglePause()); this.keys.mute.on('down', () => this.ctx.ui.toggleMute());

        this.handlers = {
            start: () => this.startRun(), jump: () => this.jump(), duckStart: () => this.setDuck(true), duckEnd: () => this.setDuck(false),
            pause: () => this.togglePause(), resume: () => this.resume(), restart: () => this.ctx.restart(),
        };
        Object.entries(this.handlers).forEach(([name, handler]) => window.addEventListener(`daily-journey-${name.replace(/[A-Z]/g, c => `-${c.toLowerCase()}`)}`, handler));
        this.visibility = () => { if (document.hidden || !document.hasFocus()) this.pauseForFocus(); };
        document.addEventListener('visibilitychange', this.visibility); window.addEventListener('blur', this.visibility);
        this.events.once('shutdown', () => this.cleanup());
        this.ctx.ui.ready();
    }

    makeArt() {
        const g = this.add.graphics();
        g.fillStyle(0xffffff, 0).fillRect(0, 0, CHARACTER_CONFIG.visualWidth, CHARACTER_CONFIG.visualHeight).generateTexture('runner-body', CHARACTER_CONFIG.visualWidth, CHARACTER_CONFIG.visualHeight);
        g.clear().fillStyle(0x6b4f3b).fillRoundedRect(0, 0, 78, 65, 12).lineStyle(6, 0x4a3527).strokeRoundedRect(0, 0, 78, 65, 12).generateTexture('rock', 78, 65);
        g.clear().fillStyle(0xff5b35).fillTriangle(0, 70, 25, 0, 45, 70).fillStyle(0xffc43d).fillTriangle(20, 70, 42, 18, 66, 70).generateTexture('fire', 70, 70);
        g.clear().fillStyle(0x7c3f1d).fillRoundedRect(0, 0, 150, 34, 8).fillStyle(0xffffff).fillRect(15, 8, 120, 18).generateTexture('sign', 150, 34);
        g.clear().fillStyle(0x020617).fillEllipse(100, 35, 200, 70).lineStyle(8, 0x475569).strokeEllipse(100, 35, 200, 70).generateTexture('pit', 200, 70);
        g.clear().fillStyle(0xfbbf24).fillCircle(24, 24, 22).fillStyle(0xffffff).fillCircle(24, 24, 8).generateTexture('star', 48, 48);
        g.clear().lineStyle(7, 0x38bdf8).strokeCircle(30, 30, 26).fillStyle(0xffffff, .35).fillCircle(30, 30, 24).generateTexture('shield', 60, 60);
        g.clear().fillStyle(0xffffff).fillRect(0, 0, 1280, 30).generateTexture('ground', 1280, 30); g.destroy();
    }

    async startRun() {
        if (this.started || this.finished) return;
        this.started = true;
        try {
            await this.ctx.post(`${this.ctx.base}/${this.ctx.session.token}/start`, {});
            await this.ctx.ui.countdown();
            this.running = true; this.log('run_started'); this.ctx.ui.playing();
        } catch (error) {
            this.started = false; this.ctx.ui.startFailed(error);
        }
    }

    update(_, delta) {
        this.updateCharacter(delta);
        if (!this.running || this.finished || this.manualPaused) return;
        delta = Math.min(delta, 50); this.activeMs += delta;
        const seconds = this.activeMs / 1000;
        const speed = Math.min(this.cfg.maximumSpeed, this.cfg.startingSpeed + this.cfg.acceleration * seconds);
        this.currentWorldSpeed = speed;
        this.distance += speed * (delta / 1000) / 10;
        const nextTier = Math.min(5, 1 + Math.floor(seconds / 30));
        if (nextTier > this.tier) { this.tier = nextTier; this.ctx.ui.toast('TRAIL SPEED UP!', 'speed'); }
        this.nextSpawn -= speed * (delta / 1000); if (this.nextSpawn <= 0) this.spawnPattern(speed);
        [...this.hazards.getChildren(), ...this.items.getChildren()].forEach(object => { object.x -= speed * (delta / 1000); if (object.x < -220) object.destroy(); });
        const score = Math.floor(this.distance) + this.collectibles * this.cfg.collectibleBonus;
        if (this.time.now - this.lastHudAt >= this.cfg.ui.hudUpdateMs) { this.lastHudAt = this.time.now; this.ctx.ui.hud({ score, distance: this.distance }); }
        const milestone = this.cfg.ui.scoreMilestones.find(value => value > this.lastMilestone && score >= value);
        if (milestone) { this.lastMilestone = milestone; this.ctx.ui.toast(`${milestone.toLocaleString()} POINTS`, 'milestone'); }
        if (!this.personalBestShown && this.ctx.personalBest > 0 && score > this.ctx.personalBest) { this.personalBestShown = true; this.ctx.ui.toast('NEW PERSONAL BEST!', 'positive', true); }
        if (!this.leaderShown && this.ctx.leaderScore > 0 && score > this.ctx.leaderScore) { this.leaderShown = true; this.ctx.ui.toast('YOU TOOK THE LEAD!', 'champion', true); }
        if (this.ducking && this.time.now > this.duckUntil) this.setDuck(false);
    }

    updateCharacter(delta) {
        if (!this.character || !this.player?.body) return;
        const grounded = !this.started || this.player.body.blocked.down || this.player.body.touching.down;
        const pitFall = this.finished && this.deathKind === 'pit';
        const context = this.characterContext;
        context.running = this.running; context.grounded = pitFall ? false : grounded;
        context.velocityY = pitFall ? 1 : this.player.body.velocity.y; context.worldSpeed = this.currentWorldSpeed;
        context.crouching = Boolean(this.ducking); context.paused = this.manualPaused; context.gameOver = this.finished && !pitFall;
        this.character.update(delta, context);
    }

    spawnPattern(speed) {
        const roll = this.rng.next(); const type = roll < .22 ? 'rock' : roll < .42 ? 'fire' : roll < .61 ? 'sign' : roll < .78 ? 'pit' : roll < .90 ? 'star' : 'shield';
        const y = type === 'sign' ? 555 : type === 'star' ? 490 : type === 'shield' ? 470 : type === 'pit' ? 615 : 600;
        const object = (type === 'star' || type === 'shield' ? this.items : this.hazards).create(1390, y, type).setOrigin(.5, 1);
        object.kind = type; object.body.setAllowGravity(false); if (type === 'sign') object.body.setSize(145, 30); if (type === 'pit') object.body.setSize(180, 38).setOffset(10, 25);
        this.nextSpawn = this.rng.between(Math.max(this.cfg.minGap - speed * .12, 300), this.cfg.maxGap);
        if ((type === 'rock' || type === 'fire') && this.rng.next() > .45) { const star = this.items.create(1390, y - 120, 'star').setOrigin(.5, 1); star.kind = 'star'; star.body.setAllowGravity(false); }
    }

    jump() { if (this.running && !this.finished && !this.manualPaused && this.player.body.blocked.down) { this.setDuck(false); this.player.setVelocityY(-760); this.log('jump'); this.ctx.tone(420, .06); } }
    setDuck(value) { if (!this.running || this.finished || this.manualPaused) return; if (value) { this.ducking = true; this.duckUntil = this.time.now + 900; this.player.body.setSize(CHARACTER_CONFIG.crouchingBody.width, CHARACTER_CONFIG.crouchingBody.height).setOffset(CHARACTER_CONFIG.crouchingBody.offsetX, CHARACTER_CONFIG.crouchingBody.offsetY); this.log('duck_started'); } else if (this.ducking) { this.ducking = false; this.player.body.setSize(CHARACTER_CONFIG.standingBody.width, CHARACTER_CONFIG.standingBody.height).setOffset(CHARACTER_CONFIG.standingBody.offsetX, CHARACTER_CONFIG.standingBody.offsetY); this.log('duck_ended'); } }
    collect(item) { if (!item.active) return; if (item.kind === 'star') { this.collectibles++; this.log('collectible_collected'); this.ctx.ui.toast(`+${this.cfg.collectibleBonus} STAR`, 'star'); this.ctx.tone(720, .05); } else { this.shield = true; this.character.setShieldActive(true); this.shieldPickups++; this.log('shield_collected'); this.ctx.ui.shield('ready'); this.ctx.ui.toast('SHIELD READY', 'shield', true); this.ctx.tone(560, .12); } item.destroy(); }
    hit(hazard) { if (!hazard.active || this.finished) return; if (this.shield && hazard.kind !== 'pit') { this.shield = false; this.character.breakShield(); this.shieldUses++; this.log('shield_used'); this.ctx.ui.shield('broken'); this.ctx.ui.toast('SHIELD BROKEN', 'warning', true); this.ctx.tone(180, .15); hazard.destroy(); return; } this.deathKind = hazard.kind; if (hazard.kind !== 'pit') this.character.playHit(); this.log(hazard.kind === 'pit' ? 'pit_fall' : 'collision'); this.gameOver(); }

    togglePause() { if (!this.running || this.finished) return; this.manualPaused ? this.resume() : this.pause(); }
    pause() { if (this.manualPaused) return; this.manualPaused = true; this.ctx.ui.paused(); }
    resume() { if (!this.manualPaused || this.finished) return; this.manualPaused = false; this.ctx.ui.playing(true); }
    pauseForFocus() { if (this.running && !this.finished && !this.manualPaused) this.pause(); }

    async gameOver() {
        if (this.finished) return;
        this.finished = true; this.running = false; this.physics.pause(); this.log('run_ended');
        const summary = { score: Math.floor(this.distance) + this.collectibles * this.cfg.collectibleBonus, distance: Math.floor(this.distance), collectibles: this.collectibles };
        const payload = { score: summary.score, distance: Number(this.distance.toFixed(2)), duration_ms: Math.floor(this.activeMs), collectible_count: this.collectibles, powerup_pickup_count: this.shieldPickups, powerup_use_count: this.shieldUses, maximum_speed_tier: this.tier, client_version: this.ctx.session.version, events: this.eventLog };
        this.ctx.ui.gameOver(summary); this.ctx.ui.submitting(summary);
        try { const result = await this.ctx.post(`${this.ctx.base}/${this.ctx.session.token}/finish`, payload); this.ctx.ui.submitted(summary, result); }
        catch (error) { this.ctx.ui.submitted(summary, error.payload || {}, error); }
    }

    log(event) { if (this.eventLog.length < 120) this.eventLog.push({ t: Math.floor(this.activeMs), e: event }); }
    cleanup() { document.removeEventListener('visibilitychange', this.visibility); window.removeEventListener('blur', this.visibility); Object.entries(this.handlers).forEach(([name, handler]) => window.removeEventListener(`daily-journey-${name.replace(/[A-Z]/g, c => `-${c.toLowerCase()}`)}`, handler)); this.character?.destroy(); }
}

export function createGame(parent, session, helpers) {
    return new Phaser.Game({ type: Phaser.AUTO, parent, width: 1280, height: 720, backgroundColor: '#70d6ff', physics: { default: 'arcade', arcade: { debug: false } }, scale: { mode: Phaser.Scale.FIT, autoCenter: Phaser.Scale.CENTER_BOTH }, render: { antialias: true, pixelArt: false }, scene: [new JourneyScene({ ...helpers, session })] });
}
