define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'user/level/index' + location.search,
                    edit_url: 'user/level/edit',
                    table: 'user_level',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'level_id',
                sortName: 'level_id',
                commonSearch: false,
                search: false,
                pagination: false,
                columns: [
                    [
                        {field: 'level_id', title: __('Level_id')},
                        {field: 'name', title: __('Name')},
                        {field: 'icon', title: __('Icon'), events: Table.api.events.image, formatter: Table.api.formatter.image, operate: false},
                        {field: 'background', title: __('Background'), events: Table.api.events.image, formatter: Table.api.formatter.image, operate: false},
                        {field: 'min_amount', title: __('Amount'), formatter: function(value, row){
                                if(row.max_amount == 0){
                                    return value + ' 以上';
                                }else{
                                    return value + ' ~ ' + row.max_amount;
                                }
                            }},
                        {field: 'commission_rate', title: __('Commission_rate'), formatter: function(value){
                                return value + '%';
                            }},
                        {field: 'order_num', title: __('Order_num')},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
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