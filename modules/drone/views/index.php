<?php
declare(strict_types=1);
?>
<div class="drone-page">

<div class="app">
<div class="panel">

    <div class="app-header">
        <h1>Drone + Metronome</h1>

        <button
            id="infoButton"
            class="info-button"
            type="button"
            aria-label="Tips and instructions"
            aria-haspopup="dialog"
            aria-expanded="false"
            aria-controls="infoPanel"
            data-action="toggleInfoPanel">
            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <circle cx="12" cy="12" r="9"></circle>
                <line x1="12" y1="11" x2="12" y2="16.5"></line>
                <circle cx="12" cy="7.3" r="1" fill="currentColor" stroke="none"></circle>
            </svg>
        </button>

        <div
            class="info-panel"
            id="infoPanel"
            role="dialog"
            aria-label="Tips and instructions"
            hidden>
            <p class="info-lead">Click notes to toggle sustained drones.</p>
            <dl class="info-shortcuts">
                <div>
                    <dt><kbd>C</kbd><kbd>D</kbd><kbd>E</kbd><kbd>F</kbd><kbd>G</kbd><kbd>A</kbd><kbd>B</kbd></dt>
                    <dd>Toggle notes<span class="info-hint">Shift for ♯/♭</span></dd>
                </div>
                <div>
                    <dt><kbd>←</kbd><kbd>→</kbd></dt>
                    <dd>Transpose playing notes</dd>
                </div>
                <div>
                    <dt><kbd>↑</kbd><kbd>↓</kbd></dt>
                    <dd>Volume</dd>
                </div>
                <div>
                    <dt><kbd>+</kbd><kbd>−</kbd></dt>
                    <dd>Octave</dd>
                </div>
                <div>
                    <dt><kbd>Esc</kbd></dt>
                    <dd>Stop all</dd>
                </div>
            </dl>
        </div>
    </div>

    <div class="subtitle"></div>


    <!-- =========================
         METRONOME
    ========================== -->

    <div class="metronome">

        <div class="metro-title">Metronome</div>

        <div class="bpm-display">
            <span class="bpm-number" id="bpmDisplay">80</span>
            <span class="bpm-label">BPM</span>
        </div>

        <div class="metro-controls">

            <button data-action="changeBPM" data-arg="-5">−5</button>

            <button
                class="metro-start"
                id="metroButton"
                data-action="toggleMetronome">
                Start
            </button>

            <button data-action="changeBPM" data-arg="5">+5</button>

            <button
                class="tap"
                data-action="tapTempo">
                Tap Tempo
            </button>

        </div>

        <div class="metro-options">

            <div class="option">
                <span>Tempo</span>
                <input
                    id="tempoSlider"
                    type="range"
                    min="30"
                    max="240"
                    value="80"
                    data-input="setBPM">
            </div>

            <div class="option">
                <span>Beat</span>

                <select id="subdivision">
                    <option value="1">Quarter</option>
                    <option value="2">Eighth</option>
                    <option value="4">16th</option>
                </select>
            </div>

            <div class="option">
                <span>Time</span>

                <select
                    id="timeSignature"
                    data-change="setTimeSignature">
                    <option value="2">2/4</option>
                    <option value="3">3/4</option>
                    <option value="4" selected>4/4</option>
                    <option value="5">5/4</option>
                    <option value="6">6/8</option>
                    <option value="7">7/8</option>
                    <option value="9">9/8</option>
                    <option value="12">12/8</option>
                </select>
            </div>

        </div>

        <div class="beats" id="beats"></div>

    </div>


    <!-- =========================
         OCTAVE CONTROLS
    ========================== -->

    <div class="controls">

        <div class="controls-row">
            <button data-action="changeOctave" data-arg="-1">
                − Octave
            </button>

            <button
                class="octave"
                id="octaveButton">
                Octave 4
            </button>

            <button data-action="changeOctave" data-arg="1">
                + Octave
            </button>

            <button
                class="stop"
                data-action="stopAll">
                Stop Drones
            </button>

            <button
                id="layoutToggle"
                class="layout-toggle"
                type="button"
                aria-label="Toggle circle and piano layout"
                aria-pressed="false"
                data-layout="circle"
                data-action="toggleLayout">
                <span class="layout-toggle-visual">
                    <span class="layout-toggle-icon layout-toggle-circle" aria-hidden="true" data-tooltip="Circle of Fifths">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                            <circle cx="12" cy="12" r="8.4"></circle>
                            <circle cx="12" cy="4.4" r="1.1" fill="currentColor" stroke="none"></circle>
                            <circle cx="18.6" cy="8.2" r="1.1" fill="currentColor" stroke="none"></circle>
                            <circle cx="18.6" cy="15.8" r="1.1" fill="currentColor" stroke="none"></circle>
                            <circle cx="12" cy="19.6" r="1.1" fill="currentColor" stroke="none"></circle>
                            <circle cx="5.4" cy="15.8" r="1.1" fill="currentColor" stroke="none"></circle>
                            <circle cx="5.4" cy="8.2" r="1.1" fill="currentColor" stroke="none"></circle>
                        </svg>
                    </span>
                    <span class="layout-toggle-switch" aria-hidden="true">
                        <span class="layout-toggle-thumb"></span>
                    </span>
                    <span class="layout-toggle-icon layout-toggle-piano" aria-hidden="true" data-tooltip="Keyboard">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round" aria-hidden="true">
                            <rect x="3" y="5.5" width="18" height="13" rx="1.5"></rect>
                            <line x1="7.4" y1="5.5" x2="7.4" y2="13.2"></line>
                            <line x1="11.8" y1="5.5" x2="11.8" y2="13.2"></line>
                            <line x1="16.2" y1="5.5" x2="16.2" y2="13.2"></line>
                        </svg>
                    </span>
                </span>
            </button>
        </div>

        <div class="controls-row controls-row--volume">
            <div class="option volume-option">
                <div class="volume-control">
                    <span class="volume-icon" aria-label="Volume">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M4 9.5v5h3.6l5 4v-13l-5 4H4z" fill="currentColor" stroke="none"></path>
                            <path d="M16.2 8.8a5 5 0 0 1 0 6.4"></path>
                            <path d="M19 6.2a8.7 8.7 0 0 1 0 11.6"></path>
                        </svg>
                    </span>
                    <div class="volume-slider-wrap">
                        <span class="volume-bubble visible" id="volumeBubble">50%</span>
                        <input
                            id="volumeSlider"
                            type="range"
                            min="0"
                            max="100"
                            value="50"
                            data-input="setVolumeSlider">
                    </div>
                </div>
            </div>
        </div>

    </div>

    <div class="status" id="status"></div>

</div>


    <!-- =========================
         CIRCLE OF FIFTHS
    ========================== -->

    <div class="circle" id="circle">

        <div class="center">
            <div
                class="center-note"
                id="centerNote">
                —
            </div>

            <div class="center-label">
                ACTIVE DRONES
            </div>
        </div>

    </div>

</div>
</div>
