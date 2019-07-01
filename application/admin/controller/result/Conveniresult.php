<?php
namespace app\admin\controller\result;

use app\common\controller\Backend;
use app\admin\controller\Common;

/**
 * @desc便检结果录入
 * @icon fa fa-circle-o
 */
class Conveniresult extends Backend
{

    protected $model = null;

    protected $user = null;

    protected $comm = null;

    protected $blood = 0;

    protected $type = "0";

    // 开关权限开启
    protected $noNeedRight = [
        '*'
    ];

    public function _initialize()
    {
        parent::_initialize();
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
                
                $em = json_decode($row['employee'], true);
                $parent = $this->comm->employee($em[0]);
                //         $son = $this->comm->employee($em[1]);
                //         $row['employee'] = $parent['name'] . ">>" . $son['name'];
                $row['employee'] = $parent['name'];
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
            // if ($params) {

            // // $this->comm->saveOrderDetail($params);
            // }
            file_put_contents("bloodreslut_edit.txt", print_r($params, TRUE));
            $this->success("success");
        }
        
        $em = json_decode($row['employee'], true);
        $parent = $this->comm->employee($em[0]);
        //         $son = $this->comm->employee($em[1]);
        //         $row['employee'] = $parent['name'] . ">>" . $son['name'];
        $row['employee'] = $parent['name'];
        $this->view->assign("wait_physical", $this->comm->wait_physical($ids));
        $this->view->assign("row", $row);
        return $this->view->fetch();
    }
    
    /**
     * 批量操作通过
     */
    public function mulit()
    {
        
        $type = $this->request->get();
        $result = $this->comm->muilts($type);
        if ($result) {
            $this->success("通过");
        }
    }
}