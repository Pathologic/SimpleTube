[+jquery+]
<link rel="stylesheet" type="text/css" href="[+site_url+]assets/plugins/simpletube/js/easy-ui/themes/bootstrap/easyui.css">
<link rel="stylesheet" type="text/css" href="[+site_url+]assets/plugins/simpletube/js/easy-ui/themes/icon.css">
<script type="text/javascript" src="[+site_url+]assets/plugins/simpletube/js/easy-ui/jquery.easyui.min.js"></script>
<script type="text/javascript" src="[+site_url+]assets/plugins/simpletube/js/easy-ui/plugins/jquery.datagrid-dnd.js"></script>
<script type="text/javascript" src="[+site_url+]assets/plugins/simpletube/js/easy-ui/plugins/jquery.edatagrid.js"></script>
<script type="text/javascript" src="[+site_url+]assets/plugins/simpletube/js/easy-ui/locale/easyui-lang-ru.js"></script>

<style type="text/css">
#SimpleTube .pagination select, #SimpleTube .pagination input {
    width:auto;
    height:auto;
}
#SimpleTube .pagination td {
    vertical-align: middle;
}
.datagrid-view td {
    padding:0 5px;
}
.datagrid-toolbar {
    padding:10px 3px;
}
.datagrid-header-inner td {
    vertical-align: middle;
}
#addVideo {
    padding:0 0 10px;
}
#addVideo a {
    margin-left:10px;
    text-decoration: none;
    display: inline-block;
    background: linear-gradient(to bottom, #ffffff 0px, #e6e6e6 100%) repeat-x;
    border: 1px solid #bbb;
    border-radius: 5px;
    color:#333;
    padding:3px 5px;
}
#addVideo a:hover {
    background: #e6e6e6;
    border: 1px solid #ddd;
    color: #00438a;
}
.datagrid-cell-c1-st_title {
    white-space:normal !important;
}
.datagrid-cell-c1-st_isactive input[type="checkbox"] {
    display: block;
    margin-left: auto;
    margin-right: auto;
}
.row-top td{
    border-top:1px solid red;
    background:#fff;
}
.row-bottom td{
    border-bottom:1px solid red;
    background:#fff;
}
.row-append td{
    border:0;
    background:#FBEC88;
}
</style>
<script type="text/javascript">
var rid = [+id+],
    stGridLoaded = false,
    stOrderBy = 'st_index',
    stOrderDir = 'desc';
(function($){

$.extend($.fn.datagrid.defaults.editors, {
    imageBrowser: {
        thumb_prefix: '',
        init: function(container, options){
            var input = $('<input type="hidden">').appendTo(container);
            var image = $('<a href="javascript:void(0)"><img style="'+options.css+'" src=""></a>').appendTo(container);
            this.thumb_prefix = options.thumb_prefix;
            if (options.browser !== undefined) image.click({target:this,field:input},options.browser);
            return input;
        },
        destroy: function(target){
            $(target).remove();
        },
        getValue: function(target){
            return $(target).val();
        },
        setValue: function(target, value){
            $(target).val(value);
            $(target).parent().find('img').attr('src',(value == '' ? '[+site_url+][+noImage+]' : this.thumb_prefix+value));
        },
        resize: function(target, width){
            return;
        }
    }
});

stGridHelper = {
    sourceRow: {},
    targetRow: {},
    point: '',
    addRow: function () {
        var url = $('input','#addVideo').val();
        if (url != '') {
          $.ajax({
              url:'[+url+]?mode=addRow',
              type: 'post',
              data: {'stUrl':url, 'st_rid':rid}
          }).done(function(response) {
            if (response) {
                response=$.parseJSON(response);
                if (!response.success) {
                    $.messager.alert('Ошибка',response.message);
                } else {
                $('input','#addVideo').val('');
            }
            $('#stGrid').edatagrid('reload');
            }
        })
       }
        return false;
    },
    browse: function(e) {
        var target = e.data.target;
        var field = e.data.field;
        var width = screen.width * 0.5;
        var height = screen.height * 0.5;
        var iLeft = (screen.width  - width) / 2 ;
        var iTop  = (screen.height - height) / 2 ;
        var sOptions = 'toolbar=no,status=no,resizable=yes,dependent=yes' ;
        var url = '[+kcfinder_url+]';
        sOptions += ',width=' + width ;
        sOptions += ',height=' + height ;
        sOptions += ',left=' + iLeft ;
        sOptions += ',top=' + iTop ;
        window.KCFinder = {};
        window.KCFinder = {
            callBack: function(url) {
                window.KCFinder = null;
                target.setValue(field,url);
            }
        };
        var oWindow = window.open(url, 'SimpleTube', sOptions);
    },
    formatTime: function(seconds) {
        if (seconds == 0) return;
        time = new Date(0, 0, 0, 0, 0, seconds, 0);

        hh = time.getHours();
        mm = time.getMinutes();
        ss = time.getSeconds() 

        output = '';
        if (hh != 0) {
            hh = ('0'+hh).slice(-2);
            output = hh+':';
        }
        mm = ('0'+mm).slice(-2);
        output += mm+':';
        output += ('0'+ss).slice(-2);
        return output; 
    },
    updateActions: function(index){
            $('#stGrid').edatagrid('updateRow',{
                index:index,
                row:{}
            });
    },
    editrow: function(target){
        $('#stGrid').edatagrid('beginEdit', this.getRowIndex(target));
    },
    saverow: function(target){
        $('#stGrid').edatagrid('endEdit', this.getRowIndex(target));
    },
    cancelrow:function(target){
        $('#stGrid').edatagrid('cancelEdit', this.getRowIndex(target));
    },
    deleteRow: function (target) {
        $('#stGrid').edatagrid('destroyRow', this.getRowIndex(target));
    },
    getRowIndex: function (target) {
        var tr = $(target).closest('tr.datagrid-row');
        return parseInt(tr.attr('datagrid-row-index'));
    },
    initGrid: function () {
        $('#stGrid').edatagrid({
            url:'[+url+]',
            singleSelect:true,
            destroyUrl:'[+url+]?mode=remove',
            updateUrl:'[+url+]?mode=edit',
            destroyMsg :{confirm:{   // when select a row
                title:'Удаление записи',
                msg:'Вы уверены, что хотите удалить запись?'
                }
            },
            pagination: true,
            fitColumns: true,
            striped: true,
            idField:'st_id',
            scrollbarSize: 0,
            sortName: 'st_index',
            sortOrder: 'DESC',
            queryParams: {st_rid:rid},
    onLoadSuccess: function(){
        $(this).edatagrid('enableDnd');
    },
    onSortColumn: function(sort,order) {
        stOrderBy = sort;
        stOrderDir = order;
    },
    onDestroy: function(index) {
        rows = $(this).edatagrid('getRows');
        m = rows.length;
        from = (stOrderDir == 'asc') ? index : 0;
        to = (stOrderDir == 'asc') ? m : index;
        for (var i = from; i < to; i++) {
            sti = rows[i].st_index;
            $(this).edatagrid('updateRow',{
                index: i,
                row: {
                    st_index: sti-1
                }
            })
       }
    },
    onBeforeDrag: function(row) {
        if (stOrderBy == 'st_index' && !row.editing) {
            $('body').css('overflow-x','hidden');
            $('.datagrid-body').css('overflow-y','hidden');
        } else {
            return false;
        }
    },
    onBeforeDrop: function(targetRow,sourceRow,point) {
        $('body').css('overflow-x','auto');
        $('.datagrid-body').css('overflow-y','auto');
        this.targetRow = targetRow;
        this.targetRow.index = $('#stGrid').edatagrid('getRowIndex',targetRow);
        this.sourceRow = sourceRow;
        this.sourceRow.index = $('#stGrid').edatagrid('getRowIndex',sourceRow);
        this.point = point;
        
    },
    onDrop:function(targetRow,sourceRow,point) {
        src = this.sourceRow.index;
        tgt = this.targetRow.index;

        state = $.data(this, 'datagrid');
        tr = $('tr',state.dc.body2);
        $.ajax({
              url:'[+url+]?mode=reorder',
              type: 'post',
              data: {
                'target':{
                    'st_id':targetRow.st_id,
                    'st_index':targetRow.st_index
                }, 
                'source':{
                    'st_id':sourceRow.st_id,
                    'st_index':sourceRow.st_index
                },
                'point':point, 
                'st_rid':rid,
                'orderDir':stOrderDir
            }
          }).done(function(response) {
            if (response) {
                response=$.parseJSON(response);
                if (!response.success) {
                    $.messager.alert('Ошибка',response.message);
                    $('#stGrid').edatagrid('reload');
                } else { 
                    rows = $('#stGrid').edatagrid('getRows');
                    if (tgt < src) {
                        rows[tgt].st_index = targetRow.st_index;
                        for (var i = tgt;i<=src;i++) {
                            rows[i].st_index = rows[i-1] != undefined ? rows[i-1].st_index - (stOrderDir == 'desc' ? 1 : -1) : rows[i].st_index;
                            $('#stGrid').edatagrid('refreshRow',i);
                        }
                    } else {
                        rows[tgt].st_index = targetRow.st_index;
                        for (var i = tgt;i>=src;i--) {
                            rows[i].st_index = rows[i+1] != undefined ? parseInt(rows[i+1].st_index) + (stOrderDir == 'desc' ? 1 : -1) : rows[i].st_index;
                            $('#stGrid').edatagrid('refreshRow',i);
                        }
                    }
                    tr.addClass('droppable');
                }
            }
        })
    },
    onSelect: function(rowIndex, rowData) {
        $('#stGrid').edatagrid('unselectRow',rowIndex);
    },
    onBeforeEdit:function(index,row){
        row.editing = true;
        stGridHelper.updateActions(index);
    },
    onAfterEdit:function(index,row){
        row.editing = false;
        stGridHelper.updateActions(index);
        state = $.data(this, 'datagrid');
        $('tr',state.dc.body2).addClass('droppable');
    },
    onCancelEdit:function(index,row){
        row.editing = false;
        stGridHelper.updateActions(index);
        state = $.data(this, 'datagrid');
        $('tr',state.dc.body2).addClass('droppable');
    },
    onClickRow: function (row) { 
        row.editing = false;
        $('#stGrid').edatagrid('cancelEdit', row);
    },
    columns:[ [
        {
            field:'st_index',
            title: '#',
            sortable:true,
        },
        {   
            field:'st_id',
            hidden:true
        },
        {
            field:'st_thumbUrl',
            title:'Превью',
            sortable:false,
            align:'center',
            resizable: false,
            width:([+w+]+10),
            fixed: true,
            formatter: function(value,row,index){
                return '<img style="width:[+w+]px;height:[+h+]px;padding:3px 0;" src="'+(value == '' ? '[+site_url+][+noImage+]' : '[+thumb_prefix+]'+value)+'">';
            }, 
            editor: {
                type: 'imageBrowser',
                options: {
                    css: 'height:[+h+]px;width:[+w+]px;padding:3px 0;margin:0 auto;display:block;',
                    thumb_prefix: '[+thumb_prefix+]',
                    browser: stGridHelper.browse
                }
            }
        },
        {
            field:'st_title',
            title:'Название',
            width:200,
            sortable:true,
            editor:{
                type:'textarea'
            }
        },
        {
            field:'st_videoUrl',
            title:'Ссылка',
            width:150,
            sortable:true,
            editor:{
                type:'text'
            }
        },
        {
            field:'st_duration',
            title:'Длина',
            align:'center',
            sortable:true, 
            formatter:function(value,row,index){
                return stGridHelper.formatTime(value);
            }
        },
        {
            field:'st_service',
            title:'Сервис',
            align:'center',
            sortable:true
        },
        {
            field:'st_createdon',
            title:'Добавлено',
            align:'center',
            sortable:true,
            formatter:function(value) {
                sql = value.split(/[- :]/);
                d = new Date(sql[0], sql[1]-1, sql[2], sql[3], sql[4], sql[5]);
                year = d.getFullYear();
                month = d.getMonth()+1;
                day = d.getDate();
                hour = d.getHours();
                min = d.getMinutes();
                return ('0'+day).slice(-2) + '.' + ('0'+month).slice(-2) + '.' + year + '<br>' + ('0'+hour).slice(-2) + ':' + ('0'+min).slice(-2);

            }
        },
        {
            field:'st_isactive',
            title:'Активно',
            align:'center',
            sortable:true,
            width:50,
            fixed:true,
            formatter:function(value){
                if (value == 1) {
                    return 'Да';
                }
                else {
                    return '<span style="color:red;">Нет</span>'
                };            
            },
            editor:{
                type:'checkbox',
                options:{
                    on: 1,
                    off: 0
                }
            }
        },
        {
            field:'action',
            width:40,
            title:'',
            align:'center',
            fixed:true,
            formatter:function(value,row,index){
                if (row.editing){
                    var save = '<a href="javascript:void(0)" onclick="stGridHelper.saverow(this)"><img src="media/style/[+theme+]/images/icons/save.png"></a> ';
                    var cancel = '<a href="javascript:void(0)" onclick="stGridHelper.cancelrow(this)"><img src="media/style/MODxRE/images/icons/delete.png"></a>';
                    return save+cancel;
                } else {
                return '<a href="javascript:void(0)" onclick="stGridHelper.deleteRow(this)" title="Удалить"><img src="media/style/[+theme+]/images/icons/trash.png"></a>';
                }

            }
        }
    ] ]
})
} //end initGrid
}  //end stGridHelper 
$(window).load(function(){
    if ($('#st-tab')) {
    $('#st-tab.selected').trigger('click');    
}
})

$(document).ready(function(){
$('#st-tab').click(function(){
    if (stGridLoaded) {
        $('#stGrid').edatagrid('reload');
        
    } else {
        $('#SimpleTube').append(
            '<div id="addVideo">' +
            '<input name="stUrl"><a href="javascript:void(0)" onclick="stGridHelper.addRow();">Добавить</a>' +
            '</div>' +
            '<table id="stGrid" width="100%"></table>'
            );
        w = $('#SimpleTube').width() - 18;
        stGridHelper.initGrid();
        stGridLoaded = true;
        $('#stGrid').edatagrid('resize',{width:w});
    }
})
})
})(jQuery)
</script>
<div id="SimpleTube" class="tab-page" style="display:none;width:100%;-moz-box-sizing: border-box; box-sizing: border-box;">
<h2 class="tab" id="st-tab">[+tabName+]</h2>
</div>