<?php
namespace app\admin\controller;

use app\common\controller\Backend;
use app\admin\controller\Common;

/**
 *
 * @desc体检登记
 *
 * @icon fa fa-circle-o
 */
class Register extends Backend
{

    protected $multiFields = 'switch';

    protected $model = null;

    protected $order = null;

    protected $orderd = null;

    protected $layout = 'register';

    // 开关权限开启
    protected $noNeedRight = [
        'index'
    ];

    /**
     * Register模型对象
     *
     * // * @var \app\admin\model\business\Register
     */
    public function _initialize()
    {
        parent::_initialize();

        $this->model = model("PhysicalUsers");
        $this->order = model("Order");
        $this->orderd = model("OrderDetail");
        $comm = new Common();

        $ins = $comm->inspect();
        $this->view->assign("inspect", $ins);

        $this->view->assign("wait_physical", $comm->wait_physical());
        $this->view->assign("pid", $comm->getemployee());
        // 获取结果检查信息
        $inspect_top = db("inspect")->field("id,name,value")->select();
        $this->view->assign("ins", $inspect_top);
    }

    public function index()
    {
        if ($this->request->isAjax()) {
            // 如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }

            list ($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = db("physical_users")->count("id");

            $userList = db("physical_users")->field("id,name,identitycard,type")->select();

            $result = array(
                "total" => $total,
                "rows" => $userList
            );

            return json($result);
        }
        return $this->view->fetch();
    }

    public function add()
    {
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                // 获取订单最后一条id
                // $orderId = $this->model->order('registertime', 'desc')->find();
                $ordernum = array();
                $ordernum = $result = db('physical_users')->field("order_serial_number")
                    ->where("order_serial_number", "like", date("Ymd", time()) . "%")
                    ->order("registertime desc")
                    ->find();
                if ($ordernum) {
                    $resultNum = $ordernum['order_serial_number'] + 1;
                } else {
                    $resultNum = date("Ymd", time()) . "0001";
                }

                $param['name'] = $params['name'];
                $param['identitycard'] = $params['identitycard'];
                $param['type'] = $params['type'];
                $param['sex'] = $params['sex'];
                $param['age'] = $params['age'];
                $param['phone'] = $params['phone'];
                $param['physictype'] = $params['physictype'];
                $param['express'] = $params['express'];
                $param['employee'] = json_encode(array(
                    $params['parent']
                    // $params['son']
                ));
                $param['company'] = $params['company'];
                $param['order_serial_number'] = $resultNum;
                // $params['bsid'] = $this->auth->id;
                // $result = $this->model->validate("Enregister.add")->save($params);
                $result = $this->model->validate("Register.add")->save($param);

                if (! $result) {
                    $this->error($this->model->getError());
                }
                // 获取医院唯一标识
                $bs_id = db("admin")->alias("a")
                    ->field("b.bs_uuid,b.charge")
                    ->join("business b", "a.businessid = b.bs_id")
                    ->where("id", "=", $this->auth->id)
                    ->find();

                $par['user_id'] = $this->model->id;
                $par['order_serial_number'] = $resultNum;
                $par['bus_number'] = $bs_id['bs_uuid'];
                $par['charge'] = $bs_id['charge'];
                $par['order_status'] = '0';
                $par['obtain_employ_type'] = $param['employee'];
                $par['obtain_employ_number'] = '';
                if ($params['express']) {
                    $par['address'] = $params['address'];
                }
                $order = $this->order->save($par);
                if (! $order) {
                    $this->error($this->model->getError());
                }
                $this->order_detial($resultNum);
                // $this->success("登记成功", "physical/register/index");
                $this->success();
            }
            $this->error();
        }

        return $this->view->fetch();
    }

    // 创建订单详细信息
    public function order_detial($orderNum)
    {
        $ins = db('inspect')->field("id,name,type")
            ->where("parent", "=", "0")
            ->select();
        $list = array();
        foreach ($ins as $res) {
            $param['order_serial_number'] = $orderNum;
            $param['physical'] = $res['type'];
            $param['physical_result'] = '';
            $param['physical_result_ext'] = '';
            $param['doctor'] = '';
            $param['item'] = $res['id'];
            $list[] = $param;
        }

        $this->orderd->saveAll($list);
    }

    public function edit($ids = '')
    {
        $list = $this->model->get([
            'id' => $ids
        ]);
        if ($this->request->isPost()) {
            $params = $this->request->isPost("row/a");
            if ($params) {}
        }
        $this->view->assign("row", $list);
        return $this->view->fetch();
    }

    public function physical_table()
    {}

    public function nav_table()
    {}
}