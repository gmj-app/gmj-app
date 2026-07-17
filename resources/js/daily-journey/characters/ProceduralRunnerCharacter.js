import {
    calculatePose, CHARACTER_CONFIG, copyPose, createPose, resolveCharacterState,
} from './procedural-runner-pose.js';

export class ProceduralRunnerCharacter {
    constructor(scene, physicsCarrier, options = {}) {
        this.scene = scene;
        this.physicsCarrier = physicsCarrier;
        this.config = options.config || CHARACTER_CONFIG;
        this.reducedMotion = options.reducedMotion ?? globalThis.matchMedia?.('(prefers-reduced-motion: reduce)').matches ?? false;
        this.root = scene.add.container(physicsCarrier.x, physicsCarrier.y).setDepth(options.depth ?? 20);
        this.shieldGraphics = scene.add.graphics();
        this.figureGraphics = scene.add.graphics();
        this.effectGraphics = scene.add.graphics();
        this.root.add([this.shieldGraphics, this.figureGraphics, this.effectGraphics]);
        this.currentPose = createPose(); this.targetPose = createPose();
        this.poseOptions = { reducedMotion: this.reducedMotion, landing: 0, velocityY: 0 };
        this.phase = 0; this.state = 'ready'; this.hitRemaining = 0; this.breakRemaining = 0; this.landingRemaining = 0;
        this.shieldActive = false; this.wasGrounded = true; this.destroyed = false;
        calculatePose(this.currentPose, 'ready'); this.draw();
    }

    setState(state) { this.state = state; return this; }
    setShieldActive(active) { this.shieldActive = Boolean(active); if (active) this.breakRemaining = 0; return this; }
    playHit() { this.hitRemaining = this.config.hitDurationMs; return this; }
    breakShield() { this.shieldActive = false; this.breakRemaining = this.config.shieldBreakDurationMs; return this; }
    setPosition(x, y) { this.root.setPosition(x, y); return this; }
    setVisible(visible) { this.root.setVisible(visible); return this; }

    update(delta, context = {}) {
        if (this.destroyed) return;
        this.setPosition(this.physicsCarrier.x, this.physicsCarrier.y);
        if (context.paused) return;

        const safeDelta = Math.min(Math.max(delta || 0, 0), 50);
        if (!this.wasGrounded && context.grounded && context.running) this.landingRemaining = this.config.landingDurationMs;
        this.wasGrounded = context.grounded;
        this.hitRemaining = Math.max(0, this.hitRemaining - safeDelta);
        this.breakRemaining = Math.max(0, this.breakRemaining - safeDelta);
        this.landingRemaining = Math.max(0, this.landingRemaining - safeDelta);
        if (context.running && context.grounded && !context.crouching && !context.gameOver) {
            const cycles = this.config.runCyclesPerSecond + Math.max(0, context.worldSpeed || 0) * this.config.runSpeedInfluence;
            this.phase = (this.phase + safeDelta * 0.001 * cycles * Math.PI * 2) % (Math.PI * 2);
        } else if (!context.gameOver && !context.crouching) {
            this.phase = (this.phase + safeDelta * 0.0015) % (Math.PI * 2);
        }

        this.state = resolveCharacterState(context, this.hitRemaining > 0);
        this.poseOptions.landing = this.landingRemaining / this.config.landingDurationMs;
        this.poseOptions.velocityY = context.velocityY || 0;
        calculatePose(this.targetPose, this.state, this.phase, this.poseOptions);
        copyPose(this.currentPose, this.targetPose, Math.min(1, safeDelta / (this.reducedMotion ? 45 : 70)));
        this.draw();
    }

    draw() {
        const p = this.currentPose; const c = this.config; const colors = c.colors; const g = this.figureGraphics;
        g.clear();
        g.lineStyle(c.limbThickness + c.outlineThickness * 2, colors.outline, 0.9);
        this.drawSkeleton(g, p);
        g.lineStyle(c.limbThickness, colors.pants, 1);
        this.line(g, p.hip, p.leftKnee); this.line(g, p.leftKnee, p.leftFoot); this.line(g, p.hip, p.rightKnee); this.line(g, p.rightKnee, p.rightFoot);
        g.lineStyle(c.limbThickness, colors.hoodie, 1);
        this.line(g, p.shoulder, p.leftElbow); this.line(g, p.leftElbow, p.leftHand); this.line(g, p.shoulder, p.rightElbow); this.line(g, p.rightElbow, p.rightHand);
        g.lineStyle(c.limbThickness + 3, colors.hoodie, 1); this.line(g, p.shoulder, p.hip);
        g.lineStyle(3, colors.hoodieAccent, 1); this.lineXY(g, p.shoulder.x + 1, p.shoulder.y + 5, p.hip.x + 1, p.hip.y - 4);
        g.fillStyle(colors.shoes, 1); g.fillRoundedRect(p.leftFoot.x - 3, p.leftFoot.y - 3, 13, 5, 2); g.fillRoundedRect(p.rightFoot.x - 3, p.rightFoot.y - 3, 13, 5, 2);
        g.lineStyle(c.outlineThickness, colors.outline, 1).fillStyle(colors.skin, 1).fillCircle(p.head.x, p.head.y, c.headRadius).strokeCircle(p.head.x, p.head.y, c.headRadius);
        g.lineStyle(2, colors.glasses, 1).strokeCircle(p.head.x - 4, p.head.y - 1, 3).strokeCircle(p.head.x + 4, p.head.y - 1, 3);
        this.lineXY(g, p.head.x - 1, p.head.y - 1, p.head.x + 1, p.head.y - 1);
        g.lineStyle(2, colors.glasses, 0.7).beginPath().moveTo(p.head.x - 4, p.head.y + 5).lineTo(p.head.x + 4, p.head.y + 6).strokePath();
        this.drawShield();
    }

    drawSkeleton(g, p) {
        this.line(g, p.shoulder, p.hip); this.line(g, p.shoulder, p.leftElbow); this.line(g, p.leftElbow, p.leftHand);
        this.line(g, p.shoulder, p.rightElbow); this.line(g, p.rightElbow, p.rightHand); this.line(g, p.hip, p.leftKnee);
        this.line(g, p.leftKnee, p.leftFoot); this.line(g, p.hip, p.rightKnee); this.line(g, p.rightKnee, p.rightFoot);
    }

    drawShield() {
        const c = this.config; const pulse = this.reducedMotion ? 0 : Math.sin(this.phase * 0.6) * 1.5;
        this.shieldGraphics.clear(); this.effectGraphics.clear();
        if (this.shieldActive) {
            this.shieldGraphics.fillStyle(c.colors.shield, 0.1).fillEllipse(0, -44, (c.shieldRadiusX + pulse) * 2, (c.shieldRadiusY + pulse) * 2);
            this.shieldGraphics.lineStyle(3, c.colors.shield, 0.8).strokeEllipse(0, -44, (c.shieldRadiusX + pulse) * 2, (c.shieldRadiusY + pulse) * 2);
        }
        if (this.breakRemaining > 0) {
            const progress = 1 - this.breakRemaining / c.shieldBreakDurationMs;
            this.effectGraphics.lineStyle(3, c.colors.shield, (1 - progress) * 0.8).strokeEllipse(0, -44, (c.shieldRadiusX + progress * 13) * 2, (c.shieldRadiusY + progress * 13) * 2);
        }
    }

    line(g, from, to) { g.beginPath().moveTo(from.x, from.y).lineTo(to.x, to.y).strokePath(); }
    lineXY(g, x1, y1, x2, y2) { g.beginPath().moveTo(x1, y1).lineTo(x2, y2).strokePath(); }

    reset() {
        this.phase = 0; this.state = 'ready'; this.hitRemaining = 0; this.breakRemaining = 0; this.landingRemaining = 0; this.shieldActive = false;
        calculatePose(this.currentPose, 'ready'); this.draw(); return this;
    }

    destroy() {
        if (this.destroyed) return;
        this.destroyed = true; this.root.destroy(true);
    }
}
