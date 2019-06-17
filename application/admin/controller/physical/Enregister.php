<?php
namespace app\admin\controller\physical;

use app\common\controller\Backend;

/**
 * 体检登记
 *
 * @icon fa fa-circle-o
 */
class Enregister extends Backend
{

    protected $multiFields = 'switch';

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
        if ($this->request->isAjax()) {
            // 如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }

            list ($where, $sort, $order, $offset, $limit) = $this->buildparams();
            $total = db("physical_users")->count("id");

            $userList = db("physical_users")->field("id,name,identitycard,type")->select();

            $result = array(
                "total" => $total,
                "rows" => $userList
            );

            return json($result);
        }
        return $this->view->fetch();
    }

    public function add()
    {
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                // name
                // identitycard
                // type
                // sex
                // age
                // phone
                // employee
                // company
                $params['bsid'] = $this->auth->id;
                $result = $this->model->validate("Enregister.add")->save($params);
                if ($result === false) {
                    $this->error($this->model->getError());
                }
                // 生成订单及体检类别

                $this->success();
            }
            $this->error();
        }

        return $this->view->fetch();
    }
}