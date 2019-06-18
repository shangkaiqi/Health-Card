define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'inspectresult/bloodresult//index' + location.search,
                    add_url: 'inspectresult/bloodresult//add',
                    edit_url: 'inspectresult/bloodresult//edit',
                    del_url: 'inspectresult/bloodresult//del',
                    multi_url: 'inspectresult/bloodresult//multi',
                    table: 'physical_users',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: 'Id'},
                        {field: 'name', title: "姓名"},
                        {field: 'identitycard', title:'身份证'},
                        {field: 'sex', title: '性别'},
                        {field: 'age', title: '年龄'},
                        {field: 'phone', title: '手机号'},
                        {field: 'employee', title: '从业类别'},
                        {field: 'order_serial_number', title: '登记编号'},
                        {field: 'order.physical_result', title: '结果'},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        add: function () {
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});