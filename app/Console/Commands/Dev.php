<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use Illuminate\Console\Command;


class Dev extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:dev';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $company = Company::find(1);

        $defaultRoles = ['公司管理员', '施工经理', '施工人员', '查勘经理', '查勘人员', '财务经理', '财务人员', '调度内勤', '出纳人员', '造价员', '复勘人员'];

        foreach ($defaultRoles as $defaultRole) {
            $role = $company->roles()->create([
                'name' => $company->id . '_' . $defaultRole,
                'guard_name' => 'api',
                'show_name' => $defaultRole
            ]);

            $role->givePermissionTo(Role::where('company_id', 0)->where('show_name', $defaultRole)->first()?->permissions?->pluck('name')->toArray());
        }

        $user = User::find($company->admin_id);

        $user->assignRole($company->id . '_公司管理员');

        $user->save();
    }
}
