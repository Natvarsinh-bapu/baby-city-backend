<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'other_data',
    ];

    protected $casts = [
        'other_data' => 'array', // automatically cast JSON to array
    ];

    public static function getValue(string $key, $default = null)
    {
        $setting = self::where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }

    public static function setValue(string $key, $value, array $otherData = [])
    {
        return self::updateOrCreate(
            ['key' => $key],
            [
                'value' => $value,
                'other_data' => $otherData,
            ]
        );
    }
}
