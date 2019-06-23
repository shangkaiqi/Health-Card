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

    protected $orderDetail = null;

    // 开关权限开启
    protected $noNeedRight = [
        'index'
    ];

    public function _initialize()
    {
        parent::_initialize();
        // $this->model = model("PhysicalUsers");

        $this->order = model("Order");
        $this->orderDetail = model("OrderDetail");
    }

    public function index()
    {
        // $params['order_serial_number'] = '1';
        // $params['physical'] = '1';
        // $params['physical_result'] = '1';
        // $params['physical_result_ext'] = '1';
        // $params['doctor'] = '1';
        // var_dump($params);
        // $this->orderDetail->save($params);
        // echo db()->getLastSql();
        $uid = 13;
        $list = db('order')->alias('o')
            ->join("order_detail od", "`o`.`order_serial_number` = `od`.`order_serial_number`")
            ->field("physical")
            ->where("user_id", "=", $uid)
            ->select();
        $arr = array();
        $uArr = array();
        foreach ($list as $row) {
            $arr[] = $row['physical'];
        }
        var_dump($arr);
        // 体检项：0.血检1.便检2体检3.透视4.视力
        if (! in_array(0, $arr)) {
            $uArr[] = "血检";
        }
        if (! in_array(1, $arr)) {
            $uArr[] = "便检";
        }
        if (! in_array(2, $arr)) {
            $uArr[] = "体检";
        }
        if (! in_array(3, $arr)) {
            $uArr[] = "透视";
        }
        $str = implode(" ", $uArr);
        echo $str;
    }
}