<?php
namespace app\admin\controller\result;

use app\common\controller\Backend;
use app\admin\controller\Common;

/**
 * @desc结果录入
 * @icon fa fa-circle-o
 */
class Resultcheck extends Backend
{

    protected $blood = 0;

    protected $type = 0;

    protected $comm = '';

    // 开关权限开启
    protected $noNeedRight = [
        '*'
    ];

    /**
     * Register模型对象
     *
     * @var \app\admin\model\business\Register
     */
    public function _initialize()
    {
        parent::_initialize();

        $comm = new Common();
        /**
         * 血检信息
         *
         * @var Ambiguous $result
         */
        $blood = array();

        $blood = $comm->inspect(0);
        $this->view->assign("blood", $blood);

        /**
         * 便检信息
         *
         * @var Ambiguous $result
         */

        $conven = array();

        $conven = $comm->inspect(0);
        $this->view->assign("conven", $conven);

        /**
         * 体检信息
         *
         * @var Ambiguous $result
         */
        $body = array();

        $body = $comm->inspect(0);
        $this->view->assign("body", $body);
        
        var_dump($body);
        /**
         * 透視信息
         *
         * @var Ambiguous $result
         */
        $tous = array();
        $tous = $comm->inspect(0);
        $this->view->assign("tous", $tous);
    }

    public function index()
    {
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $uid = db("physical_users")->where('order_serial_number', "=", date("Ymd", time()) . $params['search'])->find();
                if (! $uid) {
                    $this->error("用户不存在");
                }
                // $where = [
                // "user_id" => $uid["id"],
                // 'physical' => $this->type
                // ];
                $result = db("order")->alias("o")
                    ->join("order_detail od", "o.order_serial_number = od.order_serial_number")
                    ->select();
                $this->view->assign("body", $uid);
                return $this->view->fetch("search");
            } else {
                $this->error();
            }
        }

        return $this->view->fetch();
    }

    public function save()
    {
        $params = $this->request->post('row/a');
        file_put_contents("resultcheck-save.txt", print_r($params, true));
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