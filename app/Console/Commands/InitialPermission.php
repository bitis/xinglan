<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;

class InitialPermission extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'Init:Permission';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '初始化权限数据';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {

        $this->role();

        return 0;
    }

    public function permission()
    {
    }

    public function role()
    {
        $roles = [
            '公司管理员',
            '施工经理',
            '施工人员',
            '查勘经理',
            '查勘人员',
            '财务经理',
            '财务人员',
            '调度内勤',
            '出纳人员',
            '造价员',
        ];

        foreach ($roles as $role) {
            Role::create(['name' => $role]);
        }
    }
}
