<?php
namespace app\admin\controller\physical;

use app\common\controller\Backend;

/**
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
        $userList = db("user")->field("type,nickname,identity_card")->select();
        echo db()->getLastSql();
        var_dump($userList);

        echo "Enregister";
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

                $this->success();
            }
            $this->error();
        }

        return $this->view->fetch();
    }
}