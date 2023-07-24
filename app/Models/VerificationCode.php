<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VerificationCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'phone_number', 'code', 'getaway', 'expiration_date', 'verified'
    ];

    protected $casts = [
        'verified' => 'boolean'
    ];

    /**
     * 验证码是否有效
     *
     * @param $phone_number
     * @param $code
     * @return boolean
     */
    public static function verify($phone_number, $code): bool
    {
        return VerificationCode::where('phone_number', $phone_number)
            ->where('code', $code)
            ->where('verified', false)
            ->where('expiration_date', '>', now())
            ->exists();
    }
}
