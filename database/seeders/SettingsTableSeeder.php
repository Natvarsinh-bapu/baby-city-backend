<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Setting;

class SettingsTableSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            'address' => '123, Test, Test, 123456',
            'mobile' => '9999999999',
            'email' => 'babycity@example.com',
            'facebook' => 'https://www.facebook.com',
            'instagram' => 'https://www.instagram.com',
            'whatsapp' => 'https://www.whatsapp.com',
            'youtube' => 'https://www.youtube.com',
            'telegram' => 'https://www.telegram.com',
        ];

        foreach ($settings as $key => $value) {
            Setting::updateOrCreate(
                ['key' => $key],
                [
                    'value' => $value,
                    'other_data' => null,
                ]
            );
        }
    }
}
