<?php

namespace App\Models;

use App\Models\Traits\DefaultDatetimeFormat;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ApprovalOption extends Model
{
    use HasFactory, DefaultDatetimeFormat;

    protected $fillable = [
        'company_id',
        'type',
        'approve_mode',
        'review_mode',
        'review_conditions',
    ];

    public function approver(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            Approver::class,
            'approval_option_id',
            'user_id',
            'id',
            'id'
        )->withPivot('type');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * @throws \Exception
     */
    public static function findByType($company_id, $type)
    {
        $option = self::where('company_id', $company_id)->where('type', $type)->first();
        if (!$option) throw new \Exception('请先配置审批流程');

        return $option;
    }

}
