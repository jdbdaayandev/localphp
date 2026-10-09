
@extends('layouts.app')

@section('title')
    LocalPHP — Lightweight PHP Framework
@endsection

@section('content')

<main>
    <section class="hero">
        <div class="container hero-grid">

            <div class="hero-copy">
                <div class="eyebrow">
                    <span class="status-dot"></span>
                    YOUR LOCAL DEVELOPMENT FRAMEWORK
                </div>

                <h1>
                    Build locally.<br>
                    Build <span>beautifully.</span>
                </h1>

                <p class="hero-description">
                    Meet LocalPHP, a lightweight PHP framework built
                    to make local development simple, organized, and
                    enjoyable. Start with a clean foundation and build
                    applications your way.
                </p>

                <div class="hero-actions">
                    <a href="#getting-started"
                       class="button button-primary">
                        Get started
                        <span aria-hidden="true">&rarr;</span>
                    </a>

                    <a href="#features"
                       class="button button-secondary">
                        Explore features
                    </a>
                </div>

                <div class="hero-note">
                    <svg width="16" height="16" viewBox="0 0 24 24"
                         fill="none" stroke="currentColor"
                         stroke-width="1.8" aria-hidden="true">
                        <circle cx="12" cy="12" r="9"></circle>
                        <path d="m8 12 2.5 2.5L16 9"></path>
                    </svg>
                    PHP 8.2+ &nbsp;·&nbsp; Lightweight &nbsp;·&nbsp;
                    Local-first
                </div>
            </div>

            <div class="code-window" aria-label="PHP code preview">
                <div class="window-header">
                    <div class="window-dots">
                        <span></span>
                        <span></span>
                        <span></span>
                    </div>

                    <span class="window-title">HomeController.php</span>
                    <span class="window-title">PHP</span>
                </div>

                <div class="code-body">
                    <div class="code-line">
                        <span class="line-number">1</span>
                        <span class="code-comment">
                            &lt;?php
                        </span>
                    </div>

                    <div class="code-line">
                        <span class="line-number">2</span>
                        <span class="code-comment">
                            // Your application starts here
                        </span>
                    </div>

                    <div class="code-line">
                        <span class="line-number">3</span>
                        <span class="code-text">
                            <span class="code-keyword">namespace</span>
                            App\Controllers;
                        </span>
                    </div>

                    <div class="code-line">
                        <span class="line-number">4</span>
                        <span class="code-text">&nbsp;</span>
                    </div>

                    <div class="code-line">
                        <span class="line-number">5</span>
                        <span class="code-text">
                            <span class="code-keyword">class</span>
                            <span class="code-class">HomeController</span>
                        </span>
                    </div>

                    <div class="code-line">
                        <span class="line-number">6</span>
                        <span class="code-text">{</span>
                    </div>

                    <div class="code-line">
                        <span class="line-number">7</span>
                        <span class="code-text">
                            &nbsp;&nbsp;
                            <span class="code-keyword">public function</span>
                            <span class="code-function">index</span>()
                        </span>
                    </div>

                    <div class="code-line">
                        <span class="line-number">8</span>
                        <span class="code-text">
                            &nbsp;&nbsp;{
                        </span>
                    </div>

                    <div class="code-line">
                        <span class="line-number">9</span>
                        <span class="code-text">
                            &nbsp;&nbsp;&nbsp;&nbsp;
                            <span class="code-keyword">return</span>
                            render(
                        </span>
                    </div>

                    <div class="code-line">
                        <span class="line-number">10</span>
                        <span class="code-text">
                            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                            <span class="code-string">'home'</span>
                        </span>
                    </div>

                    <div class="code-line">
                        <span class="line-number">11</span>
                        <span class="code-text">
                            &nbsp;&nbsp;&nbsp;&nbsp;);
                        </span>
                    </div>

                    <div class="code-line">
                        <span class="line-number">12</span>
                        <span class="code-text">
                            &nbsp;&nbsp;}
                        </span>
                    </div>

                    <div class="code-line">
                        <span class="line-number">13</span>
                        <span class="code-text">}</span>
                    </div>
                </div>

                <div class="terminal">
                    <div class="terminal-label">TERMINAL</div>
                    <div>
                        <span class="terminal-prompt">$</span>
                        <span class="terminal-command">
                            php local list
                        </span>
                    </div>
                    <div class="terminal-success">
                        ✓ LocalPHP Console ready
                    </div>
                </div>
            </div>

        </div>
    </section>

    <section class="features" id="features">
        <div class="container">

            <div class="section-heading">
                <span class="section-label">Why LocalPHP?</span>

                <h2>A simpler way to build with PHP.</h2>

                <p>
                    The essentials you need, with a clean foundation
                    that stays out of your way.
                </p>
            </div>

            <div class="feature-grid">

                <article class="feature-card">
                    <div class="feature-icon">
                        <svg width="22" height="22" viewBox="0 0 24 24"
                             fill="none" stroke="currentColor"
                             stroke-width="1.8" aria-hidden="true">
                            <path d="m12 3 8 4v5c0 5-3.5 8-8 9-4.5-1-8-4-8-9V7z"></path>
                            <path d="m9 12 2 2 4-4"></path>
                        </svg>
                    </div>

                    <h3>Lightweight foundation</h3>

                    <p>
                        Keep your application lean with a focused
                        architecture and only the essentials.
                    </p>
                </article>

                <article class="feature-card">
                    <div class="feature-icon">
                        <svg width="22" height="22" viewBox="0 0 24 24"
                             fill="none" stroke="currentColor"
                             stroke-width="1.8" aria-hidden="true">
                            <rect x="3" y="4" width="18" height="16" rx="3"></rect>
                            <path d="m7 9 3 3-3 3"></path>
                            <path d="M13 15h4"></path>
                        </svg>
                    </div>

                    <h3>Developer-friendly CLI</h3>

                    <p>
                        Manage migrations, seed data, and local
                        development tasks from your terminal.
                    </p>
                </article>

                <article class="feature-card">
                    <div class="feature-icon">
                        <svg width="22" height="22" viewBox="0 0 24 24"
                             fill="none" stroke="currentColor"
                             stroke-width="1.8" aria-hidden="true">
                            <rect x="3" y="3" width="7" height="7" rx="1.5"></rect>
                            <rect x="14" y="3" width="7" height="7" rx="1.5"></rect>
                            <rect x="3" y="14" width="7" height="7" rx="1.5"></rect>
                            <rect x="14" y="14" width="7" height="7" rx="1.5"></rect>
                        </svg>
                    </div>

                    <h3>Organized MVC</h3>

                    <p>
                        Separate routes, controllers, models, and
                        views into a structure that makes sense.
                    </p>
                </article>

                <article class="feature-card">
                    <div class="feature-icon">
                        <svg width="22" height="22" viewBox="0 0 24 24"
                             fill="none" stroke="currentColor"
                             stroke-width="1.8" aria-hidden="true">
                            <circle cx="12" cy="12" r="9"></circle>
                            <path d="M12 7v5l3 2"></path>
                        </svg>
                    </div>

                    <h3>Local-first workflow</h3>

                    <p>
                        Develop and test locally with your existing
                        PHP installation and Laragon environment.
                    </p>
                </article>

            </div>
        </div>
    </section>

    <section class="features" id="getting-started"
             style="padding-top: 0;">
        <div class="container">
            <div class="section-heading">
                <span class="section-label">Get started</span>

                <h2>Your next PHP project starts here.</h2>

                <p>
                    Run your application locally, build your routes,
                    and create something useful with LocalPHP.
                </p>

                <div class="hero-actions"
                     style="justify-content: center;">
                    <a href="{{ base_url('/') }}"
                       class="button button-primary">
                        Visit LocalPHP
                        <span aria-hidden="true">&rarr;</span>
                    </a>
                </div>
            </div>
        </div>
    </section>
</main>

@endsection