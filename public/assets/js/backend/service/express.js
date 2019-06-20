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
				// 禁用默认搜索
				search : false,
				// 启用普通表单搜索
				commonSearch : true,
				// 可以控制是否默认显示搜索单表,false则隐藏,默认为false
				searchFormVisible : true,
				columns : [ [ {
					checkbox : true
				}, {
					field : 'id',
					title : 'Id',
					operate : false
				}, {
					field : 'name',
					title : "姓名",
					Table.api.formatter.search,
					operate: 'LIKE %...%'
				}, {
					field : 'phone',
					title : '手机号',
				}, {
					field : 'order.obtain_employ_number',
					title : '健康正号'
				}, {
					field : 'order.express_status',
					title : '状态',
					formatter : function(value, row) {
						if (value == 0)
							"已下单";
						if (value == 1)
							"代下单";
					}
				}, {
					field : 'order.addr',
					title : '收货地址',
					operate : false
				}, {
					field : 'order.express_num',
					title : '快递单号'
				}, {
					field : 'operate',
					title : __('Operate'),
					table : table,
					events : Table.api.events.operate,
					formatter : Table.api.formatter.operate
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
		api : {
			bindevent : function() {
				Form.api.bindevent($("form[role=form]"));
			}
		}
	};
	return Controller;
});