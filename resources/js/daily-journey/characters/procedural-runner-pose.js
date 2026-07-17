export const CHARACTER_STATES = Object.freeze(['ready', 'running', 'jumping', 'falling', 'crouching', 'hit', 'game_over']);

export const CHARACTER_CONFIG = Object.freeze({
    visualWidth: 80,
    visualHeight: 90,
    standingBody: Object.freeze({ width: 56, height: 82, offsetX: 12, offsetY: 8 }),
    crouchingBody: Object.freeze({ width: 56, height: 42, offsetX: 12, offsetY: 48 }),
    headRadius: 10,
    limbThickness: 6,
    outlineThickness: 2,
    runCyclesPerSecond: 2.7,
    runSpeedInfluence: 0.0018,
    landingDurationMs: 110,
    hitDurationMs: 280,
    shieldBreakDurationMs: 320,
    shieldRadiusX: 38,
    shieldRadiusY: 47,
    colors: Object.freeze({
        outline: 0xf8fafc,
        skin: 0xf5cfa0,
        hoodie: 0x312e81,
        hoodieAccent: 0x818cf8,
        pants: 0x172033,
        shoes: 0xf8fafc,
        glasses: 0x111827,
        shield: 0x38bdf8,
    }),
});

export const JOINT_NAMES = Object.freeze([
    'head', 'neck', 'shoulder', 'hip',
    'leftElbow', 'leftHand', 'rightElbow', 'rightHand',
    'leftKnee', 'leftFoot', 'rightKnee', 'rightFoot',
]);

export function createPose() {
    return Object.fromEntries(JOINT_NAMES.map(name => [name, { x: 0, y: 0 }]));
}

export function resolveCharacterState({ gameOver = false, hit = false, crouching = false, grounded = true, velocityY = 0, running = false } = {}, hitOverride = false) {
    if (hit || hitOverride) return 'hit';
    if (gameOver) return 'game_over';
    if (crouching) return 'crouching';
    if (!grounded) return velocityY < 0 ? 'jumping' : 'falling';
    return running ? 'running' : 'ready';
}

const set = (pose, name, x, y) => { pose[name].x = x; pose[name].y = y; };

export function calculatePose(pose, state, phase = 0, { reducedMotion = false, landing = 0, velocityY = 0 } = {}) {
    const motion = reducedMotion ? 0.48 : 1;
    const compression = Math.max(0, Math.min(1, landing)) * 6 * motion;
    let lean = 1; let bob = 0; let arm = 0; let stride = 0;

    if (state === 'running') {
        const wave = Math.sin(phase);
        stride = wave * 12 * motion;
        arm = -wave * 10 * motion;
        bob = Math.abs(Math.sin(phase * 2)) * 1.5 * motion + compression;
        lean = 4;
    } else if (state === 'ready') {
        bob = Math.sin(phase * 0.35) * 0.8 * motion;
    } else if (state === 'jumping') {
        lean = 1;
        stride = Math.max(-5, velocityY / 90);
        arm = -8;
    } else if (state === 'falling') {
        lean = -1;
        stride = 5;
        arm = 12;
    } else if (state === 'crouching') {
        set(pose, 'head', 12, -35); set(pose, 'neck', 8, -28); set(pose, 'shoulder', 4, -28); set(pose, 'hip', -2, -17);
        set(pose, 'leftElbow', 14, -20); set(pose, 'leftHand', 4, -14); set(pose, 'rightElbow', -8, -21); set(pose, 'rightHand', 1, -14);
        set(pose, 'leftKnee', 14, -10); set(pose, 'leftFoot', 24, -2); set(pose, 'rightKnee', -15, -9); set(pose, 'rightFoot', -25, -2);
        return pose;
    } else if (state === 'hit') {
        set(pose, 'head', -5, -73); set(pose, 'neck', -2, -63); set(pose, 'shoulder', 0, -59); set(pose, 'hip', 9, -33);
        set(pose, 'leftElbow', -17, -68); set(pose, 'leftHand', -26, -58); set(pose, 'rightElbow', 17, -68); set(pose, 'rightHand', 28, -74);
        set(pose, 'leftKnee', -9, -17); set(pose, 'leftFoot', -22, -5); set(pose, 'rightKnee', 22, -24); set(pose, 'rightFoot', 30, -9);
        return pose;
    } else if (state === 'game_over') {
        set(pose, 'head', -23, -25); set(pose, 'neck', -14, -23); set(pose, 'shoulder', -10, -22); set(pose, 'hip', 10, -13);
        set(pose, 'leftElbow', -4, -11); set(pose, 'leftHand', -17, -5); set(pose, 'rightElbow', 2, -28); set(pose, 'rightHand', -11, -33);
        set(pose, 'leftKnee', 23, -7); set(pose, 'leftFoot', 36, -3); set(pose, 'rightKnee', 3, -5); set(pose, 'rightFoot', -9, -2);
        return pose;
    }

    const hipY = -34 + bob;
    const shoulderY = -59 + bob;
    set(pose, 'head', lean + 2, -77 + bob); set(pose, 'neck', lean + 1, -66 + bob);
    set(pose, 'shoulder', lean, shoulderY); set(pose, 'hip', 0, hipY);
    set(pose, 'leftElbow', 8 + arm * 0.55, -48 + bob + Math.abs(arm) * 0.12); set(pose, 'leftHand', 12 + arm, -36 + bob);
    set(pose, 'rightElbow', -8 - arm * 0.55, -48 + bob + Math.abs(arm) * 0.12); set(pose, 'rightHand', -12 - arm, -36 + bob);
    set(pose, 'leftKnee', 6 + stride * 0.55, -18 + Math.max(0, -stride) * 0.25); set(pose, 'leftFoot', 7 + stride, -2 - Math.max(0, -stride) * 0.12);
    set(pose, 'rightKnee', -6 - stride * 0.55, -18 + Math.max(0, stride) * 0.25); set(pose, 'rightFoot', -7 - stride, -2 - Math.max(0, stride) * 0.12);
    return pose;
}

export function copyPose(target, source, amount = 1) {
    for (const name of JOINT_NAMES) {
        target[name].x += (source[name].x - target[name].x) * amount;
        target[name].y += (source[name].y - target[name].y) * amount;
    }
    return target;
}
