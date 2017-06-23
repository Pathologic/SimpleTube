<script type="text/javascript">
var stConfig = {
    rid:[+id+],
    stGridLoaded:false,
    url:'[+url+]'
};
var stGridColumns = [ [
    {
        field: 'st_select',
        checkbox:true
    },
    {
        field:'st_index',
        title: '#',
        sortable:true
    },
    {
        field:'st_thumbUrl',
        title:_stLang['preview'],
        sortable:false,
        align:'center',
        resizable: false,
        width:([+w+]),
        fixed: true,
        formatter: function(value){
            return '<img style="width:[+w+]px;height:[+h+]px;padding:3px;" src="'+(value == '' ? '[+site_url+][+noImage+]' : '[+thumb_prefix+]'+value)+'">';
        },
        editor: {
            type: 'imageBrowser',
            options: {
                css: 'height:[+h+]px;width:[+w+]px;padding:3px;margin:0 auto;display:block;',
                thumb_prefix: '[+thumb_prefix+]',
                noImage: '[+site_url+][+noImage+]',
                browserUrl: '[+kcfinder_url+]',
                opener: 'stGrid'
            }
        }
    },
    {
        field:'st_title',
        title:_stLang['title'],
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
            type:'textarea',
            options:{
                height:'auto'
            }
        }
    },
    {
        field:'st_videoUrl',
        title:_stLang['url'],
        width:150,
        sortable:true,
        editor:{
            type:'text'
        }
    },
    {
        field:'st_duration',
        title:_stLang['duration'],
        align:'center',
        sortable:true,
        formatter:function(value){
            return stGridHelper.formatTime(value);
        }
    },
    {
        field:'st_service',
        title:_stLang['service'],
        align:'center',
        sortable:true
    },
    {
        field:'st_createdon',
        title:_stLang['createdon'],
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
        title:_stLang['active'],
        align:'center',
        sortable:true,
        width:50,
        fixed:true,
        formatter:function(value){
            if (value == 1) {
                return _stLang['yes'];
            }
            else {
                return '<span style="color:red;">'+_stLang['no']+'</span>'
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
                var save = '<a class="action save" href="javascript:void(0)" onclick="stGridHelper.saverow('+index+')"><i class="fa fa-save fa-lg"></i></a> ';
                var cancel = '<a class="action cancel" href="javascript:void(0)" onclick="stGridHelper.cancelrow('+index+')"><i class="fa fa-ban fa-lg"></i></a>';
                return save+cancel;
            } else {
                return '<a class="action delete" href="javascript:void(0)" onclick="stGridHelper.deleteRow('+index+')" title="'+_stLang['delete']+'"><i class="fa fa-trash fa-lg"></i></a>';
            }
        }
    }
] ];
(function($){
$('#documentPane').on('click','#st-tab',function(){
    if (stConfig.stGridLoaded) {
        $('#stGrid').edatagrid('reload');
        $(window).trigger('resize');
    } else {
        stGridHelper.initGrid();
        stConfig.stGridLoaded = true;
    }
});
$(window).on('load', function(){
    if ($('#st-tab')) {
        $('#st-tab.selected').trigger('click');
    }
});
$(window).on('resize',function(){
    if ($('#st-tab').hasClass('selected')) {
        clearTimeout(this.timeout);
        this.timeout = setTimeout(function () {
            $('#SimpleTube').width($('body').width() - 60);
            if (stConfig.stGridLoaded) {
                $('#stGrid').datagrid('getPanel').panel('resize');
            }
        }, 300);
    }
})
})(jQuery)
</script>
<div id="SimpleTube" class="tab-page">
<h2 class="tab" id="st-tab">[+tabName+]</h2>
</div>
