define([ 'jquery', 'bootstrap', 'backend', 'table', 'form' ], function($,
		undefined, Backend, Table, Form) {

	var Controller = {
		index : function() {
			// 初始化表格参数配置
			Table.api.init({
				extend : {
					index_url : 'service/express//index' + location.search,
					add_url : 'service/express//add',
					edit_url : 'service/express//edit',
					del_url : 'service/express//del',
					multi_url : 'service/express//multi',
					table : 'order',
				}
			});

			var table = $("#table");

			// 初始化表格
			table.bootstrapTable({
				url : $.fn.bootstrapTable.defaults.extend.index_url,
				pk : 'id',
				sortName : 'id',
				columns : [ [ {
					checkbox : true
				}, {
					field : 'id',
					title : 'Id'
				}, {
					field : 'name',
					title : "姓名"
				}, {
					field : 'phone',
					title : '手机号'
				}, {
					field : 'identitycard',
					title : '身份证'
				}, {
					field : 'order.order_serial_number',
					title : '状态'
				}, {
					field : 'company',
					title : '行业'
				}, {
					field : 'type',
					title : '体检类别'
				}, {
					field : 'order.create_date',
					title : '体检类别'
				}, {
					field : 'order.obtain_employ_number',
					title : '健康证号'
				}, {
					field : 'order.order_status',
					title : '体检状态'
				}, {
					field : 'physical_result',
					title : '体检結果'
				}
				/*, {
					field : 'operate',
					title : __('Operate'),
					table : table,
					events : Table.api.events.operate,
					formatter : Table.api.formatter.operate
				}*/ ] ]
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
		api : {
			bindevent : function() {
				Form.api.bindevent($("form[role=form]"));
			}
		}
	};
	return Controller;
});