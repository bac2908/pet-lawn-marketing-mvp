<!DOCTYPE html>
<html lang="vi">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="noindex, nofollow">
        <title>Chỉnh sửa lead | Pet Lawn Marketing MVP</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="dashboard-page">
        @php
            $value = fn ($field, $default = null) => is_scalar(old($field, $default ?? $lead->{$field})) ? (string) old($field, $default ?? $lead->{$field}) : '';
            $detailUrl = route('leads.show', ['lead' => $lead, 'back' => $back]);
        @endphp
        <a class="skip-link" href="#lead-edit-content">Đến nội dung chính</a>
        <x-dashboard-header :back="$back" />
        <main id="lead-edit-content" class="dashboard-container lead-detail-main">
            <a class="lead-back-link" href="{{ $detailUrl }}">← Về chi tiết lead</a>
            <div class="lead-detail-heading">
                <div><p class="eyebrow">KHÁCH HÀNG TIỀM NĂNG · #{{ $lead->id }}</p><h1>Chỉnh sửa thông tin</h1><p>Cập nhật nhu cầu thực tế sau khi trao đổi với khách hàng.</p></div>
            </div>
            <div class="lead-edit-layout">
                <section class="lead-detail-card" aria-labelledby="lead-edit-heading">
                    <h2 id="lead-edit-heading">Thông tin lead</h2>
                    <p class="lead-card-intro">Các trường có dấu * là bắt buộc.</p>
                    @if ($errors->any())
                        <div class="feedback feedback-error" role="alert" tabindex="-1" data-form-feedback>
                            <strong>Chưa thể lưu. Vui lòng kiểm tra lại thông tin:</strong>
                            <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                        </div>
                    @endif
                    <form action="{{ route('leads.update', ['lead' => $lead, 'back' => $back]) }}" method="POST" class="lead-form lead-edit-form" data-lead-form novalidate>
                        @csrf
                        @method('PUT')
                        <div class="field">
                            <label for="name">Họ và tên *</label>
                            <input id="name" name="name" type="text" maxlength="255" required value="{{ $value('name') }}" aria-invalid="{{ $errors->has('name') ? 'true' : 'false' }}" @error('name') aria-describedby="name-error" @enderror>
                            @error('name') <p class="field-error" id="name-error">{{ $message }}</p> @enderror
                        </div>
                        <div class="field">
                            <label for="contact">Số điện thoại / Email <span class="optional">(tùy chọn)</span></label>
                            <input id="contact" name="contact" type="text" maxlength="255" value="{{ $value('contact') }}" aria-invalid="{{ $errors->has('contact') ? 'true' : 'false' }}" @error('contact') aria-describedby="contact-error" @enderror>
                            @error('contact') <p class="field-error" id="contact-error">{{ $message }}</p> @enderror
                        </div>
                        <div class="field">
                            <label for="pet_type">Thú cưng *</label>
                            <select id="pet_type" name="pet_type" required aria-invalid="{{ $errors->has('pet_type') ? 'true' : 'false' }}" @error('pet_type') aria-describedby="pet_type-error" @enderror>
                                <option value="">Chọn loại thú cưng</option>
                                @foreach (['Dog' => 'Chó', 'Cat' => 'Mèo', 'Other' => 'Khác'] as $option => $label)
                                    <option value="{{ $option }}" @selected($value('pet_type', ucfirst($lead->pet_type)) === $option)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('pet_type') <p class="field-error" id="pet_type-error">{{ $message }}</p> @enderror
                        </div>
                        <div class="field">
                            <label for="location">Tỉnh / Thành phố *</label>
                            <input id="location" name="location" type="text" maxlength="255" required value="{{ $value('location') }}" aria-invalid="{{ $errors->has('location') ? 'true' : 'false' }}" @error('location') aria-describedby="location-error" @enderror>
                            @error('location') <p class="field-error" id="location-error">{{ $message }}</p> @enderror
                        </div>
                        <div class="field">
                            <label for="budget">Ngân sách (VND) *</label>
                            <input id="budget" name="budget" type="number" inputmode="numeric" min="0" max="4294967295" step="1" required value="{{ $value('budget') }}" aria-invalid="{{ $errors->has('budget') ? 'true' : 'false' }}" @error('budget') aria-describedby="budget-error" @enderror>
                            @error('budget') <p class="field-error" id="budget-error">{{ $message }}</p> @enderror
                        </div>
                        <div class="field">
                            <label for="interest_level">Mức độ quan tâm *</label>
                            <select id="interest_level" name="interest_level" required aria-invalid="{{ $errors->has('interest_level') ? 'true' : 'false' }}" @error('interest_level') aria-describedby="interest_level-error" @enderror>
                                <option value="">Chọn mức độ quan tâm</option>
                                @foreach (['low' => 'Thấp', 'medium' => 'Trung bình', 'high' => 'Cao'] as $option => $label)
                                    <option value="{{ $option }}" @selected($value('interest_level') === $option)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('interest_level') <p class="field-error" id="interest_level-error">{{ $message }}</p> @enderror
                        </div>
                        <div class="field field-full">
                            <label for="pain_point">Nhu cầu cần giải quyết <span class="optional">(tùy chọn)</span></label>
                            <textarea id="pain_point" name="pain_point" rows="4" maxlength="2000" aria-invalid="{{ $errors->has('pain_point') ? 'true' : 'false' }}" @error('pain_point') aria-describedby="pain_point-error" @enderror>{{ $value('pain_point') }}</textarea>
                            @error('pain_point') <p class="field-error" id="pain_point-error">{{ $message }}</p> @enderror
                        </div>
                        <div class="field field-full">
                            <label for="source">Nguồn marketing <span class="optional">(tùy chọn)</span></label>
                            <input id="source" name="source" type="text" maxlength="100" list="source-options" value="{{ $value('source') }}" aria-invalid="{{ $errors->has('source') ? 'true' : 'false' }}" @error('source') aria-describedby="source-error" @enderror>
                            <datalist id="source-options"><option value="Facebook"></option><option value="TikTok"></option><option value="Google"></option><option value="Referral"></option><option value="Other"></option></datalist>
                            @error('source') <p class="field-error" id="source-error">{{ $message }}</p> @enderror
                        </div>
                        <div class="lead-edit-actions field-full"><button class="button" type="submit">Lưu và tính lại điểm</button><a class="button button-outline" href="{{ $detailUrl }}">Hủy chỉnh sửa</a></div>
                    </form>
                </section>
                <aside class="lead-detail-card lead-edit-summary" aria-label="Điểm trước khi chỉnh sửa">
                    <p class="eyebrow">TRƯỚC KHI CHỈNH SỬA</p><h2>{{ $lead->name }}</h2>
                    <p class="edit-score"><strong>{{ $lead->score }}</strong> / 100 · {{ $lead->segment }}</p>
                    <p>Khi lưu, điểm và phân nhóm sẽ được tính lại từ ngân sách, mức quan tâm, nhu cầu và địa điểm vừa cập nhật.</p>
                    <p>Trạng thái chăm sóc, ghi chú và lịch sử được giữ nguyên.</p>
                </aside>
            </div>
            <footer class="dashboard-footer"><span>Pet Lawn Marketing MVP</span><p>Ưu tiên đúng khách hàng, bắt đầu từ nhu cầu.</p></footer>
        </main>
    </body>
</html>
