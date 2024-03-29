<?php
namespace app\index\controller;

use app\common\controller\Backend;
use app\index\controller\Common;
use app\common\controller\Frontend;

/**
 *
 * @desc体检单位
 * @icon fa fa-circle-o
 */
class Business extends Frontend
{

    /**
     * Business模型对象
     *
     * @var \app\index\model\Business
     */
    protected $model = null;

    protected $comm = null;

    public function _initialize()
    {
        parent::_initialize();
        $this->model = new \app\index\model\Business();
        $this->comm = new Common();
    }

    /**
     * 默认生成的控制器所继承的父类中有index/add/edit/del/multi五个基础方法、destroy/restore/recyclebin三个回收站方法
     * 因此在当前控制器中可不用编写增删改查的代码,除非需要自己控制这部分逻辑
     * 需要将application/index/library/traits/Backend.php中对应的方法复制到当前控制器,然后进行修改
     */

    /**
     * 查看
     */
    public function index()
    {
        // 当前是否为关联查询
        $this->relationSearch = false;
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
            $total = $this->model->where($where)
                ->order($sort, $order)
                ->count();
            $list = $this->model->where($where)
                ->order($sort, $order)
                ->limit($offset, $limit)
                ->select();
            foreach ($list as $row) {
                $bus_num = $this->getPhysicalNum($row['bs_uuid']);
                $health = $this->getPhysicalNum($row['bs_uuid'], 1);
                $medicine = $this->getPhysicalNum($row['bs_uuid'], 2);
                $row['bus_num'] = $bus_num;
                $row['health'] = $health;
                $row['medicine'] = $medicine;
                $row['area'] = $this->comm->getAreaName($row['county']);
                $row->visible([
                    'bs_id',
                    'busisess_name',
                    'createtime',
                    'phone',
                    'address',
                    'physical_num',
                    'profession',
                    'area',
                    'charge',
                    'medicine',
                    'health',
                    'medicine',
                    'seal'
                ]);
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
    
    public function edit($ids = '')
    {
        $row = $this->model->get(['bs_id'=>$ids]);
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            var_dump($params);
            if ($params) {}
        }
        $this->view->assign("row",$row);
        return $this->view->fetch();
    }
    public function add()
    {
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            var_dump($params);
            if ($params) {}
        }
        return $this->view->fetch();
    }

    /**
     * 返回该医院的体检量
     *
     * @param string $bus_id
     *            医院id
     * @return int $bus_num; 返回医院的体检数量
     */
    protected function getPhysicalNum($bus_id)
    {
        $bus_num = db("order")->where("bus_number", "=", $bus_id)->count();
        return $bus_num;
    }

    /**
     *
     * @param string $bus_id
     * @param int $busNum
     */
    protected function getTradeNum($bus_id, $busNum)
    {
        $where['obtain_employ_type'] = $bus_id;
        $where['bus_number'] = $busNum;

        $trade = db("order")->where($where)->count();
        return $trade;
    }
}
