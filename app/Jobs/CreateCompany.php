<?php

namespace App\Jobs;

use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CreateCompany implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected Company $company;

    /**
     * Create a new job instance.
     */
    public function __construct(Company $company)
    {
        $this->company = $company;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $company = $this->company;

        $defaultRoles = ['公司管理员', '施工经理', '施工人员', '查勘经理', '查勘人员', '财务经理', '财务人员', '调度内勤', '出纳人员', '造价员',];

        foreach ($defaultRoles as $defaultRole) {
            $role = $company->roles()->create([
                'name' => $company->id . '_' . $defaultRole,
                'guard_name' => 'api',
                'show_name' => $defaultRole
            ]);

            $role->givePermissionTo(Role::where('name', $defaultRole)->first()?->permissions?->pluck('name'));
        }

        $user = User::find($company->admim_id);

        $user->assignRole($company->id . '_公司管理员');
    }
}
