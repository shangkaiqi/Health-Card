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
        $this->model = model("User");
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
            if ($params) {}
            $this->error();
        }

        return $this->view->fetch();
    }
}