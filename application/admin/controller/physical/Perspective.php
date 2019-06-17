<?php
namespace app\admin\controller\physical;

use app\common\controller\Backend;
use app\admin\controller\Common;

/**
 * 透视检查
 *
 * @icon fa fa-circle-o
 */
class Perspective extends Backend
{

    protected $model = null;

    protected $user = null;

    // 体检类别
    protected $type = 3;

    // 开关权限开启
    protected $noNeedRight = [
        'index'
    ];

    public function _initialize()
    {
        $comm = new Common();
        parent::_initialize();
        // $this->user = model("PhysicalUsers");
        $this->model = model("Order");

        $ins = $comm->inspect($this->type);
        $this->view->assign("inspect", $ins);

        $this->view->assign("wait_physical", $comm->wait_physical());
        $this->view->assign("pid", $comm->employess());
        // 获取结果检查信息
        $inspect_top = db("inspect")->field("id,name,value")
            ->where('type', '=', $this->type)
            ->select();
        $this->view->assign("ins", $inspect_top);
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
                echo db()->getLastSql();
                $where = [
                    "user_id" => $uid["id"],
                    'physical' => $this->type
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

    /**
     * 获取从业类别
     */
    public function getEmployee()
    {
        $pid = $this->request->get('pid');
        $where['pid'] = [
            '=',
            $type
        ];
        $categorylist = null;
        if ($type !== '') {
            $categorylist = $employee = db("employee")->field("id,pid,name")
                ->where('pid', '=', '0')
                ->select();
        }
        $this->success('', null, $categorylist);
    }

    /**
     *
     * @desc获取体结果选项检项
     */
    public function getInspect()
    {
        // $pid = $this->request->get('pid');
        // $where['pid'] = [
        // '=',
        // $type
        // ];
        $inspect = null;
        // if ($type !== '') {
        $inspect = db("inspect")->field("id,value,name")
            ->where('type', '=', $this->type)
            ->select();
        $ins = array();
        foreach ($inspect as $key => $val) {
            $values = json_decode($inspect[$key]['value'], TRUE);
            $ins[] = array(
                "name" => $val["name"],
                "value" => $values,
                "id" => $val['id']
            );
        }
        $this->view->assign("inspect", $ins);
        return $this->view->fetch("demo");
    }
}