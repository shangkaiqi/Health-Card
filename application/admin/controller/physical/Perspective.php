<?php
namespace app\admin\controller\physical;

use app\common\controller\Backend;

/**
 * 透视检查
 *
 * @icon fa fa-circle-o
 */
class Perspective extends Backend
{

    protected $model = null;

    protected $user = null;

    // 开关权限开启
    protected $noNeedRight = [
        'index'
    ];

    public function _initialize()
    {
        parent::_initialize();
        // $this->user = model("PhysicalUsers");
        $this->model = model("Order");

        // 操作人
        $where = [
            'id' => $this->auth->id
        ];
        $operate = db('admin')->where($where)
            ->field('nickname')
            ->find();
        $this->view->assign("operate", $operate);
    }

    public function index()
    {
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $uid = db("physical_users")->field("user_id")
                    ->where($params)
                    ->find();
                if (! user_id) {
                    $this->error("用户不存在");
                }
                $where = [
                    "user_id" => $uid["user_id"],
                    'physical' => 3
                ];
                $result = db("order")->alias("o")
                    ->join("order_detail od", "o.order_serial_number = od.order_serial_number")
                    ->where($where)
                    ->select();
                $this->view->assign("perspective", $result);
                $this->success();
            }
            $this->error();
        }
        return $this->view->fetch();
    }
}