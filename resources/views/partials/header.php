<header>
    <h1>{{ $title ?? 'Welcome' }}</h1>

    @if (isset($subtitle))
        <p>{{ $subtitle }}</p>
    @endif
</header>