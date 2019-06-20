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
    // public function index()
    // {
    // if ($this->request->isPost()) {
    // $params = $this->request->post("row/a");
    // if ($params) {
    // $where = array();
    // if ((! empty($params["start"])) && (! empty($params['end']))) {
    // $where['employ_num_time'] = [
    // '<',
    // $params['end']
    // ];
    // $where['employ_num_time'] = [
    // '>=',
    // $params['start']
    // ];
    // }
    // if ($params['status']) {
    // $where['express_status'] = [
    // '=',
    // $params['status']
    // ];
    // }
    // $result = db("order")->alias("o")
    // ->join("physical_users pu", "o.user_id = pu.id")
    // ->field("pu.name,pu.phone,o.express_status,o.express_num,o.address,o.obtain_employ_number")
    // ->where("o.obtain_employ_number", 'neq', "")
    // ->select();
    // $this->view->assign("blood", $result);
    // $this->success();
    // }
    // $this->error();
    // }
    // $params = array(
    // "start" => 1000000000,
    // "end" => 3000000000,
    // "status" => 1
    // );
    // $where['employ_num_time'] = [
    // 'BETWEEN',
    // [
    // $params['end'],
    // $params['start']
    // ]
    // ];
    // // $where['employ_num_time'] = [
    // // '>=',
    // // $params['start']
    // // ];
    // $where['express_status'] = [
    // '=',
    // $params['status']
    // ];
    // $result = db("order")->alias("o")
    // ->join("physical_users pu", "o.user_id = pu.id")
    // ->field("pu.name,pu.phone,o.express_status,o.express_num,o.address,o.obtain_employ_number")
    // ->where("o.obtain_employ_number", 'neq', "")
    // ->where($where)
    // ->select();
    // return $this->view->fetch();
    // }
}