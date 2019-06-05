<?php

namespace app\admin\controller\physical;

use app\common\controller\Backend;

/**
 * 
 *
 * @icon fa fa-circle-o
 */
class Body extends Backend
{
    
    /**
     * Register模型对象
     * @var \app\admin\model\business\Register
     */

    public function _initialize()
    {
        parent::_initialize();

    }

    public function index(){
        echo "Body";
    }
}