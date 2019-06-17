define([ 'jquery', 'bootstrap', 'backend', 'table', 'form' ], function($,
		undefined, Backend, Table, Form) {

	var Controller = {
		index : function() {
			// 初始化表格参数配置
			Table.api.init({
				extend : {
					index_url : 'physical/enregister/index',
					add_url : 'physical/enregister/add',
					edit_url : 'physical/enregister/edit',
					del_url : 'physical/enregister/del',
					multi_url : 'physical/enregister',
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
				}, /*
					 * { field : 'operate', title : __('Operate'), table :
					 * table, events : Table.api.events.operate, formatter :
					 * Table.api.formatter.operate }
					 */
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
	// api : {
	// bindevent : function() {
	// Form.api.bindevent($("form[role=form]"));
	// }
	// }
	};
	return Controller;
});