<?php
namespace app\admin\model;

use think\Model;
use think\Session;

class Business extends Model
{

    // 开启自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';

    function Admin()
    {
        return $this->hasOne('Admin', 'businessid', 'id');
    }
}