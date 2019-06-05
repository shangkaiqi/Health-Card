define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'business/register/index' + location.search,
                    add_url: 'business/register/add',
                    edit_url: 'business/register/edit',
                    del_url: 'business/register/del',
                    multi_url: 'business/register/multi',
                    table: 'business',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'bs_id',
                sortName: 'bs_id',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'bs_id', title: __('Bs_id')},
                        {field: 'bs_uuid', title: __('Bs_uuid')},
                        {field: 'busisess_name', title: __('Busisess_name')},
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