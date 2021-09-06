jQuery( document ).ready( function() {

    triggerChangeSelectDropboxFolder();

    function triggerChangeSelectDropboxFolder() {

        jQuery('select.dropbox-folder-select').change( function(){

            jQuery('.dropbox-folder-error').html('');
            jQuery('div#page-loader').removeClass('loader-hidden');

            var option          = jQuery(this);
            var pathLower       = option.val(),
                selectLength    = jQuery('.dropbox-folder-select').length,
                appendedLength  = jQuery('.sub-folder').length;

                if ( jQuery(this).parent().hasClass('sub-folder') ) {

                    jQuery(this).parent().nextAll().remove();
                }

            jQuery.ajax({
             type : "post",
             dataType : "json",
             url : variables.ajaxurl,
             data : {action: "get_dropbox_folders", path_lower : pathLower },
             success: function(response) {
                if( response.type == "success" ) {

                var newCount    = appendedLength+1;
                   // jQuery("#vote_counter").html(response.vote_count)
                   var select_html  = '<div class="sub-folder-'+newCount+' sub-folder"><select name="sub_folders" class="sub-dropbox-folder-select">';
                       select_html  += '<option value="">Select Folder</option>';
                   jQuery.each(response.folders, function(i, item) {

                       select_html  += '<option value="'+item['path_lower']+'">'+item['name']+'</option>';
                   });
                   select_html  += '</select>';

                   if ( 1 == selectLength ) {
                        jQuery('td#dcf-existing-folder-td .appended-folders-field').html('');
                        jQuery('td#dcf-existing-folder-td .appended-folders-field').append(select_html);
                   } else if ( 1 <  selectLength ) {
                        var elCount = newCount - 1;
                        jQuery('div.sub-folder-'+elCount).nextAll().remove();
                        jQuery(select_html).insertAfter('div.sub-folder-'+elCount);
                   }

                } else {
                   jQuery('.dropbox-folder-error').html('<p>No folder found beyond this. Hit the <b>Update User</b> button or try refreshing the select to select another folder.</p>');
                }
                triggerChangeSelectDropboxFolder();
                subFolderTriggerChangeSelectDropboxFolder();
                jQuery('div#page-loader').addClass('loader-hidden');
             }
          });

        });

    }

    function subFolderTriggerChangeSelectDropboxFolder() {

        jQuery('select.sub-dropbox-folder-select').change( function(){

            jQuery('div#page-loader').removeClass('loader-hidden');
            jQuery('.dropbox-folder-error').html('');

            var option          = jQuery(this);
            var pathLower       = option.val(),
                selectLength    = jQuery('.sub-dropbox-folder-select').length,
                appendedLength  = jQuery('.sub-folder').length,
                attrClass       = jQuery(this).parent().attr('class');

                if ( jQuery(this).parent().hasClass('sub-folder') ) {

                    jQuery(this).parent().nextAll().remove();
                }

            jQuery.ajax({
             type : "post",
             dataType : "json",
             url : variables.ajaxurl,
             data : {action: "get_dropbox_folders", path_lower : pathLower },
             success: function(response) {
                if( response.type == "success" ) {

                    jQuery('.dropbox-folder-error').html('');

                var newCount    = selectLength+1;

                   var select_html  = '<div class="sub-folder-'+newCount+' sub-folder"><select name="sub_folders" class="sub-dropbox-folder-select">';
                   select_html  += '<option value="">Select Folder</option>';
                   jQuery.each(response.folders, function(i, item) {

                       select_html  += '<option value="'+item['path_lower']+'">'+item['name']+'</option>';
                   });
                   select_html  += '</select></div>';

                        jQuery('div.sub-folder-'+selectLength).nextAll().remove();
                        jQuery(select_html).insertAfter('div.sub-folder-'+selectLength);


                } else {

                   jQuery('.dropbox-folder-error').html('<p>No folder found beyond this. Hit the <b>Update User</b> button or try refreshing the select to select another folder.</p>');

                }
             },
             complete: function() {
                triggerChangeSelectDropboxFolder();
                subFolderTriggerChangeSelectDropboxFolder();
                jQuery('div#page-loader').addClass('loader-hidden');

             }
          });

        });

    }
});