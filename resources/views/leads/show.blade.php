<!DOCTYPE html>
<html lang="vi">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="noindex, nofollow">
        <title>Chi tiết lead | Pet Lawn Marketing MVP</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="dashboard-page">
        @php
            $statusLabels = \App\Models\Lead::STATUS_LABELS;
            $petLabels = ['dog' => 'Chó', 'cat' => 'Mèo', 'other' => 'Khác'];
            $interestLabels = ['low' => 'Thấp', 'medium' => 'Trung bình', 'high' => 'Cao'];
            $segmentClasses = ['HOT' => 'hot', 'WARM' => 'warm', 'COLD' => 'cold'];
            $scoreHasChanged = $lead->score !== $explanation['score'] || $lead->segment !== $explanation['segment'];
            $oldStatus = old('status', $lead->status);
            $selectedStatus = is_string($oldStatus) && array_key_exists($oldStatus, $statusLabels) ? $oldStatus : $lead->status;
        @endphp

        <a class="skip-link" href="#lead-content">Đến nội dung chính</a>
        <x-dashboard-header :back="$back" />

        <main id="lead-content" class="dashboard-container lead-detail-main">
            <a class="lead-back-link" href="{{ route('dashboard', $back) }}">← Về danh sách lead</a>
            <div class="lead-detail-heading">
                <div>
                    <p class="eyebrow">KHÁCH HÀNG TIỀM NĂNG · #{{ $lead->id }}</p>
                    <h1>{{ $lead->name }}</h1>
                    <p>Xem nhu cầu và theo dõi quá trình chăm sóc khách hàng.</p>
                </div>
                <div class="lead-heading-actions">
                    <span class="lead-status">{{ $statusLabels[$lead->status] ?? $lead->status }}</span>
                    <a class="button button-small" href="{{ route('leads.edit', ['lead' => $lead, 'back' => $back]) }}">Chỉnh sửa thông tin</a>
                </div>
            </div>

            @if (session('success'))
                <div class="feedback feedback-success lead-detail-feedback" role="status" tabindex="-1" data-form-feedback>
                    <strong>{{ session('success') }}</strong>
                </div>
            @endif
            @if ($errors->any())
                <div class="feedback feedback-error lead-detail-feedback" role="alert" tabindex="-1" data-form-feedback>
                    <strong>{{ $errors->has('body') ? 'Chưa thể thêm ghi chú.' : 'Chưa thể cập nhật trạng thái.' }}</strong>
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="lead-detail-grid">
                <div class="lead-details-column">
                    <section class="lead-detail-card" aria-labelledby="lead-information-heading">
                        <h2 id="lead-information-heading">Thông tin khách hàng</h2>
                        <dl class="lead-information">
                            <div><dt>Liên hệ</dt><dd>{{ $lead->contact ?? 'Chưa có liên hệ' }}</dd></div>
                            <div><dt>Thú cưng</dt><dd>{{ $petLabels[$lead->pet_type] ?? $lead->pet_type }}</dd></div>
                            <div><dt>Địa điểm</dt><dd>{{ $lead->location }}</dd></div>
                            <div><dt>Ngân sách</dt><dd>{{ number_format($lead->budget, 0, ',', '.') }} ₫</dd></div>
                            <div><dt>Mức độ quan tâm</dt><dd>{{ $interestLabels[$lead->interest_level] ?? $lead->interest_level }}</dd></div>
                            <div><dt>Nguồn marketing</dt><dd>{{ $lead->source ?? 'Chưa rõ' }}</dd></div>
                            <div><dt>Ngày tạo</dt><dd>{{ $lead->created_at?->timezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i') ?? 'Chưa có dữ liệu' }}</dd></div>
                            <div><dt>Cập nhật gần nhất</dt><dd>{{ $lead->updated_at?->timezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i') ?? 'Chưa có dữ liệu' }}</dd></div>
                        </dl>
                        <div class="lead-pain-point">
                            <h3>Nhu cầu cần giải quyết</h3>
                            <p>{{ trim((string) $lead->pain_point) !== '' ? $lead->pain_point : 'Khách hàng chưa mô tả nhu cầu.' }}</p>
                        </div>
                        <p class="lead-time-note">Thời gian hiển thị theo giờ Việt Nam.</p>
                    </section>

                    <section class="lead-detail-card" aria-labelledby="lead-status-heading">
                        <h2 id="lead-status-heading">Trạng thái chăm sóc</h2>
                        <p class="lead-card-intro">Ghi nhận tiến độ sau mỗi lần làm việc với khách hàng.</p>
                        <form action="{{ route('leads.status.update', ['lead' => $lead, 'back' => $back]) }}" method="POST" class="lead-status-form" data-lead-form novalidate>
                            @csrf
                            @method('PATCH')
                            <div class="field">
                                <label for="status">Trạng thái hiện tại</label>
                                <select id="status" name="status" required aria-invalid="{{ $errors->has('status') ? 'true' : 'false' }}" @error('status') aria-describedby="status-error" @enderror>
                                    @foreach ($statusLabels as $value => $label)
                                        <option value="{{ $value }}" @selected($selectedStatus === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('status') <p class="field-error" id="status-error">{{ $message }}</p> @enderror
                            </div>
                            <button class="button" type="submit">Lưu trạng thái</button>
                        </form>
                        <p class="lead-status-note">Điểm và phân nhóm được giữ nguyên khi cập nhật trạng thái.</p>
                    </section>


                </div>

                <section class="lead-detail-card lead-scoring-card" aria-labelledby="lead-scoring-heading">
                    <div class="lead-score-overview">
                        <p class="eyebrow">ĐIỂM ĐÃ LƯU</p>
                        <div class="lead-score-number"><strong>{{ $lead->score }}</strong><span>/ 100</span><span class="segment-badge segment-{{ $segmentClasses[$lead->segment] ?? 'unknown' }}">{{ $lead->segment }}</span></div>
                        <p>Điểm ưu tiên theo nhu cầu của khách hàng.</p>
                    </div>
                    <h2 id="lead-scoring-heading">Giải thích điểm</h2>
                    <p class="lead-card-intro">Từng tiêu chí được tính từ thông tin hiện tại của lead.</p>
                    @if ($scoreHasChanged)
                        <div class="lead-score-notice" role="note">
                            <strong>Điểm đã lưu khác kết quả hiện tại.</strong>
                            <p>Kết quả bên dưới là {{ $explanation['score'] }} điểm / {{ $explanation['segment'] }}. Việc xem trang hoặc đổi trạng thái không cập nhật điểm đã lưu.</p>
                        </div>
                    @endif
                    <ol class="score-breakdown">
                        @foreach ($explanation['breakdown'] as $factor)
                            <li>
                                <div><h3>{{ $factor['label'] }}</h3><p>{{ $factor['reason'] }}</p></div>
                                <strong aria-label="{{ $factor['points'] }} điểm">+{{ $factor['points'] }}</strong>
                            </li>
                        @endforeach
                    </ol>
                    <div class="score-calculation">
                        <span>Tổng điểm theo tiêu chí</span>
                        <strong>{{ implode(' + ', array_column($explanation['breakdown'], 'points')) }} = {{ $explanation['score'] }}</strong>
                    </div>
                    <div class="score-segment-result">
                        <span class="segment-badge segment-{{ $segmentClasses[$explanation['segment']] ?? 'unknown' }}">{{ $explanation['segment'] }}</span>
                        <p>{{ $explanation['segment_reason'] }}</p>
                    </div>
                    <p class="score-explanation-note">Điểm giúp sắp xếp mức ưu tiên, không phải xác suất khách hàng sẽ mua.</p>
                </section>
            </div>
            <div id="lead-care" class="lead-care-grid">
                <section class="lead-detail-card" aria-labelledby="lead-notes-heading">
                    <h2 id="lead-notes-heading">Ghi chú chăm sóc <span class="care-count">{{ $notes->total() }}</span></h2>
                    <p class="lead-card-intro">Ghi lại nội dung tư vấn và việc cần làm tiếp theo.</p>
                    <form action="{{ route('leads.notes.store', ['lead' => $lead, 'back' => $back]) }}" method="POST" class="lead-note-form" data-lead-form novalidate>
                        @csrf
                        <div class="field">
                            <label for="body">Nội dung ghi chú</label>
                            <textarea id="body" name="body" rows="3" maxlength="2000" required placeholder="Ví dụ: Đã tư vấn kích thước thảm, hẹn gọi lại chiều mai." aria-invalid="{{ $errors->has('body') ? 'true' : 'false' }}" @error('body') aria-describedby="body-error" @enderror>{{ is_string(old('body')) ? old('body') : '' }}</textarea>
                            @error('body') <p class="field-error" id="body-error">{{ $message }}</p> @enderror
                        </div>
                        <button class="button button-small" type="submit">Thêm ghi chú</button>
                    </form>
                    <ol class="care-timeline">
                        @forelse ($notes as $note)
                            <li><time datetime="{{ $note->created_at->toIso8601String() }}">{{ $note->created_at->timezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i') }}</time><p class="care-note-body">{{ $note->body }}</p></li>
                        @empty
                            <li class="care-empty">Chưa có ghi chú chăm sóc.</li>
                        @endforelse
                    </ol>
                    <x-lead-pagination :paginator="$notes" label="Phân trang ghi chú" />
                </section>

                <section class="lead-detail-card" aria-labelledby="lead-history-heading">
                    <h2 id="lead-history-heading">Lịch sử trạng thái <span class="care-count">{{ $statusHistory->total() }}</span></h2>
                    <p class="lead-card-intro">Các lần thay đổi được ghi nhận từ khi tính năng này được bật.</p>
                    <ol class="care-timeline">
                        @forelse ($statusHistory as $history)
                            <li>
                                <time datetime="{{ $history->created_at->toIso8601String() }}">{{ $history->created_at->timezone('Asia/Ho_Chi_Minh')->format('d/m/Y H:i') }}</time>
                                <p class="care-status-change"><span>{{ $statusLabels[$history->from_status] ?? $history->from_status }}</span><span aria-hidden="true">→</span><strong>{{ $statusLabels[$history->to_status] ?? $history->to_status }}</strong></p>
                            </li>
                        @empty
                            <li class="care-empty">Chưa có lần thay đổi trạng thái nào được ghi nhận.</li>
                        @endforelse
                    </ol>
                    <x-lead-pagination :paginator="$statusHistory" label="Phân trang lịch sử trạng thái" />
                </section>
            </div>
            <footer class="dashboard-footer"><span>Pet Lawn Marketing MVP</span><p>Ưu tiên đúng khách hàng, bắt đầu từ nhu cầu.</p></footer>
        </main>
    </body>
</html>
