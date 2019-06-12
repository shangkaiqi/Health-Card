<?php
namespace app\admin\controller\inspectresult;

use app\common\controller\Backend;

/**
 *
 * @icon fa fa-circle-o
 */
class Resultcheck extends Backend
{

    protected $blood = 0;

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
    }

    public function index()
    {
        $where = array();
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $where['order_serial_number'] = [
                    'like',
                    date("Ymd", time()) . "%"
                ];
            }
        }
        /**
         * 客户信息
         *
         * @var Ambiguous $result
         */
        $user = db('physical_users')->alias("pu")
            ->join("order o", "pu.id=o.user_id", "left")
            ->join("order_detail od", "o.order_serial_number=od.order_serial_number", "left")
            ->field("pu.id,pu.name,pu.sex,pu.age,pu.identitycard,pu.phone,pu.employee,o.order_serial_number,od.physical_result")
            ->where($where)
            ->select();

        /**
         * 血检信息
         *
         * @var Ambiguous $result
         */
        $blood = array();
        $inspect = db('inspect')->where('type', '=', 0)
            ->field('name,value')
            ->select();
        foreach ($inspect as $key => $val) {
            $values = json_decode($inspect[$key]['value'], TRUE);
            $blood[] = array(
                $inspect[$key]['name'] => $values
            );
        }

        /**
         * 便检信息
         *
         * @var Ambiguous $result
         */

        $conven = array();
        $inspect = db('inspect')->where('type', '=', 1)
            ->field('name,value')
            ->select();
        foreach ($inspect as $key => $val) {
            $values = json_decode($inspect[$key]['value'], TRUE);
            $conven[] = array(
                $inspect[$key]['name'] => $values
            );
        }

        /**
         * 体检信息
         *
         * @var Ambiguous $result
         */
        $body = array();
        $inspect = db('inspect')->where('type', '=', 2)
            ->field('name,value')
            ->select();
        foreach ($inspect as $key => $val) {
            $values = json_decode($inspect[$key]['value'], TRUE);
            $body[] = array(
                $inspect[$key]['name'] => $values
            );
        }

        /**
         * 透視信息
         *
         * @var Ambiguous $result
         */
        $tous = array();
        $inspect = db('inspect')->where('type', '=', 3)
            ->field('name,value')
            ->select();
        foreach ($inspect as $key => $val) {
            $values = json_decode($inspect[$key]['value'], TRUE);
            $tous[] = array(
                $inspect[$key]['name'] => $values
            );
        }
        $this->view->assign("user", $user);
        $this->view->assign("blood", $blood);
        $this->view->assign("conven", $conven);
        $this->view->assign("body", $body);
        $this->view->assign("tous", $tous);
        return $this->view->fetch();
    }

    public function saveCheck()
    {
        $user_id = $this->request->post('userId');
        $where = [
            'user_id' => $user_id
        ];
        // 体征信息
        $body = $this->request->post("body/a");
        db("order_detail")->data($body)->save();
        // 胸透
        $tous = $this->request->post("tous/a");
        db("order_detail")->data($body)->save();
        // 粪便
        $conven = $this->request->post("conven/a");
        db("order_detail")->data($body)->save();
        // 血检
        $blood = $this->request->post("blood/a");
        db("order_detail")->data($body)->save();
    }
}