<?php
/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 */

if ( ! class_exists( 'Fflguard_Dropbox' ) ) {
    /**
     * The core plugin class.
     * This is used to define internationalization, admin-specific hooks, and
     * public-facing site hooks.
     */

    class Fflguard_Dropbox {

        /**
         * Define the core functionality of the plugin.
         *
         * Set the plugin name and the plugin version that can be used throughout the plugin.
         * Load the dependencies, define the locale, and set the hooks for the admin area and
         * the public-facing side of the site.
         *
         * @since    1.0.0
         */
        public function __construct() {

            $this->define_admin_hooks();
        }

        /**
         * Register all of the hooks related to the admin area functionality
         * of the plugin.
         *
         * @since    1.0.0
         * @access   private
         */
        private function define_admin_hooks() {

            // Register Hook for enqueue scripts and styles
            add_action( 'admin_enqueue_scripts', array( $this, 'dcf_enqueue_scripts' ) );

            // Register Hook for Menu Page
            add_action( 'admin_menu', array( $this, 'dcf_register_menu_page' ) );

            // Register a setting and its data.
            add_action( 'admin_init', array( $this, 'dcf_register_settings' ) );

            // Hook to add custom fields in user profile
            add_action( 'show_user_profile', array( $this, 'dcf_show_extra_profile_fields' ) );
            add_action( 'edit_user_profile', array( $this, 'dcf_show_extra_profile_fields' ) );

            // Hook to save custom fields data in usermeta profile
            add_action( 'personal_options_update', array( $this, 'crf_update_profile_fields' ) );
            add_action( 'edit_user_profile_update', array( $this, 'crf_update_profile_fields' ) );
        }

        /**
         * Register the scripts for the admin area.
         */
        public function dcf_enqueue_scripts() {
            wp_enqueue_style( 'dcf-admin-style', plugin_dir_url( __FILE__ ). 'assets/css/dcf-admin-style.css');
            wp_enqueue_style( 'dcf-select-style', plugin_dir_url( __FILE__ ). 'assets/css/select2.min.css');
            wp_enqueue_script( 'dcf-select-script', plugin_dir_url( __FILE__ ). 'assets/js/select2.min.js', array('jquery'), '', true);
            wp_enqueue_script( 'dcf-admin-script', plugin_dir_url( __FILE__ ). 'assets/js/dcf-admin-script.js', array('jquery'), '', true);
        }

        /**
        *  Include page in the menu
        */
        public function dcf_register_menu_page() {
            add_menu_page( __( 'Dropbox Client', 'dcf'), __( 'Dropbox Client', 'dcf'), 'manage_options', 'dropbox-client-files', array( $this, 'dcf_add_setting_page' ), '', 20 );
        }

        /**
        * Callback function for admin settings page.
        */
        public function dcf_add_setting_page() {
            // echo "string";
            include_once plugin_dir_path(__FILE__).'html/admin-fflgaurd-html.php';
        }

        /**
        * Registers a text field setting save to options table.
        */
        public function dcf_register_settings() {
            register_setting( 'fflgaurd_settings_options', 'fflgaurd_settings_options', array( $this, 'dcf_sanitize_settings' ) );
        }

        /**
        * Save admin form settings value to fflgaurd_settings_options option.
        */
        public function dcf_sanitize_settings() {

            if ( ! isset( $_POST['validate_submit'] ) || ! wp_verify_nonce( $_POST['validate_submit'], 'dcf_nonce_feeds' ) ) {
                return false;
            }

            $input_options = array();
            //Dropbox API
            $input_options['unavailable_message']   = sanitize_text_field( $_POST['fflgaurd_settings_options']['unavailable_message'] );
            $input_options['upload_unavailable_message']   = sanitize_text_field( $_POST['fflgaurd_settings_options']['upload_unavailable_message'] );
            $input_options['access_token']   = sanitize_text_field( $_POST['fflgaurd_settings_options']['access_token'] );
            $input_options['parent_folder']   = sanitize_text_field( $_POST['fflgaurd_settings_options']['parent_folder'] );

            return $input_options;
        }

        /**
        * Add custom fields in user profile
        */
        public function dcf_show_extra_profile_fields( $user ) {

            $settings_options   = get_option( 'fflgaurd_settings_options' );
            $access_token       = ( !empty( $settings_options['access_token'] ) ? $settings_options['access_token'] : '' );
            $parent_folder      = ( !empty( $settings_options['parent_folder'] ) ? $settings_options['parent_folder'] : '' );
            $user_id            = $user->ID;
            $user_folder        = get_user_meta( $user_id, 'dropbox_folders_files', true );

            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => "https://api.dropboxapi.com/2/files/list_folder",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_POSTFIELDS => "{\n\t\"path\":\"/".$parent_folder."\",\"recursive\":false\n}",
                CURLOPT_HTTPHEADER => array(
                "authorization: Bearer {$access_token}",
                "cache-control: no-cache",
                "content-type: application/json"
                ),
            ));

            $response = curl_exec($curl);
            $err = curl_error($curl);

            curl_close($curl);
            $encoded_response   = array();
            if ($err) {
                echo "cURL Error #:" . $err;
            } else {
                $encoded_response = json_decode( $response );

            }
            ?>
            <h3><?php esc_html_e( 'Choose Dropbox Folder', 'dcf' ); ?></h3>
            <table class="form-table">
                <tr>
                    <th>
                        <label for="dropbox-option"><?php esc_html_e( 'Method', 'dcf' ); ?></label>
                    </th>
                    <td class="dcf-option-radio">
                        <input type="radio" id="existing-method" name="dcf_select_method" checked="checked" value="existing">
                        <label for="existing-method"><?php esc_html_e( 'Select Existing', 'dcf' ); ?></label>
                    </td>
                    <td class="dcf-option-radio">
                        <input type="radio" id="create-method" name="dcf_select_method" value="create">
                        <label for="create-method"><?php esc_html_e( 'Create', 'dcf' ); ?></label>
                    </td>
                </tr>
                <tr>
                    <th><label for="dropbox-folders-files"><?php esc_html_e( 'Dropbox Folder', 'dcf' ); ?></label></th>
                    <td id="dcf-existing-folder-td">
                        <?php

                            $user_dropbox_path    = get_user_meta( $user_id, 'dropbox_folders_files', true );
                            $sub_folders          = get_user_meta( $user_id, 'sub_folders', true );
                            $user_dropbox_path    = ( isset( $sub_folders ) && !empty( $sub_folders ) ) ? $sub_folders : $user_dropbox_path;
                        ?>
                        <select id="dropbox-folders-files" name="dropbox_folders_files" class="dropbox-folder-select">
                            <option value=""><?php esc_html_e( 'Select Folder', 'dcf' ); ?></option>
                            <?php
                            if ( !empty( $encoded_response->entries) ) {
                                $folder_lists   = $encoded_response->entries;


                                foreach ($folder_lists as $folder_list) {

                                    $folder_id  = explode('id:', $folder_list->id);
                                    if ( !empty( $folder_list->path_lower ) ) {
                                        $selected   =   ( !empty( $user_folder ) & $folder_list->path_lower == $user_folder ) ? 'selected="selected"' : '';
                                        ?>
                                        <option value="<?php echo $folder_list->path_lower; ?>" <?php echo $selected; ?>><?php echo $folder_list->name; ?></option>
                                        <?php
                                    }
                                }
                            }
                            ?>
                        </select>
                        <?php
                            if ( isset( $user_dropbox_path ) && !empty( $user_dropbox_path ) ) {
                                ?>
                                    <span> The folder selected is <b><?php echo $user_dropbox_path; ?></b></span>

                                <?php
                            }
                        ?>


                        <div class="appended-folders-field">

                        </div>
                        <div class="dropbox-folder-error"></div>
                    </td>

                    <td id="dcf-create-folder-td" style="display: none;">
                        <input type="text" name="create_folder" placeholder="Folder Name">
                    </td>
                </tr>
            </table>
            <?php
        }

        /**
        * Action function to update usermeta on profile save
        */
        public function crf_update_profile_fields( $user_id ) {
            if ( ! current_user_can( 'edit_users', $user_id ) ) {
                return false;
            }

            if ( 'create' == $_POST['dcf_select_method'] ) {
                $settings_options   = get_option( 'fflgaurd_settings_options' );
                $access_token       = ( !empty( $settings_options['access_token'] ) ? $settings_options['access_token'] : '' );
                $parent_folder       = ( !empty( $settings_options['parent_folder'] ) ? $settings_options['parent_folder'] : '' );
                $folder_name        = sanitize_text_field( $_POST['create_folder'] );

                $curl = curl_init();

                curl_setopt_array($curl, array(
                  CURLOPT_URL => "https://api.dropboxapi.com/2/files/create_folder_v2",
                  CURLOPT_RETURNTRANSFER => true,
                  CURLOPT_ENCODING => "",
                  CURLOPT_MAXREDIRS => 10,
                  CURLOPT_TIMEOUT => 30,
                  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                  CURLOPT_CUSTOMREQUEST => "POST",
                  CURLOPT_POSTFIELDS => "{\n\t\"path\":\"/{$parent_folder}/{$folder_name}\",\"autorename\":false\n}",
                  CURLOPT_HTTPHEADER => array(
                    "authorization: Bearer {$access_token}",
                    "cache-control: no-cache",
                    "content-type: application/json"
                  ),
                ));

                $response = curl_exec($curl);
                $err = curl_error($curl);

                curl_close($curl);
                $json_decoded   = array();
                if ($err) {
                  echo "cURL Error #:" . $err;
                  die();
                } else {
                  $json_decoded = json_decode( $response );
                }

                if ( !empty( $json_decoded->metadata->path_lower ) ) {
                    $folder_path  = $json_decoded->metadata->path_lower;
                    if ( !empty( $folder_path ) ) {
                        update_user_meta( $user_id, 'dropbox_folders_files', $folder_path );
                    }
                }
            } elseif( 'existing' == $_POST['dcf_select_method'] ) {

                if ( ! empty( $_POST['dropbox_folders_files'] ) ) {
                    $folder_path  = sanitize_text_field( $_POST['dropbox_folders_files'] );
                    update_user_meta( $user_id, 'dropbox_folders_files', $folder_path );
                } else {
                    update_user_meta( $user_id, 'dropbox_folders_files', NULL );

                }

                if ( isset( $_POST['sub_folders'] ) && !empty( $_POST['sub_folders'] ) ) {
                   $folder_path  = sanitize_text_field( $_POST['sub_folders'] );
                   update_user_meta( $user_id, 'sub_folders', $folder_path );
               } else {
                   update_user_meta( $user_id, 'sub_folders', NULL );

               }
            }
        }
    }
}