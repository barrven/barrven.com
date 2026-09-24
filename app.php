<?php

declare(strict_types=1);

require __DIR__ . '/app-config.php';

if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    ini_set('error_log', LOG_ROOT . '/php-error.log');
}

date_default_timezone_set('America/New_York');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$projectsPath = APP_ROOT . '/data/projects.json';
$projects = [];
if (is_readable($projectsPath)) {
    $decoded = json_decode((string) file_get_contents($projectsPath), true);
    if (is_array($decoded)) {
        $projects = $decoded;
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barrven</title>
    <style>
        :root {
            --bg: #0b0d12;
            --bg-elevated: #12151c;
            --border: #23283245;
            --border-solid: #232832;
            --text: #e8eaf0;
            --text-dim: #9aa1b0;
            --accent: #6ee7ff;
            --accent-2: #a78bfa;
            --radius: 14px;
        }

        * {
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            margin: 0;
            min-height: 100vh;
            background:
                radial-gradient(1100px 600px at 15% -10%, #1c2130 0%, transparent 60%),
                radial-gradient(900px 500px at 110% 10%, #201a33 0%, transparent 55%),
                var(--bg);
            color: var(--text);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.5;
        }

        a {
            color: inherit;
        }

        /* ---- Nav ---- */
        header {
            position: sticky;
            top: 0;
            z-index: 20;
            backdrop-filter: blur(10px);
            background: #0b0d12cc;
            border-bottom: 1px solid var(--border);
        }

        nav.navbar {
            max-width: 1000px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 16px 24px;
        }

        .brand {
            font-weight: 700;
            font-size: 1.15rem;
            letter-spacing: 0.02em;
            text-decoration: none;
            background: linear-gradient(90deg, var(--accent), var(--accent-2));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .nav-toggle {
            display: none;
            flex-direction: column;
            gap: 5px;
            background: none;
            border: 1px solid var(--border-solid);
            border-radius: 8px;
            padding: 9px 10px;
            cursor: pointer;
        }

        .nav-toggle span {
            width: 20px;
            height: 2px;
            background: var(--text);
            display: block;
            transition: transform 0.25s ease, opacity 0.25s ease;
        }

        .nav-toggle.open span:nth-child(1) {
            transform: translateY(7px) rotate(45deg);
        }

        .nav-toggle.open span:nth-child(2) {
            opacity: 0;
        }

        .nav-toggle.open span:nth-child(3) {
            transform: translateY(-7px) rotate(-45deg);
        }

        .nav-links {
            display: flex;
            gap: 4px;
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .nav-links a {
            display: inline-block;
            padding: 8px 14px;
            border-radius: 999px;
            text-decoration: none;
            color: var(--text-dim);
            font-size: 0.95rem;
            transition: color 0.2s ease, background 0.2s ease;
        }

        .nav-links a:hover {
            color: var(--text);
            background: #ffffff0d;
        }

        .nav-links a.active {
            color: var(--bg);
            background: linear-gradient(90deg, var(--accent), var(--accent-2));
        }

        /* ---- Main / views ---- */
        main {
            max-width: 1000px;
            margin: 0 auto;
            padding: 56px 24px 96px;
        }

        .view {
            display: none;
            animation: fade-in 0.35s ease;
        }

        .view.active {
            display: block;
        }

        @keyframes fade-in {
            from {
                opacity: 0;
                transform: translateY(6px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .eyebrow {
            text-transform: uppercase;
            letter-spacing: 0.14em;
            font-size: 0.75rem;
            color: var(--accent);
            font-weight: 600;
            margin: 0 0 12px;
        }

        h1 {
            font-size: clamp(2rem, 5vw, 3rem);
            margin: 0 0 16px;
            line-height: 1.1;
        }

        .lede {
            font-size: 1.15rem;
            color: var(--text-dim);
            max-width: 60ch;
            margin: 0 0 32px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 22px;
            border-radius: 999px;
            text-decoration: none;
            font-weight: 600;
            background: linear-gradient(90deg, var(--accent), var(--accent-2));
            color: #0b0d12;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px -10px #6ee7ff55;
        }

        .card-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 18px;
            margin-top: 12px;
        }

        .card {
            background: var(--bg-elevated);
            border: 1px solid var(--border-solid);
            border-radius: var(--radius);
            padding: 22px;
        }

        .card h3 {
            margin: 0 0 8px;
        }

        .card p {
            margin: 0;
            color: var(--text-dim);
            font-size: 0.95rem;
        }

        .card-link {
            display: inline-block;
            margin-top: 14px;
            color: var(--accent);
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 600;
        }

        .card-link:hover {
            text-decoration: underline;
        }

        .placeholder-note {
            margin-top: 40px;
            padding: 16px 18px;
            border: 1px dashed var(--border-solid);
            border-radius: var(--radius);
            color: var(--text-dim);
            font-size: 0.9rem;
        }

        /* ---- Mobile ---- */
        @media (max-width: 640px) {
            .nav-toggle {
                display: inline-flex;
            }

            .nav-links {
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                flex-direction: column;
                gap: 2px;
                padding: 0 16px;
                background: #0b0d12f2;
                border-bottom: 1px solid transparent;
                max-height: 0;
                overflow: hidden;
                transition: max-height 0.25s ease, padding 0.25s ease;
            }

            .nav-links.open {
                max-height: 320px;
                padding: 10px 16px 18px;
                border-bottom-color: var(--border);
            }

            .nav-links a {
                width: 100%;
                border-radius: 8px;
            }

            header {
                position: sticky;
            }
        }
    </style>
</head>
<body>
    <header>
        <nav class="navbar">
            <a href="#home" class="brand">barrven</a>
            <button class="nav-toggle" id="navToggle" aria-label="Toggle menu" aria-expanded="false">
                <span></span>
                <span></span>
                <span></span>
            </button>
            <ul class="nav-links" id="navLinks">
                <li><a href="#home" data-view="home">Home</a></li>
                <li><a href="#projects" data-view="projects">Projects</a></li>
            </ul>
        </nav>
    </header>

    <main>
        <section class="view" id="view-home" data-view="home">
            <p class="eyebrow">Welcome</p>
            <h1>Hi, thanks for stopping by</h1>
            <p class="lede">
                This is a basic website to host some code that I've written. Check it out under projects. 
            </p>
            <a href="#projects" class="btn" data-view="projects">See my projects &rarr;</a>
        </section>

        <section class="view" id="view-projects" data-view="projects">
            <p class="eyebrow">Code</p>
            <h1>Projects</h1>
            <p class="lede">A few things I've built.</p>
            <?php if (empty($projects)): ?>
                <p class="lede">No projects listed yet.</p>
            <?php else: ?>
                <div class="card-grid">
                    <?php foreach ($projects as $project): ?>
                        <div class="card">
                            <h3><?= htmlspecialchars($project['title'] ?? '', ENT_QUOTES) ?></h3>
                            <p><?= htmlspecialchars($project['description'] ?? '', ENT_QUOTES) ?></p>
                            <?php if (!empty($project['link'])): ?>
                                <a href="<?= htmlspecialchars($project['link'], ENT_QUOTES) ?>" target="_blank" rel="noopener noreferrer" class="card-link">Open project &rarr;</a>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <p class="placeholder-note">More views (blog, contact, etc.) will show up in the menu above as they're built.</p>
        </section>
    </main>

    <script>
        (function () {
            var views = Array.prototype.slice.call(document.querySelectorAll('.view'));
            var navLinks = Array.prototype.slice.call(document.querySelectorAll('.nav-links a'));
            var navToggle = document.getElementById('navToggle');
            var navList = document.getElementById('navLinks');
            var defaultView = 'home';

            function showView(name) {
                if (!views.some(function (v) { return v.dataset.view === name; })) {
                    name = defaultView;
                }

                views.forEach(function (v) {
                    v.classList.toggle('active', v.dataset.view === name);
                });

                navLinks.forEach(function (a) {
                    a.classList.toggle('active', a.dataset.view === name);
                });
            }

            function closeMobileNav() {
                navList.classList.remove('open');
                navToggle.classList.remove('open');
                navToggle.setAttribute('aria-expanded', 'false');
            }

            navToggle.addEventListener('click', function () {
                var isOpen = navList.classList.toggle('open');
                navToggle.classList.toggle('open', isOpen);
                navToggle.setAttribute('aria-expanded', String(isOpen));
            });

            navLinks.forEach(function (a) {
                a.addEventListener('click', closeMobileNav);
            });

            window.addEventListener('hashchange', function () {
                showView(window.location.hash.replace('#', '') || defaultView);
            });

            showView(window.location.hash.replace('#', '') || defaultView);
        })();
    </script>
</body>
</html>
