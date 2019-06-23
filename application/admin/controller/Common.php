<?php
namespace app\admin\controller;

use app\common\controller\Backend;

class Common extends Backend
{

    protected $noNeedRight = [
        '*'
    ];

    public function _initialize()
    {
        parent::_initialize();
    }

    public function inspect($type = '')
    {
        $where = array();
        $inspect = array();
        if ($type == '') {
            $inspect = db("inspect")->field("id,value,name")
                ->where('type', '=', $type)
                ->select();
        } else {
            $inspect = db("inspect")->field("id,value,name")
                ->where('type', '=', $type)
                ->select();
        }
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
            ->where("pu.id", '=', '0')
            ->select();
        $result = "wwwwwwwww";
        return $result;
    }

    public function getemployee()
    {
        if ($this->request->isAjax()) {
            $id = $this->request->get("id");
            file_put_contents("comm-id.txt", $id);
            $employee = db("employee")->field("id,pid,name")
                ->where("pid", "=", $id)
                ->select();
            return json($employee);
        } else {
            $employee = db("employee")->field("id,pid,name")
                ->where("pid", "=", 0)
                ->select();
            return $employee;
        }
    }

    public function employee($emId)
    {
        $employee = db("employee")->field("name")
            ->where("id", "=", $emId)
            ->find();
        return $employee;
    }
}