jQuery( document ).ready( function(){

    jQuery('input[type=radio][name=dcf_select_method]').on( 'change', function() {
        var _this_val   = jQuery(this).val();
        if (_this_val == 'existing') {

            jQuery('td#dcf-create-folder-td').hide();
            jQuery('td#dcf-existing-folder-td').show();
        } else if ( _this_val == 'create') {

            jQuery('td#dcf-existing-folder-td').hide();
            jQuery('td#dcf-create-folder-td').show();
        }
    });

    jQuery('select#dropbox-folders-files').select2();
});