<!DOCTYPE html>
<html lang="vi">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="noindex, nofollow">
        <title>Dashboard | Pet Lawn Marketing MVP</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="dashboard-page">
        @php
            $statusLabels = ['new' => 'Mới', 'contacted' => 'Đã liên hệ', 'qualified' => 'Đủ điều kiện', 'converted' => 'Đã chuyển đổi', 'lost' => 'Không thành công'];
            $petLabels = ['dog' => 'Chó', 'cat' => 'Mèo', 'other' => 'Khác'];
            $interestLabels = ['low' => 'Thấp', 'medium' => 'Trung bình', 'high' => 'Cao'];
            $segmentClasses = ['HOT' => 'hot', 'WARM' => 'warm', 'COLD' => 'cold'];
            $hasFilters = $filters['q'] !== '' || $filters['segment'] !== '' || $filters['status'] !== '' || $filters['source'] !== '';
        @endphp

        <a class="skip-link" href="#dashboard-content">Đến nội dung chính</a>
        <header class="dashboard-header">
            <div class="dashboard-container dashboard-header-inner">
                <a class="brand" href="{{ route('dashboard') }}" aria-label="Pet Lawn — Dashboard">
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
                    <a class="dashboard-nav-active" href="{{ route('dashboard') }}" aria-current="page">Dashboard</a>
                    <a class="button button-outline button-small" href="{{ route('home') }}">Trang giới thiệu <span aria-hidden="true">↗</span></a>
                </nav>
            </div>
        </header>

        <main id="dashboard-content" class="dashboard-container dashboard-main">
            <div class="dashboard-heading">
                <div>
                    <p class="eyebrow">PET LAWN MARKETING</p>
                    <h1>Tổng quan khách hàng</h1>
                    <p>Theo dõi khách hàng tiềm năng và xác định cơ hội cần ưu tiên.</p>
                </div>
                <a class="button button-small" href="{{ route('home') }}#lead-form">Mở form nhận tư vấn <span aria-hidden="true">↗</span></a>
            </div>

            <section aria-label="Thống kê toàn bộ lead">
                <div class="dashboard-stats">
                    <article class="stat-card stat-total">
                        <h2>Tổng số lead</h2>
                        <strong>{{ number_format($stats['total'], 0, ',', '.') }}</strong>
                        <p>Khách hàng đã để lại thông tin</p>
                    </article>
                    @foreach (['HOT' => 'Ưu tiên liên hệ', 'WARM' => 'Tiếp tục nuôi dưỡng', 'COLD' => 'Theo dõi thêm nhu cầu'] as $segment => $description)
                        <article class="stat-card stat-{{ $segmentClasses[$segment] }}">
                            <h2><span class="segment-dot" aria-hidden="true"></span>{{ $segment }}</h2>
                            <strong>{{ number_format($stats[$segment], 0, ',', '.') }}</strong>
                            <p>{{ $description }}</p>
                        </article>
                    @endforeach
                </div>
                <p class="stats-note">Thống kê toàn bộ lead, không thay đổi theo bộ lọc bên dưới.</p>
            </section>

            @if ($errors->any())
                <div class="feedback feedback-error" role="alert" tabindex="-1" data-form-feedback>
                    <strong>Bộ lọc chưa hợp lệ. Vui lòng chọn lại.</strong>
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <section class="dashboard-panel" aria-labelledby="lead-list-heading">
                <div class="lead-list-heading">
                    <div>
                        <h2 id="lead-list-heading">Danh sách lead <span>{{ number_format($leads->total(), 0, ',', '.') }}</span></h2>
                        <p>{{ $hasFilters ? 'Kết quả phù hợp với bộ lọc đã chọn.' : 'Thông tin được ghi nhận từ form nhận tư vấn.' }}</p>
                    </div>
                </div>

                <form class="dashboard-filters" action="{{ route('dashboard') }}" method="GET" role="search" aria-label="Tìm kiếm và lọc lead">
                    <div class="field dashboard-search">
                        <label for="q">Tìm kiếm</label>
                        <input type="search" id="q" name="q" maxlength="255" value="{{ $filters['q'] }}" placeholder="Tên, liên hệ hoặc địa điểm">
                    </div>
                    <div class="field">
                        <label for="segment">Phân nhóm</label>
                        <select id="segment" name="segment">
                            <option value="">Tất cả phân nhóm</option>
                            @foreach (array_keys($segmentClasses) as $segment)
                                <option value="{{ $segment }}" @selected($filters['segment'] === $segment)>{{ $segment }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field">
                        <label for="status">Trạng thái</label>
                        <select id="status" name="status">
                            <option value="">Tất cả trạng thái</option>
                            @foreach ($statusLabels as $status => $label)
                                <option value="{{ $status }}" @selected($filters['status'] === $status)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field">
                        <label for="source">Nguồn marketing</label>
                        <select id="source" name="source">
                            <option value="">Tất cả nguồn</option>
                            @if ($filters['source'] !== '' && ! $sources->contains($filters['source']))
                                <option value="{{ $filters['source'] }}" selected>{{ $filters['source'] }}</option>
                            @endif
                            @foreach ($sources as $source)
                                <option value="{{ $source }}" @selected($filters['source'] === $source)>{{ $source }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field">
                        <label for="sort">Sắp xếp</label>
                        <select id="sort" name="sort">
                            <option value="newest" @selected($filters['sort'] === 'newest')>Mới nhất</option>
                            <option value="score_desc" @selected($filters['sort'] === 'score_desc')>Điểm cao nhất</option>
                        </select>
                    </div>
                    <div class="dashboard-filter-actions">
                        <button class="button button-small" type="submit">Áp dụng</button>
                        <a href="{{ route('dashboard') }}" class="reset-filters">Xóa bộ lọc</a>
                    </div>
                </form>

                @if ($leads->isNotEmpty())
                    <div class="lead-table-scroll" tabindex="0" role="region" aria-label="Bảng lead, có thể cuộn ngang">
                        <table class="lead-table">
                            <caption class="visually-hidden">Khách hàng tiềm năng, điểm số và trạng thái hiện tại</caption>
                            <thead>
                                <tr>
                                    <th scope="col">Khách hàng</th>
                                    <th scope="col">Thú cưng / Địa điểm</th>
                                    <th scope="col">Ngân sách / Quan tâm</th>
                                    <th scope="col">Nguồn</th>
                                    <th scope="col">Điểm</th>
                                    <th scope="col">Phân nhóm</th>
                                    <th scope="col">Trạng thái</th>
                                    <th scope="col">Ngày tạo</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($leads as $lead)
                                    <tr>
                                        <td class="lead-customer"><strong>{{ $lead->name }}</strong><span>{{ $lead->contact ?: 'Chưa có liên hệ' }}</span></td>
                                        <td><strong>{{ $petLabels[$lead->pet_type] ?? $lead->pet_type }}</strong><span>{{ $lead->location }}</span></td>
                                        <td><strong class="lead-budget">{{ number_format($lead->budget, 0, ',', '.') }} ₫</strong><span>Quan tâm: {{ $interestLabels[$lead->interest_level] ?? $lead->interest_level }}</span></td>
                                        <td>{{ $lead->source ?: 'Chưa rõ' }}</td>
                                        <td class="lead-score">{{ $lead->score }}<span> / 100</span></td>
                                        <td><span class="segment-badge segment-{{ $segmentClasses[$lead->segment] ?? 'unknown' }}">{{ $lead->segment }}</span></td>
                                        <td><span class="lead-status">{{ $statusLabels[$lead->status] ?? $lead->status }}</span></td>
                                        <td class="lead-date">{{ $lead->created_at?->timezone('Asia/Ho_Chi_Minh')->format('d/m/Y') ?? '—' }}<span>{{ $lead->created_at?->timezone('Asia/Ho_Chi_Minh')->format('H:i') }}</span></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="dashboard-empty">
                        <span class="empty-symbol" aria-hidden="true">○</span>
                        <h3>{{ $stats['total'] === 0 ? 'Chưa có lead nào' : 'Không có lead phù hợp' }}</h3>
                        <p>{{ $stats['total'] === 0 ? 'Mở form nhận tư vấn để ghi nhận khách hàng đầu tiên.' : 'Thử từ khóa khác hoặc xóa bộ lọc để xem lại danh sách.' }}</p>
                        @if ($stats['total'] === 0)
                            <a class="button button-small" href="{{ route('home') }}#lead-form">Mở form nhận tư vấn</a>
                        @else
                            <a class="button button-outline button-small" href="{{ route('dashboard') }}">Xem tất cả lead</a>
                        @endif
                    </div>
                @endif

                <div class="dashboard-list-footer">
                    <p>Hiển thị {{ $leads->firstItem() ?? 0 }}–{{ $leads->lastItem() ?? 0 }} / {{ number_format($leads->total(), 0, ',', '.') }} lead <span>· Giờ Việt Nam</span></p>
                    @if ($leads->hasPages())
                        <nav class="dashboard-pagination" aria-label="Phân trang lead">
                            @if ($leads->onFirstPage())
                                <span class="page-disabled" aria-disabled="true">← Trước</span>
                            @else
                                <a href="{{ $leads->previousPageUrl() }}" rel="prev">← Trước</a>
                            @endif
                            <span class="page-current">Trang {{ $leads->currentPage() }} / {{ $leads->lastPage() }}</span>
                            @if ($leads->hasMorePages())
                                <a href="{{ $leads->nextPageUrl() }}" rel="next">Sau →</a>
                            @else
                                <span class="page-disabled" aria-disabled="true">Sau →</span>
                            @endif
                        </nav>
                    @endif
                </div>
            </section>
            <footer class="dashboard-footer"><span>Pet Lawn Marketing MVP</span><p>Ưu tiên đúng khách hàng, bắt đầu từ nhu cầu.</p></footer>
        </main>
    </body>
</html>
