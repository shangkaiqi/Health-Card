define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'business/index' + location.search,
                    add_url: 'business/add',
                    edit_url: 'business/edit',
                    del_url: 'business/del',
                    multi_url: 'business/multi',
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
                        {field: 'bs_id', title: __('Id')},
                        {field: 'busisess_name', title: __('Busisess_name')},
                        {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'phone', title: __('Phone')},
                        {field: 'address', title: __('Address')},
                        {field: 'physical_num', title: __('Physical_num')},
                        {field: 'profession', title: __('Profession')},
                        {field: 'area', title: __('Area')},
                        {field: 'charge', title: __('Charge')},
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