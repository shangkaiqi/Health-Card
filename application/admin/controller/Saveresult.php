<?php
namespace app\admin\controller;

use app\common\controller\Backend;

class Saveresult extends Backend
{

    protected $noNeedRight = [
        '*'
    ];

    public function _initialize()
    {
        parent::_initialize();
    }

    public function bodySave(){
        
        $params = $this->request->post("row/a");
        var_dump($params);
        $this->view->fetch();
    }
}