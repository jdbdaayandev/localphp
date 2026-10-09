
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>@yield('title', 'LocalPHP')</title>

    <meta name="description"
          content="LocalPHP is a lightweight PHP framework for local development.">

    <style>
        :root {
            --primary: #2563eb;
            --primary-hover: #1d4ed8;
            --primary-light: #eff6ff;
            --background: #ffffff;
            --surface: #f8fafc;
            --border: #e2e8f0;
            --text: #0f172a;
            --muted: #64748b;
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
            font-family: Inter, -apple-system, BlinkMacSystemFont,
                "Segoe UI", sans-serif;
            color: var(--text);
            background: var(--background);
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        button,
        a {
            -webkit-tap-highlight-color: transparent;
        }

        .container {
            width: min(1120px, calc(100% - 40px));
            margin-inline: auto;
        }

        .navbar {
            height: 76px;
            display: flex;
            align-items: center;
            border-bottom: 1px solid var(--border);
            background: rgba(255, 255, 255, 0.94);
        }

        .navbar-inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 24px;
        }

        .brand {
            display: inline-flex;
            align-items: center;
            gap: 11px;
            font-size: 19px;
            font-weight: 750;
            letter-spacing: -0.7px;
        }

        .brand-icon {
            display: grid;
            place-items: center;
            width: 38px;
            height: 38px;
            color: white;
            background: var(--primary);
            border-radius: 11px;
            font-family: monospace;
            font-size: 17px;
            font-weight: 800;
            box-shadow: 0 4px 10px rgba(37, 99, 235, 0.18);
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 28px;
            color: var(--muted);
            font-size: 14px;
            font-weight: 500;
        }

        .nav-links a:hover {
            color: var(--primary);
        }

        .nav-version {
            padding: 5px 10px;
            border: 1px solid var(--border);
            border-radius: 999px;
            color: #475569;
            background: var(--surface);
            font-size: 12px;
            font-weight: 600;
        }

        .hero {
            padding: 90px 0 76px;
            overflow: hidden;
            background:
                radial-gradient(
                    ellipse at 80% 10%,
                    rgba(219, 234, 254, 0.6),
                    transparent 38%
                ),
                linear-gradient(180deg, #ffffff, #fbfdff);
        }

        .hero-grid {
            display: grid;
            grid-template-columns: 1.05fr 0.95fr;
            align-items: center;
            gap: 70px;
        }

        .eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 9px;
            padding: 7px 12px;
            border: 1px solid #dbeafe;
            border-radius: 999px;
            color: #1d4ed8;
            background: #eff6ff;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.2px;
        }

        .status-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: #3b82f6;
        }

        .hero h1 {
            max-width: 590px;
            margin: 24px 0 18px;
            font-size: clamp(42px, 5vw, 64px);
            line-height: 1.08;
            letter-spacing: -3.5px;
            font-weight: 800;
        }

        .hero h1 span {
            color: var(--primary);
        }

        .hero-description {
            max-width: 490px;
            margin: 0;
            color: var(--muted);
            font-size: 17px;
            line-height: 1.85;
        }

        .hero-actions {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 13px;
            margin-top: 30px;
        }

        .button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 9px;
            min-height: 47px;
            padding: 0 19px;
            border: 1px solid transparent;
            border-radius: 10px;
            font-size: 14px;
            font-weight: 650;
            transition: background 0.2s, transform 0.2s;
        }

        .button:hover {
            transform: translateY(-1px);
        }

        .button-primary {
            color: white;
            background: var(--primary);
            box-shadow: 0 5px 12px rgba(37, 99, 235, 0.16);
        }

        .button-primary:hover {
            background: var(--primary-hover);
        }

        .button-secondary {
            color: #334155;
            background: white;
            border-color: var(--border);
        }

        .button-secondary:hover {
            background: var(--surface);
        }

        .hero-note {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-top: 23px;
            color: #94a3b8;
            font-size: 12px;
        }

        .hero-note svg {
            flex-shrink: 0;
        }

        .code-window {
            overflow: hidden;
            border: 1px solid #dce5f1;
            border-radius: 16px;
            background: #ffffff;
            box-shadow:
                0 24px 70px rgba(15, 23, 42, 0.09),
                0 3px 10px rgba(15, 23, 42, 0.03);
            transform: rotate(0.5deg);
        }

        .window-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 15px 18px;
            border-bottom: 1px solid #e8edf5;
            background: #fbfcfe;
        }

        .window-dots {
            display: flex;
            gap: 6px;
        }

        .window-dots span {
            width: 9px;
            height: 9px;
            border-radius: 50%;
            background: #cbd5e1;
        }

        .window-dots span:first-child {
            background: #fca5a5;
        }

        .window-dots span:nth-child(2) {
            background: #fcd34d;
        }

        .window-dots span:nth-child(3) {
            background: #86efac;
        }

        .window-title {
            color: #94a3b8;
            font-size: 11px;
            font-weight: 600;
        }

        .code-body {
            padding: 27px 24px 30px;
            font-family: "SFMono-Regular", Consolas, monospace;
            font-size: 12px;
            line-height: 2.1;
            overflow-x: auto;
        }

        .code-comment {
            color: #94a3b8;
        }

        .code-keyword {
            color: #7c3aed;
        }

        .code-class {
            color: #2563eb;
        }

        .code-string {
            color: #059669;
        }

        .code-function {
            color: #c2410c;
        }

        .code-line {
            display: flex;
            gap: 19px;
            min-width: max-content;
        }

        .line-number {
            width: 14px;
            color: #cbd5e1;
            text-align: right;
            user-select: none;
        }

        .code-text {
            color: #334155;
        }

        .terminal {
            margin-top: 4px;
            padding: 15px 20px;
            border-top: 1px solid #e8edf5;
            background: #f8fafc;
            font-family: Consolas, monospace;
            font-size: 12px;
        }

        .terminal-label {
            margin-bottom: 7px;
            color: #94a3b8;
            font-family: Inter, sans-serif;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 1px;
        }

        .terminal-prompt {
            color: #16a34a;
        }

        .terminal-command {
            color: #334155;
        }

        .terminal-success {
            margin-top: 5px;
            color: #15803d;
        }

        .features {
            padding: 76px 0 90px;
            border-top: 1px solid #f1f5f9;
        }

        .section-heading {
            max-width: 610px;
            margin: 0 auto 42px;
            text-align: center;
        }

        .section-label {
            color: var(--primary);
            font-size: 12px;
            font-weight: 750;
            letter-spacing: 1.5px;
            text-transform: uppercase;
        }

        .section-heading h2 {
            margin: 12px 0 12px;
            font-size: clamp(27px, 3vw, 36px);
            line-height: 1.2;
            letter-spacing: -1.4px;
        }

        .section-heading p {
            margin: 0;
            color: var(--muted);
            font-size: 15px;
        }

        .feature-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 18px;
        }

        .feature-card {
            padding: 24px 21px;
            border: 1px solid var(--border);
            border-radius: var(--radius);
            background: white;
            transition: border-color 0.2s, box-shadow 0.2s,
                transform 0.2s;
        }

        .feature-card:hover {
            transform: translateY(-3px);
            border-color: #bfdbfe;
            box-shadow: 0 12px 28px rgba(15, 23, 42, 0.04);
        }

        .feature-icon {
            display: grid;
            place-items: center;
            width: 43px;
            height: 43px;
            margin-bottom: 19px;
            border-radius: 12px;
            color: var(--primary);
            background: var(--primary-light);
        }

        .feature-card h3 {
            margin: 0 0 9px;
            font-size: 15px;
            letter-spacing: -0.3px;
        }

        .feature-card p {
            margin: 0;
            color: var(--muted);
            font-size: 13px;
            line-height: 1.8;
        }

        .footer {
            padding: 24px 0;
            border-top: 1px solid var(--border);
            background: #fbfcfe;
        }

        .footer-inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            color: var(--muted);
            font-size: 12px;
        }

        .footer-brand {
            color: #334155;
            font-weight: 700;
        }

        @media (max-width: 900px) {
            .hero {
                padding: 70px 0 60px;
            }

            .hero-grid {
                grid-template-columns: 1fr;
                gap: 45px;
            }

            .hero-copy {
                max-width: 650px;
            }

            .code-window {
                max-width: 600px;
                width: 100%;
                transform: none;
            }

            .feature-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 560px) {
            .container {
                width: min(100% - 32px, 1120px);
            }

            .navbar {
                height: 66px;
            }

            .nav-links {
                gap: 12px;
            }

            .nav-links .nav-version {
                display: none;
            }

            .hero {
                padding: 55px 0 50px;
            }

            .hero h1 {
                font-size: 43px;
                letter-spacing: -2.5px;
            }

            .hero-description {
                font-size: 15px;
            }

            .hero-actions {
                align-items: stretch;
            }

            .hero-actions .button {
                flex: 1;
            }

            .code-body {
                padding: 20px 15px;
                font-size: 11px;
            }

            .features {
                padding: 60px 0;
            }

            .feature-grid {
                grid-template-columns: 1fr;
            }

            .feature-card {
                padding: 22px;
            }

            .footer-inner {
                align-items: flex-start;
                flex-direction: column;
                gap: 6px;
            }
        }
    </style>

    @yield('head')
</head>
<body>

    <header class="navbar">
        <div class="container navbar-inner">
            <a href="{{ base_url('/') }}" class="brand">
                <span class="brand-icon">&lt;/&gt;</span>
                <span>LocalPHP</span>
            </a>

            <nav class="nav-links">
                <a href="#features">Features</a>
                <a href="#getting-started">Get started</a>
                <span class="nav-version">PHP 8.2+</span>
            </nav>
        </div>
    </header>

    @yield('content')

    <footer class="footer">
        <div class="container footer-inner">
            <span class="footer-brand">LocalPHP</span>
            <span>Built for local development. Designed for simplicity.</span>
        </div>
    </footer>

</body>
</html>