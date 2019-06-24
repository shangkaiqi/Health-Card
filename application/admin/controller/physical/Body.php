<?php
namespace app\admin\controller\physical;

use app\common\controller\Backend;
use app\admin\controller\Common;
use think\Db;

/**
 *
 * 身体检查
 *
 * @icon fa fa-circle-o
 */
class Body extends Backend
{

    protected $model = null;

    protected $user = null;

    protected $comm = null;

    // 体检类别
    protected $type = 2;

    // 开关权限开启
    protected $noNeedRight = [
        'index'
    ];

    public function _initialize()
    {
        $comm = new Common();
        $this->comm = $comm;
        parent::_initialize();
        $this->model = model("Order");

        $ins = $comm->inspect($this->type);
        $this->view->assign("inspect", $ins);

        $this->view->assign("pid", $comm->getemployee());
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
                $user = db("physical_users")->where('order_serial_number', "=", date("Ymd", time()) . $params['search'])->find();
                if (! $user) {
                    $this->error("用户不存在");
                }
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
        $params = $this->request->post("row/a");
        if ($params) {}
    }
}