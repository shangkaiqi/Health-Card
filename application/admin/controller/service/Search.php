<?php
namespace app\admin\controller\service;

use app\common\controller\Backend;

/**
 *
 * @icon fa fa-circle-o
 */
class Search extends Backend
{

    protected $model = null;

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
            file_put_contents("express.txt", db()->getLastSql());
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

    // /**
    // * Register模型对象
    // *
    // * @var \app\admin\model\business\Register
    // */
    // public function _initialize()
    // {
    // parent::_initialize();
    // }

    // public function index()
    // {
    // if ($this->request->isPost()) {
    // $param = $this->request->get("row/a");
    // if ($param['card']) {}
    // }
    // $param = array(
    // 'start' => 1000,
    // 'end' => 3000,
    // 'card' => 123232132312,
    // 'status' => 1
    // );
    // $where['create_date'] = [
    // "between",
    // [
    // $param['start'],
    // $param['end']
    // ]
    // ];
    // $where['identitycard'] = [
    // 'eq',
    // $param['card']
    // ];
    // $where['order_status'] = [
    // 'eq',
    // $param['status']
    // ];
    // $result = db("physical_users")->alias("pu")
    // ->join("order o", "pu.id=o.user_id")
    // ->field("pu.name,pu.identitycard,pu.type,pu.phone,pu.employee,o.order_serial_number,o.create_date,o.obtain_employ_number,o.order_status")
    // ->where($where)
    // ->select();
    // echo db()->getLastSql();
    // $this->view->assign("list", $result);
    // $this->view->fetch();
    // }
}