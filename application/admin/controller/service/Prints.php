<?php
namespace app\admin\controller\service;

use app\common\controller\Backend;
use app\admin\controller\Common;

/**
 * 打印健康证
 *
 * @icon fa fa-circle-o
 */
class Prints extends Backend
{

    /**
     * Register模型对象
     */
    protected $model = null;

    protected $comm = null;

    // 开关权限开启
    protected $noNeedRight = [
        '*'
    ];

    public function _initialize()
    {
        parent::_initialize();
        $comm = new Common();
        $this->comm = $comm;
        $this->model = model("PhysicalUsers");
    }

    public function index()
    {
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $uid = db("physical_users")->where('order_serial_number', "=", $params['search'])->find();
                if (! $uid) {
                    $this->error("用户不存在");
                }       
                
                $uid['employee'] = $this->comm->getEmpName($uid['employee']);
                $this->view->assign("body", $uid);
                
                //获取打印信息
                $where['order_serial_number'] = $params['search'];
                $printInfo = db("order")->where($where)->find();
                
                //获取体检单位
                $hosp = db("business")->field("busisess_name")->where("bs_uuid","=",$printInfo['bus_number'])->find();
                
                $printInfo['name'] = $uid['name'];
                $printInfo['sex'] = $uid['sex']==0?"男":"女";
                $printInfo['employee'] = $uid['employee'];
                $printInfo['images'] = $uid['images'];
                $printInfo['company'] = $hosp['busisess_name'];
                $printInfo['physictype'] = $uid['physictype'];
                $this->view->assign("print",$printInfo);
                
                return $this->view->fetch("search");
            } else {
                $this->error();
            }
        }
        return $this->view->fetch();
    }
}