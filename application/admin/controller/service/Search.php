<?php
namespace app\admin\controller\service;

use app\common\controller\Backend;
use app\admin\controller\Common;

/**
 * 体检列表
 *
 * @icon fa fa-circle-o
 */
class Search extends Backend
{

    protected $model = null;

    protected $comm = null;

    // 开关权限开启
    protected $noNeedRight = [
        '*'
    ];

    /**
     * Register模型对象
     *
     * // * @var \app\admin\model\business\Register
     */
    public function _initialize()
    {
        parent::_initialize();
        $this->model = model("PhysicalUsers");
        $comm = new Common();
        $this->comm = $comm;
        $this->view->assign("pid", $comm->getEmployee());
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
                $row['registertime'] = date("Y-m-d H:i:s", $row['registertime']);
                $row['employee'] = $this->comm->getEmpName($row['employee']);

                // $row->visible(['name','identitycard','type','sex','age','phone','employee','company','order_serial_number']);
                // $row->visible(['order']);
                // $row->getRelation('order')->visible(['order_id', 'order_serial_number', 'bus_number']);
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
        $list = $this->model->get([
            'id' => $ids
        ]);
        if ($this->request->isPost()) {
            $params = $this->request->isPost("row/a");
            if ($params) {}
        }
        $this->view->assign("row", $list);
        return $this->view->fetch();
    }

    public function printMulit()
    {
        $ids = $this->request->get();
        
        return json(array($ids));
    }

    /**
     *
     * @desc导出Excel
     */
    function expUser()
    {
        // 导出Excel
        $xlsCell = array(
            array(
                'id',
                '账号序列'
            ),
            array(
                'name',
                '名字'
            ),
            array(
                'identitycard',
                '身份证号'
            ),
            array(
                'sex',
                '性别'
            ),
            array(
                'age',
                '院系'
            ),
            array(
                'phone',
                '电话'
            ),
            array(
                'employee',
                '从业类别'
            ),
            array(
                'company',
                '体检单位'
            ),
            array(
                'physictype',
                '体检类别'
            ),
            array(
                'registertime',
                '体检时间'
            )
        );
        $xlsData = db('physical_users')->field("id,name,identitycard,sex,age,phone,employee,company,physictype,registertime")->select();
        foreach ($xlsData as $k => $v) {
            $xlsData[$k]['sex'] = $v['sex'] == 0 ? '男' : '女';
            $xlsData[$k]['employee'] = $this->comm->getEmpName($v['employee']);
            $xlsData[$k]['registertime'] = date("Y-m-d H:m:s", $v['registertime']);
        }
        $this->comm->exportExcel("userPhysial", $xlsCell, $xlsData);
    }
}