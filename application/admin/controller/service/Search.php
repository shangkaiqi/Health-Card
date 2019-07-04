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

    protected $noNeedLogin = [
        'expUser'
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
                ->where("bs_id", "=", $this->busId)
                ->order($sort, $order)
                ->count();

            $list = $this->model->with([
                'order'
            ])
                ->where($where)
                ->where("bs_id", "=", $this->busId)
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
        // 循环遍历每一个用户
        $printArr = array();
        foreach ($uid as $row) {
            $row['employee'] = $this->comm->getEmpName($row['employee']);
            // 获取订单信息
            $where['order_serial_number'] = $row['order_serial_number'];
            $printInfo = db("order")->where($where)->find();
            // 获取体检单位
            $hosp = db("business")->field("busisess_name")
                ->where("bs_uuid", "=", $printInfo['bus_number'])
                ->find();
            $printInfo['name'] = $row['name'];
            $printInfo['sex'] = $row['sex'] == 0 ? "男" : "女";
            $printInfo['employee'] = $row['employee'];
            $printInfo['company'] = $hosp['busisess_name'];
            $printInfo['images'] = $row['images'];
            $printInfo['physictype'] = $row['physictype'];
            $printArr[] = $this->html($row['physictype'], $printInfo);
        }
        // file_put_contents("print_html.txt", print_r($printArr, true));
        // var_dump($printArr);
        $str = '';
        foreach ($printArr as $row) {
            $str .= $row;
        }
        echo "<script language=\"javascript\" src=\"http://www.card.com/LodopFuncs.js\"></script>
            <script src=\"https://cdn.bootcss.com/jquery/3.4.1/jquery.js\"></script>
            <script>
            $(document).ready(function () {
                $(\"#print\").click(function () {
                    setTimeout(\"print()\",500);//延时3秒
                })
                $(\"#print\").trigger(\"click\");
            })
                
			function print() {
                LODOP = getLodop();
                LODOP.PRINT_INITA(\"0\", \"0\", \"86.6mm\", \"56.4mm\", \"打印控件功能演示_Lodop功能_在线编辑获得程序代码\");
                {$str}
                LODOP.PREVIEW();
            }
            </script>

		<button id=\"print\" style=\"display:none\">打印文件</button>";
        $this->success('', 'index', "", 1);
    }

    private function html($type, $print)
    {
        $html = <<<EOF
        		        LODOP.NewPage();
                        LODOP.ADD_PRINT_TEXT("32mm", "25mm", "100", "30", "{$print['name']}");//姓名
                        LODOP.SET_PRINT_STYLEA(0, "FontName", "华文楷体");
                        LODOP.SET_PRINT_STYLEA(0, "FontSize", 8);
                        LODOP.ADD_PRINT_TEXT("32mm", "48mm", "100", "30", "{$print['sex']}");//性别
                        LODOP.SET_PRINT_STYLEA(0, "FontName", "华文楷体");
                        LODOP.SET_PRINT_STYLEA(0, "FontSize", 8);
                        LODOP.ADD_PRINT_TEXT("36mm", "25mm", "100", "30", "{$print['employee']}");//从业类别
                        LODOP.SET_PRINT_STYLEA(0, "FontName", "华文楷体");
                        LODOP.SET_PRINT_STYLEA(0, "FontSize", 8);
                        LODOP.ADD_PRINT_TEXT("40.5mm", "25mm", "100", "30", "{$print['obtain_employ_number']}"); //健康证号
                        LODOP.SET_PRINT_STYLEA(0, "FontName", "华文楷体");
                        LODOP.SET_PRINT_STYLEA(0, "FontSize", 8);
                        LODOP.ADD_PRINT_TEXT("45mm", "25mm", "100", "30", "2019年6月30日");//到期时间
                        LODOP.SET_PRINT_STYLEA(0, "FontName", "华文楷体");
                        LODOP.SET_PRINT_STYLEA(0, "FontSize", 8);
                        LODOP.ADD_PRINT_IMAGE("30mm","60mm","20mm","30mm","<img src=\"data:image/jpeg;base64,{$print['images']}\"/>");
                        LODOP.ADD_PRINT_TEXT("50mm", "25mm", "100", "30", "{$print['company']}");//体检单位
                        LODOP.SET_PRINT_STYLEA(0, "FontName", "华文楷体");
                        LODOP.SET_PRINT_STYLEA(0, "FontSize", 8);
                                
        EOF;
        $html1 = <<<EOF
        		        LODOP.NewPage();
                        LODOP.ADD_PRINT_TEXT("36mm", "48mm", 97, 30, "{$print['employee']}");  //从业类别
                        LODOP.SET_PRINT_STYLEA(0, "FontSize", 9);
                        LODOP.ADD_PRINT_TEXT("40mm", "48mm", 100, 30, "{$print['name']}");  //姓名
                        LODOP.SET_PRINT_STYLEA(0, "FontName", "华文楷体");
                        LODOP.SET_PRINT_STYLEA(0, "FontSize", 9);
                        LODOP.ADD_PRINT_TEXT("40mm", "76mm", 50, 30, "{$print['sex']}");  //性别
                        LODOP.SET_PRINT_STYLEA(0, "FontSize", 9);
                        LODOP.SET_PRINT_STYLEA(0, "Angle", 4);
                        LODOP.ADD_PRINT_IMAGE("25mm","10mm","16.6mm","20mm","<img src=\"data:image/jpeg;base64,{$print['images']}\"/>");//图片
                        LODOP.ADD_PRINT_TEXT("44.5mm", "48mm", 157, 30, "2019年12月31日");//到期时间
                        LODOP.SET_PRINT_STYLEA(0, "FontName", "华文楷体");
                        LODOP.SET_PRINT_STYLEA(0, "FontSize", 9);
                        LODOP.ADD_PRINT_TEXT("50mm", "23mm", 230, 29, "{$print['obtain_employ_number']}"); //健康正号
                        LODOP.SET_PRINT_STYLEA(0, "FontSize", 9);
EOF;
        return $type ? $html : $html1;
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
        $xlsData = db('physical_users')->where("id", "in", $ids)
            ->field("id,name,identitycard,sex,age,phone,employee,company,physictype,registertime")
            ->select();
        foreach ($xlsData as $k => $v) {
            $xlsData[$k]['sex'] = $v['sex'] == 0 ? '男' : '女';
            $xlsData[$k]['employee'] = $this->comm->getEmpName($v['employee']);
            $xlsData[$k]['registertime'] = date("Y-m-d H:m:s", $v['registertime']);
        }
        $this->comm->exportExcel("userPhysial", $xlsCell, $xlsData);
    }
}