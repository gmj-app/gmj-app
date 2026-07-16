# Daily Journey Challenge — Phase 1 in-game UI pass

## Implementation report

1. **Files changed:** `resources/js/daily-journey/game.js`, `index.js`, and new `ui-state.js`; `resources/views/game/preview.blade.php`; `resources/css/app.css`; `config/daily_journey.php`; `DailyJourneyController`; `package.json`; PHP and Node tests.
2. **Previous HUD:** Phaser created two disconnected text objects for score/distance and `Shield: —`, while state copy also lived in Phaser and a separate loose DOM status line.
3. **New HUD:** one safe-area top band contains consistent translucent Score, Distance, Shield, Pause, and Sound controls. Values use tabular numerals, whole metres, bounded cells, and explicit states.
4. **Rendering boundary:** Phaser owns world rendering, physics, collisions, and lightweight original effects. One semantic DOM layer positioned inside the game viewport owns the HUD, state overlays, buttons, toast surface, orientation prompt, result details, and live announcements. There is no duplicate HUD or pause system.
5. **Score:** locale-formatted whole points, throttled locally to 10 updates per second. Configured thresholds produce milestone callouts; normal increments do not animate or announce.
6. **Distance:** locale-formatted whole metres in the same HUD language as score.
7. **Shield:** geometric shield icon plus text states `EMPTY`, `READY`, and briefly `BROKEN`. Ready/broken events receive visual callouts, original tones, and major-state announcements; state is not color-only.
8. **Ready overlay:** maximum width 576px, translucent navy surface, concise copy, desktop keycaps or mobile touch guidance, Start Run primary action, reset note, and ample visible playfield around it.
9. **Loading:** `PREPARING YOUR RUN / Creating today’s trail…` is integrated inside the viewport before the real session request resolves. Failure becomes an in-game retry/exit state and never fakes readiness.
10. **Countdown:** server start must succeed before the DOM state machine shows `3, 2, 1, GO!`. Physics, input, score, distance, and active time remain stopped until the countdown promise completes.
11. **Control guide:** desktop uses grouped keycaps beneath the viewport. Mobile shows `TAP Jump` and `HOLD Duck`; desktop-only landscape advice was removed.
12. **Pause:** P, Escape, HUD button, focus loss, and tab hiding converge on one pause state. The compact overlay provides Resume, Restart, and Exit. Timers, spawns, physics movement, distance, and score stop because the scene update exits while paused.
13. **Game over:** the active touch controls hide, the stopped-world result surface shows server-relevant Score, Distance, Stars, and Today rank.
14. **Submission:** separate `game_over` and `submitting` states precede accepted, personal-best, daily-leader, suspicious, rejected, or network-failure copy. Rank appears only from the server response; rejection language is neutral and references are shown when returned.
15. **Toasts:** one bounded queue coalesces into a single nonblocking upper-safe-area callout for stars, shield events, milestones, speed tiers, personal best, lead changes, and reset warnings. It never stacks over the hazard approach lane.
16. **Daily reset:** the collapsed countdown remains authoritative context. During play only, configured 10- and 1-minute warnings appear. At the boundary, copy explains that a session issued for the prior day still counts for that trail under the existing server grace rule.
17. **Exit:** final states collapse immediately. Ready or active runs use an in-game `EXIT CURRENT RUN?` confirmation. A playing run pauses before confirmation and abandoned runs are destroyed without submission. Restart has its own confirmation.
18. **Desktop:** full top HUD, visible keycap guide, 576px maximum state panel, semantic shell footer, and separate low-emphasis exit action.
19. **Mobile:** compact HUD cells, icon-first Pause/Sound, touch-specific ready instructions, large touch buttons only during play, and a dismissible portrait orientation prompt stored locally.
20. **Accessibility:** semantic buttons/links, visible focus rings, descriptive Pause/Sound labels, text shield states, a polite live region for major changes, keyboard access, no browser dialogs, no flashing, and no live score flood.
21. **Reduced motion:** CSS animations/transitions respect `motion-reduce`; countdown remains a simple text sequence, and existing homepage scrolling respects the preference.
22. **Performance:** no HUD network traffic or per-frame DOM reconstruction. Five text nodes are updated at most every 100ms; toast and overlay DOM are reused. Phaser and UI modules remain in the existing lazy chunk.
23. **Configuration:** countdown step, toast duration/queue limit, HUD update interval, shield-break duration, score milestones, reset warning thresholds, and overlay width live under `daily_journey.ui` and are sent with the issued session.
24. **Automated tests:** Node’s built-in test runner covers state registration/order, contradictory transitions, countdown, pause/resume, HUD formatting, all shield states, accepted/rank/PB/leader/suspicious/rejected/network copy. Laravel tests cover the rendered accessible shell and existing secure run loop.
25. **Manual viewports:** interactive viewport automation was unavailable in the implementation environment. Desktop/tablet/mobile viewport QA remains explicitly outstanding; it is not recorded as passed.
