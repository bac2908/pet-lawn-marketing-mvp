@props(['back' => []])
<header class="dashboard-header">
    <div class="dashboard-container dashboard-header-inner">
        <a class="brand" href="{{ route('dashboard', $back) }}" aria-label="Pet Lawn — Dashboard">
            <span class="brand-icon">
                <svg viewBox="0 0 32 32" aria-hidden="true">
                    <ellipse cx="7" cy="11" rx="3.5" ry="4.5" transform="rotate(-25 7 11)" />
                    <ellipse cx="14" cy="6" rx="3.3" ry="4.5" />
                    <ellipse cx="22" cy="7" rx="3.3" ry="4.5" transform="rotate(20 22 7)" />
                    <ellipse cx="27" cy="14" rx="3" ry="4" transform="rotate(30 27 14)" />
                    <path d="M7 23c0-4 6-11 10-11s10 7 10 11c0 6-6 5-10 3-4 2-10 3-10-3Z" />
                </svg>
            </span>
            Pet Lawn<span class="brand-dot">.</span>
        </a>
        <nav aria-label="Điều hướng dashboard">
            <a class="dashboard-nav-active" href="{{ route('dashboard', $back) }}" @if (request()->routeIs('dashboard')) aria-current="page" @endif>Dashboard</a>
            <a class="button button-outline button-small" href="{{ route('home') }}">Trang giới thiệu <span aria-hidden="true">↗</span></a>
        </nav>
    </div>
</header>
