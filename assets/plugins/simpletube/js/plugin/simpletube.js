(function($) {
    stGridHelper = {
        addRow: function () {
            var url = $('input', '#addVideo').val();
            var grid = $('#stGrid');
            if (url != '') {
                grid.datagrid('loading');
                $.ajax({
                    url: stConfig.url+'?mode=addRow',
                    type: 'post',
                    dataType: 'json',
                    data: {'stUrl': url, 'st_rid': stConfig.rid}
                }).done(function (response) {
                    grid.datagrid('loaded');
                    if (!response.success) {
                        response.message = (_stLang[response.message] != undefined) ? _stLang[response.message] : response.message;
                        $.messager.alert(_stLang['error'], response.message);
                    } else {
                        $('input', '#addVideo').val('');
                    }
                   grid.edatagrid('reload');
                }).fail(stGridHelper.handleAjaxError);
            }
            return false;
        },
        formatTime: function (seconds) {
            if (seconds == 0) return;
            time = new Date(0, 0, 0, 0, 0, seconds, 0);

            hh = time.getHours();
            mm = time.getMinutes();
            ss = time.getSeconds()

            output = '';
            if (hh != 0) {
                hh = ('0' + hh).slice(-2);
                output = hh + ':';
            }
            mm = ('0' + mm).slice(-2);
            output += mm + ':';
            output += ('0' + ss).slice(-2);
            return output;
        },
        saverow: function (index) {
            $('#stGrid').edatagrid('endEdit', index);
        },
        cancelrow: function (index) {
            $('#stGrid').edatagrid('cancelEdit', index);
        },
        deleteRow: function (index) {
            $('#stGrid').edatagrid('destroyRow', index);
        },
        getSelected: function() {
            var ids = [];
            var rows = $('#stGrid').edatagrid('getChecked');
            if (rows.length) {
                $.each(rows, function(i, row) {
                    ids.push(row.st_id);
                });
            }
            return ids;
        },
        deleteAll: function() {
            var ids = this.getSelected();
            $.messager.confirm(_stLang['delete'],_stLang['are_you_sure_to_delete_many'],function(r){
                if (r && ids.length > 0){
                    $.post(
                        stConfig.url+'?mode=remove', 
                        {
                            ids:ids.join(),
                            st_rid:stConfig.rid
                        },
                        function(data) {
                            if(data.success) {
                                $('#stGrid').edatagrid('reload');
                            } else {
                                $.messager.alert(_stLang['error'],_stLang['cannot_delete']);
                            }
                        },'json'
                    ).fail(stGridHelper.handleAjaxError);
                }
            });
        },
        handleAjaxError: function(xhr){
            var message = xhr.status == 200 ? _stLang['parse_error'] : _stLang['server_error'] + xhr.status + ' ' + xhr.statusText;
            $.messager.alert(_stLang['error'], message, 'error');
        },
        initGrid: function () {
            $('#SimpleTube').append(
                '<div id="addVideo">' +
                '<label><b>'+_stLang['video_url']+':</b></label><br>' +
                '<input name="stUrl"><a href="javascript:void(0)" id="stAddBtn"></a>' +
                '</div>' +
                '<table id="stGrid" width="100%"></table>'
            );
            $('#stAddBtn').linkbutton({
                text: _stLang['add'],
                iconCls: 'fa fa-video-camera fa-lg',
                onClick: function(){
                    stGridHelper.addRow();
                }
            });
            var stGrid = new EUIGrid({
                url: stConfig.url+'',
                destroyUrl: stConfig.url+'?mode=remove&st_rid='+stConfig.rid,
                updateUrl: stConfig.url+'?mode=edit',
                idField: 'st_id',
                indexField: 'st_index',
                sortName: 'st_index',
                sortOrder: 'DESC',
                queryParams: {st_rid: stConfig.rid},
                columns: stGridColumns
            },'#stGrid');
            var pager = stGrid.datagrid('getPager');    // get the pager of datagrid
            pager.pagination({
                buttons:[
                {
                    iconCls:'fa fa-trash fa-lg btn-extra',
                    handler:function(){stGridHelper.deleteAll();}
                }
                ]
            });
            $('.btn-extra').parent().parent().hide();
            $('#stGrid').datagrid('getPanel').panel('resize');
        }
    }
})(jQuery);
