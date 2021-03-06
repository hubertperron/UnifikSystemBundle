$(function(){
    var quickCreateLink = null;
    var quickCreateContainer = null;
    var quickCreateField = null;

    // Options for the dialog
    var quickCreateDialogOptions = {
        width: 'auto',
        height: 'auto',
        modal: true,
        buttons: {
            "Create": function() {

                formObj = quickCreateContainer.children('form');

                // Post the form
                $.ajax({
                    type: "POST",
                    url: formObj.attr('action'),
                    data: formObj.serialize(),
                    dataType: 'json',
                    beforeSend: function(){
                        $('#loading').show();
                    }
                })

                .done(function(data, textStatus, jqXHR){

                    // Item created successfully
                    if (data.createSuccess) {

                        // Add the new choice in the form
                        // Select option
                        if (quickCreateContainer.attr('data-input-type') == 'select') {
                            var choice = '<option value="' + data.entity.id + '" selected="selected">' + data.entity.name + '</option>';
                        }

                        // Checkbox or radio
                        else {
                            choice = $('<li />');

                            input = $('<input />').attr({
                                type: quickCreateContainer.attr('data-input-type'),
                                id: quickCreateContainer.attr('data-input-id') + '_' + data.entity.id,
                                name: quickCreateContainer.attr('data-input-name') + (quickCreateContainer.attr('data-input-type') == 'checkbox' ? '[]' : ''),
                                value: data.entity.id,
                                checked: 'checked'
                            }).appendTo(choice);

                            choice.append('&nbsp;');

                            label = $('<label />').attr({
                                for: quickCreateContainer.attr('data-input-id') + '_' + data.entity.id
                            }).text(data.entity.name).appendTo(choice);
                        }

                        quickCreateField.append(choice);
                        quickCreateContainer.dialog("close");

                    } else {
                        quickCreateContainer.html(data.response);
                    }
                })

                .fail(function(jqXHR, textStatus, errorThrown){
                    quickCreateContainer.html(textStatus + ': ' + errorThrown);
                })

                .always(function(){
                    $('#loading').hide();
                });
            },

            "Cancel": function() {
                $(this).dialog("close");
            }
        },

        close: function() {
            quickCreateContainer.dialog("destroy"); // to take the div out of the dialog and put it back to its original place
            quickCreateContainer.html('');

            // Put the container back to its original place
            quickCreateContainer.insertAfter(quickCreateLink);
        }
    };

    // Trigger dialog
    $('a.quick_create_link').click(function(e){

        quickCreateLink = $(this);
        quickCreateContainer = quickCreateLink.next();
        quickCreateField = quickCreateLink.prev();

        // Temporarily move the container out of the form (to prevent nested forms issues)
        quickCreateContainer.appendTo($('body'));

        // Call the quick create controller
        $.ajax({
            type: "GET",
            url: quickCreateLink.attr('href'),
            dataType: 'json',
            beforeSend: function(){
                $('#loading').show();
            }
        })

        .done(function(data, textStatus, jqXHR){
            quickCreateContainer.html(data.response);
        })

        .fail(function(jqXHR, textStatus, errorThrown){
            quickCreateContainer.html(textStatus + ': ' + errorThrown);
        })

        .always(function(){
            $('#loading').hide();
            quickCreateContainer.dialog(quickCreateDialogOptions);
            quickCreateContainer.dialog({ title: quickCreateLink.text() });
        });

        e.preventDefault();
    });
});
