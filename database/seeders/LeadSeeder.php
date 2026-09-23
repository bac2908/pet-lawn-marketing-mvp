<?php

namespace Database\Seeders;

use App\Models\Lead;
use App\Services\LeadScoringService;
use Illuminate\Database\Seeder;

class LeadSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(LeadScoringService $scoringService): void
    {
        // Fictional contacts; sample budgets are whole VND amounts.
        $leads = [
            [
                'name' => 'Nguyễn Minh Anh',
                'contact' => 'minh.anh@example.com',
                'pet_type' => 'dog',
                'location' => 'HCM',
                'budget' => 1200000,
                'pain_point' => 'Nuôi Corgi trong căn hộ, cần thảm cỏ ở ban công để chó đi vệ sinh khi trời mưa.',
                'interest_level' => 'high',
                'source' => 'Facebook',
            ],
            [
                'name' => 'Trần Quốc Huy',
                'contact' => 'quoc.huy@example.com',
                'pet_type' => 'cat',
                'location' => 'Cầu Giấy, Hà Nội',
                'budget' => 450000,
                'pain_point' => 'Muốn có góc cỏ nhỏ cho mèo nằm chơi, cần loại dễ vệ sinh và ít rụng.',
                'interest_level' => 'medium',
                'source' => 'TikTok',
            ],
            [
                'name' => 'Lê Ngọc Mai',
                'contact' => 'ngoc.mai@example.com',
                'pet_type' => 'dog',
                'location' => 'Hải Châu, Đà Nẵng',
                'budget' => 1800000,
                'pain_point' => 'Sân nhỏ thường bị lầy sau mưa, cần khu vực sạch cho hai chú chó vận động.',
                'interest_level' => 'high',
                'source' => 'Google',
            ],
            [
                'name' => 'Phạm Thanh Tùng',
                'contact' => 'thanh.tung@example.com',
                'pet_type' => 'cat',
                'location' => 'TP.HCM',
                'budget' => 250000,
                'pain_point' => 'Đang tìm hiểu thảm cỏ cho mèo nhưng chưa chắc mèo sẽ sử dụng.',
                'interest_level' => 'low',
                'source' => 'Facebook',
            ],
            [
                'name' => 'Võ Thu Hà',
                'contact' => 'thu.ha@example.com',
                'pet_type' => 'dog',
                'location' => 'Ninh Kiều, Cần Thơ',
                'budget' => 750000,
                'pain_point' => 'Chó con đang tập đi vệ sinh, muốn thử khay cỏ thay cho miếng lót dùng một lần.',
                'interest_level' => 'medium',
                'source' => 'Referral',
            ],
            [
                'name' => 'Đặng Bảo Long',
                'contact' => 'bao.long@example.com',
                'pet_type' => 'dog',
                'location' => 'Lê Chân, Hải Phòng',
                'budget' => 2200000,
                'pain_point' => 'Cần thảm cỏ bền cho chó lớn, ưu tiên thoát nước tốt và dễ làm sạch mỗi ngày.',
                'interest_level' => 'high',
                'source' => 'Google',
            ],
            [
                'name' => 'Bùi Lan Phương',
                'contact' => 'lan.phuong@example.com',
                'pet_type' => 'cat',
                'location' => 'Thành phố Hồ Chí Minh',
                'budget' => 600000,
                'pain_point' => 'Muốn cải tạo một góc ban công có lưới chắn cho mèo, đang so sánh kích thước thảm.',
                'interest_level' => 'medium',
                'source' => 'TikTok',
            ],
            [
                'name' => 'Hoàng Đức Nam',
                'contact' => 'duc.nam@example.com',
                'pet_type' => 'dog',
                'location' => 'Thanh Xuân, Hà Nội',
                'budget' => 350000,
                'pain_point' => null,
                'interest_level' => 'low',
                'source' => 'Facebook',
            ],
            [
                'name' => 'Đỗ Thảo Nhi',
                'contact' => 'thao.nhi@example.com',
                'pet_type' => 'cat',
                'location' => 'Nha Trang, Khánh Hòa',
                'budget' => 900000,
                'pain_point' => 'Bạn bè giới thiệu thảm cỏ, muốn đặt góc chơi cho hai mèo trong tuần này.',
                'interest_level' => 'high',
                'source' => 'Referral',
            ],
            [
                'name' => 'Huỳnh Gia Bảo',
                'contact' => 'gia.bao@example.com',
                'pet_type' => 'dog',
                'location' => 'Biên Hòa, Đồng Nai',
                'budget' => 500000,
                'pain_point' => 'Chưa chuyển sang nhà mới, đang tìm giải pháp khu vệ sinh cho chó để mua sau.',
                'interest_level' => 'low',
                'source' => 'Google',
            ],
        ];

        foreach ($leads as $attributes) {
            // Preserve existing inputs/status and refresh only the derived scoring fields.
            $lead = Lead::firstOrNew(['contact' => $attributes['contact']], $attributes);

            // DatabaseSeeder disables model events, so score explicitly through the same service.
            $lead->fill($scoringService->calculate($lead));
            $lead->save();
        }
    }
}
