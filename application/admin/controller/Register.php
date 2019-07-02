<?php
namespace app\admin\controller;

use app\common\controller\Backend;
use app\admin\controller\Common;

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

    // 开关权限开启
    protected $noNeedRight = [
        'index'
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
                // 获取订单最后一条id
                // $orderId = $this->model->order('registertime', 'desc')->find();
                $ordernum = array();
                $ordernum = $result = db('physical_users')->field("order_serial_number")
                    ->where("order_serial_number", "like", date("Ymd", time()) . "%")
                    ->order("registertime desc")
                    ->find();
                if ($ordernum) {
                    $resultNum = $ordernum['order_serial_number'] + 1;
                } else {
                    $resultNum = date("Ymd", time()) . "0001";
                }

                $param['name'] = $params['name'];
                $param['identitycard'] = $params['identitycard'];
                $param['type'] = $params['type'];
                $param['sex'] = $params['sex'];
                $param['age'] = $params['age'];
                $param['phone'] = $params['phone'];
                $param['physictype'] = $params['physictype'];
                $param['express'] = $params['express'];
                $param['employee'] = json_encode(array(
                    $params['parent']
                    // $params['son']
                ));
                $param['company'] = $params['company'];
                $param['order_serial_number'] = $resultNum;
                // $params['bsid'] = $this->auth->id;
                // $result = $this->model->validate("Enregister.add")->save($params);
                $result = $this->model->validate("Register.add")->save($param);

                if (! $result) {
                    $this->error($this->model->getError());
                }
                // 获取医院唯一标识
                $bs_id = db("admin")->alias("a")
                    ->field("b.bs_uuid,b.charge")
                    ->join("business b", "a.businessid = b.bs_id")
                    ->where("id", "=", $this->auth->id)
                    ->find();

                $par['user_id'] = $this->model->id;
                $par['order_serial_number'] = $resultNum;
                $par['bus_number'] = $bs_id['bs_uuid'];
                $par['charge'] = $bs_id['charge'];
                $par['order_status'] = '0';
                $par['obtain_employ_type'] = $param['employee'];
                $par['obtain_employ_number'] = '';
                if ($params['express']) {
                    $par['address'] = $params['address'];
                }
                $order = $this->order->save($par);
                if (! $order) {
                    $this->error($this->model->getError());
                }
                $this->order_detial($resultNum);
                // $this->success("登记成功", "physical/register/index");
                $this->success();
            }
            $this->error();
        }

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
            $params = $this->request->isPost("row/a");
            if ($params) {}
        }
        $this->view->assign("row", $list);
        return $this->view->fetch();
    }

    public function physical_table()
    {
    echo <<<EOF

<script src="http://www.card.com/LodopFuncs.js"></script>
<div id="print">
<p class="MsoNormal">
	<span style="font-size:16.0pt;font-family:仿宋_GB2312;">附件</span><span
		style="font-size:16.0pt;font-family:仿宋_GB2312;">1<span>：</span></span>
</p>
<p class="MsoNormal">
	<span style="font-family:宋体;">&nbsp;</span>
</p>
<p class="MsoNormal">
	<span><span> </span></span>
</p>
<p class="MsoNormal" style="text-indent:27.1pt; width: 900px">
	<b><span style="font-size:18.0pt;font-family:黑体; margin: 0 auto;">河北省食品药品从业人员健康检查表</span></b><b><span
			style="font-size:18.0pt;font-family:黑体;"></span></b>
</p>
<p class="MsoNormal" style="margin-left:.05pt;text-align:center;text-indent:-.15pt;" align="center">
	<span style="font-size:15.0pt;font-family:黑体;">&nbsp;</span>
</p>
<p class="MsoNormal">
	<span style="font-family:宋体;">体检日期：</span><span style="font-family:宋体;"><span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
		</span><span>年</span><span>&nbsp;&nbsp;&nbsp; </span><span>月</span><span>&nbsp;&nbsp;&nbsp;
		</span><span>日</span><span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
		</span><span>编号</span>:<u><span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
			</span></u></span>
</p>
<table class="MsoNormalTable" style="border-collapse:collapse;border:none;width: 900px" cellspacing="0" cellpadding="0" border="1">
	<tbody>
		<tr>
			<td colspan="14" style="border:solid windowtext 1.0pt; vertical-align: middle; line-height: 30px" height="100" valign="top">
				<table width="700" style="float: left;">
					<tr height="30">
						<td>姓名：<span style="text-decoration: underline; font-size: 16px;">aaaaaaaa</span></td>
						<td>性别:<span style="text-decoration: underline; font-size: 16px;">aaaaaaaa</span></td>
						<td>年龄:<span style="text-decoration: underline; font-size: 16px;">aaaaaaaa</span></td></tr>
					<tr height="30">
						<td>从业类别：<span style="text-decoration: underline; font-size: 16px;">aaaaaaaa</span></td>
						<td>体检单位：<span style="text-decoration: underline; font-size: 16px;">aaaaaaaa</span></td></tr>
					<tr height="30"><td>身份证号:<span style="text-decoration: underline; font-size: 16px;">aaaaaaaa</span></td></tr>
				</table>
				<img src="" height="90" width="30" style="float: left;">
			</td>
		</tr>
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
					视力及辨色力<br>
					（直接接触药品质量检验、验收、养护人员）
				</p>
				<p class="MsoNormal">
				</p>
			</td>
			<td colspan="2" style="border:solid windowtext 1.0pt;" width="62">
				<p class="MsoNormal" style="margin-left:-2.4pt;">
					<span style="font-family:宋体; padding-left: 10px">视力</span><span style="font-family:宋体;"></span>
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
					<span style="font-family:宋体;padding-left: 10px">右</span><span style="font-family:宋体;"></span>
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
					<span style="font-family:宋体;padding-left: 10px">辨色力</span><span style="font-family:宋体;"></span>
				</p>
			</td>
			<td colspan="5" style="border:solid windowtext 1.0pt;" width="206">
				<p class="MsoNormal" style="margin-left:-2.4pt;">
					<span style="font-family:宋体;">&nbsp;</span>
				</p>
			</td>
			<td colspan="3" style="border:solid windowtext 1.0pt;" width="77">
				<p class="MsoNormal" style="margin-left:-2.4pt;">
					<span style="font-family:宋体;padding-left: 10px">医师签名</span><span style="font-family:宋体;"></span>
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
						</span><span>查</span><span>&nbsp; </span><span>项</span><span>&nbsp; </span><span>目</span></span>
				</p>
			</td>
			<td colspan="6" style="border:solid windowtext 1.0pt;" width="208">
				<p class="MsoNormal" style="margin-left:-2.4pt;text-align:center;" align="center">
					<span style="font-family:宋体;">单</span><span style="font-family:宋体;"><span>&nbsp;
						</span><span>位</span><span>&nbsp; </span><span>结</span><span>&nbsp; </span><span>果</span></span>
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
					<span style="font-family:宋体;padding-left: 10px">痢疾杆菌</span><span style="font-family:宋体;"></span>
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
					<span style="font-family:宋体;padding-left: 10px">伤寒或副伤寒</span><span style="font-family:宋体;"></span>
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
					<span style="font-family:宋体;padding-left: 10px">谷丙转氨酶</span><span style="font-family:宋体;"></span>
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
					<span style="font-family:宋体;padding-left: 10px">其它</span><span style="font-family:宋体;"></span>
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
					<span style="font-family:宋体;">主检医师签名</span><span
						style="font-family:宋体;">:<span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
						</span>(<span>公章</span>)</span>
				</p>
				<p class="MsoNormal" style="margin-left:111.7pt;">
					<span style="font-family:宋体;"><span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
						</span><span>年</span><span>&nbsp;&nbsp; </span><span>月</span><span>&nbsp;&nbsp;
						</span><span>日</span></span>
				</p>
			</td>
		</tr>
		<tr>
			<td style="border:none;" width="73">
				<br />
			</td>
			<td style="border:none;" width="38">
				<br />
			</td>
			<td style="border:none;" width="24">
				<br />
			</td>
			<td style="border:none;" width="4">
				<br />
			</td>
			<td style="border:none;" width="71">
				<br />
			</td>
			<td style="border:none;" width="27">
				<br />
			</td>
			<td style="border:none;" width="44">
				<br />
			</td>
			<td style="border:none;" width="60">
				<br />
			</td>
			<td style="border:none;" width="11">
				<br />
			</td>
			<td style="border:none;" width="31">
				<br />
			</td>
			<td style="border:none;" width="36">
				<br />
			</td>
			<td style="border:none;" width="5">
				<br />
			</td>
			<td style="border:none;" width="71">
				<br />
			</td>
			<td style="border:none;" width="71">
				<br />
			</td>
		</tr>
	</tbody>
</table>
<p class="MsoNormal">
	<span style="font-size:14.0pt;font-family:仿宋_GB2312;">*<span>说明：发现谷丙转氨酶异常的，加做</span></span><span
		style="font-size:14.0pt;font-family:宋体;">HAV-IgM<span>、</span>HEV-IgM<span>两个指标。</span></span>
</p>
</div>
<style>
tr{
	height: 35px;
}
</style>

		<script src="https://cdn.bootcss.com/jquery/3.4.1/jquery.js"></script>
<script>



    //点击事件
	$(document).ready(function() {
		$("#print").click(function() {
            setTimeout("print()","700");
		});
	   $("#print").trigger('click');
        function print(){
            LODOP=getLodop();         
       		LODOP.PRINT_INIT("");		            
    		LODOP.ADD_PRINT_HTM(0,0,"900px","1000px",document.getElementById("print").value);
            LODOP.PREVIEW();
        }
	});

</script>
EOF;
    }

    public function nav_table()
    {}
}