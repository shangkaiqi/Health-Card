<?php
namespace app\admin\controller;

use app\common\controller\Backend;

class Common extends Backend
{

    public function inspect($type)
    {
        $inspect = db("inspect")->field("id,value,name")
            ->where('type', '=', $type)
            ->select();
        $ins = array();
        foreach ($inspect as $key => $val) {
            $values = json_decode($inspect[$key]['value'], TRUE);
            $ins[] = array(
                "name" => $val["name"],
                "value" => $values,
                "id" => $val['id']
            );
        }
        return $ins;
    }

    public function wait_physical()
    {
        // 待体检项：
        $result = db("order")->alias("o")
            ->join("physical_users pu", "o.user_id = pu.id")
            ->join("order_detail od", "o.order_serial_number = od.order_serial_number")
            ->field("physical")
            ->where("pu.id", '=', '1')
            ->select();
        $result = "wwwwwwwww";
        return $result;
    }

    public function employess()
    {
        $employee = db("employee")->field("pid,name")->select();
        return $employee;
    }
}