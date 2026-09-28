<?php
declare(strict_types=1);

// Serves a single MusicXML file out of data/mxml (outside the web root) so
// the browser can fetch it by name. Any other request just renders the page.
$mxmlDir = realpath(__DIR__ . '/../data/mxml');

if (isset($_GET['mxml'])) {
    $requested = basename((string) $_GET['mxml']);

    if ($mxmlDir === false || !preg_match('/^[A-Za-z0-9.-]+\.musicxml$/', $requested)) {
        http_response_code(400);
        header('Content-Type: text/plain; charset=UTF-8');
        exit('Invalid file name');
    }

    $path = realpath($mxmlDir . '/' . $requested);

    if ($path === false || dirname($path) !== $mxmlDir || !is_file($path)) {
        http_response_code(404);
        header('Content-Type: text/plain; charset=UTF-8');
        exit('File not found');
    }

    header('Content-Type: application/vnd.recordare.musicxml+xml; charset=UTF-8');
    header('Content-Length: ' . (string) filesize($path));
    header('Cache-Control: public, max-age=86400');
    readfile($path);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Scale Randomizer</title>
  <style>
    :root {
      --bg: #f6f5f2;
      --card: #ffffff;
      --text: #1d1d1f;
      --muted: #6b6b70;
      --accent: #3b5bdb;
      --accent-hover: #2f4ac0;
      --border: #e2e0da;
      --done: #2f9e44;
      --problem: #e03131;
    }

    @media (prefers-color-scheme: dark) {
      :root {
        --bg: #17171a;
        --card: #222226;
        --text: #f2f2f4;
        --muted: #a0a0a8;
        --accent: #7b93ff;
        --accent-hover: #93a7ff;
        --border: #34343a;
        --done: #40c057;
        --problem: #ff6b6b;
      }
    }

    * { box-sizing: border-box; }

    body {
      margin: 0;
      min-height: 100vh;
      background: var(--bg);
      color: var(--text);
      font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
      display: flex;
      justify-content: center;
      padding: 48px 16px;
    }

    main {
      width: 100%;
      max-width: 560px;
    }

    h1 {
      font-size: 1.6rem;
      margin: 0;
    }

    .header-row {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 16px;
      margin-bottom: 24px;
    }

    #filter {
      font: inherit;
      font-size: 0.9rem;
      color: var(--text);
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: 8px;
      padding: 8px 12px;
      cursor: pointer;
    }

    #results {
      list-style: none;
      margin: 0 0 24px;
      padding: 0;
      display: grid;
      /* Every card matches the tallest one if text wraps past the minimum */
      grid-auto-rows: 1fr;
      gap: 12px;
    }

    .entry {
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: 12px;
      padding: 16px 20px;
      /* Room for all four lines plus one wrapped line on narrow screens */
      min-height: 8.25rem;
      display: grid;
      grid-template-columns: auto 1fr auto;
      column-gap: 16px;
      row-gap: 12px;
      align-items: center;
    }

    .entry.expanded {
      border-color: var(--accent);
    }

    .num {
      font-size: 1.4rem;
      font-weight: 600;
      color: var(--muted);
    }

    .info {
      display: flex;
      flex-direction: column;
    }

    .key {
      font-size: 1.25rem;
      font-weight: 600;
    }

    .detail {
      color: var(--muted);
      font-size: 0.95rem;
    }

    #shuffle {
      width: 100%;
      padding: 14px;
      font-size: 1rem;
      font-weight: 600;
      color: #fff;
      background: var(--accent);
      border: none;
      border-radius: 10px;
      cursor: pointer;
    }

    #shuffle:hover { background: var(--accent-hover); }

    .progress {
      margin: 0 0 24px;
    }

    .progress-label {
      display: flex;
      justify-content: space-between;
      align-items: baseline;
      margin-bottom: 8px;
      font-size: 0.95rem;
      color: var(--muted);
    }

    .progress-track {
      height: 10px;
      background: var(--border);
      border-radius: 999px;
      overflow: hidden;
    }

    .progress-fill {
      height: 100%;
      width: 0;
      background: var(--done);
      border-radius: 999px;
      transition: width 0.3s ease;
    }

    #reset {
      background: none;
      border: none;
      padding: 0;
      font: inherit;
      color: var(--muted);
      text-decoration: underline;
      cursor: pointer;
    }

    #reset:hover { color: var(--text); }

    .entry.done {
      border-color: var(--done);
    }

    .entry.done.expanded {
      border-color: var(--accent);
    }

    .done-toggle {
      padding: 8px 14px;
      font-size: 0.9rem;
      font-weight: 600;
      color: var(--text);
      background: none;
      border: 1px solid var(--border);
      border-radius: 8px;
      cursor: pointer;
      white-space: nowrap;
    }

    .done-toggle:hover { border-color: var(--muted); }

    .entry.done .done-toggle {
      color: #fff;
      background: var(--done);
      border-color: var(--done);
    }

    .actions {
      display: flex;
      flex-direction: column;
      gap: 8px;
    }

    .problem-toggle {
      padding: 8px 14px;
      font-size: 0.9rem;
      font-weight: 600;
      color: var(--text);
      background: none;
      border: 1px solid var(--border);
      border-radius: 8px;
      cursor: pointer;
      white-space: nowrap;
    }

    .problem-toggle:hover { border-color: var(--problem); }

    .entry.problem .problem-toggle {
      color: #fff;
      background: var(--problem);
      border-color: var(--problem);
    }

    .empty-message {
      text-align: center;
      color: var(--muted);
      padding: 24px 0;
    }

    .sheet-toggle {
      grid-column: 1 / -1;
      padding: 8px 14px;
      font-size: 0.9rem;
      font-weight: 600;
      color: var(--accent);
      background: none;
      border: 1px solid var(--border);
      border-radius: 8px;
      cursor: pointer;
    }

    .sheet-toggle:hover { border-color: var(--accent); }

    .entry.expanded .sheet-toggle {
      color: #fff;
      background: var(--accent);
      border-color: var(--accent);
    }

    /* Sheet music modal */
    .modal-overlay {
      position: fixed;
      inset: 0;
      background: rgba(0, 0, 0, 0.55);
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 24px;
      z-index: 100;
    }

    .modal-overlay[hidden] { display: none; }

    .modal {
      background: var(--card);
      color: var(--text);
      border-radius: 12px;
      width: 100%;
      max-width: 820px;
      max-height: 90vh;
      display: flex;
      flex-direction: column;
      overflow: hidden;
    }

    .modal-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 16px;
      padding: 16px 20px;
      border-bottom: 1px solid var(--border);
    }

    .modal-header h2 {
      margin: 0;
      font-size: 1.1rem;
    }

    .modal-close {
      background: none;
      border: none;
      font-size: 1.5rem;
      line-height: 1;
      color: var(--muted);
      cursor: pointer;
      padding: 4px 8px;
    }

    .modal-close:hover { color: var(--text); }

    .modal-body {
      overflow-y: auto;
      padding: 20px;
    }

    .sheet-section + .sheet-section {
      margin-top: 28px;
    }

    .sheet-section h3 {
      margin: 0 0 12px;
      font-size: 1rem;
      color: var(--muted);
    }

    .osmd-container {
      background: #fff;
      border-radius: 8px;
      padding: 8px;
      min-height: 120px;
      overflow-x: auto;
      color: #1d1d1f;
      font-size: 0.9rem;
    }
  </style>
</head>
<body>
  <main>
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
    <button id="shuffle">New set</button>
  </main>

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

  <script src="https://cdn.jsdelivr.net/npm/opensheetmusicdisplay@1.9.0/build/opensheetmusicdisplay.min.js"></script>
  <script>
    const COUNT = 4;
    const lists = {
      keys: [
        "A minor",
        "E minor",
        "B minor",
        "F♯ minor",
        "C♯ minor",
        "G♯ minor",
        "A♭ minor",
        "E♭ minor",
        "A♯ minor",
        "B♭ minor",
        "D minor",
        "G minor",
        "C minor",
        "F minor",
        "D♯ minor",
        "C major",
        "G major",
        "D major",
        "A major",
        "E major",
        "B major",
        "F♯ major",
        "C♯ major",
        "F major",
        "B♭ major",
        "E♭ major",
        "A♭ major",
        "D♭ major",
        "G♭ major",
        "C♭ major",
      ],
      articulations: [
        "All slurred",
        "All tongued",
        "2 slurred, 2 tongued",
        "2 tongued, 2 slurred",
        "2 slurred, 2 slurred",
        "3 slurred, 1 tongued",
        "1 tongued, 3 slurred",
        "1 tongued, 2 slurred, 1 tongued",
      ],
      arpeggios: [
        "Arpeggio",
        "Dominant 7th",
        "Diminished 7th",
      ],
      minorForms: [
        "Harmonic",
        "Melodic",
      ],
    };

    function pick(list) {
      return list[Math.floor(Math.random() * list.length)];
    }

    // Fisher-Yates shuffle, then take the first n: guarantees unique keys
    function sample(list, n) {
      const copy = [...list];
      for (let i = copy.length - 1; i > 0; i--) {
        const j = Math.floor(Math.random() * (i + 1));
        [copy[i], copy[j]] = [copy[j], copy[i]];
      }
      return copy.slice(0, n);
    }

    // Progress is tracked per pitch, so enharmonic spellings (F♯ / G♭) share one entry
    const STORAGE_KEY = "scale-randomizer-progress";
    const NOTE_VALUES = { C: 0, D: 2, E: 4, F: 5, G: 7, A: 9, B: 11 };

    function pitchClass(key) {
      const [note] = key.split(" ");
      let value = NOTE_VALUES[note[0]];
      if (note.includes("♯")) value += 1;
      if (note.includes("♭")) value -= 1;
      return (value + 12) % 12;
    }

    function progressId(key, form) {
      const quality = key.endsWith("minor") ? "minor" : "major";
      return form ? `${pitchClass(key)} ${quality} ${form}` : `${pitchClass(key)} ${quality}`;
    }

    const allIds = new Set();
    lists.keys.forEach(key => {
      if (key.endsWith("minor")) {
        lists.minorForms.forEach(form => allIds.add(progressId(key, form)));
      } else {
        allIds.add(progressId(key));
      }
    });

    function loadCompleted() {
      try {
        const saved = JSON.parse(localStorage.getItem(STORAGE_KEY)) || [];
        return new Set(saved.filter(id => allIds.has(id)));
      } catch {
        return new Set();
      }
    }

    function saveCompleted() {
      try {
        localStorage.setItem(STORAGE_KEY, JSON.stringify([...completed]));
      } catch {
        // Storage unavailable: progress lasts until the page is closed
      }
    }

    const completed = loadCompleted();

    // Scales the player has flagged as needing extra work; filterable via the "Problem" option
    const PROBLEM_STORAGE_KEY = "scale-randomizer-problems";

    function loadProblems() {
      try {
        const saved = JSON.parse(localStorage.getItem(PROBLEM_STORAGE_KEY)) || [];
        return new Set(saved.filter(id => allIds.has(id)));
      } catch {
        return new Set();
      }
    }

    function saveProblems() {
      try {
        localStorage.setItem(PROBLEM_STORAGE_KEY, JSON.stringify([...problems]));
      } catch {
        // Storage unavailable: flags last until the page is closed
      }
    }

    const problems = loadProblems();

    let currentFilter = "all";

    function updateProgress() {
      const done = completed.size;
      const total = allIds.size;
      document.getElementById("progress-text").textContent = `${done} / ${total} completed`;
      document.getElementById("progress-fill").style.width = `${(done / total) * 100}%`;
    }

    function setDoneState(li, id) {
      const isDone = completed.has(id);
      li.classList.toggle("done", isDone);
      li.querySelector(".done-toggle").textContent = isDone ? "Done ✓" : "Mark done";
    }

    function setProblemState(li, id) {
      const isProblem = problems.has(id);
      li.classList.toggle("problem", isProblem);
      li.querySelector(".problem-toggle").textContent = isProblem ? "Flagged 🚩" : "Flag as problem";
    }

    // Maps a card's key/form/articulation/arpeggio onto the corresponding
    // MusicXML file names under data/mxml (served through this same page
    // via the ?mxml= endpoint, since data/ sits outside the web root).
    function keySlug(key) {
      const [note] = key.split(" ");
      const letter = note[0];
      if (note.includes("♯")) return `${letter}-sharp`;
      if (note.includes("♭")) return `${letter}-flat`;
      return letter;
    }

    function articulationSlug(articulation) {
      return articulation.replace(/,\s*/g, "-").replace(/\s+/g, "-");
    }

    function scaleFileName(key, form, articulation) {
      const slug = keySlug(key);
      if (key.endsWith("minor")) {
        return `${slug}-minor-${form.toLowerCase()}-${articulationSlug(articulation)}.musicxml`;
      }
      return `${slug}-major-${articulationSlug(articulation)}.musicxml`;
    }

    // Natural-letter order used to count scale degrees up from the tonic
    const SCALE_LETTERS = ["C", "D", "E", "F", "G", "A", "B"];
    const PITCH_CLASS_SLUGS = ["C", "C-sharp", "D", "D-sharp", "E", "F", "F-sharp", "G", "G-sharp", "A", "A-sharp", "B"];

    // Finds the correctly-spelled note `letterSteps` diatonic degrees above the
    // tonic (e.g. 4 steps = a 5th, 6 steps = a 7th) and `semitoneInterval`
    // semitones above it, falling back to a plain enharmonic spelling for
    // notes with no single-accidental file (B♯, E♯, F♭, or a double accidental)
    function degreeNoteSlug(tonicLetter, tonicPitchClass, letterSteps, semitoneInterval) {
      const tonicLetterIndex = SCALE_LETTERS.indexOf(tonicLetter);
      const degreeLetter = SCALE_LETTERS[(tonicLetterIndex + letterSteps) % 7];
      const naturalPitchClass = NOTE_VALUES[degreeLetter];
      const targetPitchClass = ((tonicPitchClass + semitoneInterval) % 12 + 12) % 12;
      let diff = ((targetPitchClass - naturalPitchClass) % 12 + 12) % 12;
      if (diff > 6) diff -= 12;

      if (diff === 0) return degreeLetter;
      if (diff === 1) {
        if (degreeLetter === "B") return "C";
        if (degreeLetter === "E") return "F";
        return `${degreeLetter}-sharp`;
      }
      if (diff === -1) {
        if (degreeLetter === "F") return "E";
        return `${degreeLetter}-flat`;
      }
      return PITCH_CLASS_SLUGS[targetPitchClass];
    }

    // The dom7 arpeggio is rooted on the 5th scale degree (the dominant) and
    // the dim7 arpeggio on the raised 7th (the leading tone) of the card's key
    function arpeggioRootSlug(key, arpeggioType) {
      if (arpeggioType === "Arpeggio") return keySlug(key);
      const tonicLetter = key[0];
      const tonicPitchClass = pitchClass(key);
      return arpeggioType === "Dominant 7th"
        ? degreeNoteSlug(tonicLetter, tonicPitchClass, 4, 7)
        : degreeNoteSlug(tonicLetter, tonicPitchClass, 6, 11);
    }

    function arpeggioFileName(key, arpeggioType) {
      const slug = arpeggioRootSlug(key, arpeggioType);
      if (arpeggioType === "Dominant 7th") return `${slug}-dom7.musicxml`;
      if (arpeggioType === "Diminished 7th") return `${slug}-dim7.musicxml`;
      const quality = key.endsWith("minor") ? "minor" : "major";
      return `${slug}-${quality}-arpeggio.musicxml`;
    }

    // Restricts the key/form pool to whatever the filter dropdown currently selects
    function filteredKeys() {
      if (currentFilter === "major") return lists.keys.filter(key => key.endsWith("major"));
      if (currentFilter === "harmonic minor" || currentFilter === "melodic minor") {
        return lists.keys.filter(key => key.endsWith("minor"));
      }
      return lists.keys;
    }

    function filteredForms(key) {
      const forms = key.endsWith("minor") ? lists.minorForms : [null];
      if (currentFilter === "harmonic minor") return forms.filter(form => form === "Harmonic");
      if (currentFilter === "melodic minor") return forms.filter(form => form === "Melodic");
      if (currentFilter === "problem") return forms.filter(form => problems.has(progressId(key, form)));
      return forms;
    }

    // Picks COUNT distinct keys, taking unfinished key/form combos first and
    // topping up with finished ones once fewer than COUNT remain unfinished
    function chooseSet() {
      const chosen = [];
      const usedKeys = new Set();
      const usedIds = new Set();

      function add(key, forms) {
        const options = forms.filter(form => !usedIds.has(progressId(key, form)));
        if (usedKeys.has(key) || options.length === 0) return;
        const form = pick(options);
        chosen.push({ key, form });
        usedKeys.add(key);
        usedIds.add(progressId(key, form));
      }

      const keys = filteredKeys();

      for (const key of sample(keys, keys.length)) {
        if (chosen.length === COUNT) break;
        add(key, filteredForms(key).filter(form => !completed.has(progressId(key, form))));
      }
      for (const key of sample(keys, keys.length)) {
        if (chosen.length === COUNT) break;
        add(key, filteredForms(key));
      }
      return sample(chosen, chosen.length);
    }

    // --- Sheet music modal ---

    const modal = document.getElementById("sheet-modal");
    const modalTitle = document.getElementById("modal-title");
    const modalClose = document.getElementById("modal-close");
    const scaleContainer = document.getElementById("osmd-scale");
    const arpeggioContainer = document.getElementById("osmd-arpeggio");
    const arpeggioHeading = document.getElementById("arpeggio-heading");

    function closeModal() {
      modal.hidden = true;
      document.querySelectorAll(".entry.expanded").forEach(el => el.classList.remove("expanded"));
    }

    modalClose.addEventListener("click", closeModal);
    modal.addEventListener("click", (event) => {
      if (event.target === modal) closeModal();
    });
    document.addEventListener("keydown", (event) => {
      if (event.key === "Escape" && !modal.hidden) closeModal();
    });

    async function renderSheet(container, fileName) {
      container.textContent = "Loading…";
      try {
        const response = await fetch(`randomizer.php?mxml=${encodeURIComponent(fileName)}`);
        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        const xml = await response.text();
        container.textContent = "";
        const osmd = new opensheetmusicdisplay.OpenSheetMusicDisplay(container, {
          autoResize: true,
          drawTitle: false,
        });
        await osmd.load(xml);
        osmd.render();
      } catch (err) {
        container.textContent = `Couldn't load ${fileName}.`;
      }
    }

    function slugToDisplayName(slug) {
      return slug.replace("-sharp", "♯").replace("-flat", "♭");
    }

    function openSheetMusic(li, key, form, articulation, arpeggioType) {
      document.querySelectorAll(".entry.expanded").forEach(el => el.classList.remove("expanded"));
      li.classList.add("expanded");

      modalTitle.textContent = form ? `${key} ${form} — ${articulation}` : `${key} — ${articulation}`;
      arpeggioHeading.textContent = arpeggioType === "Arpeggio"
        ? arpeggioType
        : `${arpeggioType} (${slugToDisplayName(arpeggioRootSlug(key, arpeggioType))})`;
      modal.hidden = false;

      renderSheet(scaleContainer, scaleFileName(key, form, articulation));
      renderSheet(arpeggioContainer, arpeggioFileName(key, arpeggioType));
    }

    function render() {
      const results = document.getElementById("results");
      results.innerHTML = "";
      const set = chooseSet();

      if (set.length === 0) {
        const li = document.createElement("li");
        li.className = "empty-message";
        li.textContent = "No scales match this filter yet.";
        results.appendChild(li);
        return;
      }

      set.forEach(({ key, form }, i) => {
        const li = document.createElement("li");
        li.className = "entry";
        li.innerHTML = `
          <span class="num">${i + 1}</span>
          <div class="info">
            <span class="key"></span>
            <span class="detail minor-form"></span>
            <span class="detail articulation"></span>
            <span class="detail arpeggio"></span>
          </div>
          <div class="actions">
            <button class="done-toggle" type="button"></button>
            <button class="problem-toggle" type="button"></button>
          </div>
          <button class="sheet-toggle" type="button">View sheet music</button>`;
        li.querySelector(".key").textContent = key;
        const minorForm = li.querySelector(".minor-form");
        if (form) {
          minorForm.textContent = form;
        } else {
          // Keep an empty line (moved to the bottom) so major cards match the height of minor cards
          minorForm.textContent = " ";
          minorForm.setAttribute("aria-hidden", "true");
          minorForm.parentElement.appendChild(minorForm);
        }
        const articulation = pick(lists.articulations);
        const arpeggioType = pick(lists.arpeggios);
        li.querySelector(".articulation").textContent = articulation;
        li.querySelector(".arpeggio").textContent = arpeggioType;

        const id = progressId(key, form);
        setDoneState(li, id);
        li.querySelector(".done-toggle").addEventListener("click", () => {
          if (completed.has(id)) completed.delete(id);
          else completed.add(id);
          saveCompleted();
          setDoneState(li, id);
          updateProgress();
        });

        setProblemState(li, id);
        li.querySelector(".problem-toggle").addEventListener("click", () => {
          if (problems.has(id)) problems.delete(id);
          else problems.add(id);
          saveProblems();
          setProblemState(li, id);
          if (currentFilter === "problem") render();
        });

        li.querySelector(".sheet-toggle").addEventListener("click", () => {
          openSheetMusic(li, key, form, articulation, arpeggioType);
        });

        results.appendChild(li);
      });
    }

    document.getElementById("reset").addEventListener("click", () => {
      if (!confirm("Reset all progress?")) return;
      completed.clear();
      saveCompleted();
      updateProgress();
      render();
    });

    document.getElementById("filter").addEventListener("change", (event) => {
      currentFilter = event.target.value;
      render();
    });

    const button = document.getElementById("shuffle");
    button.addEventListener("click", render);
    updateProgress();
    render();
  </script>
</body>
</html>
