# Pet Lawn Marketing MVP

Tài liệu kỹ thuật bổ sung. Xem [README và ảnh demo](../README.md)
để có phần giới thiệu tổng quan và hướng dẫn chạy dự án.

Nền tảng Laravel 12 chạy bằng Docker, với PHP 8.4, MySQL 8.4, Blade,
JavaScript và Laravel Vite. Mã nguồn nằm trực tiếp trong thư mục dự án.

Trang `/` là landing page Pet Lawn bằng tiếng Việt, có phần giới thiệu,
lợi ích và biểu mẫu nhận tư vấn. Lead hợp lệ được chấm điểm và lưu vào MySQL.

Đã có phần nền, model Lead, bảng `leads`, 10 lead mẫu, chấm điểm theo quy tắc
và luồng tiếp nhận lead công khai. Dashboard tại `/dashboard` hiển thị thống kê,
danh sách lead, tìm kiếm, bộ lọc và phân trang.
Trang chi tiết `/leads/{lead}` giải thích điểm, ghi chú và lịch sử trạng thái chăm sóc.
Form `/leads/{lead}/edit` cập nhật thông tin và chấm lại điểm; dashboard có thống kê
năm trạng thái và giữ bộ lọc khi quay lại danh sách.
Chưa triển khai AI, machine learning hoặc REST API.
Không có giao diện đăng nhập, thanh toán, Redis hoặc tiến trình queue.

## Yêu cầu

- Docker Desktop đang chạy với Linux containers.
- Node.js 22.12+ hoặc 24 và npm trên Windows.
- Git để quản lý mã nguồn.

PHP và Composer chạy trong container. Không cần AMPPS hoặc PHP/Composer
trên Windows. Container ứng dụng dùng PHP 8.4; Composer yêu cầu Laravel
`^12.0`, không tự chuyển lên Laravel 13.

## Cài đặt lần đầu

Mở PowerShell tại thư mục dự án:

```powershell
cd "D:\Pet Lawn Marketing MVP"
if (!(Test-Path .env)) { Copy-Item .env.example .env }
```

Chỉnh các giá trị sau trong `.env` trước khi chạy Docker:

- `DB_DATABASE`: tên database, ví dụ `pet_lawn_marketing`.
- `DB_USERNAME`: tài khoản ứng dụng riêng, ví dụ `pet_lawn`; không dùng `root`.
- `DB_PASSWORD`: mật khẩu riêng cho tài khoản ứng dụng.
- `DB_ROOT_PASSWORD`: mật khẩu khác dành cho tài khoản quản trị MySQL.

Giữ `DB_HOST=mysql`, `DB_PORT=3306` và `DB_CONNECTION=mysql`.
`.env.example` chỉ chứa giá trị mẫu; `.env` và các bản sao môi trường
đã được loại khỏi Git và Docker build context. Không commit mật khẩu hoặc APP_KEY.
Với thư mục đã được thiết lập sẵn, giữ nguyên `.env` hiện có.

```powershell
docker compose build app
docker compose run --rm --no-deps app composer install
docker compose run --rm --no-deps app php artisan key:generate
npm ci
npm run build
docker compose up -d
docker compose exec app php artisan migrate
docker compose exec app php artisan db:seed
```

Mở [http://localhost:8000](http://localhost:8000).
Endpoint kiểm tra Laravel: [http://localhost:8000/up](http://localhost:8000/up).

## Chạy lại dự án đã cài đặt

```powershell
docker compose up -d
```

Khi chỉnh CSS hoặc JavaScript, chạy `npm run dev` ở terminal riêng để dùng
Vite, hoặc chạy lại `npm run build` để cập nhật tài nguyên tĩnh.

## Kiểm tra

```powershell
docker compose exec app php --version
docker compose exec app composer --version
docker compose exec app php artisan --version
docker compose exec app composer check-platform-reqs
docker compose exec app php artisan test
docker compose exec app php artisan db:table leads --json
npm run build
```

Bộ kiểm thử mặc định của Laravel được giữ nguyên, dùng cấu hình test độc lập
với database MySQL của ứng dụng. Session và cache dùng file; queue dùng
`sync`, không cần worker.

## Dữ liệu Lead — bước 2

Migration `2026_09_23_124015_create_leads_table` tạo bảng `leads`.
Các migration mặc định của Laravel vẫn được giữ nguyên.
`DatabaseSeeder` gọi `LeadSeeder`, không tạo tài khoản người dùng mẫu.

Chạy migration và seeder trên môi trường đã cài đặt:

```powershell
docker compose exec app php artisan migrate
docker compose exec app php artisan db:seed
```

Hoặc chỉ chạy seeder Lead:

```powershell
docker compose exec app php artisan db:seed --class=LeadSeeder
```

10 bản ghi mẫu dùng tên và địa điểm Việt Nam, ngân sách VND, email giả
thuộc `example.com`; có chó, mèo, cả ba mức quan tâm và bốn nguồn marketing.
Seeder nhận diện dữ liệu mẫu bằng `contact`; chạy lại không nhân đôi hay
ghi đè thông tin đầu vào hoặc status của bản ghi mẫu còn giữ nguyên contact.
Mỗi lần chạy sẽ tính lại `score` và `segment` qua `LeadScoringService`.
Không xóa các lead khác.

`score = 0`, `segment = COLD`, `status = new` là mặc định ở cả model và
database. Khi tạo qua Eloquent, score và segment được tính trước khi insert.
Model cast `budget` và `score` thành integer.
`interest_level` và `status` là cột string, chưa có ràng buộc danh sách giá trị
ở database. Các giá trị dự kiến là `low / medium / high` và
`new / contacted / qualified / converted / lost`.

Kiểm tra số lượng lead (kết quả là `10` trên database chỉ có dữ liệu mẫu):

```powershell
docker compose exec app php artisan tinker --execute="dump(App\Models\Lead::count());"
```

Kiểm tra schema:

```powershell
docker compose exec app php artisan db:table leads --json
```

Image PHP hiện chưa có extension `intl`; lệnh `db:table` dạng bảng cần
extension này, còn `--json` hoạt động bình thường. Điều này không ảnh hưởng
đến migration, seeding hoặc kiểm thử Lead.

## Chấm điểm Lead — bước 3

Toàn bộ quy tắc nằm trong `app/Services/LeadScoringService.php`.
`calculate(Lead $lead): array` trả về `score` và `segment`, không sửa model,
không truy vấn hoặc ghi database.

| Đặc điểm | Điểm |
| --- | --- |
| Budget >= 5.000.000 VND | +30 |
| Budget >= 2.000.000 và < 5.000.000 VND | +20 |
| Budget < 2.000.000 VND | +10 |
| Interest high / medium / low | +30 / +20 / +10 |
| Pain point có nội dung sau khi trim | +20 |
| Pain point null, rỗng hoặc chỉ có khoảng trắng | +0 |
| Location thuộc danh sách HCM | +20 |
| Location khác | +10 |

Location được trim và chuyển thành chữ thường UTF-8 rồi so sánh toàn bộ giá trị
với sáu tên: `HCM`, `Ho Chi Minh`, `Ho Chi Minh City`, `TP.HCM`, `TP HCM`,
`Thành phố Hồ Chí Minh`. Không suy đoán địa điểm từ một chuỗi địa chỉ dài hơn.
Interest cũng được trim/chuyển chữ thường; giá trị ngoài `low/medium/high`
sẽ gây `InvalidArgumentException` để tránh âm thầm chấm điểm sai.

Tổng điểm hợp lệ từ **30 đến 100**. Điểm **>= 80 → HOT**,
**50–79 → WARM**, **< 50 → COLD**. Đây là điểm ưu tiên theo quy tắc,
chưa phải xác suất khách hàng sẽ mua.

Model Lead có event `creating` gọi service để điền hai giá trị trước lần
insert đầu tiên. Cách này bảo đảm `Lead::create()` và `new Lead(...)->save()`
đều tự chấm điểm, không cần lặp công thức trong controller sau này. Event
không gọi `save()` nên không có lần ghi thứ hai hay vòng lặp event.

Với lead đã tồn tại, sau khi thay đổi thông tin, gọi service và lưu chủ động:

```php
$result = app(\App\Services\LeadScoringService::class)->calculate($lead);
$lead->fill($result)->save();
```

Lệnh query builder như `DB::table(...)->insert(...)`, các thao tác bulk và
`saveQuietly()`/`withoutEvents()` không chạy event tạo model; khi dùng chúng
cần gọi service chủ động. `DatabaseSeeder` đang tắt model events, vì vậy
`LeadSeeder` luôn gọi cùng service trước khi lưu, không hardcode điểm.

Đã chuẩn hóa ba địa điểm HCM trong seed về tên thành phố được hỗ trợ và để
Hoàng Đức Nam chưa có pain point. Sau khi seed vào database trống, kết quả:

| Segment | Số lead |
| --- | --- |
| HOT | 2 |
| WARM | 7 |
| COLD | 1 |

Ví dụ từ dữ liệu mẫu, theo thứ tự budget + interest + pain point + location:

- Nguyễn Minh Anh: 10 + 30 + 20 + 20 = **80 → HOT**.
- Trần Quốc Huy: 10 + 20 + 20 + 10 = **60 → WARM**.
- Hoàng Đức Nam: 10 + 10 + 0 + 10 = **30 → COLD**.

Kiểm thử gồm các ngưỡng 50/80, điểm tối đa 100, biên ngân sách, sáu tên HCM
không phân biệt hoa thường, pain point rỗng, tính thuần túy của service,
tự chấm điểm khi tạo và tính lại điểm qua seeder.

```powershell
docker compose exec app php artisan test
docker compose exec app php artisan tinker --execute="dump(App\Models\Lead::select('segment')->selectRaw('COUNT(*) as total')->groupBy('segment')->get()->toArray());"
```

## Landing page và tiếp nhận Lead — bước 4

Mở [http://localhost:8000](http://localhost:8000).
Trang sử dụng Blade, CSS responsive, JavaScript nhỏ và Vite hiện có.
Không cần migration mới hoặc package mới cho bước này.

| Thành phần | Tệp / vị trí |
| --- | --- |
| Landing page, form và thông báo | `resources/views/landing.blade.php` |
| CSS, JavaScript | `resources/css/app.css`, `resources/js/app.js` |
| Routes web | `routes/web.php` |
| Hiển thị trang, tạo lead, redirect | `app/Http/Controllers/LeadController.php` |
| Validation phía server | `app/Http/Requests/StoreLeadRequest.php` |
| Chấm điểm | `app/Services/LeadScoringService.php`, được gọi từ event `creating` của Lead |
| Ghi MySQL | `Lead::create($attributes)` trong `LeadController::store()` |
| Kiểm thử luồng form | `tests/Feature/LeadCaptureTest.php` |

Routes:

- `GET /` → `LeadController::create()`; tên route `home`.
- `POST /leads` → `LeadController::store()`; tên route `leads.store`.

Luồng xử lý:

1. Khách điền form và gửi POST kèm CSRF token.
2. `StoreLeadRequest` kiểm tra dữ liệu trước khi controller chạy.
3. Controller chỉ lấy `$request->validated()`, chuẩn hóa pet type về chữ thường
   để thống nhất với dữ liệu mẫu, rồi gọi `Lead::create()`.
4. Event `creating` hiện có gọi `LeadScoringService` đúng một lần để gán điểm và
   phân khúc trước khi insert. Controller không chứa công thức tính điểm.
5. Sau khi lưu MySQL thành công, redirect về `/#lead-form` và hiển thị lời cảm ơn.

Điểm và phân khúc không xuất hiện trong form hoặc thông báo thành công.
Các trường `score`, `segment`, `status` do người dùng tự gửi thêm không được
đưa vào dữ liệu lưu. Status mới vẫn là `new`.

| Trường | Validation |
| --- | --- |
| name | required, string, tối đa 255 ký tự |
| contact | nullable, string, tối đa 255 ký tự; nhận số điện thoại hoặc email |
| pet_type | required, string, một trong Dog / Cat / Other |
| location | required, string, tối đa 255 ký tự |
| budget | required, integer, từ 0 đến 4.294.967.295 |
| pain_point | nullable, string, tối đa 2.000 ký tự |
| interest_level | required, string, một trong low / medium / high |
| source | nullable, string, tối đa 100 ký tự |

Giới hạn trên của budget khớp kiểu unsigned INT trong MySQL. Giao diện gợi ý
1, 2, 3, 5 và 7 triệu VND. Giá trị pet type lưu trong database là
`dog / cat / other`; nhãn hiển thị là `Chó / Mèo / Khác`.

Form dùng `novalidate` để hiển thị thông báo tiếng Việt từ backend một cách
nhất quán. Khi có lỗi, Laravel giữ old input trong session; Blade hiển thị lại
dữ liệu với escaping, cùng lỗi ở đầu form và từng trường. JavaScript chỉ đưa
focus đến thông báo và ngăn nhấn nút gửi liên tiếp trong lúc đang gửi.

Chạy và kiểm tra:

```powershell
docker compose up -d
npm run build
docker compose exec app php artisan test
```

Đã kiểm tra 60 tests / 235 assertions, Laravel Pint và Vite build. Feature test
thay service bằng mock với kết quả riêng để chứng minh request thực sự dùng
`LeadScoringService` đúng một lần, thay vì lặp lại công thức trong controller.
Đã thử HTTP thực với CSRF/session, redirect, MySQL, thông báo thành công và
validation lỗi. Bản ghi QA tạm đã được xóa riêng; 10 lead mẫu được giữ nguyên.

Kiểm tra trực tiếp trên trình duyệt:

1. Mở trang ở kích thước desktop và điện thoại, bấm **Nhận tư vấn**.
2. Nhập dữ liệu thử, chọn ngân sách 5.000.000 VND, mức quan tâm Cao,
   location HCM và pain point có nội dung; gửi form.
3. Xác nhận lời cảm ơn xuất hiện và không có điểm/phân khúc trên trang.
4. Kiểm tra bản ghi mới nhất trong MySQL:

```powershell
docker compose exec app php artisan tinker --execute="dump(App\Models\Lead::latest('id')->first(['id', 'name', 'score', 'segment', 'status']));"
```

5. Gửi form thiếu họ tên hoặc địa điểm; kiểm tra lỗi tiếng Việt và dữ liệu cũ.

Đã chụp và kiểm tra giao diện desktop bằng Edge headless trong lần cập nhật
README. Xem ảnh [landing page](images/landing-page.png) và
[form tư vấn](images/lead-form.png). Phần quan sát trên điện thoại chưa được xác minh.

## Dashboard và danh sách Lead — bước 5

Mở [http://localhost:8000/dashboard](http://localhost:8000/dashboard).
Route `GET /dashboard`, tên `dashboard`, gọi `DashboardController::index()`.
Trang dùng Blade và CSS hiện có; không cần package hoặc migration mới.

Chức năng:

- Bốn ô thống kê **Tổng số lead / HOT / WARM / COLD** trên toàn bộ database.
  Các bộ lọc chỉ tác động đến danh sách, không thay đổi các ô thống kê.
- Danh sách tên, liên hệ, thú cưng, địa điểm, ngân sách VND, mức quan tâm,
  nguồn marketing, điểm, phân nhóm, trạng thái và ngày tạo theo giờ Việt Nam.
- Tìm kiếm một phần tên, liên hệ hoặc địa điểm. Ký tự `%`, `_`, `!` được
  tìm như văn bản thông thường; giá trị tìm kiếm được truyền bằng tham số SQL.
- Lọc kết hợp theo phân nhóm, trạng thái và nguồn marketing.
  Danh sách nguồn lấy từ dữ liệu hiện có, không cần sửa code khi có nguồn mới.
- Mặc định sắp xếp mới nhất; có tùy chọn điểm cao nhất. Thời gian tạo và ID
  dùng để giữ thứ tự ổn định khi có cùng điểm.
- Phân trang 10 lead/trang, giữ bộ lọc và thứ tự khi chuyển trang.
- Nút xóa bộ lọc, thông báo khi chưa có dữ liệu hoặc không có kết quả.
- Giao diện điều chỉnh theo màn hình; bảng cuộn ngang trên màn hình nhỏ.

Các query parameter: `q`, `segment`, `status`, `source`, `sort`, `page`.
`DashboardRequest` kiểm tra độ dài, kiểu dữ liệu và danh sách lựa chọn hợp lệ.
Tham số không hợp lệ đưa về `/dashboard` với thông báo tiếng Việt,
tránh lặp redirect về URL lỗi. Thay bộ lọc bằng form sẽ trở về trang 1.

Dashboard chỉ đọc `score` và `segment` đã lưu; không tính lại điểm hoặc
ghi dữ liệu khi mở trang. Luồng chấm điểm tập trung trong service của bước 3
vẫn được dùng khi khách gửi form. Bước 5 hiển thị và lọc trạng thái;
bước 6 bên dưới bổ sung trang chi tiết và thao tác cập nhật.

| Thành phần | Tệp |
| --- | --- |
| Truy vấn và thống kê | `app/Http/Controllers/DashboardController.php` |
| Kiểm tra bộ lọc | `app/Http/Requests/DashboardRequest.php` |
| Giao diện | `resources/views/dashboard.blade.php` |
| CSS responsive | `resources/css/app.css` |
| Route dashboard | `routes/web.php` |
| Kiểm thử | `tests/Feature/DashboardTest.php` |

Chạy trên dự án đã được thiết lập:

```powershell
docker compose up -d
npm run build
docker compose exec app php artisan test
```

Không cần chạy `migrate:fresh` hoặc seed lại để sử dụng dashboard.
Database vừa seed có 10 lead: **HOT = 2, WARM = 7, COLD = 1**.

Tại thời điểm hoàn thành bước 5: **88 tests / 358 assertions**, gồm 28 trường hợp dashboard;
Laravel Pint và Vite build đều đạt. Các test dashboard kiểm tra thống kê,
tìm kiếm, lọc kết hợp, năm trạng thái, phân trang, thứ tự, trường nullable,
HTML escaping, đầu vào không hợp lệ và việc không thay đổi dữ liệu khi xem.
Đã đối chiếu các trang/bộ lọc qua HTTP với MySQL thực; không xóa hoặc seed lại dữ liệu.
Đã chụp và kiểm tra giao diện desktop: [dashboard](images/dashboard.png) và
[bộ lọc HOT](images/dashboard-hot.png). Phần quan sát trên điện thoại chưa được xác minh.

Demo nhanh: mở dashboard, lọc HOT để thấy 2 lead, tìm `minh.anh@example.com`,
sau đó xóa bộ lọc và chọn sắp xếp điểm cao nhất. Khi gửi thêm một lead từ
landing page, tải lại dashboard để xem dữ liệu mới.

Dashboard chưa có đăng nhập theo phạm vi MVP hiện tại. Dùng cho demo cục bộ
với cổng `127.0.0.1:8000`; cần bảo vệ quyền truy cập trước khi công khai dữ liệu lead.

## Chi tiết Lead, giải thích điểm và trạng thái — bước 6

Từ dashboard, bấm vào tên khách hàng để mở chi tiết. Trang hiển thị liên hệ,
thú cưng, địa điểm, ngân sách, mức quan tâm, nguồn, nhu cầu và thời gian theo giờ
Việt Nam. Thông tin khách hàng được escape bằng Blade; nhu cầu giữ xuống dòng.

Routes mới:

- `GET /leads/{lead}` → `LeadController::show()`, tên `leads.show`.
- `PATCH /leads/{lead}/status` → `LeadController::updateStatus()`, tên `leads.status.update`.

Route model binding tìm Lead theo ID; ID không tồn tại hoặc không phải số trả 404.
Các route thuộc middleware web, dùng CSRF cho form cập nhật. Theo phạm vi MVP,
chưa có authentication; chỉ dùng trên môi trường local hiện có.

`LeadScoringService::explain(Lead $lead)` trả về `score`, `segment`,
`segment_reason` và `breakdown` gồm bốn tiêu chí với `label`, `points`, `reason`.
`calculate()` lấy `score` và `segment` từ kết quả này, giữ nguyên API của bước 3.
Các ngưỡng điểm không thay đổi; service vẫn thuần tính toán và không lưu model.
Blade chỉ trình bày kết quả, không chứa công thức scoring.

Điểm đã lưu được hiển thị riêng. Nếu khác với kết quả tính từ dữ liệu hiện tại,
trang nêu rõ sự khác biệt. GET chi tiết không cập nhật điểm, tránh việc chỉ xem
trang đã gây thay đổi dữ liệu. Không thêm thao tác tính lại điểm ở bước này.

`Lead::STATUS_LABELS` là danh sách chung cho form chi tiết, validation và bộ lọc:

| Giá trị lưu | Nhãn |
| --- | --- |
| new | Mới |
| contacted | Đã liên hệ |
| qualified | Đủ điều kiện |
| converted | Đã chuyển đổi |
| lost | Không thành công |

`UpdateLeadStatusRequest` chỉ cho phép một trong năm giá trị. Controller chỉ gán
`status`, lưu rồi redirect về trang chi tiết với thông báo tiếng Việt. Các trường
gửi thêm như name, budget, score hoặc segment không được đưa vào dữ liệu cập nhật.
Event chấm điểm chỉ chạy khi tạo, nên cập nhật trạng thái không tính lại score.
MVP cho phép chuyển trực tiếp giữa năm trạng thái, chưa có state machine.
Lịch sử được bổ sung trong phần cải tiến bên dưới, cùng hai migration mới.

Tệp chính: `resources/views/leads/show.blade.php`,
`app/Http/Requests/UpdateLeadStatusRequest.php`, `LeadController`,
`LeadScoringService`, `Lead`, `DashboardRequest`, `routes/web.php` và CSS hiện có.
Header dùng chung qua `resources/views/components/dashboard-header.blade.php`.
JS hiện có xử lý focus thông báo và ngăn nhấn gửi liên tiếp cho form trạng thái.

Tại thời điểm hoàn thành bước 6: **114 tests / 607 assertions**, Pint và Vite build đạt.
Unit tests xác nhận breakdown khớp phép tính; feature tests xác nhận đủ năm
trạng thái, input lỗi, HTML escaping, nullable, 404, giữ nguyên các trường khác,
không ghi khi xem chi tiết và không tính lại điểm khi cập nhật trạng thái.
Đã thử POST với `_method=PATCH`, CSRF/session và redirect trên MySQL thực;
thiếu CSRF trả 419. Bản ghi QA đã được xóa riêng, đối chiếu xác nhận 13 lead
có sẵn không đổi.

Ảnh đã kiểm tra: [desktop](images/lead-detail.png) và
[điện thoại](images/lead-detail-mobile.png). Có thể chụp lại riêng hai ảnh
bằng Node.js với Edge/Chrome cài sẵn:

```powershell
node docs/capture-screenshots.mjs http://localhost:8000 lead-detail.png lead-detail-mobile.png
```

Script mặc định dùng lead mẫu ID 1, chỉ đọc dữ liệu; không gửi form.
Có thể chọn lead khác bằng `$env:SCREENSHOT_LEAD_ID = '2'` trước khi chạy.

## Bốn cải tiến trước bước 7

### 1. Chỉnh sửa Lead và chấm lại điểm

- `GET /leads/{lead}/edit` → `LeadController::edit()`, tên `leads.edit`.
- `PUT /leads/{lead}` → `LeadController::update()`, tên `leads.update`.
- `UpdateLeadRequest` kế thừa quy tắc của `StoreLeadRequest`, chỉ đổi nơi redirect
  khi validation lỗi. Form giữ giá trị nhập, hiển thị lỗi tiếng Việt và dùng CSRF.
- Tám trường khách hàng được cập nhật; `score`, `segment`, `status`, ID và thời gian
  không lấy trực tiếp từ request. Ngân sách cho phép số nguyên bất kỳ trong giới hạn
  cột unsigned integer, nên không làm mất ngân sách cũ như 450.000 VND.
- Controller gán dữ liệu hợp lệ, gọi `LeadScoringService::calculate()` rồi lưu một lần.
  Tính lại điểm là thao tác tường minh khi lưu form sửa. Event `creating` vẫn chỉ
  phục vụ lead mới; GET, đổi trạng thái và thêm ghi chú không chấm lại điểm.

Ví dụ đã kiểm tra: ngân sách 450.000, quan tâm medium, có nhu cầu, Hà Nội
→ 10 + 20 + 20 + 10 = **60/WARM**. Đổi riêng ngân sách thành 5.000.000
→ 30 + 20 + 20 + 10 = **80/HOT**. Trạng thái và dữ liệu chăm sóc không đổi.

### 2. Ghi chú và lịch sử trạng thái

Hai migration thêm bảng mới, không sửa dữ liệu trong bảng `leads`:

| Bảng | Các cột |
| --- | --- |
| `lead_notes` | `id` bigint PK, `lead_id` bigint FK, `body` text, `created_at`, `updated_at` |
| `lead_status_histories` | `id` bigint PK, `lead_id` bigint FK, `from_status` varchar(255), `to_status` varchar(255), `created_at`, `updated_at` |

Cả hai FK trỏ đến `leads.id`, cascade khi lead bị xóa; có index `(lead_id, created_at)`.
`Lead` có quan hệ `notes()` và `statusHistories()`; hai model con có `belongsTo(Lead::class)`.

`POST /leads/{lead}/notes` → `LeadController::storeNote()`, tên `leads.notes.store`.
`StoreLeadNoteRequest` yêu cầu nội dung văn bản không rỗng, tối đa 2.000 ký tự.
Controller tạo ghi chú qua quan hệ của lead trong URL, không nhận `lead_id` do form gửi.
Blade escape nội dung và giữ xuống dòng. Ghi chú hiện chỉ hỗ trợ thêm/xem.

`updateStatus()` đọc lại lead bằng `lockForUpdate()` trong transaction, so sánh
trạng thái thật trong database với giá trị mới. Chỉ khi khác nhau mới lưu lead và
thêm dòng lịch sử chứa trạng thái trước/sau. Nhờ cùng transaction, lỗi ghi lịch sử
sẽ rollback cả cập nhật trạng thái; hai cập nhật đồng thời không dùng trạng thái cũ
từ route binding để tạo lịch sử sai. Gửi lại cùng trạng thái không tạo dòng trùng.

Ghi chú và lịch sử hiển thị mới nhất trước (`created_at`, rồi `id`), mỗi phần 5 dòng/trang,
dùng `notes_page` và `history_page`. Thời gian hiển thị theo giờ Việt Nam.
Chỉ ghi nhận lịch sử từ các lần cập nhật qua luồng này; không suy dựng các lần đổi
trạng thái trước đây hoặc tự theo dõi câu lệnh SQL/cập nhật model ở bên ngoài controller.
Chưa có người thao tác vì dự án chưa triển khai authentication.

### 3. Thống kê tiến độ chăm sóc

`DashboardController` nhóm và đếm theo `status`, điền 0 cho trạng thái chưa có lead.
Năm ô thống kê dùng chung `Lead::STATUS_LABELS`; luôn đếm toàn bộ dữ liệu,
độc lập với bộ lọc. Bấm một ô sẽ lọc trạng thái đó, giữ tìm kiếm, nguồn, phân nhóm,
sắp xếp hiện tại và quay về trang đầu.

### 4. Giữ ngữ cảnh danh sách

Dashboard truyền `back[q]`, `back[segment]`, `back[status]`, `back[source]`,
`back[sort]`, `back[page]` vào liên kết chi tiết. Liên kết sửa, các form, redirect
thành công/validation lỗi và nút quay lại tiếp tục truyền cùng ngữ cảnh.

`DashboardContext::fromRequest()` chỉ giữ tham số hợp lệ theo `DashboardRequest`.
Không nhận URL redirect tùy ý; ngữ cảnh lỗi sẽ về dashboard mặc định. Cách dùng
query string không ghi đè bộ lọc của tab khác bằng một biến session chung.
Nếu lead vừa đổi phân nhóm/trạng thái làm trang cuối không còn kết quả,
dashboard redirect về trang hợp lệ cuối cùng, giữ nguyên bộ lọc.

### Chạy và kiểm chứng

Trên dự án đã có dữ liệu:

```powershell
docker compose up -d
docker compose exec app php artisan migrate
npm run build
docker compose exec app php artisan test
docker compose exec app vendor/bin/pint --test
```

Không chạy `migrate:fresh` hoặc seed lại cho cải tiến này. Hai migration đã chạy
trên MySQL local, giữ nguyên 13 lead hiện có (5 HOT, 7 WARM, 1 COLD).

Kết quả: **150 tests / 883 assertions**, Pint và Vite build đạt. Sau khi chỉnh
bố cục trang chi tiết, chạy lại 56 test liên quan cũng đạt (464 assertions).
Ba tệp test mới: `LeadEditingTest`, `LeadCareTest`, `DashboardWorkflowTest`.
Phạm vi gồm validation, gọi đúng service, bảo toàn trạng thái/ghi chú khi sửa,
HTML escaping, phân trang ghi chú, lịch sử đúng thứ tự, rollback, thống kê,
giữ bộ lọc qua mọi form và xử lý trang cuối trống.

Đã kiểm tra HTTP thực với CSRF/session và MySQL: sửa 60/WARM → 80/HOT,
thêm ghi chú, Mới → Đã liên hệ → Đủ điều kiện; lưu lặp không thêm lịch sử,
form lỗi không đổi database, thiếu CSRF trả 419. Lead QA riêng được xóa sau khi
chụp ảnh; đối chiếu SHA-256 của dữ liệu theo ID xác nhận 13 lead gốc không đổi.

Ảnh đã xem: dashboard desktop, chi tiết/chỉnh sửa desktop 1440px và điện thoại 390px,
cùng phần [ghi chú và lịch sử](images/lead-care.png). Chụp lại bằng script hiện có:

```powershell
# Chọn ID đang tồn tại nếu muốn chụp một lead cụ thể.
$env:SCREENSHOT_LEAD_ID = '1'
node docs/capture-screenshots.mjs http://localhost:8000 dashboard.png dashboard-hot.png lead-detail.png lead-detail-mobile.png lead-edit.png lead-edit-mobile.png lead-care.png
```

Ảnh chi tiết/chỉnh sửa/ghi chú trong README sử dụng lead demo riêng đã hoàn tất
luồng chăm sóc. Dữ liệu này không được thêm vào seeder hoặc giữ lại sau QA.
Bước 7 chưa được triển khai.

## Dừng và xem log

```powershell
docker compose logs --tail=50 app
docker compose down
```

Dữ liệu MySQL và dependency PHP được lưu trong hai volume của riêng dự án.
Lệnh Composer/Artisan cần chạy qua Docker; `vendor` nằm trong volume Docker.
MySQL chỉ được truy cập từ mạng Docker, không mở cổng database trên Windows.

Cấu hình này dành cho phát triển và trình diễn cục bộ, sử dụng
`php artisan serve` và chỉ mở cổng ứng dụng trên `127.0.0.1:8000`.
Chưa phải cấu hình triển khai production.
