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
        $this->order_detial("201906260001", 1);
    }

    public function getInspece($parent)
    {
        $in_a = db("inspec")->field("id,name")
            ->where("parent", "=", $parent)
            ->select();
        return $in_a;
    }

    public function order_detial($orderNum, $physictype)
    {

        // $physictype 0 食药安全 1 卫生监督
        // physical 0.血检1.便检2体检3.透视4.视力
        // physical_result 0未检查，1已建成，3。无异常4.异常'
        $ins = db('inspect')->field("name,type")->where("parent","=","0")->select();
        $list = array();
        foreach ($ins as $res) {
            $param['order_serial_number'] = $orderNum;
            $param['physical'] = $res['type'];
            $param['physical_result'] = '';
            $param['physical_result_ext'] = '';
            $param['doctor'] = '';
            $param['item'] = $res['name'];
            $list[] = $param;
        }
        
        $this->orderDetail->saveAll($list);
        var_dump($list);

        echo db()->getLastSql();
    }
}