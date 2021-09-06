<template>
    <div id="sub-folder" v-if="showSubFolder">
        <div class="dcf-lists">

            <div class="dcf-loading" v-show="showLoader"></div>

            <div class="nav-breadcrumb" v-html="renderBreadcrumb(folderItemArray)">
                <a href="#" class="dcf-return" data-path="{{pathLower}}" data-name="{{subFolderName}}" @click="triggerBack(this)">Main</a>
            </div>


            <div class="dcf-error" v-if="folderError">{{folderError}}</div>

            <div class="dcf-item-listings">
                <table class="dcf-files-view-list">
                    <tbody class="dcf-table-body">
                        <tr v-for="(item, index ) in subFolderItems" class="dcf-list-item" :class="[item.key == 'folder' ? 'dashicons-portfolio' : 'dashicons-media-default']" :id="'dcf-tr-'+index" @click="downloadFile_enderDirectory(item.path_lower, item.key, item.name, index, $event)">
                            <td>{{item.name}}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="dcf-upload">
                <h4>Upload File.</h4>
                <form class="dcff-upload-process-form" method="POST" id="dcff-upload-form" enctype="multipart/form-data" @submit="proccessSubForm">
                    <input type="hidden" name="dcf_sub_attr" v-model="dcf_sub_hidden_attr">
                    <input type="hidden" name="dcf_sub_folder_path" id="folder-path" v-model="current_folder_path">
                    <input type="file"  ref="file_sub" id="file-upload" name="dcf_file_sub" @change="addParameters" >
                    <input type="submit" name="dcf_submit" value="Submit">
                    <p class="dcf-upload-error" v-if="dcf_file_sub_error.length">{{dcf_file_sub_error}}</p>
                </form>
            </div>
        </div>


    </div>
</template>

<script>
    import {HTTP} from '../http.js';
    export default {
        data: () => ({
            unavailable_message: '',
            upload_unavailable_message: variables.upload_unavailable_message,
            main_dropbox_path: variables.user_dropbox_path,
            is_sub_folder: false,
            dcf_sub_hidden_attr: null,
            dcf_sub_hidden_item_name: null,
            dcf_file_sub_error: '',
            nested_item_array: [],
            name_array: [],
            nonEmptySubObject: false,
            unavailable_message: '',
            emptyFolder: '',
            navigationBreadcrumb: '',
            current_folder_path: ''
        }),
        props:[
            'showSubFolder',
            'pathLower',
            'subFolderName',
            'showLoader',
            'subFolderKey',
            'subFolderItems',
            'folderError',
            'folderItemArray',
            'folderNameArray',
        ],
        mounted: function() {

            let self = this;
            if ( self.pathLower.length > 0 ) {
                self.current_folder_path = self.pathLower;
            }

        },
        methods: {
            triggerBack: function(e) {

                let self = this;
                jQuery('a.dcf-return').click( function(event){
                    event.preventDefault();

                    self.dcf_file_sub_error = '';
                    var folder_path = event.target.getAttribute('data-path');
                    var folder_name = event.target.getAttribute('data-name');
                    self.downloadFile_enderDirectory(folder_path, 'folder', folder_name, '', '');

                    for (var i = 0; i < self.folderNameArray.length; i++) {

                        if ( folder_name == self.folderNameArray[i] ) {

                            self.folderNameArray.splice( i+1, self.folderNameArray.length-1);
                            self.folderItemArray.splice( i+1, self.folderItemArray.length-1);
                        }

                    }
                    self.current_folder_path = folder_path;
                    let folderr_path    = jQuery('input#folder-path').val();

                    self.renderBreadcrumb(self.folderItemArray);

                });

            },
            renderBreadcrumb: function(nested_folder_array){

                let self = this;
                self.html = '';

                self.objLength = nested_folder_array.length;

                if ( self.objLength > 1 ) {
                    var i = 0;
                    for (var i = 0; i < self.objLength; i++) {

                        if ( i > 0 ) {
                            self.html += " >> ";
                        }

                        if ( i == self.objLength-1 ) {
                            self.html += '<span class="title-dcf-item">'+nested_folder_array[i]['folderName']+'</span>';
                        } else {

                            self.html += '<a href="#" data-path="'+nested_folder_array[i]['folderPath']+'" data-name="'+nested_folder_array[i]['folderName']+'" class="dcf-return" @click="triggerBack(this)">'+nested_folder_array[i]['folderName']+'</a>';
                        }
                    }
                }
                this.triggerBack();
                return self.html;

            },
            downloadFile_enderDirectory: function(path_lower, item_type, item_name, index, event) {

                let self = this;
                let end_point   = variables.rest_point;

                if ( 'folder' == item_type) {

                    self.dcf_file_sub_error = '';
                    jQuery('div.dcf-loading').show();
                    self.showLoader = true;

                    if(!self.folderNameArray.includes(self.subFolderName)){

                        self.folderNameArray.push(self.subFolderName);

                        self.folderItemArray.push( {'folderName' : self.subFolderName, 'folderPath' : self.pathLower } );
                    }

                    if(!self.folderNameArray.includes(item_name)){

                        self.folderNameArray.push(item_name);
                        self.folderItemArray.push( {'folderName' : item_name, 'folderPath' : path_lower } );
                    }

                    let apiUrl = `${end_point}/wp-json/dcf-dropboxapi/2/get-item?item=folders&item_path=${path_lower}`;

                    HTTP.post(apiUrl).then(response => {

                        if ( response.data.entries.length > 0 ) {
                            self.subFolderItems = response.data.entries;
                            for (var i = 0; i <= response.data.entries.length-1; i++) {
                               self.subFolderItems[i]['key']    = response.data.entries[i]['.tag'];
                            }
                            self.nonEmptySubObject      = true;
                            self.unavailable_message    = "";
                            self.folderError            = false;

                        } else {
                            self.unavailable_message    = "";
                            self.nonEmptySubObject      = false;
                            self.subFolderItems         = [];
                            self.folderError            = "This folder is empty.";
                        }

                        jQuery('div.dcf-loading').hide();
                        self.current_folder_path = path_lower;
                    }).catch(e => {

                        self.showLoader     = false;
                        console.log(e);
                        self.responseError  = e;

                    });
                    self.current_folder_path = path_lower;

                } else if ( 'file' == item_type ) {

                    let apiUrl = `${end_point}/wp-json/dcf-dropboxapi/2/get-item?item=get-file&item_path=${path_lower}&item_name=${item_name}`;

                    HTTP.post(apiUrl).then(response => {

                        if ( typeof response.data.link !== undefined ) {
                            window.open(response.data.link, '_self');
                        }

                        self.showLoader = false;
                    }).catch(e => {
                        self.showLoader     = false;
                        self.folderError    = e;
                    });

                }
            },
            proccessSubForm: function(event) {

                let self = this;
                let end_point   = variables.rest_point;
                let image_formData = document.getElementById("file-upload").value;

                if (!image_formData) {
                    self.dcf_file_sub_error = 'Select a file.';
                event.preventDefault();
                } else {
                    return true;

                }
            },
            addParameters: function() {
                let self                    = this;
                self.dcf_sub_hidden_attr    = 1;
                let folder_path    = document.getElementById("folder-path").value;

                if (folder_path.length < 1 ) {

                    self.current_folder_path = self.pathLower;
                }
            }
        }
    }

</script>
<style>
.sub-folder {
    padding-top: 10px;
}
.dcf-error {
    text-align: center;
    color: #ff0303;
}
</style>