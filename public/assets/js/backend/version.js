define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'version/index',
                    add_url: 'version/add',
                    edit_url: 'version/edit',
                    del_url: 'version/del',
                    multi_url: 'version/multi',
                    dragsort_url: '',
                    table: 'version',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                sortName: 'id',
                columns: [
                    [
                        {field: 'state', checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'platform', title: __('Platform'),searchList:{'1':__('Android'),'2':__('IOS')},formatter:Table.api.formatter.normal},
                        {field: 'version_code', title: __('Version_code')},
                        {field: 'old_version', title: __('Old_version')},
                        {field: 'new_version', title: __('New_version')},
                        {field: 'package_size', title: __('Package_size')},
                        {field: 'content', title: __('Content')},
                        {field: 'download_url', title: __('Download_url'), formatter: Table.api.formatter.url},
                        {field: 'enforce', title: __('Enforce'), searchList: {'1':__('Yes'),'0':__('No')}, formatter: Table.api.formatter.normal},
                        {field: 'status', title: __('Status'), searchList: {'1':__('Normal'),'0':__('Hidden')}, formatter: Table.api.formatter.status},
                        {field: 'create_time', title: __('Create_time'), formatter: Table.api.formatter.datetime, operate: 'RANGE', addclass: 'datetimerange'},
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