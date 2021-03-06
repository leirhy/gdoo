<style>
.modal-body { overflow:hidden; }
</style>

<div class="wrapper-xs">
    <div id="dialog-promotion-toolbar">
        <form id="dialog-promotion-search-form" name="dialog_promotion_search_form" class="form-inline" method="get">
            @include('searchForm3')
        </form>
    </div>
</div>

<div id="ref_promotion" class="ag-theme-balham" style="width:100%;height:180px;"></div>

<div class="m-t-xs">
    <div id="ref_promotion_data" class="ag-theme-balham" style="width:100%;height:240px;"></div>
</div>

<script>
var $ref_promotion = null;
var $ref_promotion_data = null;
var params = JSON.parse('{{json_encode($query)}}');
(function($) {
    params['master'] = 1;

    var option = gdoo.formKey(params);
    var event = gdoo.event.get(option.key);
    event.trigger('query', params);

    var mGridDiv = document.querySelector("#ref_promotion");
    var mGrid = new agGridOptions();
    mGrid.remoteDataUrl = '{{url()}}';
    mGrid.remoteParams = params;
    mGrid.rowMultiSelectWithClick = false;
    mGrid.rowSelection = 'multiple';
    mGrid.autoColumnsToFit = false;
    mGrid.defaultColDef.suppressMenu = true;
    mGrid.defaultColDef.sortable = false;
    mGrid.columnDefs = [
        {cellClass:'text-center', checkboxSelection: true, headerCheckboxSelection: true, suppressSizeToFit: true, width: 40},
        {cellClass:'text-center', field: 'sn', headerName: '单据编号', minWidth: 160},
        {cellClass:'text-center', field: 'apply_dt', type: 'datetime', headerName: '单据日期', width: 120},
        {cellClass:'text-center', field: 'master_status', cellRenderer: 'htmlCellRenderer', headerName: '状态', width: 160},
        {cellClass:'text-center', field: 'customer_code', headerName: '客户编码', width: 120},
        {field:'customer_name', headerName: '客户名称', width: 160},
        {cellClass:'text-center',field:'region_name', headerName: '销售团队', width: 120},
        {cellClass:'text-right',field:'apply_money', headerName: '申请费用', width: 100},
        {cellClass:'text-right',field:'verification_cost', headerName: '批复费用', width: 100},
        {cellClass:'text-center', field: 'id', headerName: 'ID', width: 60}
    ];

    mGrid.onRowClicked = function(row) {
        var selected = row.node.isSelected();
        if (selected === false) {
            row.node.setSelected(true, true);
        }
        var rows = mGrid.api.getSelectedRows();
        var ids = [];
        for (let i = 0; i < rows.length; i++) {
            ids.push(rows[i].id);
        }
        params.ids = ids;
        sGrid.remoteData(params);
    };

    mGrid.onRowDoubleClicked = function (row) {
        var ret = writeSelected();
        if (ret == true) {
            $('#gdoo-dialog-' + params.dialog_index).dialog('close');
        }
    };

    /**
     * 初始化选择
     */
    function initSelected() {
        if (params.is_grid) {
        } else {
            var rows = {};
            var id = $('#'+option.id).val();
            if (id) {
                var ids = id.split(',');
                for (var i = 0; i < ids.length; i++) {
                    rows[ids[i]] = ids[i];
                }
            }
            mGrid.api.forEachNode(function(node) {
                var key = node.data['id'];
                if (rows[key] != undefined) {
                    node.setSelected(true);
                }
            });
        }
    }

    /**
     * 写入选中
     */
    function writeSelected() {
        var rows = mGrid.api.getSelectedRows();
        if (params.is_grid) {
            var list = gdoo.forms[params.form_id];
            list.api.dialogSelected(params);
        } else {
            var id = [];
            var text = [];
            $.each(rows, function(k, row) {
                id.push(row['id']);
                text.push(row.name);
            });
            $('#'+option.id).val(id.join(','));
            $('#'+option.id+'_text').val(text.join(','));

            if (event.exist('onSelect')) {
                return event.trigger('onSelect', multiple ? rows : rows[0]);
            }
        }
        return true;
    }
    mGrid.writeSelected = writeSelected;
    gdoo.dialogs[option.id] = mGrid;

    new agGrid.Grid(mGridDiv, mGrid);

    // 读取数据
    mGrid.remoteData();
    $ref_promotion = mGrid;

    params['master'] = 0;
    var sGridDiv = document.querySelector("#ref_promotion_data");
    var sGrid = new agGridOptions();
    sGrid.remoteDataUrl = '{{url()}}';
    sGrid.remoteParams = params;
    sGrid.rowSelection = 'multiple';
    sGrid.defaultColDef.suppressMenu = true;
    sGrid.defaultColDef.sortable = false;
    sGrid.suppressRowClickSelection = true;
    sGrid.getRowClass = function(params) {
        params.node.setSelected(true);
    };

    sGrid.columnDefs = [
        {cellClass:'text-center', checkboxSelection: true, headerCheckboxSelection: true, suppressSizeToFit: true, width: 40},
        {cellClass:'text-center', field: 'product_code', headerName: '产品编码', width: 100},
        {field: 'product_name', headerName: '商品名称', minWidth: 180},
        {cellClass:'text-center', field: 'product_spec', headerName: '商品规格', width: 120},
        {cellClass:'text-center', field: 'product_barcode', headerName: '商品条码', width: 140},
        {cellClass:'text-center', field: 'product_unit', headerName: '计量单位', width: 80},
        {cellClass:'text-right', field: 'price', headerName: '单价(元)', width: 80},
        {cellClass:'text-right', field: 'money', headerName: '金额(元)', width: 80},
        {cellClass:'text-center', field: 'id', headerName: 'ID', width: 60}
    ];

    sGrid.onRowClicked = function(row) {
        var selected = row.node.isSelected();
        if (selected === false) {
            row.node.setSelected(true, true);
        }
    };

    new agGrid.Grid(sGridDiv, sGrid);
    // 读取数据
    sGrid.remoteData();
    $ref_promotion_data = sGrid;

    var data = JSON.parse('{{json_encode($search["forms"])}}');
    var search = $('#dialog-promotion-search-form').searchForm({
        data: data,
        init:function(e) {}
    });

    search.find('#search-submit').on('click', function() {
        var query = search.serializeArray();
        $.map(query, function(row) {
            params[row.name] = row.value;
        });

        params['master'] = 1;
        mGrid.remoteData(params);

        params['master'] = 0;
        sGrid.remoteData(params);
        return false;
    });

})(jQuery);
</script>