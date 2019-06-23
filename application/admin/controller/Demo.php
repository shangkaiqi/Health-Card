<?php
namespace app\admin\controller;

use app\common\controller\Backend;

/**
 * 透视检查
 *
 * @icon fa fa-circle-o
 */
class Demo extends Backend
{

    protected $model = null;

    protected $order = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = model("PhysicalUsers");

        $this->order = model("Order");
    }

    public function index()
    {
        $orderNum = db('physical_users')->field("order_serial_number")
            ->where("order_serial_number", "like", date("Ymd", time()) . "%")
            ->order("registertime desc")
            ->find();
        if ($orderNum) {
            $resultNum = $orderNum['order_serial_number'] + 1;
        } else {
            $resultNum = date("Ymd", time()) . "0001";
        }
        $params['name'] = "施e立辉";
        $params['identitycard'] = "130555555858d85854";
        $params['type'] = "1";
        $params['sex'] = "1";
        $params['age'] = "28";
        $params['phone'] = "18932900685";
        $params['employee'] = "dddd";
        $params['company'] = "sss";
        $result = $this->model->save($params);
        // 获取医院唯一标识
        $bs_id = db("admin")->alias("a")
            ->field("b.bs_uuid,b.charge")
            ->join("business b", "a.businessid = b.bs_id")
            ->where("id", "=", $this->auth->id)
            ->find();

        if ($result) {
            $par['user_id'] = $this->model->id;
            $par['order_serial_number'] = $resultNum;
            $par['bus_number'] = $bs_id['bs_uuid'];
            $par['charge'] = $bs_id['charge'];
            $par['order_status'] = '0';
            $par['obtain_employ_type'] = $params['employee'];
            $par['createdate'] = time();
            $this->order->save($par);
        }
    }
}