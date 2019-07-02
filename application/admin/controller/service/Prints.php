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
                $uid = db("physical_users")->where('order_serial_number', "=", date("Ymd", time()) . $params['search'])->find();
                // if (! $uid) {
                // $this->error("用户不存在");
                // }
                $where = [
                    "user_id" => $uid["id"]
                ];
                $result = db("order")->alias("o")
                    ->join("order_detail od", "o.order_serial_number = od.order_serial_number")
                    ->where($where)
                    ->select();
                $uid['employee'] = $this->comm->getEmpName($uid['employee']);
                $this->view->assign("body", $uid);
                return $this->view->fetch("search");
            } else {
                $this->error();
            }
        }
        return $this->view->fetch();
    }
}