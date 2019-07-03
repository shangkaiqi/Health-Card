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
    protected $noNeedLogin = ['expUser'];

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
        $params = $this->request->get("id");
        $ids = explode(",", $params);
        $uid = db("physical_users")->where('id', "in", $ids)->select();
        //循环遍历每一个用户
        $arr = array();
        foreach ($uid as $row){            
            $row['employee'] = $this->comm->getEmpName($row['employee']);
            //获取订单信息
            $where['order_serial_number'] = $row['order_serial_number'];
            $printInfo = db("order")->where($where)->find();
            //获取体检单位
            $hosp = db("business")->field("busisess_name")->where("bs_uuid","=",$printInfo['bus_number'])->find();
            $printInfo['name'] = $row['name'];
            $printInfo['sex'] = $row['sex']==0?"男":"女";
            $printInfo['employee'] = $row['employee'];
            $printInfo['company'] = $hosp['busisess_name'];
            $printInfo['images'] = $row['images'];
            $printInfo['physictype'] = $row['physictype'];
            $arr[] = $printInfo;
            
            $this->html($printInfo);
            
            
        }
        var_dump($arr);
        
//         function myPreview() {
//             if($type['physictype'] == 0){
//                 CreatePrintPage();
//             }else{
//                 CreatePrintPage1();
//             }
//             LODOP.PREVIEW();
//         };
        
        
        
        
//         return json(array($ids));
    }
    private function html($data){
        $html = <<<EOF
            	function CreatePrintPage() {       
            		LODOP=getLodop();         	
            		LODOP.PRINT_INITA("0.2292in","0.3646in","7.0104in","4.4688in","打印控件功能演示_Lodop功能_在线编辑获得程序代码");
            		LODOP.ADD_PRINT_SETUP_BKIMG("C:\\Users\\Shilh\\Desktop\\QQ截图20190702132927.png");
            		LODOP.ADD_PRINT_TEXT("2.5in","1.9479in","0.8542in","0.3646in","王经理");
            		LODOP.SET_PRINT_STYLEA(0,"FontName","华文楷体");
            		LODOP.SET_PRINT_STYLEA(0,"FontSize",16);
            		LODOP.ADD_PRINT_TEXT("2.5313in","3.8854in","1.0417in","0.3125in","男");
            		LODOP.SET_PRINT_STYLEA(0,"FontName","华文楷体");
            		LODOP.SET_PRINT_STYLEA(0,"FontSize",16);
            		LODOP.ADD_PRINT_TEXT("2.8854in","1.9375in","1.4583in","0.3646in","医疗卫生");
            		LODOP.SET_PRINT_STYLEA(0,"FontName","华文楷体");
            		LODOP.SET_PRINT_STYLEA(0,"FontSize",16);
            		LODOP.ADD_PRINT_TEXT("3.2813in","1.9479in","1.7188in","0.3646in","冀13052585485");
            		LODOP.SET_PRINT_STYLEA(0,"FontName","华文楷体");
            		LODOP.SET_PRINT_STYLEA(0,"FontSize",16);
            		LODOP.ADD_PRINT_TEXT("3.6771in","1.9479in","1.75in","0.3229in","2019年6月30日");
            		LODOP.SET_PRINT_STYLEA(0,"FontName","华文楷体");
            		LODOP.SET_PRINT_STYLEA(0,"FontSize",16);
            		LODOP.ADD_PRINT_TEXT("4.0104in","1.9479in","1.4479in","0.3646in","河北中医院");
            		LODOP.SET_PRINT_STYLEA(0,"FontName","华文楷体");
            		LODOP.SET_PRINT_STYLEA(0,"FontSize",16);
            	};  
            	
            
            	function CreatePrintPage1() {  
            		LODOP.PRINT_INITA(0,0,678,426,"打印控件功能演示_Lodop功能_在线编辑获得程序代码");
            		LODOP.ADD_PRINT_SETUP_BKIMG("C:\\Users\\Shilh\\Desktop\\QQ截图20190702132946.png");
            		LODOP.ADD_PRINT_TEXT(311,371,97,30,"王经理");
            		LODOP.SET_PRINT_STYLEA(0,"FontSize",16);
            		LODOP.ADD_PRINT_TEXT(278,375,100,30,"医药卫生");
            		LODOP.SET_PRINT_STYLEA(0,"FontName","华文楷体");
            		LODOP.SET_PRINT_STYLEA(0,"FontSize",16);
            		LODOP.ADD_PRINT_TEXT(310,579,50,30,"男");
            		LODOP.SET_PRINT_STYLEA(0,"FontSize",16);
            		LODOP.SET_PRINT_STYLEA(0,"Angle",4);
            		LODOP.ADD_PRINT_TEXT(343,375,157,30,"2019年12月31日");
            		LODOP.SET_PRINT_STYLEA(0,"FontName","华文楷体");
            		LODOP.SET_PRINT_STYLEA(0,"FontSize",16);
            		LODOP.ADD_PRINT_TEXT(389,167,230,29,"冀158565855858");
            		LODOP.SET_PRINT_STYLEA(0,"FontSize",16);
            	}
EOF;
    }
    /**
     *
     * @desc导出Excel
     */
    public function expUser()
    {
        $params = $this->request->get('id');
        $ids = explode(",", $params);
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
        $xlsData = db('physical_users')->where("id","in",$ids)->field("id,name,identitycard,sex,age,phone,employee,company,physictype,registertime")->select();
        foreach ($xlsData as $k => $v) {
            $xlsData[$k]['sex'] = $v['sex'] == 0 ? '男' : '女';
            $xlsData[$k]['employee'] = $this->comm->getEmpName($v['employee']);
            $xlsData[$k]['registertime'] = date("Y-m-d H:m:s", $v['registertime']);
        }
        $this->comm->exportExcel("userPhysial", $xlsCell, $xlsData);
    }
}