<?php
namespace app\admin\controller\service;

use app\common\controller\Backend;

/**
 *
 * @icon fa fa-circle-o
 */
class Prints extends Backend
{

    /**
     * Register模型对象
     *
     * @var \app\admin\model\business\Register
     */
    protected $model = null;

    // 开关权限开启
    protected $noNeedRight = [
        '*'
    ];

    public function _initialize()
    {
        parent::_initialize();
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
                $this->view->assign("body", $uid);
                return $this->view->fetch("search");
            } else {
                $this->error();
            }
        }
        return $this->view->fetch();
    }
}