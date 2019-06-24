<?php
namespace app\admin\controller\inspectresult;

use app\common\controller\Backend;
use app\admin\controller\Common;

/**
 * 便检查
 */
class Convenience extends Backend
{

    protected $model = null;

    protected $user = null;

    protected $blood = 0;

    protected $comm = null;

    protected $type = "0";

    // 开关权限开启
    protected $noNeedRight = [
        'index',
        'edit'
    ];

    public function _initialize()
    {
        parent::_initialize();
        $comm = new Common();
        $this->comm = $comm;
        $ins = $comm->inspect($this->type);
        $this->view->assign("inspect", $ins);

        $this->view->assign("pid", $comm->getEmployee());
        // 获取结果检查信息
        $inspect_top = db("inspect")->field("id,name,value")
            ->where('type', '=', $this->type)
            ->select();
        $this->view->assign("ins", $inspect_top);
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
            foreach ($list as $row) {}
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
            if ($params) {}
            file_put_contents("bloodreslut_edit.txt", print_r($params, TRUE));
            $this->success("success");
        }
        $em = json_decode($row['employee'], true);
        $parent = $this->comm->employee($em[0]);
        $son = $this->comm->employee($em[1]);
        $row['employee'] = $parent['name'] . ">>" . $son['name'];
        $this->view->assign("row", $row);
        $this->view->assign("wait_physical", $this->comm->wait_physical($ids));
        return $this->view->fetch();
    }
}