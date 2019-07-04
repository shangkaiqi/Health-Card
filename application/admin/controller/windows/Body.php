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
class Body extends Backend
{

    protected $model = null;

    protected $orderde = null;

    protected $user = null;

    protected $admin = null;

    protected $type = 2;

    protected $inspect = null;

    // 开关权限开启
    protected $noNeedRight = [
        '*'
    ];

    public function _initialize()
    {
        $comm = new Common();
        $this->comm = $comm;
        parent::_initialize();
        $this->orderde = model("OrderDetail");
        $this->model = model("Order");
        $this->user = model("PhysicalUsers");
        $this->inspect = model("Inspect");
        $this->admin = model("Admin");

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
                    'status' => '1'
                ], [
                    'order_serial_number' => $order_id,
                    'physical' => $this->type
                ]);

                $user['employee'] = $this->comm->getEmpName($user['employee']);
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

    /**
     * 保存检查结果
     */
    public function save()
    {
        $params = $this->request->post();
        $username = $this->admin->get([
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

                $sql = "select id,name from fa_inspect where 
                        id=(select parent from fa_inspect where id = (select parent from fa_inspect where id = $index))  limit 1";
                $ins = db()->query($sql);
                $where = [
                    'physical' => $this->type,
                    'order_serial_number' => $params["order_serial_number"],
                    'item' => $ins[0]['id']
                ];

                $phyresult = $inspectStatus['name'] == "正常"?"0":1;
                $phyresult_ext = $phyresult == 0 ? 0:$index; 
                $list = [
                    "physical_result" => 1,
                    "status" => 1,
                    "physical_result" => $phyresult,
                    "physical_result_ext" => $phyresult_ext,
                    "doctor" => $username['nickname']
                ];
                $update = $this->orderde->where($where)->update($list);
                if (! $update) {
                    $status = 1;
                }
            }
            if ($status) {
                $this->success('保存成功', null,'',1);
            } else {
                $this->error();
            }
        }
    }
}