define([ 'jquery', 'bootstrap', 'backend', 'table', 'form' ], function($,
		undefined, Backend, Table, Form) {

	var Controller = {
		index : function() {
			// 初始化表格参数配置
			Table.api.init({
				extend : {
					index_url : 'service/search//index' + location.search,
					add_url : 'service/search//add',
					edit_url : 'service/search//edit',
					del_url : 'service/search//del',
					multi_url : 'service/search//multi',
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
					title : "姓名"
				}, {
					field : 'phone',
					title : '手机号',
					operate : false
				}, {
					field : 'identitycard',
					title : '身份证',
					operate : false
				}, {
					field : 'order.order_serial_number',
					title : '登记号码'
				}, {
					field : 'company',
					title : '行业'
				}, {
					field : 'type',
					title : '体检类别',
					formatter : function(value, row) {
						if (value == 0)
							return "个人";
						if (value == 1)
							return '团体';
						if (value == 2)
							return '临时';
					}
				}, {
					// field : 'order.create_date',
					field : 'registertime',
					title : '体检时间',
					operate : 'RANGE',
					addclass : 'datetimerange',
					formatter : Table.api.formatter.datetime

				}, {
					// field : 'order.create_date',
					field : 'createtime',
					title : '体检时间--',
					operate : 'RANGE',
					addclass : 'datetimerange',
					formatter : Table.api.formatter.datetime

				}, {
					field : 'order.obtain_employ_number',
					title : '健康证号',
					operate : false
				}, {
					field : 'order.order_status',
					title : '体检状态',
					formatter : function(value, row) {
						if (value == 0)
							return "未开始";
						if (value == 1)
							return '已体检';
						if (value == 2)
							return '已出证';
					}
				}, {
					field : 'physical_result',
					title : '体检结果'
				}
				/*
				 * , { field : 'operate', title : __('Operate'), table : table,
				 * events : Table.api.events.operate, formatter :
				 * Table.api.formatter.operate }
				 */] ]
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
			},
		}
	};
	return Controller;
});