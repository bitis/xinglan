<?php

namespace App\Models;

use App\Models\Traits\DefaultDatetimeFormat;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
         return self::where('company_id', $company_id)->where('type', $type)->first();
    }


    public static function groupByType($approvers)
    {
        $checkers = [];
        $reviewers = [];
        $receivers = [];

        foreach ($approvers as $approver) {
            if ($approver->pivot->type == Approver::TYPE_CHECKER) {
                $checkers[] = ['id' => $approver['id'], 'name' => $approver['name']];
            } elseif ($approver->pivot->type == Approver::TYPE_REVIEWER) {
                $reviewers[] = ['id' => $approver['id'], 'name' => $approver['name']];
            } elseif ($approver->pivot->type == Approver::TYPE_RECEIVER) {
                $receivers[] = ['id' => $approver['id'], 'name' => $approver['name']];
            }
        }

        return [$checkers, $reviewers, $receivers];
    }

    public function approvalExtends(): HasMany
    {
        return $this->hasMany(ApprovalExtend::class, 'approval_option_id');
    }

    public function extends(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            ApprovalExtend::class,
            'approval_option_id',
            'user_id',
            'id',
            'id'
        )->withPivot('type');
    }
}
