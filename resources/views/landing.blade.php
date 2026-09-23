<!DOCTYPE html>
<html lang="vi">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="Giải pháp Pet Lawn phù hợp cho căn hộ, ban công và sân nhỏ. Nhận tư vấn cho không gian của thú cưng nhà bạn.">
        <title>Pet Lawn</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body>
        <a class="skip-link" href="#main-content">Đến nội dung chính</a>
        <svg class="svg-sprite" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
            <symbol id="paw-mark" viewBox="0 0 32 32">
                <ellipse cx="7" cy="11" rx="3.5" ry="4.5" transform="rotate(-25 7 11)" />
                <ellipse cx="14" cy="6" rx="3.3" ry="4.5" />
                <ellipse cx="22" cy="7" rx="3.3" ry="4.5" transform="rotate(20 22 7)" />
                <ellipse cx="27" cy="14" rx="3" ry="4" transform="rotate(30 27 14)" />
                <path d="M7 23c0-4 6-11 10-11s10 7 10 11c0 6-6 5-10 3-4 2-10 3-10-3Z" />
            </symbol>
        </svg>

        <header class="site-header container">
            <a class="brand" href="{{ route('home') }}" aria-label="Pet Lawn — Trang chủ">
                <span class="brand-icon"><svg aria-hidden="true"><use href="#paw-mark" /></svg></span>
                Pet Lawn<span class="brand-dot">.</span>
            </a>
            <nav aria-label="Điều hướng chính">
                <a class="nav-link" href="#benefits">Vì sao Pet Lawn?</a>
                <a class="button button-outline button-small" href="#lead-form">Nhận tư vấn <span aria-hidden="true">↗</span></a>
            </nav>
        </header>

        <main id="main-content">
            <section class="hero container" aria-labelledby="hero-title">
                <div class="hero-copy">
                    <p class="eyebrow"><span class="green-dot" aria-hidden="true"></span> MỘT GÓC XANH CHO BẠN NHỎ</p>
                    <h1 id="hero-title">Không gian xanh sạch và tiện lợi cho <span>thú cưng của bạn.</span></h1>
                    <p class="hero-subtitle">Giải pháp Pet Lawn phù hợp cho căn hộ, ban công và sân nhỏ.</p>
                    <a class="button" href="#lead-form">Nhận tư vấn <span aria-hidden="true">↗</span></a>
                    <p class="hero-note">Bắt đầu từ nhu cầu của bạn và thú cưng.</p>
                </div>
                <figure class="hero-art">
                    <div class="art-label"><span class="green-dot" aria-hidden="true"></span> CHO NHỮNG GÓC NHỎ</div>
                    <svg class="lawn-illustration" viewBox="0 0 480 340" aria-hidden="true" focusable="false">
                        <defs>
                            <pattern id="grass" width="22" height="20" patternUnits="userSpaceOnUse">
                                <path d="m5 16 1-7 3 7m5-6 1-5 2 6" fill="none" stroke="#8fb46b" stroke-width="1.5" stroke-linecap="round" />
                            </pattern>
                        </defs>
                        <circle cx="260" cy="146" r="115" fill="#e6ecd8" />
                        <path d="M36 211 234 119 451 212 253 308Z" fill="#d7dbbd" />
                        <path d="m56 199 181-84 191 81-181 88Z" fill="#245b3a" />
                        <path d="M56 186 237 102 428 183 247 271Z" fill="#719550" />
                        <path d="M56 186 237 102 428 183 247 271Z" fill="url(#grass)" />
                        <path d="m56 186 191 85v13L56 199Z" fill="#3e7144" />
                        <path d="m247 271 181-88v13l-181 88Z" fill="#285b39" />
                        <g fill="#e7edcc" opacity=".92">
                            <use href="#paw-mark" x="149" y="147" width="44" height="44" transform="rotate(-25 171 169)" />
                            <use href="#paw-mark" x="210" y="176" width="44" height="44" transform="rotate(-25 232 198)" />
                            <use href="#paw-mark" x="272" y="137" width="37" height="37" transform="rotate(-25 290 155)" />
                        </g>
                        <ellipse cx="371" cy="137" rx="32" ry="12" fill="#bfc6a6" />
                        <path d="m344 103 7 34q20 13 40 0l7-34Z" fill="#c58b65" />
                        <ellipse cx="371" cy="103" rx="27" ry="10" fill="#e2b58e" />
                        <ellipse cx="371" cy="102" rx="21" ry="7" fill="#796444" />
                        <path d="M371 101V49m0 34-18-18m18 8 19-19" stroke="#3f6a40" stroke-width="4" fill="none" stroke-linecap="round" />
                        <path d="M371 65c-23-2-28-22-16-30 13 2 19 17 16 30Zm1 16c2-25 16-34 31-29-1 17-15 28-31 29Zm-3 16c-25-1-35-12-31-26 18-3 29 10 31 26Z" fill="#60894c" />
                        <path d="M113 95h19m-9-9v18M313 278h15m-7-7v15" stroke="#7c9b60" stroke-width="2" stroke-linecap="round" />
                    </svg>
                    <figcaption>Một góc xanh.<br><strong>Thêm niềm vui mỗi ngày.</strong></figcaption>
                    <div class="space-tags"><span>Căn hộ</span><span>Ban công</span><span>Sân nhỏ</span></div>
                </figure>
            </section>

            <section class="benefits-section" id="benefits" aria-labelledby="benefits-title">
                <div class="container">
                    <div class="section-heading">
                        <p class="eyebrow">NHỎ GỌN TRONG KHÔNG GIAN, TIỆN LỢI MỖI NGÀY</p>
                        <h2 id="benefits-title">Bạn nhẹ việc. Thú cưng thêm vui.</h2>
                    </div>
                    <div class="benefits-grid">
                        <article class="benefit">
                            <span class="benefit-number" aria-hidden="true">01</span>
                            <h3>Dễ vệ sinh</h3>
                            <p>Bớt thời gian dọn dẹp, thêm thời gian bên những người bạn nhỏ.</p>
                        </article>
                        <article class="benefit">
                            <span class="benefit-number" aria-hidden="true">02</span>
                            <h3>Thân thiện với thú cưng</h3>
                            <p>Một góc riêng để thú cưng nghỉ ngơi và vui chơi ngay tại nhà.</p>
                        </article>
                        <article class="benefit">
                            <span class="benefit-number" aria-hidden="true">03</span>
                            <h3>Phù hợp căn hộ, ban công và sân nhỏ</h3>
                            <p>Linh hoạt với không gian sống và nhu cầu của gia đình bạn.</p>
                        </article>
                        <article class="benefit">
                            <span class="benefit-number" aria-hidden="true">04</span>
                            <h3>Giảm bùn đất và mùi khó chịu</h3>
                            <p>Giữ khu vực sinh hoạt gọn gàng, dễ chăm sóc mỗi ngày.</p>
                        </article>
                    </div>
                </div>
            </section>

            <section class="consultation container" id="lead-form" aria-labelledby="consultation-title">
                <div class="consultation-copy">
                    <p class="eyebrow">CÙNG TÌM GIẢI PHÁP PHÙ HỢP</p>
                    <h2 id="consultation-title">Bắt đầu từ<br>một góc xanh.</h2>
                    <p>Chia sẻ một chút về thú cưng và không gian của bạn. Pet Lawn sẽ cùng bạn tìm giải pháp phù hợp.</p>
                    <ol class="consultation-steps">
                        <li><span>1</span><div><strong>Bạn chia sẻ nhu cầu</strong><p>Điền thông tin trong biểu mẫu bên cạnh.</p></div></li>
                        <li><span>2</span><div><strong>Pet Lawn liên hệ tư vấn</strong><p>Cùng chọn giải pháp cho góc nhỏ nhà bạn.</p></div></li>
                    </ol>
                </div>

                <div class="form-card">
                    <h3>Nhận tư vấn Pet Lawn</h3>
                    <p class="form-intro">Các trường có dấu <span aria-hidden="true">*</span> là bắt buộc.</p>

                    @if (session('success'))
                        <div class="feedback feedback-success" role="status" tabindex="-1" data-form-feedback>
                            <strong>Đã nhận thông tin của bạn!</strong>
                            <p>{{ session('success') }}</p>
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="feedback feedback-error" role="alert" tabindex="-1" data-form-feedback>
                            <strong>Vui lòng kiểm tra lại thông tin:</strong>
                            <ul>
                                @foreach ($errors->getMessages() as $field => $messages)
                                    <li><a href="#{{ $field }}">{{ $messages[0] }}</a></li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @php($oldValue = fn ($field) => is_scalar(old($field)) ? (string) old($field) : '')

                    <form action="{{ route('leads.store') }}" method="POST" class="lead-form" data-lead-form novalidate>
                        @csrf
                        <div class="field">
                            <label for="name">Họ và tên <span aria-hidden="true">*</span></label>
                            <input id="name" name="name" type="text" value="{{ $oldValue('name') }}" autocomplete="name" maxlength="255" placeholder="Tên của bạn" required aria-invalid="{{ $errors->has('name') ? 'true' : 'false' }}" @error('name') aria-describedby="name-error" @enderror>
                            @error('name') <p class="field-error" id="name-error">{{ $message }}</p> @enderror
                        </div>

                        <div class="field">
                            <label for="contact">Số điện thoại / Email <span class="optional">(tùy chọn)</span></label>
                            <input id="contact" name="contact" type="text" value="{{ $oldValue('contact') }}" maxlength="255" placeholder="Để chúng tôi liên hệ với bạn" aria-invalid="{{ $errors->has('contact') ? 'true' : 'false' }}" @error('contact') aria-describedby="contact-error" @enderror>
                            @error('contact') <p class="field-error" id="contact-error">{{ $message }}</p> @enderror
                        </div>

                        <div class="field">
                            <label for="pet_type">Thú cưng của bạn <span aria-hidden="true">*</span></label>
                            <select id="pet_type" name="pet_type" required aria-invalid="{{ $errors->has('pet_type') ? 'true' : 'false' }}" @error('pet_type') aria-describedby="pet_type-error" @enderror>
                                <option value="">Chọn loại thú cưng</option>
                                @foreach (['Dog' => 'Chó', 'Cat' => 'Mèo', 'Other' => 'Khác'] as $value => $label)
                                    <option value="{{ $value }}" @selected($oldValue('pet_type') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('pet_type') <p class="field-error" id="pet_type-error">{{ $message }}</p> @enderror
                        </div>

                        <div class="field">
                            <label for="location">Tỉnh / Thành phố <span aria-hidden="true">*</span></label>
                            <input id="location" name="location" type="text" value="{{ $oldValue('location') }}" autocomplete="address-level1" maxlength="255" placeholder="Ví dụ: HCM, Hà Nội" list="location-suggestions" required aria-invalid="{{ $errors->has('location') ? 'true' : 'false' }}" @error('location') aria-describedby="location-error" @enderror>
                            <datalist id="location-suggestions">
                                <option value="Thành phố Hồ Chí Minh"></option>
                                <option value="Hà Nội"></option>
                                <option value="Đà Nẵng"></option>
                                <option value="Cần Thơ"></option>
                                <option value="Hải Phòng"></option>
                            </datalist>
                            @error('location') <p class="field-error" id="location-error">{{ $message }}</p> @enderror
                        </div>

                        <div class="field">
                            <label for="budget">Ngân sách dự kiến <span aria-hidden="true">*</span></label>
                            <select id="budget" name="budget" required aria-invalid="{{ $errors->has('budget') ? 'true' : 'false' }}" @error('budget') aria-describedby="budget-error" @enderror>
                                <option value="">Chọn ngân sách (VND)</option>
                                @foreach ([1000000, 2000000, 3000000, 5000000, 7000000] as $amount)
                                    <option value="{{ $amount }}" @selected($oldValue('budget') === (string) $amount)>{{ number_format($amount, 0, ',', '.') }} ₫</option>
                                @endforeach
                            </select>
                            @error('budget') <p class="field-error" id="budget-error">{{ $message }}</p> @enderror
                        </div>

                        <div class="field">
                            <label for="interest_level">Mức độ quan tâm <span aria-hidden="true">*</span></label>
                            <select id="interest_level" name="interest_level" required aria-invalid="{{ $errors->has('interest_level') ? 'true' : 'false' }}" @error('interest_level') aria-describedby="interest_level-error" @enderror>
                                <option value="">Chọn mức độ quan tâm</option>
                                @foreach (['low' => 'Thấp', 'medium' => 'Trung bình', 'high' => 'Cao'] as $value => $label)
                                    <option value="{{ $value }}" @selected($oldValue('interest_level') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('interest_level') <p class="field-error" id="interest_level-error">{{ $message }}</p> @enderror
                        </div>

                        <div class="field field-full">
                            <label for="pain_point">Bạn đang cần giải quyết điều gì? <span class="optional">(tùy chọn)</span></label>
                            <textarea id="pain_point" name="pain_point" rows="4" maxlength="2000" placeholder="Ví dụ: ban công khó vệ sinh, mùi, thiếu không gian cho thú cưng..." aria-invalid="{{ $errors->has('pain_point') ? 'true' : 'false' }}" @error('pain_point') aria-describedby="pain_point-error" @enderror>{{ $oldValue('pain_point') }}</textarea>
                            @error('pain_point') <p class="field-error" id="pain_point-error">{{ $message }}</p> @enderror
                        </div>

                        <div class="field field-full">
                            <label for="source">Bạn biết đến Pet Lawn từ đâu? <span class="optional">(tùy chọn)</span></label>
                            <select id="source" name="source" aria-invalid="{{ $errors->has('source') ? 'true' : 'false' }}" @error('source') aria-describedby="source-error" @enderror>
                                <option value="">Chọn nguồn giới thiệu</option>
                                @foreach (['Facebook' => 'Facebook', 'TikTok' => 'TikTok', 'Google' => 'Google', 'Referral' => 'Bạn bè giới thiệu', 'Other' => 'Khác'] as $value => $label)
                                    <option value="{{ $value }}" @selected($oldValue('source') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('source') <p class="field-error" id="source-error">{{ $message }}</p> @enderror
                        </div>

                        <div class="form-actions field-full">
                            <button class="button" type="submit">Gửi yêu cầu tư vấn <span aria-hidden="true">↗</span></button>
                            <p>Thông tin bạn chia sẻ giúp chúng tôi tư vấn phù hợp hơn.</p>
                        </div>
                    </form>
                </div>
            </section>

            <section class="closing-section" aria-label="Lời mời tư vấn">
                <div class="container closing-inner">
                    <div><p class="eyebrow">PET LAWN</p><h2>Một thay đổi nhỏ.<br>Một góc sống dễ chịu hơn.</h2></div>
                    <a class="button button-light" href="#lead-form">Nhận tư vấn <span aria-hidden="true">↗</span></a>
                </div>
            </section>
        </main>

        <footer class="site-footer container">
            <a class="brand" href="{{ route('home') }}">Pet Lawn<span class="brand-dot">.</span></a>
            <p>Không gian xanh cho những người bạn nhỏ.</p>
            <span>© {{ date('Y') }} Pet Lawn</span>
        </footer>
    </body>
</html>
