<?php
namespace app\index\controller\result;

use app\common\controller\Backend;
use app\index\controller\Common;
use app\common\controller\Frontend;

/**
 *
 * @desc血检结果录入
 * @icon fa fa-circle-o
 */
class Bloodresult extends Frontend
{

    protected $model = null;

    protected $user = null;

    protected $comm = null;

    protected $inspect = null;

    protected $orderde = null;

    protected $type = "0";

    protected $admin = null;

    // 开关权限开启
    protected $noNeedRight = [
        '*'
    ];

    public function _initialize()
    {
        parent::_initialize();
        $this->inspect = model("Inspect");
        $this->admin = model("admin");
        $this->orderde = model("OrderDetail");
        $comm = new Common();
        $this->comm = $comm;

        $this->view->assign("pid", $comm->getEmployee());
        $this->model = model("PhysicalUsers");
    }

    /**
     * 血检用户列表
     *
     * @return string
     */
    public function index()
    {
        // 当前是否为关联查询
        $this->relationSearch = true;
        // 设置过滤方法
        $this->request->filter([
            'strip_tags'
        ]);
        if ($this->request->isAjax()) {
            // 如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }
            list ($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = $this->model->with([
                'order'
            ])
                ->where($where)
                ->where("bs_id", "=", $this->busId)
                ->order($sort, $order)
                ->count();

            $list = $this->model->with([
                'order'
            ])
                ->where($where)
                ->where("bs_id", "=", $this->busId)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            foreach ($list as $row => $v) {
                $resWhere['order_serial_number'] = $v['order_serial_number'];
                $resWhere['odbs_id'] = $this->busId;
                $resWhere['physical'] = $this->type;
                $res = $this->orderde->field("physical_result")
                    ->where($resWhere)
                    ->select();
                $status = 0;
                foreach ($res as $r) {
                    if ($r['physical_result'] != 0) {
                        $status ++;
                    }
                }
                if ($status == 0)
                    $list[$row]['physical_result'] = 0;
                else
                    $list[$row]['physical_result'] = 1;
            }
            $list = collection($list)->toArray();
            $result = array(
                "total" => $total,
                "rows" => $list
            );

            return json($result);
        }
        return $this->view->fetch();
    }

    public function edit($ids = null)
    {
        $row = $this->model->get([
            'id' => $ids
        ]);
        $username = $this->admin->get([
            'id' => $this->auth->id
        ]);
        if (! $row)
            $this->error(__('No Results were found'));
        if ($this->request->isPost()) {
            $params = $this->request->post();
            if ($params) {
                $result = $this->comm->saveOrderDetail($params,$this->type,$username['nickname']);
                if ($result) {
                    $this->success('保存成功', "index", '', 1);
                } else {
                    $this->error('没有变更数据', 'index');
                }
            }
        }

        $ins = $this->comm->inspect($this->type,$row['order_serial_number']);
        $this->view->assign("inspect", $ins);
        $this->view->assign("wait_physical", $this->comm->wait_physical($ids));
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }

    /**
     * 保存检查结果
     */
    public function save()
    {
        $params = $this->request->post();
        $username = $this->admin->get([
            'id' => $this->auth->id
        ]);
        $status = 1;
        if ($params) {
            foreach ($params['phitem'] as $index) {
                $inspectInfo = $this->inspect->get([
                    "id" => $index
                ]);
                $inspectStatus = $this->inspect->get([
                    "id" => $inspectInfo['parent']
                ]);
                $where = [
                    'physical' => $this->type,
                    'order_serial_number' => $params["order_serial_number"],
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
                echo db()->getLastSql();
                if (! $update) {
                    $status = 1;
                }
            }

            $this->comm->check_resultstatus($params["order_serial_number"]);

            if ($status) {
                $this->success('保存成功', null);
            } else {
                $this->error('', 'index');
            }
        }
    }

    /**
     * 批量操作通过
     */
    public function mulit($ids = null)
    {
        $user = $this->request->get("id");
        $users = explode(",", $user);
        $result = $this->comm->muilts($users, $this->type);
        if ($result) {
            $this->success('', null);
        } else {
            $this->error("批量保存成功");
        }
    }
}