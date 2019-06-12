<?php
namespace app\admin\controller\inspectresult;

use app\common\controller\Backend;

/**
 * 便检查
 */
class Convenience extends Backend
{

    protected $model = null;

    protected $user = null;

    // 开关权限开启
    protected $noNeedRight = [
        'index'
    ];

    public function _initialize()
    {
        parent::_initialize();
        // 操作人
        $where = [
            'id' => $this->auth->id
        ];
        $operate = db('admin')->where($where)
            ->field('nickname')
            ->find();
        $this->view->assign("operate", $operate);
        // $this->user = model("PhysicalUsers");
        $this->model = model("Order");
    }

    public function index()
    {
        echo "blood result";
        $where = [
            'physical' => '1'
        ];

        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                if (! empty($params['time'])) {
                    $where["create_date"] = $params['time'];
                } else if (! empty($params['yesterday'])) {
                    $where["create_date"] = $params['yesterday'];
                } else if (! empty($params['yesterday_before'])) {
                    $where["create_date"] = $params['yesterday_before'];
                } else if (! empty($params['threeday_ago'])) {
                    $where["create_date"] = $params['threeday_ago'];
                } else {
                    $where['order_serial_number'] = [
                        'like',
                        date("Ymd", time()) . "%"
                    ];
                }
            }
        }
        $where['pu.id'] = 1;
        $result = db('physical_users')->alias("pu")
            ->join("order o", "pu.id=o.user_id", "left")
            ->join("order_detail od", "o.order_serial_number=od.order_serial_number", "left")
            ->field("pu.id,pu.name,pu.sex,pu.age,pu.identitycard,pu.phone,pu.employee,o.order_serial_number,od.physical_result")
            ->select();
        $this->view->assign("bloodResult", $result);
        return $this->view->fetch();
    }
}