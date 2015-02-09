(function($) {
    stGridHelper = {
        sourceRow: {},
        targetRow: {},
        point: '',
        addRow: function () {
            var url = $('input', '#addVideo').val();
            if (url != '') {
                $.ajax({
                    url: stConfig.url+'?mode=addRow',
                    type: 'post',
                    data: {'stUrl': url, 'st_rid': stConfig.rid}
                }).done(function (response) {
                    if (response) {
                        response = $.parseJSON(response);
                        if (!response.success) {
                            $.messager.alert('Ошибка', response.message);
                        } else {
                            $('input', '#addVideo').val('');
                        }
                        $('#stGrid').edatagrid('reload');
                    }
                })
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
        updateActions: function (index) {
            $('#stGrid').edatagrid('updateRow', {
                index: index,
                row: {}
            });
        },
        editrow: function (target) {
            $('#stGrid').edatagrid('beginEdit', this.getRowIndex(target));
        },
        saverow: function (target) {
            $('#stGrid').edatagrid('endEdit', this.getRowIndex(target));
        },
        cancelrow: function (target) {
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
            $('#SimpleTube').append(
                '<div id="addVideo">' +
                '<label><b>Ссылка на видео:</b></label><br>' +
                '<input name="stUrl"><a href="javascript:void(0)" onclick="stGridHelper.addRow();">Добавить</a>' +
                '</div>' +
                '<table id="stGrid" width="100%"></table>'
            );
            $('#stGrid').edatagrid({
                url: stConfig.url+'',
                singleSelect: true,
                destroyUrl: stConfig.url+'?mode=remove',
                updateUrl: stConfig.url+'?mode=edit',
                destroyMsg: {
                    confirm: {   // when select a row
                        title: 'Удаление записи',
                        msg: 'Вы уверены, что хотите удалить запись?'
                    }
                },
                pagination: true,
                pageList:[10,25,50,100],
                fitColumns: true,
                striped: true,
                idField: 'st_id',
                scrollbarSize: 0,
                sortName: 'st_index',
                sortOrder: 'DESC',
                queryParams: {st_rid: stConfig.rid},
                onLoadSuccess: function () {
                    $(this).edatagrid('enableDnd');
                },
                onSortColumn: function (sort, order) {
                    stConfig.stOrderBy = sort;
                    stConfig.stOrderDir = order;
                },
                onDestroy: function (index) {
                    rows = $(this).edatagrid('getRows');
                    m = rows.length;
                    from = (stConfig.stOrderDir == 'asc') ? index : 0;
                    to = (stConfig.stOrderDir == 'asc') ? m : index;
                    for (var i = from; i < to; i++) {
                        sti = rows[i].st_index;
                        $(this).edatagrid('updateRow', {
                            index: i,
                            row: {
                                st_index: sti - 1
                            }
                        })
                    }
                },
                onBeforeDrag: function (row) {
                    if (stConfig.stOrderBy == 'st_index' && !row.editing) {
                        $('body').css('overflow-x', 'hidden');
                        $('.datagrid-body').css('overflow-y', 'hidden');
                    } else {
                        return false;
                    }
                },
                onBeforeDrop: function (targetRow, sourceRow, point) {
                    $('body').css('overflow-x', 'auto');
                    $('.datagrid-body').css('overflow-y', 'auto');
                    this.targetRow = targetRow;
                    this.targetRow.index = tgt = $('#stGrid').edatagrid('getRowIndex', targetRow);
                    this.sourceRow = sourceRow;
                    this.sourceRow.index = src = $('#stGrid').edatagrid('getRowIndex', sourceRow);
                    this.point = point;
                    dif = tgt - src;
                    if ((point == 'bottom' && dif == -1) || (point == 'top' && dif == 1)) return false;
                },
                onDrop: function (targetRow, sourceRow, point) {
                    src = this.sourceRow.index;
                    tgt = this.targetRow.index;
                    $.ajax({
                        url: stConfig.url+'?mode=reorder',
                        type: 'post',
                        data: {
                            'target': {
                                'st_id': targetRow.st_id,
                                'st_index': targetRow.st_index
                            },
                            'source': {
                                'st_id': sourceRow.st_id,
                                'st_index': sourceRow.st_index
                            },
                            'point': point,
                            'st_rid': stConfig.rid,
                            'orderDir': stConfig.stOrderDir
                        }
                    }).done(function (response) {
                        if (response) {
                            response = $.parseJSON(response);
                            if (!response.success) {
                                $.messager.alert('Ошибка', response.message);
                                $('#stGrid').edatagrid('reload');
                            } else {
                                rows = $('#stGrid').edatagrid('getRows');
                                if (tgt < src) {
                                    rows[tgt].st_index = targetRow.st_index;
                                    for (var i = tgt; i <= src; i++) {
                                        rows[i].st_index = rows[i - 1] != undefined ? rows[i - 1].st_index - (stConfig.stOrderDir == 'desc' ? 1 : -1) : rows[i].st_index;
                                        $('#stGrid').edatagrid('refreshRow', i);
                                    }
                                } else {
                                    rows[tgt].st_index = targetRow.st_index;
                                    for (var i = tgt; i >= src; i--) {
                                        rows[i].st_index = rows[i + 1] != undefined ? parseInt(rows[i + 1].st_index) + (stConfig.stOrderDir == 'desc' ? 1 : -1) : rows[i].st_index;
                                        $('#stGrid').edatagrid('refreshRow', i);
                                    }
                                }
                            }
                        }
                    })
                },
                onSelect: function (rowIndex, rowData) {
                    $('#stGrid').edatagrid('unselectRow', rowIndex);
                },
                onBeforeEdit: function (index, row) {
                    row.editing = true;
                    stGridHelper.updateActions(index);
                },
                onAfterEdit: function (index, row) {
                    row.editing = false;
                    stGridHelper.updateActions(index);
                },
                onCancelEdit: function (index, row) {
                    row.editing = false;
                    stGridHelper.updateActions(index);
                },
                onClickRow: function (row) {
                    row.editing = false;
                    $('#stGrid').edatagrid('cancelEdit', row);
                },
                columns: stGridColumns
            })
        }
    }
})(jQuery);
