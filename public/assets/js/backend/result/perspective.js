define([ 'jquery', 'bootstrap', 'backend', 'table', 'form' ], function($,
		undefined, Backend, Table, Form) {

	var Controller = {
		index : function() {
			// 初始化表格参数配置
			Table.api.init({
				extend : {
					index_url : 'result/convenience//index'
							+ location.search,
					add_url : 'result/convenience//add',
					edit_url : 'result/convenience//edit',
					del_url : 'result/convenience//del',
					multi_url : 'result/convenience//multi',
					table : 'physical_users',
				}
			});

			var table = $("#table");

			// 初始化表格
			table.bootstrapTable({
				url : $.fn.bootstrapTable.defaults.extend.index_url,
				pk : 'id',
				sortName : 'id',
				// 禁用默认搜索
				search : false,
				// 启用普通表单搜索
				commonSearch : true,
				// 可以控制是否默认显示搜索单表,false则隐藏,默认为false
				searchFormVisible : true,
				columns : [ [
						{
							checkbox : true
						},
						{
							field : 'id',
							title : 'Id',
							operate : false
						},
						{
							field : 'name',
							title : "姓名",
							operate: 'LIKE %...%', 
							placeholder: '模糊搜索，*表示任意字符'
						},
						{
							field : 'identitycard',
							title : '身份证'
						},
						{
							field : 'sex',
							title : '性别',
							operate : false,
							formatter: Table.api.formatter.label,
							searchList: {1: __('女'), 0: __('男')}
						},
						{
							field : 'age',
							title : '年龄',
							operate : false
						},
						{
							field : 'phone',
							title : '手机号'
						},
						{
							field : 'employee',
							title : '从业类别',
							operate : false
						},
						{
							field : 'order_serial_number',
							title : '登记编号',
							operate : false
						},
						{
							field : 'order.physical_result',
							title : '结果',
							operate : false
						},
						{
							field : 'operate',
							title : __('Operate'),
							table : table,
							events : Table.api.events.operate,
							formatter : function(value, row, index) {
								var that = $.extend({}, this);
								var table = $(that.table).clone(true);
								$(table).data("operate-del", null);
								that.table = table;
								return Table.api.formatter.operate.call(that,
										value, row, index);
							}
						} ] ]
			});

			// 为表格绑定事件
			Table.api.bindevent(table);
		},
		add : function() {
			Controller.api.bindevent();
		},
		edit : function() {
			Controller.api.bindevent();
		},
		withdraw : function() {
			alert("aaaaaa");
			Controller.api.bindevent();
		},
		api : {
			bindevent : function() {
				Form.api.bindevent($("form[role=form]"));
			}
		}
	};
	return Controller;
});