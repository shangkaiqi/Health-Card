<?php
namespace app\admin\controller;

use app\common\controller\Backend;
// use app\admin\model\Business;
use fast\Random;
use think\addons\Service;
use think\Cache;
use think\Config;
use think\Db;
use think\Lang;
use think\session;

/**
 * Ajax异步请求接口
 *
 * @internal
 */
class Business extends Backend
{

    protected $multiFields = 'switch';

    // 开关权限开启
    protected $noNeedRight = [
        'index'
    ];

    /**
     *
     * 
     */
    protected $model = null;

    // protected $noNeedRight = [
    // 'check'
    // ];
    public function _initialize()
    {
        parent::_initialize();
        $this->model = model('Business');
    }

    public function index()
    {
        // echo "Business";
        // // $this->assign('auth', $this->auth);
        // var_dump($this->auth->id);
        // echo "<br>";
        // var_dump($this->auth->getGroupIds());
        // echo "<br>";
        // var_dump($this->auth->getGroups());
        // echo "<br>";
        // $aaa = $this->model->get($this->auth->id);
        // $buss = $this->model->where([
        // `admin` . 'id' => $this->auth->id
        // ])->select();
        $buss = db("business")->alias("b")
            ->join("admin a", "b.bs_id=a.businessid")
            ->where([
            'a.id' => $this->auth->id
        ])
            ->select();
        $this->assign("business", $buss);
        return $this->view->fetch();
    }
}