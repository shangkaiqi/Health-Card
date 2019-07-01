<?php
namespace app\admin\controller;

use app\common\controller\Backend;
require './phpexcel/PHPExcel.php';
class Common extends Backend
{

    protected $noNeedRight = [
        '*'
    ];

    public function _initialize()
    {
        parent::_initialize();
    }

    public function getInspece($parent)
    {
        $in_a = db("inspect")->field("id,name")
            ->where("parent", "=", $parent)
            ->select();
        return $in_a;
    }

    //打印健康证
    public function physical_table(){
        $params = $this->request->get();
        var_dump($params);
        //姓名   性别   从业列表   证号  有效期  体检单位
        
        
    }
    //打印复验单
    public function nav_table(){
        
    }
    public function inspect($type = '')
    {
        $where = array();
        $inspect = array();
        $where['type'] = [
            "eq",
            $type
        ];
        $where['parent'] = [
            "eq",
            0
        ];
        if ($type == '') {
            $inspect = db("inspect")->field("id,name")
                ->where($where)
                ->select();
        } else {
            $inspect = db("inspect")->field("id,name")
                ->where($where)
                ->select();
        }
        $ins = array();
        foreach ($inspect as $key => $val) {
            $in_a = $this->getInspece($val['id']);
            $ins[] = array(
                "name" => $val['name'],
                "value" => $in_a,
                "id" => $val['id']
            );
        }
        return $ins;
    }

    /**
     * 获取待检测信息
     *
     * @return string
     */
    public function wait_physical($uid = '')
    {
        if ($uid == '') {
            return "";
        }
        // 待体检项：
        $result = db('order')->alias('o')
            ->join("order_detail od", "`o`.`order_serial_number` = `od`.`order_serial_number`")
            ->field("physical")
            ->where("user_id", "=", $uid)
            ->select();
        $arr = array();
        foreach ($result as $row) {
            $arr[] = $row['physical'];
        }
        // 体检项：0.血检1.便检2体检3.透视4.视力
        if (! in_array(0, $arr)) {
            $uArr[] = "血检";
        }
        if (! in_array(1, $arr)) {
            $uArr[] = "便检";
        }
        if (! in_array(2, $arr)) {
            $uArr[] = "体检";
        }
        if (! in_array(3, $arr)) {
            $uArr[] = "透视";
        }

        $result = implode(" ", $uArr);
        return $result;
    }

    public function getemployee()
    {
        if ($this->request->isAjax()) {
            $id = $this->request->get("id");
            file_put_contents("comm-id.txt", $id);
            $employee = db("employee")->field("id,pid,name")
                ->where("pid", "=", $id)
                ->select();
            return json($employee);
        } else {
            $employee = db("employee")->field("id,pid,name")
                ->where("pid", "=", 0)
                ->select();
            return $employee;
        }
    }

    /**
     * 获取从业信息
     *
     * @param int $emId
     * @return array|\think\Model
     */
    public function employee($emId)
    {
        $employee = db("employee")->field("name")
            ->where("id", "=", $emId)
            ->find();
        return $employee;
    }

    /**
     * 保存体检信息
     *
     * @param array $params
     * @return boolean
     */
    public function saveOrderDetail($data,$where)
    {        
        $result = $this->orderde->where($where)->update($data);
        return $result;
    }

    /**
     * 获取体结果选项检项
     */
    public function getInspect()
    {
        $id = $this->request->get('id');
        $inspect = array();
        $inspect = array();
        $inspect = db("inspect")->field("id,name")
            ->where('parent', '=', $id)
            ->select();
        return json($inspect);
    }
    
    public function exportExcel($expTitle,$expCellName,$expTableData){
        $xlsTitle = iconv('utf-8', 'gb2312', $expTitle);//文件名称
        $fileName ='usersdd'.date('_YmdHis',time());//or $xlsTitle 文件名称可根据自己情况设定
        $cellNum = count($expCellName);
        $dataNum = count($expTableData);
        //         vendor("PHPExcel");
        
        $objPHPExcel = new \PHPExcel();
        $cellName = array('A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z','AA','AB','AC','AD','AE','AF','AG','AH','AI','AJ','AK','AL','AM','AN','AO','AP','AQ','AR','AS','AT','AU','AV','AW','AX','AY','AZ');
        
        $objPHPExcel->getActiveSheet(0)->mergeCells('A1:'.$cellName[$cellNum-1].'1');//合并单元格
        $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A1', $expTitle.'  Export time:'.date('Y-m-d H:i:s'));
        for($i=0;$i<$cellNum;$i++){
            $objPHPExcel->setActiveSheetIndex(0)->setCellValue($cellName[$i].'2', $expCellName[$i][1]);
        }
        // Miscellaneous glyphs, UTF-8
        for($i=0;$i<$dataNum;$i++){
            for($j=0;$j<$cellNum;$j++){
                $objPHPExcel->getActiveSheet(0)->setCellValue($cellName[$j].($i+3), $expTableData[$i][$expCellName[$j][0]]);
            }
        }
        
        header('pragma:public');
        header('Content-type:application/vnd.ms-excel;charset=GB2312;name="'.$xlsTitle.'.xls"');
        header("Content-Disposition:attachment;filename=$fileName.xls");//attachment新窗口打印inline本窗口打印
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
        $objWriter->save('php://output');
        exit;
    }
    
    public function muilts($type){
        // 根据用户查询属于哪个医院
        $medicine = $db("admin")->alias("a")
            ->join("business b", "b.bs_id=a.businessid")
            ->field("bs_uuid")
            ->where("id", "=", $this->auth->id)
            ->find();
        $where['type'] = $type;
        $where['status'] = 1;
        $where['bus_number'] = $medicine['bs_uuid'];
        $data['physical_result'] = 0;
        $result = db("order_detail")->where($where)->update($data);
    }
}