define([ 'jquery', 'bootstrap', 'backend', 'table', 'form' ], function($,
		undefined, Backend, Table, Form) {

	var Controller = {
		index : function() {
			// 初始化表格参数配置
			Table.api.init({
				extend : {
					index_url : 'physical/register/index',
					add_url : 'physical/register/add',
					edit_url : 'physical/register/edit',
					del_url : 'physical/register/del',
					multi_url : 'physical/register',
					table : 'physical_users',
				}
			});

			var table = $("#table");

			// 初始化表格
			table.bootstrapTable({
				url : $.fn.bootstrapTable.defaults.extend.index_url,
				pk : 'id',
				sortName : 'user.id',
				columns : [ [ {
					checkbox : true
				}, {
					field : 'id',
					title : 'Id',
					sortable : true
				}, {
					field : 'type',
					title : '类别'
				}, {
					field : 'name',
					title : "姓名"
				}, {
					field : 'identitycard',
					title : '身份证'
				}, {
					field : 'operate',
					title : __('Operate'),
					table : table,
					events : Table.api.events.operate,
//					formatter : Table.api.formatter.operate,
					formatter : function(value, row, index) {
						var that = $.extend({}, this);
						var table = $(that.table).clone(true);
						$(table).data("operate-del", null);
						$(table).data("operate-edit", null);
						that.table = table;
						return Table.api.formatter.operate.call(that,
								value, row, index);
					},					
				    buttons: [
				        {
				            name: 'physical_table',
				            text: __('打印体检表'),
//				            icon: 'fa fa-list',
				            classname: 'btn btn-xs btn-primary  btn-addtabs',
				            url: 'physical/register/physical_table/{ids}',
				        },
				        {
				            name: 'nav_table',
				            text: __('打印引导表'),
//				            icon: 'fa fa-list',
				            classname: 'btn btn-xs btn-primary  btn-addtabs',
				            url: 'physical/register/nav_table/{ids}',
				        }
				    ],
		
					
				}					 
				] ]
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