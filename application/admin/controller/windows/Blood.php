<?php
namespace app\admin\controller\windows;

use app\common\controller\Backend;
use app\admin\controller\Common;

/**
 *
 * @desc采血窗口
 *
 * @icon fa fa-circle-o
 */
class Blood extends Backend
{

    protected $model = null;

    protected $orderde = null;

    protected $user = null;

    protected $inspect = null;

    protected $type = 0;

    // 开关权限开启
    protected $noNeedRight = [
        '*'
    ];

    public function _initialize()
    {
        $comm = new Common();
        $this->comm = $comm;
        parent::_initialize();
        $this->model = model("Order");
        $this->orderde = model("OrderDetail");
        $this->user = model("admin");
        $this->inspect = model("Inspect");

        $ins = $comm->inspect($this->type);
        $this->view->assign("inspect", $ins);

        $this->view->assign("pid", $comm->getemployee());
    }

    public function index()
    {
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $order_id = date("Ymd", time()) . $params['search'];
                $user = db("physical_users")->where('order_serial_number', "=", $order_id)->find();
                if (! $user) {
                    $this->error("用户不存在");
                }
                // 修改用户是否采血

                $this->orderde->update([
                    'status' => '1',
                    'physical' => $this->type
                ], [
                    'order_serial_number' => $order_id
                ]);

                $em = json_decode($user['employee'], true);
                $parent = $this->comm->employee($em[0]);
                $son = $this->comm->employee($em[1]);
                $user['employee'] = $parent['name'] . ">>" . $son['name'];
                $where = [
                    "user_id" => $user["id"],
                    'physical' => $this->type
                ];
                $this->view->assign("wait_physical", $this->comm->wait_physical($user['id']));
                $this->view->assign("body", $user);
                return $this->view->fetch("search");
            } else {
                $this->error();
            }
        }
        $this->view->assign("wait_physical", $this->comm->wait_physical());
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

    public function save()
    {
        $params = $this->request->post("rows/a");
        $username = $this->user->get([
            'id' => $this->auth->id
        ]);
        $status = 0;
        if ($params) {
            foreach ($params['phitem'] as $index) {
                $inspectInfo = $this->inspect->get([
                    "id" => $index
                ]);
                $inspectStatus = $this->inspect->get([
                    "id" => $inspectInfo['parent']
                ]);
                // echo $inspectInfo['id'] . "-" . $inspectInfo['name'] . "-" . $inspectInfo['type'] . "-" . $inspectInfo['parent'];
                $where = [
                    'physical' => $this->type,
                    'order_serial_number' => $params['ordernum'],
                    'item' => $index
                ];

                $list = [
                    "physical_result" => 1,
                    "status" => 1,
                    "physical_result" => $inspectStatus['name'],
                    "physical_result_ext" => $inspectInfo['name'],
                    "doctor" => $username['nickname']
                ];
                $update = $this->orderde->where($where)->update($list);
                if (! $update) {
                    $status = 1;
                }
            }
            if ($status) {
                $this->success('', null, $provincelist);
            } else
                $this->error();
        }
    }

}