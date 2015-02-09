<script type="text/javascript">
var stConfig = {
    rid:[+id+],
    stGridLoaded:false,
    stOrderBy:'st_index',
    stOrderDir:'desc',
    url:'[+url+]'
};
var stGridColumns = [ [
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
                noImage: '[+site_url+][+noImage+]',
                browserUrl: '[+kcfinder_url+]',
                opener: 'stGrid'
            }
        }
    },
    {
        field:'st_title',
        title:'Название',
        width:200,
        sortable:true,
        formatter: function(value) {
            return value
                    .replace(/&/g, '&amp;')
                    .replace(/>/g, '&gt;')
                    .replace(/</g, '&lt;')
                    .replace(/"/g, '&quot;');
        },
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
            }
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
] ];
(function($){
$(window).load(function(){
    if ($('#st-tab')) {
    $('#st-tab.selected').trigger('click');    
}
});
$(document).ready(function(){
$('#st-tab').click(function(){
    if (stConfig.stGridLoaded) {
        $('#stGrid').edatagrid('reload');
    } else {
        stGridHelper.initGrid();
        stConfig.stGridLoaded = true;
        $(window).trigger('resize'); //stupid hack
    }
})
})
})(jQuery)
</script>
<div id="SimpleTube" class="tab-page" style="width:100%;-moz-box-sizing: border-box; box-sizing: border-box;">
<h2 class="tab" id="st-tab">[+tabName+]</h2>
</div>