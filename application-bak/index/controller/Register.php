<?php
namespace app\index\controller;

use app\common\controller\Backend;
use app\index\controller\Common;
use Monolog\Logger;
use think\Log;

/**
 *
 * @desc体检登记
 *
 * @icon fa fa-circle-o
 */
class Register extends Backend
{

    protected $multiFields = 'switch';

    protected $model = null;

    protected $order = null;

    protected $orderd = null;

    protected $layout = 'register';

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
        $this->order = model("Order");
        $this->orderd = model("OrderDetail");
        $comm = new Common();
        $this->comm = $comm;

        $ins = $comm->inspect();
        $this->view->assign("inspect", $ins);

        $this->view->assign("wait_physical", $comm->wait_physical());
        $this->view->assign("pid", $comm->getemployee());
        // 获取结果检查信息
        $inspect_top = db("inspect")->field("id,name,value")->select();
        $this->view->assign("ins", $inspect_top);
    }

    public function index()
    {
        if ($this->request->isAjax()) {
            // 如果发送的来源是Selectpage，则转发到Selectpage
            if ($this->request->request('keyField')) {
                return $this->selectpage();
            }

            list ($where, $sort, $order, $offset, $limit) = $this->buildparams();
            // $total = db("physical_users")->count("id");

            // $userList = db("physical_users")->field("id,name,identitycard,type")->select();
            $total = $this->model->where("bs_id", "=", $this->busId)->count("id");
            $userList = $this->model->where("bs_id", "=", $this->busId)->select();
            foreach ($userList as $row) {
                $row['registertime'] = date("Y-m-d H:i", $row['registertime']);
            }
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

        // 获取医院唯一标识
        $bs_id = db("admin")->alias("a")
            ->field("b.bs_uuid,isprint,b.charge,b.bs_id,b.print_form_id")
            ->join("business b", "a.businessid = b.bs_id")
            ->where("id", "=", $this->auth->id)
            ->find();
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                // 获取订单最后一条id
                // $orderId = $this->model->order('registertime', 'desc')->find();
				$ordernum = array();
				$phwhere['order_serial_number'] = ['like',date("Ymd", time()) . "%"];
				$phwhere['bs_id'] = $this->busId;
                $ordernum = $result = db('physical_users')->field("order_serial_number")
                    ->where($phwhere)
                    ->order("registertime desc")
                    ->find();
                if ($ordernum) {
                    $resultNum = $ordernum['order_serial_number'] + 1;
                } else {
                    $resultNum = date("Ymd", time()) . "0001";
                }
                $emp = db('employee')->field('name,id')
                    ->where('id', '=', $params['parent'])
                    ->find();
                $param['name'] = $params['name'];
                $param['identitycard'] = $params['identitycard'];
                $param['type'] = $params['type'];
                $param['sex'] = $params['sex'];
                $param['images'] = $params['avatar'];
                $param['age'] = $params['age'];
                $param['phone'] = $params['phone'];
                $param['express'] = $params['express'];
                $param['bs_id'] = $this->busId;
                $param['employee'] = $emp['name'];
                $param['employee_id'] = $params['parent'];
                $param['company'] = $params['company'];
                $param['order_serial_number'] = $resultNum;
                // $params['bsid'] = $this->auth->id;
                // $result = $this->model->validate("Enregister.add")->save($params);
                $result = $this->model->validate("Register.add")->save($param);

                if (! $result) {
                    $this->error($this->model->getError());
                }
                if (strlen($bs_id['bs_id']) == 1) {
                    $bs_id['bs_id'] = "00" . $bs_id['bs_id'];
                } else if (strlen($bs_id['bs_id']) == 2) {
                    $bs_id['bs_id'] = "0" . $bs_id['bs_id'];
                }
                $par['user_id'] = $this->model->id;
                $par['order_serial_number'] = $resultNum;
                $par['bus_number'] = $bs_id['bs_uuid'];
                $par['charge'] = $bs_id['charge'];
                $par['order_status'] = '0';
                $par['obtain_employ_type'] = $param['employee'];                
                $par['obs_id'] = $this->busId;
                $rand = mt_rand(100, 999);
                $par['obtain_employ_number'] = $bs_id['bs_id'] . date("ymdHis", time()) . $rand;
                if ($params['express']) {
                    $par['address'] = $params['address'];
                }
                $order = $this->order->save($par);
                if (! $order) {
                    $this->error($this->model->getError());
                }
                $this->order_detial($resultNum);
                if($bs_id['isprint']){
                    $param['time'] = date("Y年m月d日",time());
                    $param['print_form_id'] = $bs_id['print_form_id'];
                    $html = $this->get_html($param);
                    echo $html;
                }
                $this->success();
            }
            $this->error();
        }
        $this->view->assign("isprint", $bs_id['isprint']);
        return $this->view->fetch();
    }

    // 创建订单详细信息
    public function order_detial($orderNum)
    {
        $ins = db('inspect')->field("id,name,type")
            ->where("parent", "=", "0")
            ->select();
        $list = array();
        foreach ($ins as $res) {
            $param['order_serial_number'] = $orderNum;
            $param['physical'] = $res['type'];
            $param['physical_result'] = '';
            $param['physical_result_ext'] = '';
            $param['doctor'] = '';
            $param['item'] = $res['id'];
            $param['odbs_id'] = $this->busId;
            $list[] = $param;
        }

        $this->orderd->saveAll($list);
    }

    public function edit($ids = '')
    {
        $list = $this->model->get([
            'id' => $ids
        ]);
        if ($this->request->isPost()) {
            $params = $this->request->post("row/a");
            if ($params) {
                
                $emp = db('employee')->field('name,id')
                ->where('id', '=', $params['parent'])
                ->find();
                $param['name'] = $params['name'];
                $param['identitycard'] = $params['identitycard'];
                $param['type'] = $params['type'];
                $param['sex'] = $params['sex'];
                $param['images'] = $params['avatar'];
                $param['age'] = $params['age'];
                $param['phone'] = $params['phone'];
                $param['employee'] = $emp['name'];
                $param['employee_id'] = $params['parent'];
                $param['company'] = $params['company'];
                $where['id'] = $ids;
                $result = $this->model->where($where)->update($param);
                if ($result)
                    $this->success();
                else
                    $this->error();
            }
        }
        $this->view->assign("row", $list);
        return $this->view->fetch();
    }

    /**
     * 批量打印体检表
     */
    public function physical_table()
    {
        $params = $this->request->get('id');
        $print = $this->getPrint($params);
        $printArr = array();
        foreach ($print as $row){
            $printArr[] = $this->lodopJs($row);
        }
        $str = '';
        foreach ($printArr as $row) {
            $str .= $row;
//             $html.=$row['html'];
            
        }
        
        $bus = db("business")->field('print_form_id')->where('bs_id',"=",$this->busId)->find();
        
        
        $html = $this->getMulit_html();
        echo "<script language=\"javascript\" src=\"http://39.100.89.92:8080/LodopFuncs.js\"></script>
        <script src=\"https://cdn.bootcss.com/jquery/3.4.1/jquery.js\"></script>
            <script>
            $(document).ready(function () {
                $(\"#prints\").click(function () {
                    setTimeout(\"print()\",500);//延时3秒
                })
                $(\"#prints\").trigger(\"click\");
            })
            
			function print() {
                LODOP = getLodop();
                LODOP.PRINT_INITA(9, 0, 794, 1122, \"打印控件功能演示_Lodop功能_在线编辑获得程序代码\");
                {$str}                
		        if (LODOP.SET_PRINTER_INDEX({$bus['print_form_id']}))
                LODOP.PREVIEW();
            }
            </script>            
		<button id=\"prints\" style=\"display:none\">打印文件</button>{$html}";
        $this->success("", "index",'',1);
    }
    
    protected function lodopJs($print){
        $lodop = <<<EOF
        LODOP.NewPage();
        LODOP.SET_PRINT_MODE("PRINT_NOCOLLATE", 1);
        LODOP.ADD_PRINT_TEXT(43, 150, 465, 45, "河北省食品药品从业人员健康检查表");
        LODOP.SET_PRINT_STYLEA(0, "FontName", "黑体");
        LODOP.SET_PRINT_STYLEA(0, "FontSize", 20);
        LODOP.SET_PRINT_STYLEA(0, "Alignment", 2);
        LODOP.ADD_PRINT_SHAPE(4, 150, 46, 702, 2, 0, 1, "#000000");
        LODOP.ADD_PRINT_TEXT(122, 50, 79, 26, "体检日期: ");
        LODOP.SET_PRINT_STYLEA(0, "FontSize", 11);
        LODOP.ADD_PRINT_TEXT(122, 570, 160, 26, "编号：{$print['order_serial_number']}");
        LODOP.SET_PRINT_STYLEA(0, "FontSize", 12);
        LODOP.ADD_PRINT_TEXT(122, 140, 170, 26, "{$print['time']}");
        LODOP.SET_PRINT_STYLEA(0, "FontSize", 12);

        LODOP.ADD_PRINT_TEXT(170, 70, 50, 26, "姓名:");
        LODOP.SET_PRINT_STYLEA(0, "FontSize", 12);
        LODOP.ADD_PRINT_TEXT(170, 120, 60, 26, "{$print['name']}");
        LODOP.SET_PRINT_STYLEA(0, "FontSize", 12);
        LODOP.ADD_PRINT_TEXT(170, 240, 60, 26, "性别:");
        LODOP.SET_PRINT_STYLEA(0, "FontSize", 12);
        LODOP.ADD_PRINT_TEXT(170, 300, 60, 26, "{$print['sex']}");
        LODOP.SET_PRINT_STYLEA(0, "FontSize", 12);
        LODOP.ADD_PRINT_TEXT(170, 400, 60, 26, "年龄");
        LODOP.SET_PRINT_STYLEA(0, "FontSize", 12);
        LODOP.ADD_PRINT_TEXT(170, 460, 60, 26, "{$print['age']}");
        LODOP.SET_PRINT_STYLEA(0, "FontSize", 12);

        LODOP.ADD_PRINT_TEXT(210, 70, 80, 26, "从业类别");
        LODOP.SET_PRINT_STYLEA(0, "FontSize", 12);
        LODOP.ADD_PRINT_TEXT(210, 150, 80, 26, "{$print['employee']}");
        LODOP.SET_PRINT_STYLEA(0, "FontSize", 12);
        LODOP.ADD_PRINT_TEXT(210, 300, 80, 26, "体检单位");
        LODOP.SET_PRINT_STYLEA(0, "FontSize", 12);
        LODOP.ADD_PRINT_TEXT(210, 380, 80, 26, "{$print['company']}");
        LODOP.SET_PRINT_STYLEA(0, "FontSize", 12);

        LODOP.ADD_PRINT_TEXT(250, 70, 80, 26, "身份证号");
        LODOP.SET_PRINT_STYLEA(0, "FontSize", 12);
        LODOP.ADD_PRINT_TEXT(250, 150, 200, 26, "{$print['identitycard']}");
        LODOP.SET_PRINT_STYLEA(0, "FontSize", 12);


        LODOP.ADD_PRINT_IMAGE(160, 600, 102, 126, "<img src=\"data:image/jpeg;base64,{$print['images']}\"/>");
        LODOP.SET_PRINT_STYLEA(0, "TransColor", "#0F0100");
        LODOP.ADD_PRINT_TABLE(290, 56, 680, 760, document.getElementById("print_8").innerHTML);
EOF;
        return $lodop;
    }
    
    protected function getMulit_html(){
        
        $js = <<<EOF
        <div id="print_8" style="display:none">
        			<table class="MsoNormalTable" width="670" style="border-collapse:collapse;border:none;" cellspacing="0"	cellpadding="0" border="1">
        				<tbody>
        					<tr>
        						<td rowspan="2" style="border:solid windowtext 1.0pt;" width="73">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<b><span style="font-family:宋体;">既往</span></b><b><span style="font-family:宋体;"></span></b>
        							</p>
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<b><span style="font-family:宋体;">病史</span></b><b><span style="font-family:宋体;"></span></b>
        							</p>
        						</td>
        						<td colspan="3" style="border:solid windowtext 1.0pt;" width="66">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<b><span style="font-family:宋体;">病</span></b><b><span style="font-family:宋体;"><span>&nbsp;
        										</span><span>名</span></span></b>
        							</p>
        						</td>
        						<td style="border:solid windowtext 1.0pt;" width="71">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<b><span style="font-family:宋体;">肝</span></b><b><span style="font-family:宋体;"><span>&nbsp;
        										</span><span>类</span></span></b>
        							</p>
        						</td>
        						<td colspan="2" style="border:solid windowtext 1.0pt;" width="71">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<b><span style="font-family:宋体;">痢</span></b><b><span style="font-family:宋体;"><span>&nbsp;
        										</span><span>疾</span></span></b>
        							</p>
        						</td>
        						<td colspan="2" style="border:solid windowtext 1.0pt;" width="71">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<b><span style="font-family:宋体;">伤</span></b><b><span style="font-family:宋体;"><span>&nbsp;
        										</span><span>寒</span></span></b>
        							</p>
        						</td>
        						<td colspan="3" style="border:solid windowtext 1.0pt;" width="71">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<b><span style="font-family:宋体;">肺结核</span></b><b><span style="font-family:宋体;"></span></b>
        							</p>
        						</td>
        						<td style="border:solid windowtext 1.0pt;" width="71">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<b><span style="font-family:宋体;">皮肤病</span></b><b><span style="font-family:宋体;"></span></b>
        							</p>
        						</td>
        						<td style="border:solid windowtext 1.0pt;" width="71">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<b><span style="font-family:宋体;">其</span></b><b><span style="font-family:宋体;"><span>&nbsp;
        										</span><span>它</span></span></b>
        							</p>
        						</td>
        					</tr>
        					<tr>
        						<td colspan="3" style="border:solid windowtext 1.0pt;" width="66">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">患病时间</span><span style="font-family:宋体;"></span>
        							</p>
        						</td>
        						<td style="border:solid windowtext 1.0pt;" width="71">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">&nbsp;</span>
        							</p>
        						</td>
        						<td colspan="2" style="border:solid windowtext 1.0pt;" width="71">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">&nbsp;</span>
        							</p>
        						</td>
        						<td colspan="2" style="border:solid windowtext 1.0pt;" width="71">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">&nbsp;</span>
        							</p>
        						</td>
        						<td colspan="3" style="border:solid windowtext 1.0pt;" width="71">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">&nbsp;</span>
        							</p>
        						</td>
        						<td style="border:solid windowtext 1.0pt;" width="71">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">&nbsp;</span>
        							</p>
        						</td>
        						<td style="border:solid windowtext 1.0pt;" width="71">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">&nbsp;</span>
        							</p>
        						</td>
        					</tr>
        					<tr>
        						<td rowspan="4" style="border:solid windowtext 1.0pt;" width="73">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">体征</span><span style="font-family:宋体;"></span>
        							</p>
        						</td>
        						<td style="border:solid windowtext 1.0pt;" width="38">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">心</span><span style="font-family:宋体;"></span>
        							</p>
        						</td>
        						<td colspan="6" style="border:solid windowtext 1.0pt;" width="230">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">&nbsp;</span>
        							</p>
        						</td>
        						<td colspan="2" style="border:solid windowtext 1.0pt;" width="41">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">肝</span><span style="font-family:宋体;"></span>
        							</p>
        						</td>
        						<td colspan="4" style="border:solid windowtext 1.0pt;" width="182">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">&nbsp;</span>
        							</p>
        						</td>
        					</tr>
        					<tr>
        						<td style="border:solid windowtext 1.0pt;" width="38">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">肺</span><span style="font-family:宋体;"></span>
        							</p>
        						</td>
        						<td colspan="6" style="border:solid windowtext 1.0pt;" width="230">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">&nbsp;</span>
        							</p>
        						</td>
        						<td colspan="2" style="border:solid windowtext 1.0pt;" width="41">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">脾</span><span style="font-family:宋体;"></span>
        							</p>
        						</td>
        						<td colspan="4" style="border:solid windowtext 1.0pt;" width="182">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">&nbsp;</span>
        							</p>
        						</td>
        					</tr>
        					<tr>
        						<td style="border:solid windowtext 1.0pt;" width="38">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">皮肤</span><span style="font-family:宋体;"></span>
        							</p>
        						</td>
        						<td colspan="12" style="border:solid windowtext 1.0pt;" width="454">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">手癣 指甲癣 手部湿疹 银屑</span><span
        									style="font-family:宋体;">(<span>或鳞屑</span>)<span>病 渗出性皮肤病 化脓性皮肤病</span></span>
        							</p>
        						</td>
        					</tr>
        					<tr>
        						<td style="border:solid windowtext 1.0pt;" width="38">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">其它</span><span style="font-family:宋体;"></span>
        							</p>
        						</td>
        						<td colspan="6" style="border:solid windowtext 1.0pt;" width="230">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">&nbsp;</span>
        							</p>
        						</td>
        						<td colspan="3" style="border:solid windowtext 1.0pt;" width="77">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">医师签名</span><span style="font-family:宋体;"></span>
        							</p>
        						</td>
        						<td colspan="3" style="border:solid windowtext 1.0pt;" width="147">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">&nbsp;</span>
        							</p>
        						</td>
        					</tr>
        					<tr>
        						<td rowspan="2" style="border:solid windowtext 1.0pt;" width="73">
        							<p class="MsoNormal" style="font-size: 14px;">
        								视力及辨色力<br>（直接接触药品质量检验、验收、养护人员）
        							</p>
        							<p class="MsoNormal">
        							</p>
        						</td>
        						<td colspan="2" style="border:solid windowtext 1.0pt;" width="62">
        							<p class="MsoNormal" style="margin-left:-2.4pt;">
        								<span style="font-family:宋体; padding-left: 10px">视力</span>
                                        <span style="font-family:宋体;"></span>
        							</p>
        							
        						</td>
        						<td colspan="3" style="border:solid windowtext 1.0pt;" width="102">
        							<p class="MsoNormal" style="margin-left:-2.4pt;">
        								<span style="font-family:宋体;padding-left: 10px">左</span>
        								<span style="font-family:宋体;"></span>
        							</p>
        						</td>
        						<td colspan="2" style="border:solid windowtext 1.0pt;" width="104">
        							<p class="MsoNormal" style="margin-left:-2.4pt;">
        								<span style="font-family:宋体;">&nbsp;</span>
        							</p>
        						</td>
        						<td colspan="3" style="border:solid windowtext 1.0pt;" width="77">
        							<p class="MsoNormal" style="margin-left:-2.4pt;">
        								<span style="font-family:宋体;padding-left: 10px">右</span><span
        									style="font-family:宋体;"></span>
        							</p>
        						</td>
        						<td colspan="3" style="border:solid windowtext 1.0pt;" width="147">
        							<p class="MsoNormal" style="margin-left:-2.4pt;">
        								<span style="font-family:宋体;">&nbsp;</span>
        							</p>
        						</td>
        					</tr>
        					<tr>
        						<td colspan="2" style="border:solid windowtext 1.0pt;" width="62">
        							<p class="MsoNormal" style="margin-left:-2.4pt;">
        								<span style="font-family:宋体;padding-left: 10px">辨色力</span><span
        									style="font-family:宋体;"></span>
        							</p>
        						</td>
        						<td colspan="5" style="border:solid windowtext 1.0pt;" width="206">
        							<p class="MsoNormal" style="margin-left:-2.4pt;">
        								<span style="font-family:宋体;">&nbsp;</span>
        							</p>
        						</td>
        						<td colspan="3" style="border:solid windowtext 1.0pt;" width="77">
        							<p class="MsoNormal" style="margin-left:-2.4pt;">
        								<span style="font-family:宋体;padding-left: 10px">医师签名</span><span
        									style="font-family:宋体;"></span>
        							</p>
        						</td>
        						<td colspan="3" style="border:solid windowtext 1.0pt;" width="147">
        							<p class="MsoNormal" style="margin-left:-2.4pt;">
        								<span style="font-family:宋体;">&nbsp;</span>
        							</p>
        						</td>
        					</tr>
        					<tr>
        						<td style="border:solid windowtext 1.0pt;" width="73">
        							<p class="MsoNormal" style="text-align:center;" align="center">
        								<span style="font-family:宋体;">摄影检查</span><span style="font-family:宋体;"></span>
        							</p>
        							<p class="MsoNormal" style="text-align:center;" align="center">
        								<span style="font-family:宋体;">胸部</span><span style="font-family:宋体;">x<span>射线</span></span>
        							</p>
        						</td>
        						<td colspan="13" style="border:solid windowtext 1.0pt;" width="491">
        							<p class="MsoNormal" style="margin-left:-2.4pt;">
        								<span style="font-family:宋体;">&nbsp;</span>
        							</p>
        							<p class="MsoNormal">
        								<span style="font-family:宋体;">&nbsp;</span>
        							</p>
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-indent:210.0pt;">
        								<span style="font-family:宋体;">医师签名</span><span style="font-family:宋体;">:</span>
        							</p>
        						</td>
        					</tr>
        					<tr>
        						<td rowspan="7" style="border:solid windowtext 1.0pt;" width="73">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">实 化</span><span style="font-family:宋体;"></span>
        							</p>
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">验 验</span><span style="font-family:宋体;"></span>
        							</p>
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">室 单</span><span style="font-family:宋体;"></span>
        							</p>
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">检 附</span><span style="font-family:宋体;"></span>
        							</p>
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">查 后</span><span style="font-family:宋体;"></span>
        							</p>
        						</td>
        						<td colspan="4" style="border:solid windowtext 1.0pt;" width="137">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">检</span><span style="font-family:宋体;"><span>&nbsp;
        									</span><span>查</span><span>&nbsp; </span><span>项</span><span>&nbsp;
        									</span><span>目</span></span>
        							</p>
        						</td>
        						<td colspan="6" style="border:solid windowtext 1.0pt;" width="208">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">单</span><span style="font-family:宋体;"><span>&nbsp;
        									</span><span>位</span><span>&nbsp; </span><span>结</span><span>&nbsp;
        									</span><span>果</span></span>
        							</p>
        						</td>
        						<td colspan="3" style="border:solid windowtext 1.0pt;" width="147">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">检验师签名</span><span style="font-family:宋体;"></span>
        							</p>
        						</td>
        					</tr>
        					<tr>
        						<td rowspan="2" style="border:solid windowtext 1.0pt;" width="38">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">大便</span><span style="font-family:宋体;"></span>
        							</p>
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">培养</span><span style="font-family:宋体;"></span>
        							</p>
        						</td>
        						<td colspan="3" style="border:solid windowtext 1.0pt;" width="99">
        							<p class="MsoNormal" style="margin-left:-2.4pt;">
        								<span style="font-family:宋体;padding-left: 10px">痢疾杆菌</span><span
        									style="font-family:宋体;"></span>
        							</p>
        						</td>
        						<td colspan="6" style="border:solid windowtext 1.0pt;" width="208">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">&nbsp;</span>
        							</p>
        						</td>
        						<td colspan="3" rowspan="2" style="border:solid windowtext 1.0pt;" width="147">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">&nbsp;</span>
        							</p>
        						</td>
        					</tr>
        					<tr>
        						<td colspan="3" style="border:solid windowtext 1.0pt;" width="99">
        							<p class="MsoNormal" style="margin-left:-2.4pt;">
        								<span style="font-family:宋体;padding-left: 10px">伤寒或副伤寒</span><span
        									style="font-family:宋体;"></span>
        							</p>
        						</td>
        						<td colspan="6" style="border:solid windowtext 1.0pt;" width="208">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">&nbsp;</span>
        							</p>
        						</td>
        					</tr>
        					<tr>
        						<td rowspan="3" style="border:solid windowtext 1.0pt;" width="38">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">肝</span><span style="font-family:宋体;"></span>
        							</p>
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">功</span><span style="font-family:宋体;"></span>
        							</p>
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">能</span><span style="font-family:宋体;"></span>
        							</p>
        						</td>
        						<td colspan="3" style="border:solid windowtext 1.0pt;" width="99">
        							<p class="MsoNormal" style="margin-left:-2.4pt;">
        								<span style="font-family:宋体;padding-left: 10px">谷丙转氨酶</span><span
        									style="font-family:宋体;"></span>
        							</p>
        						</td>
        						<td colspan="6" style="border:solid windowtext 1.0pt;" width="208">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">&nbsp;</span>
        							</p>
        						</td>
        						<td colspan="3" style="border:solid windowtext 1.0pt;" width="147">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">&nbsp;</span>
        							</p>
        						</td>
        					</tr>
        					<tr>
        						<td colspan="3" style="border:solid windowtext 1.0pt;" width="99">
        							<p class="MsoNormal" style="margin-left:-2.4pt;">
        								<span style="font-family:宋体;padding-left: 10px">HAV-IgM </span><sup><span
        										style="font-size:14.0pt;font-family:宋体;">*</span></sup><sup><span
        										style="font-family:宋体;"></span></sup>
        							</p>
        						</td>
        						<td colspan="6" style="border:solid windowtext 1.0pt;" width="208">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">&nbsp;</span>
        							</p>
        						</td>
        						<td colspan="3" style="border:solid windowtext 1.0pt;" width="147">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">&nbsp;</span>
        							</p>
        						</td>
        					</tr>
        					<tr>
        						<td colspan="3" style="border:solid windowtext 1.0pt;" width="99">
        							<p class="MsoNormal" style="margin-left:-2.4pt;">
        								<span style="font-family:宋体;padding-left: 10px">HEV-IgM </span><sup><span
        										style="font-size:14.0pt;font-family:宋体;">*</span></sup><sup><span
        										style="font-family:宋体;"></span></sup>
        							</p>
        						</td>
        						<td colspan="6" style="border:solid windowtext 1.0pt;" width="208">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">&nbsp;</span>
        							</p>
        						</td>
        						<td colspan="3" style="border:solid windowtext 1.0pt;" width="147">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">&nbsp;</span>
        							</p>
        						</td>
        					</tr>
        					<tr>
        						<td colspan="4" style="border:solid windowtext 1.0pt;" width="137">
        							<p class="MsoNormal" style="margin-left:-2.4pt;">
        								<span style="font-family:宋体;padding-left: 10px">其它</span><span
        									style="font-family:宋体;"></span>
        							</p>
        						</td>
        						<td colspan="6" style="border:solid windowtext 1.0pt;" width="208">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">&nbsp;</span>
        							</p>
        						</td>
        						<td colspan="3" style="border:solid windowtext 1.0pt;" width="147">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">&nbsp;</span>
        							</p>
        						</td>
        					</tr>
        					<tr>
        						<td colspan="14" style="border:solid windowtext 1.0pt;" width="565" valign="top">
        							<p class="MsoNormal" style="margin-left:.85pt;text-indent:5.25pt;">
        								<span style="font-family:宋体;">检查结论</span><span style="font-family:宋体;">:</span>
        							</p>
        							<p class="MsoNormal" style="margin-left:.85pt;">
        								<span style="font-family:宋体;">&nbsp;</span>
        							</p>
        							<p class="MsoNormal" style="margin-left:.85pt;text-indent:194.25pt;">
        								<span style="font-family:宋体;">主检医师签名</span>
        								<span style="font-family:宋体;">:
        									<span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>
        									<span>(公章)</span>
        								</span>
        							</p>
        							<p class="MsoNormal" style="margin-left:111.7pt;">
        								<span style="font-family:宋体;">
        									<span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>
        									<span>年</span><span>&nbsp;&nbsp;
        									</span><span>月</span><span>&nbsp;&nbsp;</span><span>日</span>
        								</span>
        							</p>
        						</td>
        					</tr>
        					<tr>
        						<td colspan="14" style="border-bottom:none; border-left: none;border-right: none;" width="565"
        							valign="middle">
        							<span style="padding-left:10px;font-size:14.0pt;font-family:仿宋_GB2312; color: red; height: 60px; display: inline-block; line-height:60px">
        								*说明：发现谷丙转氨酶异常的，加做
        								<span style="font-size:14.0pt;font-family:宋体;">HAV-IgM、HEV-IgM两个指标。</span>
        							</span>
        						</td>
        					</tr>
        				</tbody>
        			</table>
        		</div>
EOF;
        return $js;
    }
    public function get_html($print)
    {
        return <<<EOF
        	<!DOCTYPE html>
        	<html>
        	<head>
        		<script language="javascript" src="http://39.100.89.92:8080/LodopFuncs.js"></script>
        		<script src="https://cdn.bootcss.com/jquery/3.4.1/jquery.js"></script>
        		<script>
        			$(document).ready(function () {
        				$("#print").click(function () {
        					setTimeout("print()",500);//延时3秒
        				})
        				$("#print").trigger("click");
        			})
        			function print() {
        				LODOP = getLodop();
        				LODOP.PRINT_INITA(9, 0, 794, 1122, "打印控件功能演示_Lodop功能_在线编辑获得程序代码");
        				LODOP.SET_PRINT_MODE("PRINT_NOCOLLATE", 1);
        				LODOP.ADD_PRINT_TEXT(43, 150, 465, 45, "河北省食品药品从业人员健康检查表");
        				LODOP.SET_PRINT_STYLEA(0, "FontName", "黑体");
        				LODOP.SET_PRINT_STYLEA(0, "FontSize", 20);
        				LODOP.SET_PRINT_STYLEA(0, "Alignment", 2);
        				LODOP.ADD_PRINT_SHAPE(4, 150, 46, 702, 2, 0, 1, "#000000");
        				LODOP.ADD_PRINT_TEXT(122, 50, 79, 26, "体检日期: ");
        				LODOP.SET_PRINT_STYLEA(0, "FontSize", 11);
        				LODOP.ADD_PRINT_TEXT(122, 570, 160, 26, "编号：{$print['order_serial_number']}");
        				LODOP.SET_PRINT_STYLEA(0, "FontSize", 12);
        				LODOP.ADD_PRINT_TEXT(122, 140, 170, 26, "{$print['time']}");
        				LODOP.SET_PRINT_STYLEA(0, "FontSize", 12);

                        LODOP.ADD_PRINT_TEXT(170, 70, 50, 26, "姓名:");
                        LODOP.SET_PRINT_STYLEA(0, "FontSize", 12);
                        LODOP.ADD_PRINT_TEXT(170, 120, 60, 26, "{$print['name']}");
                        LODOP.SET_PRINT_STYLEA(0, "FontSize", 12);
                        LODOP.ADD_PRINT_TEXT(170, 240, 60, 26, "性别:");
                        LODOP.SET_PRINT_STYLEA(0, "FontSize", 12);
                        LODOP.ADD_PRINT_TEXT(170, 300, 60, 26, "{$print['sex']}");
                        LODOP.SET_PRINT_STYLEA(0, "FontSize", 12);
                        LODOP.ADD_PRINT_TEXT(170, 400, 60, 26, "年龄");
                        LODOP.SET_PRINT_STYLEA(0, "FontSize", 12);
                        LODOP.ADD_PRINT_TEXT(170, 460, 60, 26, "{$print['age']}");
                        LODOP.SET_PRINT_STYLEA(0, "FontSize", 12);
                
                        LODOP.ADD_PRINT_TEXT(210, 70, 80, 26, "从业类别");
                        LODOP.SET_PRINT_STYLEA(0, "FontSize", 12);
                        LODOP.ADD_PRINT_TEXT(210, 150, 80, 26, "{$print['employee']}");
                        LODOP.SET_PRINT_STYLEA(0, "FontSize", 12);
                        LODOP.ADD_PRINT_TEXT(210, 300, 80, 26, "体检单位");
                        LODOP.SET_PRINT_STYLEA(0, "FontSize", 12);
                        LODOP.ADD_PRINT_TEXT(210, 380, 80, 26, "{$print['company']}");
                        LODOP.SET_PRINT_STYLEA(0, "FontSize", 12);
                
                        LODOP.ADD_PRINT_TEXT(250, 70, 80, 26, "身份证号");
                        LODOP.SET_PRINT_STYLEA(0, "FontSize", 12);
                        LODOP.ADD_PRINT_TEXT(250, 150, 200, 26, "{$print['identitycard']}");
                        LODOP.SET_PRINT_STYLEA(0, "FontSize", 12);



        				LODOP.ADD_PRINT_IMAGE(160, 600, 102, 126, "<img src=\"data:image/jpeg;base64,{$print['images']}\"/>");
        				LODOP.SET_PRINT_STYLEA(0, "TransColor", "#0F0100");
        				LODOP.ADD_PRINT_TABLE(290, 56, 680, 760, document.getElementById("print_8").innerHTML);

		                if (LODOP.SET_PRINTER_INDEX({$print['print_form_id']}))
        				LODOP.PREVIEW();
        			}
        		</script>
        	</head>
        	
        	<body>
        		<button id="print" style="display:none">打印文件</button>
        		<div id="print_8" style="display:none">
        			<table class="MsoNormalTable" width="670" style="border-collapse:collapse;border:none;" cellspacing="0"	cellpadding="0" border="1">
        				<tbody>
        					<tr>
        						<td rowspan="2" style="border:solid windowtext 1.0pt;" width="73">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<b><span style="font-family:宋体;">既往</span></b><b><span style="font-family:宋体;"></span></b>
        							</p>
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<b><span style="font-family:宋体;">病史</span></b><b><span style="font-family:宋体;"></span></b>
        							</p>
        						</td>
        						<td colspan="3" style="border:solid windowtext 1.0pt;" width="66">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<b><span style="font-family:宋体;">病</span></b><b><span style="font-family:宋体;"><span>&nbsp;
        										</span><span>名</span></span></b>
        							</p>
        						</td>
        						<td style="border:solid windowtext 1.0pt;" width="71">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<b><span style="font-family:宋体;">肝</span></b><b><span style="font-family:宋体;"><span>&nbsp;
        										</span><span>类</span></span></b>
        							</p>
        						</td>
        						<td colspan="2" style="border:solid windowtext 1.0pt;" width="71">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<b><span style="font-family:宋体;">痢</span></b><b><span style="font-family:宋体;"><span>&nbsp;
        										</span><span>疾</span></span></b>
        							</p>
        						</td>
        						<td colspan="2" style="border:solid windowtext 1.0pt;" width="71">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<b><span style="font-family:宋体;">伤</span></b><b><span style="font-family:宋体;"><span>&nbsp;
        										</span><span>寒</span></span></b>
        							</p>
        						</td>
        						<td colspan="3" style="border:solid windowtext 1.0pt;" width="71">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<b><span style="font-family:宋体;">肺结核</span></b><b><span style="font-family:宋体;"></span></b>
        							</p>
        						</td>
        						<td style="border:solid windowtext 1.0pt;" width="71">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<b><span style="font-family:宋体;">皮肤病</span></b><b><span style="font-family:宋体;"></span></b>
        							</p>
        						</td>
        						<td style="border:solid windowtext 1.0pt;" width="71">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<b><span style="font-family:宋体;">其</span></b><b><span style="font-family:宋体;"><span>&nbsp;
        										</span><span>它</span></span></b>
        							</p>
        						</td>
        					</tr>
        					<tr>
        						<td colspan="3" style="border:solid windowtext 1.0pt;" width="66">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">患病时间</span><span style="font-family:宋体;"></span>
        							</p>
        						</td>
        						<td style="border:solid windowtext 1.0pt;" width="71">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">&nbsp;</span>
        							</p>
        						</td>
        						<td colspan="2" style="border:solid windowtext 1.0pt;" width="71">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">&nbsp;</span>
        							</p>
        						</td>
        						<td colspan="2" style="border:solid windowtext 1.0pt;" width="71">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">&nbsp;</span>
        							</p>
        						</td>
        						<td colspan="3" style="border:solid windowtext 1.0pt;" width="71">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">&nbsp;</span>
        							</p>
        						</td>
        						<td style="border:solid windowtext 1.0pt;" width="71">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">&nbsp;</span>
        							</p>
        						</td>
        						<td style="border:solid windowtext 1.0pt;" width="71">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">&nbsp;</span>
        							</p>
        						</td>
        					</tr>
        					<tr>
        						<td rowspan="4" style="border:solid windowtext 1.0pt;" width="73">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">体征</span><span style="font-family:宋体;"></span>
        							</p>
        						</td>
        						<td style="border:solid windowtext 1.0pt;" width="38">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">心</span><span style="font-family:宋体;"></span>
        							</p>
        						</td>
        						<td colspan="6" style="border:solid windowtext 1.0pt;" width="230">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">&nbsp;</span>
        							</p>
        						</td>
        						<td colspan="2" style="border:solid windowtext 1.0pt;" width="41">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">肝</span><span style="font-family:宋体;"></span>
        							</p>
        						</td>
        						<td colspan="4" style="border:solid windowtext 1.0pt;" width="182">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">&nbsp;</span>
        							</p>
        						</td>
        					</tr>
        					<tr>
        						<td style="border:solid windowtext 1.0pt;" width="38">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">肺</span><span style="font-family:宋体;"></span>
        							</p>
        						</td>
        						<td colspan="6" style="border:solid windowtext 1.0pt;" width="230">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">&nbsp;</span>
        							</p>
        						</td>
        						<td colspan="2" style="border:solid windowtext 1.0pt;" width="41">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">脾</span><span style="font-family:宋体;"></span>
        							</p>
        						</td>
        						<td colspan="4" style="border:solid windowtext 1.0pt;" width="182">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">&nbsp;</span>
        							</p>
        						</td>
        					</tr>
        					<tr>
        						<td style="border:solid windowtext 1.0pt;" width="38">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">皮肤</span><span style="font-family:宋体;"></span>
        							</p>
        						</td>
        						<td colspan="12" style="border:solid windowtext 1.0pt;" width="454">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">手癣 指甲癣 手部湿疹 银屑</span><span
        									style="font-family:宋体;">(<span>或鳞屑</span>)<span>病 渗出性皮肤病 化脓性皮肤病</span></span>
        							</p>
        						</td>
        					</tr>
        					<tr>
        						<td style="border:solid windowtext 1.0pt;" width="38">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">其它</span><span style="font-family:宋体;"></span>
        							</p>
        						</td>
        						<td colspan="6" style="border:solid windowtext 1.0pt;" width="230">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">&nbsp;</span>
        							</p>
        						</td>
        						<td colspan="3" style="border:solid windowtext 1.0pt;" width="77">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">医师签名</span><span style="font-family:宋体;"></span>
        							</p>
        						</td>
        						<td colspan="3" style="border:solid windowtext 1.0pt;" width="147">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">&nbsp;</span>
        							</p>
        						</td>
        					</tr>
        					<tr>
        						<td rowspan="2" style="border:solid windowtext 1.0pt;" width="73">
        							<p class="MsoNormal" style="font-size: 14px;">
        								视力及辨色力<br>（直接接触药品质量检验、验收、养护人员）
        							</p>
        							<p class="MsoNormal">
        							</p>
        						</td>
        						<td colspan="2" style="border:solid windowtext 1.0pt;" width="62">
        							<p class="MsoNormal" style="margin-left:-2.4pt;">
        								<span style="font-family:宋体; padding-left: 10px">视力</span>
                                        <span style="font-family:宋体;"></span>
        							</p>
        							
        						</td>
        						<td colspan="3" style="border:solid windowtext 1.0pt;" width="102">
        							<p class="MsoNormal" style="margin-left:-2.4pt;">
        								<span style="font-family:宋体;padding-left: 10px">左</span>
        								<span style="font-family:宋体;"></span>
        							</p>
        						</td>
        						<td colspan="2" style="border:solid windowtext 1.0pt;" width="104">
        							<p class="MsoNormal" style="margin-left:-2.4pt;">
        								<span style="font-family:宋体;">&nbsp;</span>
        							</p>
        						</td>
        						<td colspan="3" style="border:solid windowtext 1.0pt;" width="77">
        							<p class="MsoNormal" style="margin-left:-2.4pt;">
        								<span style="font-family:宋体;padding-left: 10px">右</span><span
        									style="font-family:宋体;"></span>
        							</p>
        						</td>
        						<td colspan="3" style="border:solid windowtext 1.0pt;" width="147">
        							<p class="MsoNormal" style="margin-left:-2.4pt;">
        								<span style="font-family:宋体;">&nbsp;</span>
        							</p>
        						</td>
        					</tr>
        					<tr>
        						<td colspan="2" style="border:solid windowtext 1.0pt;" width="62">
        							<p class="MsoNormal" style="margin-left:-2.4pt;">
        								<span style="font-family:宋体;padding-left: 10px">辨色力</span><span
        									style="font-family:宋体;"></span>
        							</p>
        						</td>
        						<td colspan="5" style="border:solid windowtext 1.0pt;" width="206">
        							<p class="MsoNormal" style="margin-left:-2.4pt;">
        								<span style="font-family:宋体;">&nbsp;</span>
        							</p>
        						</td>
        						<td colspan="3" style="border:solid windowtext 1.0pt;" width="77">
        							<p class="MsoNormal" style="margin-left:-2.4pt;">
        								<span style="font-family:宋体;padding-left: 10px">医师签名</span><span
        									style="font-family:宋体;"></span>
        							</p>
        						</td>
        						<td colspan="3" style="border:solid windowtext 1.0pt;" width="147">
        							<p class="MsoNormal" style="margin-left:-2.4pt;">
        								<span style="font-family:宋体;">&nbsp;</span>
        							</p>
        						</td>
        					</tr>
        					<tr>
        						<td style="border:solid windowtext 1.0pt;" width="73">
        							<p class="MsoNormal" style="text-align:center;" align="center">
        								<span style="font-family:宋体;">摄影检查</span><span style="font-family:宋体;"></span>
        							</p>
        							<p class="MsoNormal" style="text-align:center;" align="center">
        								<span style="font-family:宋体;">胸部</span><span style="font-family:宋体;">x<span>射线</span></span>
        							</p>
        						</td>
        						<td colspan="13" style="border:solid windowtext 1.0pt;" width="491">
        							<p class="MsoNormal" style="margin-left:-2.4pt;">
        								<span style="font-family:宋体;">&nbsp;</span>
        							</p>
        							<p class="MsoNormal">
        								<span style="font-family:宋体;">&nbsp;</span>
        							</p>
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-indent:210.0pt;">
        								<span style="font-family:宋体;">医师签名</span><span style="font-family:宋体;">:</span>
        							</p>
        						</td>
        					</tr>
        					<tr>
        						<td rowspan="7" style="border:solid windowtext 1.0pt;" width="73">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">实 化</span><span style="font-family:宋体;"></span>
        							</p>
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">验 验</span><span style="font-family:宋体;"></span>
        							</p>
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">室 单</span><span style="font-family:宋体;"></span>
        							</p>
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">检 附</span><span style="font-family:宋体;"></span>
        							</p>
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">查 后</span><span style="font-family:宋体;"></span>
        							</p>
        						</td>
        						<td colspan="4" style="border:solid windowtext 1.0pt;" width="137">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">检</span><span style="font-family:宋体;"><span>&nbsp;
        									</span><span>查</span><span>&nbsp; </span><span>项</span><span>&nbsp;
        									</span><span>目</span></span>
        							</p>
        						</td>
        						<td colspan="6" style="border:solid windowtext 1.0pt;" width="208">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">单</span><span style="font-family:宋体;"><span>&nbsp;
        									</span><span>位</span><span>&nbsp; </span><span>结</span><span>&nbsp;
        									</span><span>果</span></span>
        							</p>
        						</td>
        						<td colspan="3" style="border:solid windowtext 1.0pt;" width="147">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">检验师签名</span><span style="font-family:宋体;"></span>
        							</p>
        						</td>
        					</tr>
        					<tr>
        						<td rowspan="2" style="border:solid windowtext 1.0pt;" width="38">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">大便</span><span style="font-family:宋体;"></span>
        							</p>
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">培养</span><span style="font-family:宋体;"></span>
        							</p>
        						</td>
        						<td colspan="3" style="border:solid windowtext 1.0pt;" width="99">
        							<p class="MsoNormal" style="margin-left:-2.4pt;">
        								<span style="font-family:宋体;padding-left: 10px">痢疾杆菌</span><span
        									style="font-family:宋体;"></span>
        							</p>
        						</td>
        						<td colspan="6" style="border:solid windowtext 1.0pt;" width="208">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">&nbsp;</span>
        							</p>
        						</td>
        						<td colspan="3" rowspan="2" style="border:solid windowtext 1.0pt;" width="147">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">&nbsp;</span>
        							</p>
        						</td>
        					</tr>
        					<tr>
        						<td colspan="3" style="border:solid windowtext 1.0pt;" width="99">
        							<p class="MsoNormal" style="margin-left:-2.4pt;">
        								<span style="font-family:宋体;padding-left: 10px">伤寒或副伤寒</span><span
        									style="font-family:宋体;"></span>
        							</p>
        						</td>
        						<td colspan="6" style="border:solid windowtext 1.0pt;" width="208">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">&nbsp;</span>
        							</p>
        						</td>
        					</tr>
        					<tr>
        						<td rowspan="3" style="border:solid windowtext 1.0pt;" width="38">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">肝</span><span style="font-family:宋体;"></span>
        							</p>
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">功</span><span style="font-family:宋体;"></span>
        							</p>
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">能</span><span style="font-family:宋体;"></span>
        							</p>
        						</td>
        						<td colspan="3" style="border:solid windowtext 1.0pt;" width="99">
        							<p class="MsoNormal" style="margin-left:-2.4pt;">
        								<span style="font-family:宋体;padding-left: 10px">谷丙转氨酶</span><span
        									style="font-family:宋体;"></span>
        							</p>
        						</td>
        						<td colspan="6" style="border:solid windowtext 1.0pt;" width="208">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">&nbsp;</span>
        							</p>
        						</td>
        						<td colspan="3" style="border:solid windowtext 1.0pt;" width="147">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">&nbsp;</span>
        							</p>
        						</td>
        					</tr>
        					<tr>
        						<td colspan="3" style="border:solid windowtext 1.0pt;" width="99">
        							<p class="MsoNormal" style="margin-left:-2.4pt;">
        								<span style="font-family:宋体;padding-left: 10px">HAV-IgM </span><sup><span
        										style="font-size:14.0pt;font-family:宋体;">*</span></sup><sup><span
        										style="font-family:宋体;"></span></sup>
        							</p>
        						</td>
        						<td colspan="6" style="border:solid windowtext 1.0pt;" width="208">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">&nbsp;</span>
        							</p>
        						</td>
        						<td colspan="3" style="border:solid windowtext 1.0pt;" width="147">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">&nbsp;</span>
        							</p>
        						</td>
        					</tr>
        					<tr>
        						<td colspan="3" style="border:solid windowtext 1.0pt;" width="99">
        							<p class="MsoNormal" style="margin-left:-2.4pt;">
        								<span style="font-family:宋体;padding-left: 10px">HEV-IgM </span><sup><span
        										style="font-size:14.0pt;font-family:宋体;">*</span></sup><sup><span
        										style="font-family:宋体;"></span></sup>
        							</p>
        						</td>
        						<td colspan="6" style="border:solid windowtext 1.0pt;" width="208">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">&nbsp;</span>
        							</p>
        						</td>
        						<td colspan="3" style="border:solid windowtext 1.0pt;" width="147">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">&nbsp;</span>
        							</p>
        						</td>
        					</tr>
        					<tr>
        						<td colspan="4" style="border:solid windowtext 1.0pt;" width="137">
        							<p class="MsoNormal" style="margin-left:-2.4pt;">
        								<span style="font-family:宋体;padding-left: 10px">其它</span><span
        									style="font-family:宋体;"></span>
        							</p>
        						</td>
        						<td colspan="6" style="border:solid windowtext 1.0pt;" width="208">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">&nbsp;</span>
        							</p>
        						</td>
        						<td colspan="3" style="border:solid windowtext 1.0pt;" width="147">
        							<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
        								<span style="font-family:宋体;">&nbsp;</span>
        							</p>
        						</td>
        					</tr>
        					<tr>
        						<td colspan="14" style="border:solid windowtext 1.0pt;" width="565" valign="top">
        							<p class="MsoNormal" style="margin-left:.85pt;text-indent:5.25pt;">
        								<span style="font-family:宋体;">检查结论</span><span style="font-family:宋体;">:</span>
        							</p>
        							<p class="MsoNormal" style="margin-left:.85pt;">
        								<span style="font-family:宋体;">&nbsp;</span>
        							</p>
        							<p class="MsoNormal" style="margin-left:.85pt;text-indent:194.25pt;">
        								<span style="font-family:宋体;">主检医师签名</span>
        								<span style="font-family:宋体;">:
        									<span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>
        									<span>(公章)</span>
        								</span>
        							</p>
        							<p class="MsoNormal" style="margin-left:111.7pt;">
        								<span style="font-family:宋体;">
        									<span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span>
        									<span>年</span><span>&nbsp;&nbsp;
        									</span><span>月</span><span>&nbsp;&nbsp;</span><span>日</span>
        								</span>
        							</p>
        						</td>
        					</tr>
        					<tr>
        						<td colspan="14" style="border-bottom:none; border-left: none;border-right: none;" width="565"
        							valign="middle">
        							<span
        								style="padding-left:10px;font-size:14.0pt;font-family:仿宋_GB2312; color: red; height: 60px; display: inline-block; line-height:60px">
        								*说明：发现谷丙转氨酶异常的，加做
        								<span style="font-size:14.0pt;font-family:宋体;">HAV-IgM、HEV-IgM两个指标。</span>
        							</span>
        						</td>
        					</tr>
        				</tbody>
        			</table>
        		</div>
        	</body>
        	
        	</html>
EOF;
    }

    protected function getPrint($userid)
    {
        $result = db("physical_users")->where("id", "in", $userid)->select();
        foreach ($result as $row => $v){
            $v['sex'] = $v['sex'] == 0 ? "男" : "女";
            $result[$row]['time'] = date("Y年m月d日",time());
        }
        return $result;
    }
}