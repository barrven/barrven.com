
/* ============================================================
   AUDIO ENGINE
============================================================ */

let audioContext = null;

function initAudio() {

    if (!audioContext) {
        audioContext = new (
            window.AudioContext ||
            window.webkitAudioContext
        )();
    }

    if (audioContext.state === "suspended") {
        audioContext.resume();
    }
}


/* ============================================================
   DRONE
============================================================ */

const notes = [
    "C",
    "G",
    "D",
    "A",
    "E",
    "B",
    "F#",
    "Db",
    "Ab",
    "Eb",
    "Bb",
    "F"
];

let currentLayout = "circle";

const semitones = {
    C: 0,
    "Db": 1,
    D: 2,
    "Eb": 3,
    E: 4,
    F: 5,
    "F#": 6,
    G: 7,
    "Ab": 8,
    A: 9,
    "Bb": 10,
    B: 11
};

const NOTE_BY_SEMITONE =
    Object.keys(semitones).reduce((acc, note) => {

        acc[semitones[note]] = note;

        return acc;
    }, []);

/*
    The piano layout spans a full octave plus a fifth (C to the G
    a fifth above the next C), in actual keyboard order (chromatic)
    rather than the circle-of-fifths order `notes` uses for the
    wheel. Each entry is a white key; octaveOffset marks whether it
    belongs to the base octave (0) or the extra octave-and-a-fifth
    span above it (1).
*/
const PIANO_RANGE = [
    { note: "C", octaveOffset: 0 },
    { note: "D", octaveOffset: 0 },
    { note: "E", octaveOffset: 0 },
    { note: "F", octaveOffset: 0 },
    { note: "G", octaveOffset: 0 },
    { note: "A", octaveOffset: 0 },
    { note: "B", octaveOffset: 0 },
    { note: "C", octaveOffset: 1 },
    { note: "D", octaveOffset: 1 },
    { note: "E", octaveOffset: 1 },
    { note: "F", octaveOffset: 1 },
    { note: "G", octaveOffset: 1 }
];

const PIANO_BLACK_KEY_AFTER = {
    C: "Db",
    D: "Eb",
    F: "F#",
    G: "Ab",
    A: "Bb"
};

/*
    Note identifiers (used as drones{}/semitones{} keys, notes[]
    indices, etc.) stay plain ASCII — only the displayed label
    swaps in the proper sharp/flat glyphs. Trailing apostrophes
    mark a key from a higher octave-and-a-fifth span (e.g. "C'"
    is one octave above "C", "C''" two octaves above) and trailing
    commas mark a lower one (e.g. "C," is one octave below "C"),
    so keys at any octave can be told apart as independent,
    simultaneously-playable notes. This also lets transposeActive()
    carry a note into a neighboring octave instead of wrapping it
    back in place.
*/

function formatNoteLabel(note) {

    return note
        .replace("#", "♯")
        .replace(/b$/, "♭");
}


function makePianoKeyId(note, octaveOffset) {

    if (octaveOffset > 0) return note + "'".repeat(octaveOffset);
    if (octaveOffset < 0) return note + ",".repeat(-octaveOffset);
    return note;
}


function parsePianoKeyId(id) {

    const apostropheMatch = id.match(/'+$/);

    if (apostropheMatch) {
        return {
            note: id.slice(0, -apostropheMatch[0].length),
            octaveOffset: apostropheMatch[0].length
        };
    }

    const commaMatch = id.match(/,+$/);

    if (commaMatch) {
        return {
            note: id.slice(0, -commaMatch[0].length),
            octaveOffset: -commaMatch[0].length
        };
    }

    return { note: id, octaveOffset: 0 };
}


let octave = 4;

let masterVolume = 0.5;

const BASE_GAIN = 0.16;

const drones = {};


function getFrequency(note, octave) {

    const midi =
        (octave + 1) * 12 +
        semitones[note];

    return 440 *
        Math.pow(2, (midi - 69) / 12);
}


function startDrone(id) {

    initAudio();

    const { note, octaveOffset } =
        parsePianoKeyId(id);

    const frequency =
        getFrequency(note, octave + octaveOffset);

    const now =
        audioContext.currentTime;

    const master =
        audioContext.createGain();

    master.gain.setValueAtTime(
        0,
        now
    );

    master.gain.linearRampToValueAtTime(
        BASE_GAIN * masterVolume,
        now + 1.5
    );


    const filter =
        audioContext.createBiquadFilter();

    filter.type = "lowpass";

    filter.frequency.value =
        Math.min(frequency * 5, 5000);

    filter.Q.value = 0.5;


    const fundamental =
        audioContext.createOscillator();

    fundamental.type = "sine";

    fundamental.frequency.value =
        frequency;


    const fifth =
        audioContext.createOscillator();

    fifth.type = "sine";

    fifth.frequency.value =
        frequency * 1.5;


    const fifthGain =
        audioContext.createGain();

    fifthGain.gain.value = 0.18;


    fundamental.connect(filter);

    fifth.connect(fifthGain);

    fifthGain.connect(filter);

    filter.connect(master);

    master.connect(
        audioContext.destination
    );


    fundamental.start();

    fifth.start();


    drones[id] = {
        fundamental,
        fifth,
        master,
        filter
    };
}


function stopDrone(note) {

    const drone = drones[note];

    if (!drone) return;

    const now =
        audioContext.currentTime;

    drone.master.gain.cancelScheduledValues(now);

    drone.master.gain.setValueAtTime(
        drone.master.gain.value,
        now
    );

    drone.master.gain.linearRampToValueAtTime(
        0,
        now + 0.8
    );

    drone.fundamental.stop(
        now + 0.9
    );

    drone.fifth.stop(
        now + 0.9
    );

    delete drones[note];
}


function toggleNote(note) {

    if (drones[note]) {
        stopDrone(note);
    } else {
        startDrone(note);
    }

    updateNoteStyles();
    updateStatus();
}


function stopAll() {

    Object.keys(drones).forEach(
        stopDrone
    );

    updateNoteStyles();
    updateStatus();
}


function stopEverything() {

    stopAll();

    if (metronomeRunning) {
        stopMetronome();
    }
}

function restartDrones(){

    const noteList= Object.keys(drones);
    Object.keys(drones).forEach(
        stopDrone
    );
    
    //loop through noteList and run startDrone(note)
    for(let note of noteList){
        startDrone(note)
    }
}

/* ============================================================
   OCTAVE
============================================================ */

function changeOctave(amount) {

    octave += amount;

    octave =
        Math.max(1, Math.min(7, octave));

    document.getElementById(
        "octaveButton"
    ).textContent =
        `Octave ${octave}`;

    restartDrones();
    renderNotes();
}


/* ============================================================
   CIRCLE
============================================================ */

function updateLayoutButton() {

    const button =
        document.getElementById("layoutToggle");

    if (!button) return;

    const isPiano = currentLayout === "piano";
    button.dataset.layout = currentLayout;
    button.setAttribute("aria-pressed", String(isPiano));
    button.setAttribute(
        "aria-label",
        isPiano ? "Switch to circle of fifths layout" : "Switch to keyboard layout"
    );
}


function setLayout(layout) {

    if (currentLayout === layout) return;

    currentLayout = layout;

    renderNotes();
    updateLayoutButton();
}


function toggleLayout() {
    setLayout(currentLayout === "circle" ? "piano" : "circle");
}


/* ============================================================
   INFO PANEL (tips & instructions)
============================================================ */

function setInfoPanelOpen(open) {

    const button = document.getElementById("infoButton");
    const panel = document.getElementById("infoPanel");

    if (!button || !panel) return;

    panel.hidden = !open;
    button.setAttribute("aria-expanded", String(open));
}

function toggleInfoPanel() {

    const panel = document.getElementById("infoPanel");

    if (!panel) return;

    setInfoPanelOpen(panel.hidden);
}

document.addEventListener("click", (event) => {

    const panel = document.getElementById("infoPanel");
    const button = document.getElementById("infoButton");

    if (!panel || panel.hidden) return;

    if (panel.contains(event.target) || button.contains(event.target)) {
        return;
    }

    setInfoPanelOpen(false);
});


function renderNotes() {

    const circle =
        document.getElementById("circle");

    if (!circle) return;

    circle.classList.toggle(
        "layout-piano",
        currentLayout === "piano"
    );

    document
        .querySelectorAll(".note")
        .forEach(el => el.remove());

    document
        .querySelectorAll(".piano")
        .forEach(el => el.remove());

    if (currentLayout === "piano") {

        const piano =
            document.createElement("div");

        piano.className = "piano";

        const makeKey = (note, kind, octaveOffset) => {

            const id =
                makePianoKeyId(note, octaveOffset);

            const button =
                document.createElement("button");

            button.className = `note ${kind}`;
            button.dataset.note = id;

            button.innerHTML = `
                <div class="name">
                    ${formatNoteLabel(note)}
                </div>
            `;

            button.onclick =
                () => toggleNote(id);

            return button;
        };

        PIANO_RANGE.forEach((key, index) => {

            const wrap =
                document.createElement("div");

            wrap.className = "white-key-wrap";

            wrap.appendChild(
                makeKey(key.note, "white-key", key.octaveOffset)
            );

            const blackNote =
                PIANO_BLACK_KEY_AFTER[key.note];

            const hasNextKey =
                index < PIANO_RANGE.length - 1;

            if (blackNote && hasNextKey) {
                wrap.appendChild(
                    makeKey(blackNote, "black-key", key.octaveOffset)
                );
            }

            piano.appendChild(wrap);
        });

        circle.appendChild(piano);
        updateNoteStyles();
        updateLayoutButton();
        return;
    }

    const radius =
        window.innerWidth <= 500
            ? 42
            : 43;

    notes.forEach((note, index) => {

        const angle =
            (index * 30 - 90) *
            Math.PI / 180;

        const x =
            50 +
            radius *
            Math.cos(angle);

        const y =
            50 +
            radius *
            Math.sin(angle);

        const button =
            document.createElement("div");

        button.className = "note";
        button.dataset.note = note;

        button.style.left =
            `${x}%`;

        button.style.top =
            `${y}%`;

        button.innerHTML = `
            <div class="name">
                ${formatNoteLabel(note)}
            </div>
        `;

        button.onclick =
            () => toggleNote(note);

        circle.appendChild(button);
    });

    updateNoteStyles();
    updateLayoutButton();
}


function updateNoteStyles() {

    document
        .querySelectorAll(".note")
        .forEach(button => {

            button.classList.toggle(
                "active",
                !!drones[button.dataset.note]
            );
        });
}


function transposeActive(amount) {

    const activeNotes =
        Object.keys(drones);

    if (!activeNotes.length) return;

    /*
        Shift every currently-playing note by the same number of
        steps, so the interval(s) between them are preserved.
        Stop-then-restart (rather than a pitch glide) matches how
        changeOctave() already re-pitches playing drones.

        The step size depends on layout: the keyboard (piano) layout
        is chromatic, so a step is one semitone; the circle-of-fifths
        layout steps around that wheel instead, matching what ←/→
        visually move you along on screen.

        On the piano layout, stepping past B going up (or past C
        going down) should land on the next octave's C, not wrap
        back onto the same one — so the octave offset is carried
        whenever the semitone index overflows or underflows.
    */

    const usesSemitoneStep =
        currentLayout === "piano";

    const transposedNotes =
        activeNotes.map(id => {

            const { note, octaveOffset } =
                parsePianoKeyId(id);

            const index =
                usesSemitoneStep
                    ? semitones[note]
                    : notes.indexOf(note);

            const stepCount =
                usesSemitoneStep ? 12 : notes.length;

            const rawIndex =
                index + amount;

            const newIndex =
                ((rawIndex % stepCount) + stepCount) % stepCount;

            const octaveCarry =
                Math.floor(rawIndex / stepCount);

            const newNote =
                usesSemitoneStep
                    ? NOTE_BY_SEMITONE[newIndex]
                    : notes[newIndex];

            const newOctaveOffset =
                usesSemitoneStep
                    ? octaveOffset + octaveCarry
                    : octaveOffset;

            return makePianoKeyId(
                newNote,
                newOctaveOffset
            );
        });

    activeNotes.forEach(stopDrone);

    transposedNotes.forEach(startDrone);

    updateNoteStyles();
    updateStatus();
}


function setMasterVolume(amount) {

    masterVolume =
        Math.max(0, Math.min(1, masterVolume + amount));

    applyMasterVolume();
    updateVolumeDisplay();
}


function setVolumeSlider(value) {

    masterVolume =
        Math.max(0, Math.min(1, Number(value) / 100));

    applyMasterVolume();
    updateVolumeDisplay();
}


function applyMasterVolume() {

    if (!audioContext) return;

    const now =
        audioContext.currentTime;

    Object.values(drones).forEach(drone => {

        drone.master.gain.cancelScheduledValues(now);

        drone.master.gain.setValueAtTime(
            drone.master.gain.value,
            now
        );

        drone.master.gain.linearRampToValueAtTime(
            BASE_GAIN * masterVolume,
            now + 0.15
        );
    });
}


function showVolumeBubble() {

    const bubble =
        document.getElementById("volumeBubble");

    if (bubble) {
        bubble.classList.add("visible");
    }
}

function hideVolumeBubble() {

    const bubble =
        document.getElementById("volumeBubble");

    if (bubble) {
        bubble.classList.remove("visible");
    }
}

function updateVolumeDisplay() {

    const percent =
        Math.round(masterVolume * 100);

    const bubble =
        document.getElementById("volumeBubble");

    if (bubble) {
        bubble.textContent = `${percent}%`;
    }

    document.getElementById(
        "volumeSlider"
    ).value = percent;
}


function updateStatus() {

    const active =
        Object.keys(drones);

    const status =
        document.getElementById("status");

    if (!active.length) {

        status.textContent = "";

    } else {

        status.textContent =
            `Playing: ${active.map(formatNoteLabel).join(" • ")}`;
    }


    document.getElementById(
        "centerNote"
    ).textContent =
        active.length || "—";
}


/* ============================================================
   METRONOME
============================================================ */

let bpm = 80;

let metronomeRunning = false;

let metroTimer = null;

let metroBeat = 0;

let beatsPerBar = 4;

let tapTimes = [];


function renderBeats() {

    const beats =
        document.getElementById("beats");

    beats.innerHTML = "";

    for (let i = 0; i < beatsPerBar; i++) {

        const beat =
            document.createElement("div");

        beat.className =
            i === 0 ? "beat first" : "beat";

        beats.appendChild(beat);
    }
}


function setTimeSignature(value) {

    beatsPerBar = Number(value);

    metroBeat = 0;

    renderBeats();
    fitLayout();
}


/*
    The metronome uses a repeating timer.
    The actual click is scheduled through
    the Web Audio API for accurate timing.
*/

function metroClick() {

    initAudio();

    const now =
        audioContext.currentTime;

    const oscillator =
        audioContext.createOscillator();

    const gain =
        audioContext.createGain();


    /*
        The first beat of every bar
        has a higher pitch.
    */

    if (metroBeat === 0) {

        oscillator.frequency.value =
            1200;

    } else {

        oscillator.frequency.value =
            800;
    }


    gain.gain.setValueAtTime(
        0.0001,
        now
    );

    gain.gain.exponentialRampToValueAtTime(
        0.5,
        now + 0.002
    );

    gain.gain.exponentialRampToValueAtTime(
        0.0001,
        now + 0.07
    );


    oscillator.connect(gain);

    gain.connect(
        audioContext.destination
    );

    oscillator.start(now);

    oscillator.stop(
        now + 0.08
    );


    flashBeat();

    metroBeat =
        (metroBeat + 1) % beatsPerBar;
}


function toggleMetronome() {

    if (metronomeRunning) {

        stopMetronome();

    } else {

        startMetronome();
    }
}


function startMetronome() {

    initAudio();

    metronomeRunning = true;

    metroBeat = 0;

    document.getElementById(
        "metroButton"
    ).textContent = "Stop";


    metroClick();

    scheduleNextClick();
}


function scheduleNextClick() {

    if (!metronomeRunning) return;


    const subdivision =
        Number(
            document.getElementById(
                "subdivision"
            ).value
        );


    const interval =
        (60000 / bpm) /
        subdivision;


    metroTimer =
        setTimeout(() => {

            metroClick();

            scheduleNextClick();

        }, interval);
}


function stopMetronome() {

    metronomeRunning = false;

    clearTimeout(metroTimer);

    metroTimer = null;

    document.getElementById(
        "metroButton"
    ).textContent = "Start";


    document
        .querySelectorAll(".beat")
        .forEach(beat => {

            beat.classList.remove(
                "active"
            );
        });
}


function flashBeat() {

    const lights =
        document.querySelectorAll(
            ".beat"
        );


    /*
        Convert subdivision into
        visual movement.
    */

    const visualBeat =
        metroBeat % beatsPerBar;


    lights.forEach((light, i) => {

        light.classList.toggle(
            "active",
            i === visualBeat
        );
    });


    /*
        Automatically turn the light off
        shortly after the click.
    */

    setTimeout(() => {

        if (!metronomeRunning) return;

        lights.forEach(light => {
            light.classList.remove(
                "active"
            );
        });

    }, 90);
}


/* ============================================================
   BPM
============================================================ */

function setBPM(value) {

    bpm =
        Math.round(Number(value));

    bpm =
        Math.max(
            30,
            Math.min(240, bpm)
        );


    document.getElementById(
        "bpmDisplay"
    ).textContent = bpm;


    document.getElementById(
        "tempoSlider"
    ).value = bpm;


    /*
        Restart timing when BPM changes.
    */

    if (metronomeRunning) {

        clearTimeout(metroTimer);

        scheduleNextClick();
    }
}


function changeBPM(amount) {

    setBPM(bpm + amount);
}


/* ============================================================
   TAP TEMPO
============================================================ */

function tapTempo() {

    const now =
        performance.now();


    /*
        Forget taps older than 2 seconds.
    */

    tapTimes =
        tapTimes.filter(
            time => now - time < 2000
        );


    tapTimes.push(now);


    if (tapTimes.length >= 2) {

        let intervals = [];

        for (
            let i = 1;
            i < tapTimes.length;
            i++
        ) {

            intervals.push(
                tapTimes[i] -
                tapTimes[i - 1]
            );
        }


        const average =
            intervals.reduce(
                (a, b) => a + b,
                0
            ) / intervals.length;


        const tappedBPM =
            60000 / average;


        setBPM(
            Math.round(tappedBPM)
        );
    }


    /*
        Keep the last 5 taps.
    */

    if (tapTimes.length > 5) {
        tapTimes.shift();
    }
}


/* ============================================================
   RESPONSIVE LAYOUT
============================================================ */

/*
    Sizes the circle to whatever vertical space is actually left
    over after the header, metronome, controls and status have
    laid themselves out — so the whole UI fits the viewport with
    no scrolling, at any screen size or breakpoint.
*/

const MIN_COMFORTABLE_CIRCLE = 180;

const MIN_CIRCLE = 130;


/*
    Height available below the shared site header, which sits above
    the app in the page layout.
*/
function viewportHeight() {
    const header = document.querySelector(".site-header");
    return window.innerHeight - (header ? header.offsetHeight : 0);
}


function measureFit(app, panel, circle) {

    const appStyle =
        getComputedStyle(app);

    const isRow =
        appStyle.flexDirection === "row";

    const paddingV =
        parseFloat(appStyle.paddingTop) +
        parseFloat(appStyle.paddingBottom);

    let availableHeight;
    let availableWidth;

    if (isRow) {

        /*
            Panel sits beside the circle, so it only eats into
            width, not height.
        */

        const gap =
            parseFloat(appStyle.columnGap) || 0;

        availableHeight =
            viewportHeight() - paddingV;

        availableWidth =
            app.clientWidth -
            panel.getBoundingClientRect().width -
            gap;

    } else {

        /*
            Panel stacks above the circle, so it eats into height.
        */

        const gap =
            parseFloat(appStyle.rowGap) || 0;

        availableHeight =
            viewportHeight() -
            paddingV -
            gap -
            panel.getBoundingClientRect().height;

        availableWidth =
            app.clientWidth;
    }

    return {
        size:
            Math.min(
                availableHeight,
                availableWidth,
                600
            ) - 6,
        availableWidth
    };
}


/*
    The square circle-size was originally tuned to fit the 7 white
    keys of a single octave comfortably. The keyboard now shows
    PIANO_RANGE.length white keys, so scale the piano's width up
    from that same baseline to keep each key roughly as wide as it
    used to be, instead of squeezing more keys into the same
    square footprint the wheel uses.
*/
const PIANO_BASELINE_WHITE_KEYS = 7;


function fitLayout() {

    const app =
        document.querySelector(".app");

    const panel =
        document.querySelector(".panel");

    const circle =
        document.getElementById("circle");

    if (!app || !panel || !circle) return;

    /*
        Try the normal layout first; if a comfortable circle
        doesn't fit, progressively trim optional chrome (titles,
        padding) before ever shrinking below a usable circle size
        or allowing the page to scroll.
    */

    app.classList.remove("compact", "ultra-compact");

    let { size, availableWidth } =
        measureFit(app, panel, circle);

    if (size < MIN_COMFORTABLE_CIRCLE) {
        app.classList.add("compact");
        ({ size, availableWidth } =
            measureFit(app, panel, circle));
    }

    if (size < MIN_COMFORTABLE_CIRCLE) {
        app.classList.add("ultra-compact");
        ({ size, availableWidth } =
            measureFit(app, panel, circle));
    }

    size = Math.max(MIN_CIRCLE, Math.min(size, 600));

    document.documentElement.style.setProperty(
        "--circle-size",
        `${size}px`
    );

    /*
        The piano needs to be wider than it is tall to give its
        twelve white keys (an octave plus a fifth) a comfortable
        width, unlike the circle-of-fifths wheel which stays
        square. Scale it up from the same baseline the square
        circle-size was tuned for, then clamp to whatever
        horizontal space is actually available.
    */

    const desiredPianoWidth =
        size *
        (PIANO_RANGE.length / PIANO_BASELINE_WHITE_KEYS);

    const pianoWidth =
        Math.min(desiredPianoWidth, availableWidth);

    document.documentElement.style.setProperty(
        "--piano-width",
        `${pianoWidth}px`
    );
}


/* ============================================================
   INIT
============================================================ */

renderNotes();
renderBeats();
fitLayout();

window.addEventListener(
    "resize",
    () => {
        renderNotes();
        fitLayout();
    }
);


/* ============================================================
   KEYBOARD (note selection)
============================================================ */

function isFormField(el) {

    return (
        el &&
        (el.tagName === "INPUT" ||
            el.tagName === "SELECT" ||
            el.tagName === "TEXTAREA")
    );
}

/*
    Letter keys start/stop notes directly, laid out like a piano:
    the natural-letter key plays the white key, Shift+letter plays
    the black key immediately above it (no black key above E or B,
    so those have no shifted variant).
*/

const LETTER_NOTE_MAP = {
    c: { plain: "C", shifted: "Db" },
    d: { plain: "D", shifted: "Eb" },
    e: { plain: "E", shifted: null },
    f: { plain: "F", shifted: "F#" },
    g: { plain: "G", shifted: "Ab" },
    a: { plain: "A", shifted: "Bb" },
    b: { plain: "B", shifted: null }
};

document.addEventListener("keydown", (event) => {

    const key = event.key;

    /*
        Escape is a panic stop — it should work
        even if a form field (e.g. the tempo
        slider) currently has focus.
    */

    if (key === "Escape") {
        event.preventDefault();
        stopEverything();
        return;
    }

    if (isFormField(document.activeElement)) return;

    const letterEntry =
        !event.ctrlKey && !event.metaKey && !event.altKey
            ? LETTER_NOTE_MAP[key.toLowerCase()]
            : undefined;

    if (letterEntry) {

        const note = event.shiftKey ? letterEntry.shifted : letterEntry.plain;

        if (note) {
            event.preventDefault();
            initAudio();
            toggleNote(note);
        }

        return;
    }

    if (!event.ctrlKey && !event.metaKey && !event.altKey) {

        const lowerKey = key.toLowerCase();

        if (lowerKey === "o" || lowerKey === "p") {
            event.preventDefault();
            setLayout(lowerKey === "o" ? "circle" : "piano");
            return;
        }
    }

    if (
        key !== "ArrowLeft" &&
        key !== "ArrowRight" &&
        key !== "ArrowUp" &&
        key !== "ArrowDown" &&
        key !== "+" &&
        key !== "=" &&
        key !== "-"
    ) {
        return;
    }

    event.preventDefault();

    initAudio();

    switch (key) {

        case "ArrowLeft":
            transposeActive(-1);
            break;

        case "ArrowRight":
            transposeActive(1);
            break;

        case "ArrowUp":
            setMasterVolume(0.05);
            break;

        case "ArrowDown":
            setMasterVolume(-0.05);
            break;

        case "+":
        case "=":
            changeOctave(1);
            break;

        case "-":
            changeOctave(-1);
            break;
    }
});



/* ============================================================
   CONTROL WIRING
   The markup carries data-action / data-input / data-change
   attributes instead of inline onclick="..." handlers, because the
   site's Content Security Policy blocks inline JavaScript.
============================================================ */

const DRONE_ACTIONS = {
    toggleInfoPanel,
    changeBPM,
    toggleMetronome,
    tapTempo,
    setBPM,
    setTimeSignature,
    changeOctave,
    stopAll,
    toggleLayout,
    setVolumeSlider
};

document.querySelectorAll(".drone-page [data-action]").forEach(el => {
    el.addEventListener("click", () => {
        const arg = el.dataset.arg;
        DRONE_ACTIONS[el.dataset.action](
            arg === undefined ? undefined : Number(arg)
        );
    });
});

document.querySelectorAll(".drone-page [data-input]").forEach(el => {
    el.addEventListener("input", () => DRONE_ACTIONS[el.dataset.input](el.value));
});

document.querySelectorAll(".drone-page [data-change]").forEach(el => {
    el.addEventListener("change", () => DRONE_ACTIONS[el.dataset.change](el.value));
});

(() => {
    const slider = document.getElementById("volumeSlider");
    if (!slider) return;
    slider.addEventListener("pointerdown", showVolumeBubble);
    slider.addEventListener("pointerup", hideVolumeBubble);
    slider.addEventListener("pointerleave", hideVolumeBubble);
    slider.addEventListener("focus", showVolumeBubble);
    slider.addEventListener("blur", hideVolumeBubble);
})();
