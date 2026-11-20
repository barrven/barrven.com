// Scale Randomizer. Loaded deferred, so the DOM is ready when this runs.
// Sheet music comes from /randomizer/mxml/<file> (see modules/randomizer).

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
    const response = await fetch(`/randomizer/mxml/${encodeURIComponent(fileName)}`);
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
