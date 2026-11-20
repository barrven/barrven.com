<?php
declare(strict_types=1);
?>
<div class="randomizer">
    <div class="header-row">
        <h1>Scale Randomizer</h1>
        <select id="filter" aria-label="Filter scales">
            <option value="all">All</option>
            <option value="major">Major</option>
            <option value="harmonic minor">Harmonic minor</option>
            <option value="melodic minor">Melodic minor</option>
            <option value="problem">Problem</option>
        </select>
    </div>
    <div class="progress">
        <div class="progress-label">
            <span id="progress-text"></span>
            <button id="reset" type="button">Reset</button>
        </div>
        <div class="progress-track">
            <div class="progress-fill" id="progress-fill"></div>
        </div>
    </div>
    <ul id="results"></ul>
    <button id="shuffle" type="button">New set</button>

    <div class="modal-overlay" id="sheet-modal" hidden>
        <div class="modal" role="dialog" aria-modal="true" aria-labelledby="modal-title">
            <div class="modal-header">
                <h2 id="modal-title"></h2>
                <button class="modal-close" id="modal-close" type="button" aria-label="Close">&times;</button>
            </div>
            <div class="modal-body">
                <div class="sheet-section">
                    <h3>Scale</h3>
                    <div id="osmd-scale" class="osmd-container"></div>
                </div>
                <div class="sheet-section">
                    <h3 id="arpeggio-heading">Arpeggio</h3>
                    <div id="osmd-arpeggio" class="osmd-container"></div>
                </div>
            </div>
        </div>
    </div>
</div>
