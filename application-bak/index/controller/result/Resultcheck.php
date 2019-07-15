<?php
namespace app\index\controller\result;

use app\common\controller\Backend;
use app\index\controller\Common;

/**
 *
 * @desc结果录入
 * @icon fa fa-circle-o
 */
class Resultcheck extends Backend
{

    protected $blood = 0;

    protected $type = 0;

    protected $comm = '';

    protected $orderde = null;
    protected $inspect = null;
    protected $admin = null;
    protected $order = null;
    

    // 开关权限开启
    protected $noNeedRight = [
        '*'
    ];

    /**
     * Register模型对象
     */
    public function _initialize()
    {
        parent::_initialize();
        $this->inspect = model("Inspect");
        $this->admin = model("Admin");
        $this->order = model("Order");
        $this->orderde = model("OrderDetail");
        $comm = new Common();
        $this->comm = $comm;
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

        $conven = $comm->inspect(1);
        $this->view->assign("conven", $conven);

        /**
         * 体检信息
         *
         * @var Ambiguous $result
         */
        $body = array();

        $body = $comm->inspect(2);
        $this->view->assign("body", $body);
        /**
         * 透視信息
         *
         * @var Ambiguous $result
         */
        $tous = array();
        $tous = $comm->inspect(3);
        $this->view->assign("tous", $tous);
    }

    public function index()
    {
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                $where['order_serial_number'] = date("Ymd", time()) . $params['search'];
                $where['bs_id'] = $this->busId;
                $uid = db("physical_users")->where($where)->find();
                if (! $uid) {
                    $this->error("用户不存在");
                }
                $this->view->assign("userinfo", $uid);
                return $this->view->fetch("search");
            } else {
                $this->error();
            }
        }

        return $this->view->fetch();
    }

    public function save()
    {
        $params = $this->request->post();
        $username = $this->admin->get([
            'id' => $this->auth->id
        ]);
        $status = 0;

        if ($params) {
//             $where['type'] = $this->type;
            $where['parent'] = 0;
            $inspectInfo = $this->inspect->where($where)->select();
            foreach ($inspectInfo as $row) {
                if (! empty($params['result'])) {
                    foreach ($params['result'] as $rs) {
                        $sql = "select id,name from fa_inspect where
                        id=(select parent from fa_inspect where id = $rs)  limit 1";
                        $ins = db()->query($sql);
                        if ($ins[0]['id'] == $row['id']) {

                            $where = [
//                                 'physical' => $this->type,
                                'order_serial_number' => $params["order_serial_number"],
                                'item' => $ins[0]['id'],
                                'odbs_id' => $this->busId
                            ];
                            $list = [
                                "physical_result" => 1,
                                "physical_result_ext" => $rs,
                                "status" => 1,
                                "doctor" => $username['nickname']
                            ];
                            $update = $this->orderde->where($where)->update($list);
                            if (! $update) {
                                $status ++;
                            }
                        } else {
                            $where = [
//                                 'physical' => $this->type,
                                'order_serial_number' => $params["order_serial_number"],
                                'item' => $row['id'],
                                'odbs_id' => $this->busId
                            ];
                            $list = [
                                "physical_result" => 0,
                                "physical_result_ext" => 0,
                                "status" => 1,
                                "doctor" => $username['nickname']
                            ];
                            $update = $this->orderde->where($where)->update($list);
                            if (! $update) {
                                $status ++;
                            }
                        }
                    }
                } else {
                    $where = [
//                         'physical' => $this->type,
                        'order_serial_number' => $params["order_serial_number"],
                        'item' => $row['id'],
                        'odbs_id' => $this->busId
                    ];
                    $list = [
                        "physical_result" => 0,
                        "physical_result_ext" => 0,
                        "status" => 1,
                        "doctor" => $username['nickname']
                    ];
                    $update = $this->orderde->where($where)->update($list);
                    if (! $update) {
                        $status ++;
                    }
                }
            }
        }
        echo db()->getLastSql();
        $this->comm->check_resultstatus($params["order_serial_number"]);        
        echo db()->getLastSql();
        if ($status == 0) {
//             $this->success('保存成功', "index", '', 1);
        } else {
//             $this->error('', 'index');
        }
    }

    public function saveResult($params, $type)
    {
        $username = $this->admin->get([
            'id' => $this->auth->id
        ]);

        foreach ($params as $index) {
            $inspectInfo = $this->inspect->get([
                "id" => $index
            ]);
            $inspectStatus = $this->inspect->get([
                "id" => $inspectInfo['parent']
            ]);
            $where = [
                'physical' => $type,
                'order_serial_number' => $params['ordernum'],
                'item' => $index
            ];

            $list = [
                "physical_result" => 1,
                "status" => 1,
                "physical_result" => $inspectStatus['name'],
                "physical_result_ext" => $inspectInfo['name'],
                "doctor" => $username['nickname']
            ];
            $update = $this->orderde->where($where)->update($list);
            if (! $update) {
                $status = 1;
            }
        }
    }
}