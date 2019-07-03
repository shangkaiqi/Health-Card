<?php
namespace app\admin\controller\result;

use app\common\controller\Backend;
use app\admin\controller\Common;

/**
 *
 * @desc透视结果录入
 *
 * @icon fa fa-circle-o
 */
class Perspective extends Backend
{

    protected $model = null;

    protected $user = null;

    protected $comm = null;

    protected $inspect = null;

    protected $orderde = null;

    protected $admin = null;

    protected $type = "3";

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
        $ins = $comm->inspect($this->type);
        $this->view->assign("inspect", $ins);

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
                ->order($sort, $order)
                ->count();

            $list = $this->model->with([
                'order'
            ])
                ->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();

            foreach ($list as $row) {
                $row['employee'] = $this->comm->getEmpName($row['employee']);
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
        if (! $row)
            $this->error(__('No Results were found'));
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            $params = $this->request->post();
            if ($params) {
                $username = $this->admin->get([
                    'id' => $this->auth->id
                ]);

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
                    if (! $update) {
                        $status = 1;
                    }
                }
                if ($status) {
                    $this->success('', "index", '', 1);
                } else {
                    $this->error();
                }
            }
        }

        $row['employee'] = $this->comm->getEmpName($row['employee']);

        $this->view->assign("wait_physical", $this->comm->wait_physical($ids));
        $this->view->assign("row", $row);
        return $this->view->fetch();
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