function refreshField(fieldName, form){
    $.post(renderFieldUrl, $(form).serialize() + '&field=' + fieldName, function(data){
        $('#' + fieldName).closest('.clearfix').after(data).remove();
        if ($('#' + fieldName).attr('type') == 'hidden') {
            $('#' + fieldName).after(data).remove();
        }
    })
}

function refreshFieldGroup(fieldName, form){
    $.post(renderFieldGroupUrl, $(form).serialize() + '&field_grp='+fieldName, function(data){
        $('#' + fieldName).closest('.clearfix').after(data).remove();
    })
}