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

    // public function inspect($type = '')
    // {
    // $where = array();
    // $inspect = array();
    // if ($type == '') {
    // $inspect = db("inspect")->field("id,value,name")
    // ->where('type', '=', $type)
    // ->select();
    // } else {
    // $inspect = db("inspect")->field("id,value,name")
    // ->where('type', '=', $type)
    // ->select();
    // }
    // $ins = array();
    // foreach ($inspect as $key => $val) {
    // $values = json_decode($inspect[$key]['value'], TRUE);
    // $ins[] = array(
    // "name" => $val["name"],
    // "value" => $values,
    // "id" => $val['id']
    // );
    // }
    // return $ins;
    // }
    public function getInspece($parent)
    {
        $in_a = db("inspect")->field("id,name")
            ->where("parent", "=", $parent)
            ->select();
        return $in_a;
    }

    public function inspect($type = '')
    {
        $where = array();
        $inspect = array();
        $where['type'] = [
            "eq",
            $type
        ];
        $where['parent'] = [
            "eq",
            0
        ];
        if ($type == '') {
            $inspect = db("inspect")->field("id,name")
                ->where($where)
                ->select();
        } else {
            $inspect = db("inspect")->field("id,name")
                ->where($where)
                ->select();
        }
        $ins = array();
        foreach ($inspect as $key => $val) {
            $in_a = $this->getInspece($val['id']);
            $ins[] = array(
                "name" => $val['name'],
                "value" => $in_a,
                "id" => $val['id']
            );
        }
        return $ins;
    }

    /**
     * 获取待检测信息
     *
     * @return string
     */
    public function wait_physical($uid = '')
    {
        if ($uid == '') {
            return "";
        }
        // 待体检项：
        $result = db('order')->alias('o')
            ->join("order_detail od", "`o`.`order_serial_number` = `od`.`order_serial_number`")
            ->field("physical")
            ->where("user_id", "=", $uid)
            ->select();
        foreach ($result as $row) {
            $arr[] = $row['physical'];
        }
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

        $result = implode(" ", $uArr);
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

    /**
     * 获取从业信息
     *
     * @param int $emId
     * @return array|\think\Model
     */
    public function employee($emId)
    {
        $employee = db("employee")->field("name")
            ->where("id", "=", $emId)
            ->find();
        return $employee;
    }

    /**
     * 保存体检信息
     *
     * @param array $params
     * @return boolean
     */
    public function saveOrderDetail()
    {
        $save = $this->request->post();
        var_dump($save);
    }

    /**
     * 获取体结果选项检项
     */
    public function getInspect()
    {
        $id = $this->request->get('id');
        $inspect = array();
        $inspect = array();
        $inspect = db("inspect")->field("id,name")
            ->where('parent', '=', $id)
            ->select();
        return json($inspect);
    }
}