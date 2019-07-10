<?php
namespace app\admin\controller\windows;

use app\common\controller\Backend;
use app\admin\controller\Common;

/**
 *
 * @desc查体窗口
 *
 * @icon fa fa-circle-o
 */
class Perspective extends Backend
{

    protected $model = null;

    protected $orderde = null;

    protected $user = null;

    protected $admin = null;

    protected $inspect = null;

    protected $type = 3;

    // 开关权限开启
    protected $noNeedRight = [
        '*'
    ];

    public function _initialize()
    {
        $comm = new Common();
        $this->comm = $comm;
        parent::_initialize();
        $this->inspect = model("Inspect");
        $this->orderde = model("OrderDetail");
        $this->model = model("Order");
        $this->user = model("PhysicalUsers");
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
                $where['order_serial_number'] = $order_id;
                $where['bs_id'] = $this->busId;
                $user = db("physical_users")->where($where)->find();
                if (! $user) {
                    $this->error("用户不存在");
                }

                // 修改用户是否采血
                $this->orderde->update([
                    'status' => '1'
                ], [
                    'order_serial_number' => $order_id,
                    'physical' => $this->type,
                    'odbs_id' =>$this->busId
                ]);

                $user['employee'] = $user['employee'];
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
        $params = $this->request->post();
        $username = $this->admin->get([
            'id' => $this->auth->id
        ]);
        $status = 0;
        if ($params) {
            $where['type'] = $this->type;
            $where['parent'] = 0;
            $inspectInfo = $this->inspect->where($where)->select();
            foreach ($inspectInfo as $row) {
                if (! empty($params['result'])) {
                    foreach ($params['result'] as $rs) {
                        $sql = "select id,name from fa_inspect where
                        id=(select parent from fa_inspect where id = $rs)  limit 1";
                        $ins = db()->query($sql);
                        if ($ins[0]['id'] == $row['id']) {

                            $where = [
                                'physical' => $this->type,
                                'order_serial_number' => $params["order_serial_number"],
                                'item' => $ins[0]['id'],
                                'odbs_id' =>$this->busId
                            ];
                            $list = [
                                "physical_result" => 1,
                                "physical_result_ext" => $rs,
                                "status" => 1,
                                "doctor" => $username['nickname']
                            ];
                            $update = $this->orderde->where($where)->update($list);
                            if (! $update) {
                                $status ++;
                            }
                        } else {
                            $where = [
                                'physical' => $this->type,
                                'order_serial_number' => $params["order_serial_number"],
                                'item' => $row['id'],
                                'odbs_id' =>$this->busId
                            ];
                            $list = [
                                "physical_result" => 0,
                                "physical_result_ext" => 0,
                                "status" => 1,
                                "doctor" => $username['nickname']
                            ];
                            $update = $this->orderde->where($where)->update($list);
                            if (! $update) {
                                $status ++;
                            }
                        }
                    }
                } else {
                    $where = [
                        'physical' => $this->type,
                        'order_serial_number' => $params["order_serial_number"],
                        'item' => $row['id'],
                        'odbs_id' =>$this->busId
                    ];
                    $list = [
                        "physical_result" => 0,
                        "physical_result_ext" => 0,
                        "status" => 1,
                        "doctor" => $username['nickname']
                    ];
                    $update = $this->orderde->where($where)->update($list);
                    if (! $update) {
                        $status ++;
                    }
                }
            }
        }        
        $this->comm->check_resultstatus($params["order_serial_number"]);
        if ($status == 0) {
            $this->success('保存成功', "index", '', 1);
        } else {
            $this->error('', 'index');
        }
    }
}