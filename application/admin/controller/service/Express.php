<?php
namespace app\admin\controller\service;

use app\common\controller\Backend;

/**
 *
 * @icon fa fa-circle-o
 */
class Express extends Backend
{

    protected $model = null;

    // 开关权限开启
    protected $noNeedRight = [
        'index','edit'
    ];

    /**
     * Register模型对象
     *
     * @var \app\admin\model\business\Register
     */
    public function _initialize()
    {
        parent::_initialize();
        $this->model = model("PhysicalUsers");
    }

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
                $list[$row]['registertime'] = date("Y-m-d H:i:s", $list[$row]['createtime']);
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
        $ids = $this->request->get([
            "id" => $ids
        ]);
        if ($this->request->isPost()) {}

        if (! $row)
            $this->error(__('No Results were found'));
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {}
            file_put_contents("bloodreslut_edit.txt", print_r($params, TRUE));
            $this->success("success");
        }
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }
}