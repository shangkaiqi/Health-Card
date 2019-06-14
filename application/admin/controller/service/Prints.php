<?php
namespace app\admin\controller\service;

use app\common\controller\Backend;

/**
 *
 * @icon fa fa-circle-o
 */
class Prints extends Backend
{

    /**
     * Register模型对象
     *
     * @var \app\admin\model\business\Register
     */
    public function _initialize()
    {
        parent::_initialize();
    }

    public function index()
    {
        if ($this->request->isPost()) {
            $param = $this->request->post("row/a");
            if ($param) {
                // ->join("order_detail do", "do.order_serial_number=o.order_serial_number")
                $param['user_id'] = "0001";
                $result = db("physical_users")->alias("pu")
                    ->join("order o", "pu.id=o.user_id")
                    ->where("o.order_serial_number", "=", date("Ymd", time()) . $param['user_id'])
                    ->field("pu.*,o.order_serial_number")
                    ->find();
                echo db()->getLastSql();
            }
        }
        $this->view->fetch();
    }

    public function printCard()
    {
        $param = $this->request->post("row/a");
        $param['user_id'] = "0001";
        $result = db("physical_users")->alias("pu")
            ->join("order o", "pu.id=o.user_id")
            ->where("o.order_serial_number", "=", date("Ymd", time()) . $param['user_id'])
            ->field("pu.*,o.order_serial_number")
            ->find();
    }

    public function printBill()
    {}
}