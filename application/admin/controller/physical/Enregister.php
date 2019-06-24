<?php
namespace app\admin\controller\physical;

use app\common\controller\Backend;
use app\admin\controller\Common;

/**
 * 体检登记
 *
 * @icon fa fa-circle-o
 */
class Enregister extends Backend
{

    protected $multiFields = 'switch';

    protected $model = null;

    protected $order = null;

    // 开关权限开启
    protected $noNeedRight = [
        'index'
    ];

    /**
     * Register模型对象
     *
     * @var \app\admin\model\business\Register
     */
    public function _initialize()
    {
        parent::_initialize();
        $this->model = model("PhysicalUsers");
        $this->order = model("Order");
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
            file_put_contents("register-params.txt", print_r($params, true));
            if ($params) {
                // 获取订单最后一条id
                // $orderId = $this->model->order('registertime', 'desc')->find();
                $ordernum = $result = db('physical_users')->field("order_serial_number")
                    ->where("order_serial_number", "like", date("Ymd", time()) . "%")
                    ->order("registertime desc")
                    ->find();
                if ($orderNum) {
                    $resultNum = $orderNum['order_serial_number'] + 1;
                } else {
                    $resultNum = date("Ymd", time()) . "0001";
                }

                $param['name'] = $params['name'];
                $param['identitycard'] = $params['identitycard'];
                $param['type'] = $params['type'];
                $param['sex'] = $params['sex'];
                $param['age'] = $params['age'];
                $param['phone'] = $params['phone'];
                $param['employee'] = json_encode(array(
                    $params['parent'],
                    $params['son']
                ));
                $param['company'] = $params['company'];
                $param['order_serial_number'] = $resultNum;
                // $params['bsid'] = $this->auth->id;
                // $result = $this->model->validate("Enregister.add")->save($params);
                $result = $this->model->save($param);

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
                $par['obtain_employ_type'] = $params['employee'];
                $par['obtain_employ_number'] = '';
                $order = $this->order->save($par);
                if (! $order) {
                    $this->error($this->model->getError());
                }
                $this->success("登记成功", "Enregister/index");
            }
            $this->error();
        }

        return $this->view->fetch();
    }
}