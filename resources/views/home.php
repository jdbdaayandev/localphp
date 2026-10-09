<?php
/**
 * LocalPHP welcome page.
 * This page is intentionally self-contained: page-specific CSS and JavaScript
 * live here, so no shared layout is required.
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#f8fafc">
    <meta name="description" content="LocalPHP is a lightweight PHP framework for building clean, organized web applications.">
    <title>LocalPHP — Build locally. Build beautifully.</title>
    <style>
        :root {
            color-scheme: light;
            --ink: #142033;
            --muted: #68768b;
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --line: #e4eaf2;
            --surface: #ffffff;
            --soft: #f4f7fb;
            --radius: 18px;
        }
        * { box-sizing: border-box; }
        html { scroll-behavior: smooth; }
        body {
            margin: 0;
            color: var(--ink);
            background: #fff;
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            -webkit-font-smoothing: antialiased;
        }
        a { color: inherit; text-decoration: none; }
        .container { width: min(1120px, calc(100% - 44px)); margin-inline: auto; }
        .navbar { height: 78px; border-bottom: 1px solid rgba(226,232,240,.8); background: rgba(255,255,255,.88); }
        .navbar-inner { display: flex; height: 100%; align-items: center; justify-content: space-between; gap: 24px; }
        .brand { display: inline-flex; align-items: center; gap: 11px; font-size: 18px; font-weight: 800; letter-spacing: -.6px; }
        .brand-icon { display: grid; width: 38px; height: 38px; place-items: center; border-radius: 12px; color: #fff; background: linear-gradient(145deg,#3b82f6,#1d4ed8); box-shadow: 0 7px 18px #2563eb35; font-family: ui-monospace, monospace; font-size: 15px; }
        .nav-links { display: flex; align-items: center; gap: 28px; color: #64748b; font-size: 13px; font-weight: 650; }
        .nav-links a:hover { color: var(--primary); }
        .version { padding: 7px 10px; border: 1px solid var(--line); border-radius: 999px; color: #475569; background: var(--soft); font-size: 11px; }
        .hero { position: relative; overflow: hidden; padding: 94px 0 84px; background: radial-gradient(ellipse at 80% 12%,#dbeafe80,transparent 35%),linear-gradient(180deg,#fbfdff,#fff); }
        .hero::before { position: absolute; top: 35px; right: -100px; width: 360px; height: 360px; border: 1px solid #dbeafe80; border-radius: 50%; content: ""; pointer-events: none; }
        .hero-grid { position: relative; display: grid; grid-template-columns: 1.03fr .97fr; align-items: center; gap: 64px; }
        .eyebrow { display: inline-flex; align-items: center; gap: 9px; color: #2563eb; font-size: 11px; font-weight: 800; letter-spacing: 1.5px; text-transform: uppercase; }
        .status-dot { width: 8px; height: 8px; border-radius: 50%; background: #22c55e; box-shadow: 0 0 0 4px #dcfce7; }
        h1 { max-width: 620px; margin: 22px 0 20px; font-size: clamp(44px,5.6vw,68px); font-weight: 820; letter-spacing: -4px; line-height: 1.03; }
        h1 span { color: var(--primary); }
        .hero-description { max-width: 530px; margin: 0; color: var(--muted); font-size: 16px; line-height: 1.9; }
        .hero-actions { display: flex; flex-wrap: wrap; gap: 12px; margin-top: 29px; }
        .button { display: inline-flex; min-height: 46px; align-items: center; justify-content: center; gap: 10px; padding: 0 18px; border: 1px solid transparent; border-radius: 10px; font-size: 13px; font-weight: 750; transition: transform .18s, box-shadow .18s, background .18s; }
        .button:hover { transform: translateY(-2px); }
        .button-primary { color: #fff; background: var(--primary); box-shadow: 0 9px 20px #2563eb30; }
        .button-primary:hover { background: var(--primary-dark); box-shadow: 0 12px 24px #2563eb3d; }
        .button-secondary { border-color: var(--line); color: #334155; background: #fff; }
        .button-secondary:hover { background: var(--soft); }
        .hero-note { display: flex; align-items: center; gap: 9px; margin-top: 24px; color: #94a3b8; font-size: 12px; }
        .hero-note svg { color: #16a34a; }
        .code-window { overflow: hidden; border: 1px solid #dce5f1; border-radius: 17px; background: #fff; box-shadow: 0 28px 80px #0f172a12,0 3px 10px #0f172a06; transform: rotate(.4deg); }
        .window-header { display: flex; align-items: center; justify-content: space-between; padding: 15px 18px; border-bottom: 1px solid #e8edf5; background: #fbfcfe; }
        .window-dots { display: flex; gap: 6px; }
        .window-dots i { width: 9px; height: 9px; border-radius: 50%; background: #cbd5e1; }
        .window-dots i:first-child { background: #fca5a5; } .window-dots i:nth-child(2) { background: #fcd34d; } .window-dots i:nth-child(3) { background: #86efac; }
        .window-title { color: #94a3b8; font-size: 11px; font-weight: 650; }
        .code-body { overflow-x: auto; padding: 25px 23px 28px; font: 12px/2.15 ui-monospace, SFMono-Regular, Consolas, monospace; }
        .code-line { display: flex; gap: 18px; min-width: max-content; }
        .line-number { width: 14px; color: #cbd5e1; text-align: right; user-select: none; }
        .code-comment { color: #94a3b8; } .code-keyword { color: #7c3aed; } .code-class { color: #2563eb; } .code-string { color: #059669; } .code-function { color: #c2410c; } .code-text { color: #334155; }
        .terminal { padding: 14px 20px 17px; border-top: 1px solid #e8edf5; background: #f8fafc; font: 12px/1.8 ui-monospace, monospace; }
        .terminal-label { margin-bottom: 6px; color: #94a3b8; font: 700 10px/1.4 Inter, sans-serif; letter-spacing: 1px; }
        .terminal-prompt,.terminal-success { color: #15803d; } .terminal-command { color: #334155; }
        .features { padding: 76px 0 88px; border-top: 1px solid #f1f5f9; }
        .section-heading { max-width: 620px; margin: 0 auto 40px; text-align: center; }
        .section-label { color: var(--primary); font-size: 11px; font-weight: 800; letter-spacing: 1.5px; text-transform: uppercase; }
        h2 { margin: 12px 0; font-size: clamp(28px,3.4vw,37px); letter-spacing: -1.4px; }
        .section-heading p { margin: 0; color: var(--muted); font-size: 14px; line-height: 1.8; }
        .feature-grid { display: grid; grid-template-columns: repeat(4,minmax(0,1fr)); gap: 17px; }
        .feature-card { padding: 23px 21px; border: 1px solid var(--line); border-radius: var(--radius); background: #fff; transition: transform .2s,border-color .2s,box-shadow .2s; }
        .feature-card:hover { transform: translateY(-3px); border-color: #bfdbfe; box-shadow: 0 12px 28px #0f172a08; }
        .feature-icon { display: grid; width: 42px; height: 42px; place-items: center; margin-bottom: 18px; border-radius: 12px; color: var(--primary); background: #eff6ff; font-size: 19px; }
        .feature-card h3 { margin: 0 0 9px; font-size: 14px; letter-spacing: -.25px; }
        .feature-card p { margin: 0; color: var(--muted); font-size: 12px; line-height: 1.8; }
        .getting-started { padding: 0 0 80px; }
        .start-panel { display: flex; align-items: center; justify-content: space-between; gap: 25px; padding: 30px 32px; border: 1px solid #dbeafe; border-radius: 18px; background: linear-gradient(120deg,#eff6ff,#f8fbff); }
        .start-panel h2 { margin: 0 0 8px; font-size: 24px; }
        .start-panel p { margin: 0; color: var(--muted); font-size: 13px; line-height: 1.8; }
        .footer { padding: 23px 0; border-top: 1px solid var(--line); background: #fbfcfe; }
        .footer-inner { display: flex; align-items: center; justify-content: space-between; gap: 16px; color: var(--muted); font-size: 12px; }
        .footer-brand { color: #334155; font-weight: 800; }
        @media (max-width: 900px) { .hero { padding: 70px 0 60px; } .hero-grid { grid-template-columns: 1fr; gap: 42px; } .hero-copy { max-width: 650px; } .code-window { max-width: 620px; transform: none; } .feature-grid { grid-template-columns: repeat(2,minmax(0,1fr)); } }
        @media (max-width: 560px) { .container { width: min(100% - 32px,1120px); } .navbar { height: 68px; } .nav-links { gap: 13px; } .nav-links .nav-secondary { display: none; } .hero { padding: 55px 0 48px; } h1 { font-size: 44px; letter-spacing: -2.6px; } .hero-description { font-size: 14px; } .hero-actions { align-items: stretch; } .hero-actions .button { flex: 1; } .code-body { padding: 19px 14px; font-size: 11px; } .features { padding: 58px 0; } .feature-grid { grid-template-columns: 1fr; } .feature-card { padding: 21px; } .start-panel { align-items: flex-start; flex-direction: column; padding: 24px; } .footer-inner { align-items: flex-start; flex-direction: column; gap: 7px; } }
        @media (prefers-reduced-motion: reduce) { *,*::before,*::after { scroll-behavior: auto !important; transition: none !important; } }
    </style>
</head>
<body>
    <header class="navbar">
        <div class="container navbar-inner">
            <a class="brand" href="{{ base_url('/') }}" aria-label="LocalPHP home">
                <span class="brand-icon">&lt;/&gt;</span><span>LocalPHP</span>
            </a>
            <nav class="nav-links" aria-label="Main navigation">
                <a class="nav-secondary" href="#features">Features</a>
                <a href="#getting-started">Get started</a>
                <span class="version">PHP 8.2+</span>
            </nav>
        </div>
    </header>

    <main>
        <section class="hero">
            <div class="container hero-grid">
                <div class="hero-copy">
                    <div class="eyebrow"><span class="status-dot"></span> Your local development framework</div>
                    <h1>Build locally.<br>Build <span>beautifully.</span></h1>
                    <p class="hero-description">Meet LocalPHP, a lightweight PHP framework built to make local development simple, organized, and enjoyable. Start with a clean foundation and build applications your way.</p>
                    <div class="hero-actions">
                        <a href="#getting-started" class="button button-primary">Get started <span aria-hidden="true">→</span></a>
                        <a href="#features" class="button button-secondary">Explore features</a>
                    </div>
                    <div class="hero-note">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="12" cy="12" r="9"></circle><path d="m8 12 2.5 2.5L16 9"></path></svg>
                        PHP 8.2+ &nbsp;·&nbsp; Lightweight &nbsp;·&nbsp; Local-first
                    </div>
                </div>
                <div class="code-window" aria-label="Example LocalPHP route">
                    <div class="window-header"><div class="window-dots"><i></i><i></i><i></i></div><span class="window-title">routes/web.php</span><span class="window-title">PHP</span></div>
                    <div class="code-body" aria-label="PHP code example">
                        <div class="code-line"><span class="line-number">1</span><span class="code-comment">&lt;?php</span></div>
                        <div class="code-line"><span class="line-number">2</span><span class="code-comment">// Define a route in a few lines</span></div>
                        <div class="code-line"><span class="line-number">3</span><span class="code-text">$router-&gt;<span class="code-function">get</span>(</span></div>
                        <div class="code-line"><span class="line-number">4</span><span class="code-string">&nbsp;&nbsp;&nbsp;&nbsp;'/hello/{name}',</span></div>
                        <div class="code-line"><span class="line-number">5</span><span class="code-keyword">&nbsp;&nbsp;&nbsp;&nbsp;function</span><span class="code-text"> ($request, $name) {</span></div>
                        <div class="code-line"><span class="line-number">6</span><span class="code-keyword">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;return</span><span class="code-text"> "Hello, {$name}!";</span></div>
                        <div class="code-line"><span class="line-number">7</span><span class="code-text">&nbsp;&nbsp;&nbsp;&nbsp;}</span></div>
                        <div class="code-line"><span class="line-number">8</span><span class="code-text">);</span></div>
                    </div>
                    <div class="terminal"><div class="terminal-label">LOCAL TERMINAL</div><div><span class="terminal-prompt">➜</span> <span class="terminal-command">php local serve</span></div><div class="terminal-success">✓ LocalPHP development server is ready</div></div>
                </div>
            </div>
        </section>

        <section class="features" id="features">
            <div class="container">
                <div class="section-heading"><div class="section-label">A simpler foundation</div><h2>Everything starts with a clean structure.</h2><p>Keep your application understandable as it grows, without carrying unnecessary complexity from day one.</p></div>
                <div class="feature-grid">
                    <article class="feature-card"><div class="feature-icon" aria-hidden="true">⌘</div><h3>Simple routing</h3><p>Connect URLs to controllers or small callbacks using readable route definitions.</p></article>
                    <article class="feature-card"><div class="feature-icon" aria-hidden="true">▤</div><h3>Organized views</h3><p>Keep presentation separate from application logic with a lightweight view engine.</p></article>
                    <article class="feature-card"><div class="feature-icon" aria-hidden="true">◇</div><h3>Practical core</h3><p>Build on focused framework services for requests, responses, sessions, and data.</p></article>
                    <article class="feature-card"><div class="feature-icon" aria-hidden="true">⚡</div><h3>Local-first workflow</h3><p>Develop and experiment on your machine before choosing how to deploy.</p></article>
                </div>
            </div>
        </section>

        <section class="getting-started" id="getting-started">
            <div class="container"><div class="start-panel"><div><h2>Your next application starts here.</h2><p>Explore the project, define a route, and shape LocalPHP around the way you like to build.</p></div><a class="button button-primary" href="#top" id="back-to-top">Back to top ↑</a></div></div>
        </section>
    </main>

    <footer class="footer"><div class="container footer-inner"><span class="footer-brand">LocalPHP</span><span>Built for local development. Designed for simplicity.</span></div></footer>

    <!-- Page-specific JavaScript belongs in this script block. -->
    <script>
        // Keep JavaScript for this page here instead of loading it globally.
        document.querySelector('#back-to-top')?.addEventListener('click', function (event) {
            event.preventDefault();
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    </script>
</body>
</html>
