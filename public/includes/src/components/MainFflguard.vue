<template>
    <div class="dcf-response">
        <div class="dcf-loading" v-show="showloader"></div>
        <div class="dcf-lists" v-if="is_folder_set">
            <div class="dcf-upload">
                <div class="dcf-error" v-if="responseError">{{responseError}}</div>
                <h4>Upload File.</h4>
                <form class="dcf-upload-process-form" method="POST" id="dcf-upload-form" enctype="multipart/form-data" @submit="proccessForm">
                    <input type="hidden" name="dcf_hidden_attr" v-model="dcf_hidden_attr">
                    <input type="file" name="dcf_file" id="main-file-upload" @change="addParameters" >
                    <input type="submit" name="dcf_submit" value="Submit">
                    <p class="dcf-upload-error" v-if="dcf_file_error.length">{{dcf_file_error}}</p>
                </form>
            </div>
            <div class="dcf-empty-response" v-if="!nonEmptyObject">{{unavailable_message}}</div>
            <table class="dcf-files-view-list" v-if="nonEmptyObject">
                <tbody class="dcf-table-body">
                    <tr v-for="(item, index ) in item_folders" class="dcf-list-item" :class="[item.key == 'folder' ?
'dashicons-portfolio' : 'dashicons-media-default']" @click="proccedToNext(item.path_lower, item.key, item.name, index, $event)" :id="'dcf-tr-'+index">
                        <td>{{item.name}}</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="dcf-no-item" v-else>{{unavailable_message}}</div>

        <Subfolder :showSubFolder="is_sub_folder" :pathLower="subFolderPath" :itemName="item_name" :subFolderItems="itemSubFolders" :subFolderName="next_item_name" :subFolderKey="next_item_key" :folderItemArray="folder_item_array" :folderNameArray="folder_name_array" :showLoader="showloader" :folderError="emptyFolder"></Subfolder>
    </div>
</template>

<script>
import Subfolder from './Subfolder.vue';
import {
  HTTP
} from '../http.js';
export default {
    data() {
        return {
            is_folder_set: false,
            is_sub_folder: false,
            unavailable_message: '',
            // dcf_file: null,
            dcf_hidden_attr: null,
            dcf_file_error: '',
            upload_unavailable_message: variables.upload_unavailable_message,
            browse_unavailable_message: variables.browse_unavailable_message,
            user_dropbox_path: variables.user_dropbox_path,
            item_folders: [],
            showloader: false,
            nonEmptyObject: false,
            proceedToSubFolder: false,
            subFolderPath: '',
            item_name: '',
            itemSubFolders: '',
            nonEmptySubObject: false,
            responseError: '',
            emptyFolder: '',
            next_item_name: '',
            next_item_key: '',
            folder_item_array: [],
            folder_name_array: [],
        }
    },
    components: {
      Subfolder
    },
    mounted: function() {
        let self        = this;
        let end_point   = variables.rest_point;
        self.showloader = true;

        if ( self.user_dropbox_path.length > 0 ) {
            let assigned_dropbox_path   = self.user_dropbox_path;

            self.folder_name_array.push('Main');
            self.folder_item_array.push( {'folderName' : 'Main', 'folderPath' : self.user_dropbox_path } );

            let apiUrl                  = `${end_point}/wp-json/dcf-dropboxapi/2/get-item?item=folders&item_path=${assigned_dropbox_path}`;
            self.item_folders           = [];

            HTTP.post(apiUrl).then(response => {

                if ( response.data.entries ) {
                    for (var i = 0; i <= response.data.entries.length-1; i++) {
                       self.item_folders[i]    = response.data.entries[i];
                       self.item_folders[i]['key']    = response.data.entries[i]['.tag'];
                    }
                    self.nonEmptyObject = true;
                self.is_folder_set = true;
                } else {
                    self.nonEmptyObject = false;
                    self.is_folder_set = false;
                    self.unavailable_message = self.upload_unavailable_message;
                }
                self.showloader = false;
            }).catch(e => {
                self.showloader = false;
                console.log(e);
                self.responseError = e;
            });
        } else {
                self.showloader = false;
            self.unavailable_message = self.browse_unavailable_message;

        }

    },
    methods: {
        triggerBack: function(e) {
            e.preventDefault();

            let self = this;
            self.is_sub_folder = false;
            self.is_folder_set = true;
            self.itemSubFolders = '';
            self.unavailable_message = '';
            self.emptyFolder = '';
            self.dcf_file_error = '';

        },
        proccedToNext: function(path_lower, item_type, item_name, index, event) {
            let self =  this;
            self.showloader = true;

            if ( 'folder' == item_type) {
                self.is_sub_folder = true;

                self.item_name = item_name;
                self.subFolderPath = path_lower;

                self.next_item_name = item_name;
                self.next_item_key = item_type;

                self.is_folder_set = false;

                let end_point   = variables.rest_point;

                let apiUrl = `${end_point}/wp-json/dcf-dropboxapi/2/get-item?item=folders&item_path=${path_lower}`;

                self.folder_name_array.push(item_name);
                self.folder_item_array.push( {'folderName' : item_name, 'folderPath' : path_lower } );

                HTTP.post(apiUrl).then(response => {
                    if ( response.data.entries.length > 0 ) {
                        self.itemSubFolders = response.data.entries;
                        for (var i = 0; i <= response.data.entries.length-1; i++) {
                           self.itemSubFolders[i]['key']    = response.data.entries[i]['.tag'];
                        }
                        self.nonEmptySubObject = true;
                        self.unavailable_message = "";
                    } else {
                        self.unavailable_message = "";
                        self.nonEmptySubObject = false;
                        self.itemSubFolders = '';
                        self.emptyFolder = "This folder is empty.";
                    }

                    self.showloader = false;
                }).catch(e => {
                    self.showloader = false;
                    console.log(e);
                    self.responseError = e;
                });
            } else if ( 'file' == item_type ) {
                let self = this;

                self.next_item_name = item_name;
                self.next_item_key = item_type;

                let end_point   = variables.rest_point;
                let apiUrl = `${end_point}/wp-json/dcf-dropboxapi/2/get-item?item=get-file&item_path=${path_lower}&item_name=${item_name}`;

                HTTP.post(apiUrl).then(response => {

                    if ( typeof response.data.link !== undefined ) {
                        window.open(response.data.link, '_self');
                    }
                    self.showloader = false;
                }).catch(e => {
                    self.showloader = false;
                    console.log(e);
                    self.responseError = e;
                });

            }
        },
        proccessForm: function(event) {
            let self = this;
            let image_formData = document.getElementById("main-file-upload").value;
            if (image_formData) {
                return true;
            }
            if (!image_formData) {
                self.dcf_file_error = 'Select a file.';
            event.preventDefault();
            }
        },
        addParameters: function() {
            let self = this;
            self.dcf_hidden_attr = 1;
        }
    }
}
</script>
<style type="text/css">
.dcf-upload-error {
    color: #ff0303;
    margin-left: 10px;
}
input#main-file-upload, input[type="submit"] {
    margin: 10px;
}
.dcf-return {
    margin-bottom: 10px;
    display: inline-block;
}
.dcf-list-item {
    cursor: pointer;
}
.dcf-lists .dashicons-portfolio:before, .dcf-lists .dashicons-media-default:before {
    display: inline-block;
    width: 20px;
    height: 20px;
    font-size: 37px;
    line-height: 1;
    font-family: dashicons;
    text-decoration: inherit;
    font-weight: 400;
    font-style: normal;
    vertical-align: top;
    text-align: center;
    transition: color .1s ease-in 0;
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
}

.dcf-loading {
    position: fixed;
    width: 100%;
    left: 0;
    right: 0;
    top: 0;
    bottom: 0;
    background-color: rgba(255,255,255,0.7);
    z-index:9999;
}

@-webkit-keyframes spin {
    from {-webkit-transform:rotate(0deg);}
    to {-webkit-transform:rotate(360deg);}
}

@keyframes spin {
    from {transform:rotate(0deg);}
    to {transform:rotate(360deg);}
}

.dcf-loading::after {
    content:'';
    display:block;
    position:absolute;
    left:48%;top:40%;
    width:40px;height:40px;
    border-style:solid;
    border-color:black;
    border-top-color:transparent;
    border-width: 4px;
    border-radius:50%;
    -webkit-animation: spin .8s linear infinite;
    animation: spin .8s linear infinite;
}

.dcf-upload {
    float: right;
    display: block;
    width: 24%;
}

table.dcf-files-view-list {
    width: 75%;
    float: left;
}
table.dcf-files-view-list td:first-child {
    padding-left: 20px;
}
@media screen and ( max-width: 67em ) {
    .dcf-upload {
        left: 0;
        width: 100%;
    }

    table.dcf-files-view-list {
        width: 100%;
        float: left;
    }
}
</style>