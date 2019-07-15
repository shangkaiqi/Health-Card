<?php

namespace app\admin\behavior;

class AdminLog
{
    public function run(&$params)
    {
        if (request()->isPost()) {
            \app\index\model\AdminLog::record();
        }
    }
}
