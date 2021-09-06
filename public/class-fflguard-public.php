<?php
/**
 * The file that defines the core plugin class for front end
 *
 * A class definition that includes attributes and functions used across the
 * public-facing side of the site.
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 */
class Fflguard_Public extends WP_REST_Controller {

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param      string    $plugin_name       The name of the plugin.
     * @param      string    $version    The version of this plugin.
     */
    public function __construct() {

        $this->namespace = 'dcf-dropboxapi/2';
        $this->base = 'get-item';

        // Register the scripts and stylesheet for the public-facing side of the site.
        add_action( 'wp_enqueue_scripts', array( $this, 'public_enqueue_styles' ) );

        add_action( 'admin_enqueue_scripts', array( $this, 'dropbox_admin_enqueue_styles' ) );
        add_action( 'admin_head', array( $this, 'dropbox_admin_head' ) );

        // Register their hooks on this action rather than another action to ensure they’re only loaded when needed.
        add_action( 'rest_api_init', array( $this, 'dcf_register_routes' ) );

        // shortcode to display content in front-end
        add_shortcode( 'dropbox-browse', array( $this, 'dcf_register_shortcode' ) );

        add_action( 'wp_ajax_get_dropbox_folders', array( $this, 'dropbox_get_dropbox_folders' ) );
    }

    /**
    * Enqueue Scripts and Styles for front-end.
    */
    public function public_enqueue_styles() {

        wp_enqueue_style( 'dashicons' );

        wp_enqueue_script( 'dcf-build-js', plugin_dir_url( __FILE__ ). 'includes/dist/build.js', array('jquery'), '', true );
        $user_id            = get_current_user_id();
        $user_dropbox_path    = get_user_meta( $user_id, 'dropbox_folders_files', true );
        $sub_folders          = get_user_meta( $user_id, 'sub_folders', true );
        $user_dropbox_path    = ( isset( $sub_folders ) && !empty( $sub_folders ) ) ? $sub_folders : $user_dropbox_path;
        $settings_options = get_option( 'fflgaurd_settings_options' );
        $unavailable_message    = ( isset( $settings_options['unavailable_message'] ) && !empty( $settings_options['unavailable_message'] ) ) ? esc_attr( $settings_options['unavailable_message'] ) : 'Browse Unavailable Message';
        $upload_unavailable_message = ( isset( $settings_options['upload_unavailable_message'] ) && !empty( $settings_options['upload_unavailable_message'] ) ) ? esc_attr( $settings_options['upload_unavailable_message'] ) : 'Upload Unavailable Message';
        $local_variables    = array(
            'rest_point'                    => home_url(),
            'user_id'                       => $user_id,
            'browse_unavailable_message'    => $unavailable_message,
            'upload_unavailable_message'    => $upload_unavailable_message,
            'user_dropbox_path'             => $user_dropbox_path,
            'nonce'                         => wp_create_nonce( 'wp_rest' )
        );
        wp_localize_script( 'dcf-build-js', 'variables', $local_variables );
    }

    public function dropbox_admin_enqueue_styles() {

      $screen = get_current_screen();

      if ( isset( $screen->id ) && ( 'user-edit' == $screen->id || 'profile' == $screen->id ) ) {
        wp_enqueue_script( 'user-script', plugin_dir_url( __FILE__ ) . 'scripts/user-script.js', array('jquery'), '', true );
        $local_variables    = array(
            'ajaxurl'   => admin_url( 'admin-ajax.php' )
        );
        wp_localize_script( 'user-script', 'variables', $local_variables );
      }
    }

    public function dropbox_admin_head() {

      $screen = get_current_screen();

      if ( isset( $screen->id ) && ( 'user-edit' == $screen->id || 'profile' == $screen->id ) ) {
        ?>
        <div id="page-loader" class="flexbox loader-hidden" ><img src="<?php echo plugin_dir_url( __FILE__ ). 'images/spinner.gif' ?>" class="loader-icon"></div>
        <style type="text/css">
          div#page-loader{
              justify-content: center;
              align-items: center;
              height: 100vh;
              width: 100%;
              background: #ffffff;
              position: fixed;
              top: 0;
              left: 0;
              z-index: 9999;
              transition: opacity 0.3s linear;
          }

          .flexbox.loader-hidden{
            display: none;
          }
          .flexbox {
            display: flex;
          }

        </style>
        <?php
      }
    }

    public function dcf_register_routes() {
        register_rest_route( $this->namespace, '/' . $this->base, array(
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => array( $this, 'get_items' )
            )
        );

        register_rest_field( 'user',
            'usermeta',
            array(
                'get_callback'      => array( $this, 'user_meta_callback' ),
                'update_callback'   => null,
                'schema'            => null,
            )
        );
    }

    public function get_items( $request ) {
        $params = $request->get_params();
        $http_query = http_build_query( $params );
        $api_url_base = 'https://api.dropboxapi.com/2';

        $user       = wp_get_current_user();

        if ( in_array( 'administrator', $user->roles ) || in_array( 'subscriber', $user->roles ) || in_array( 'editor', $user->roles ) ) {

          if ( 'folders' == $params['item'] ) {
              // $item_path        = sanitize_text_field($params['item_path']);
              $api_url   = $api_url_base.'/files/list_folder';
              return new WP_REST_Response( $this->call_endpoint( $api_url, $params ), 200 );

          } elseif ( 'get-file' == $params['item'] ) {
              $api_url   = 'https://api.dropboxapi.com/2/files/get_temporary_link';
              return new WP_REST_Response( $this->call_download_endpoint( $api_url, $params ), 200 );
          } elseif ( 'upload-file' == $params['item'] ) {
              $api_url   = 'https://content.dropboxapi.com/2/files/upload';
              return new WP_REST_Response( $this->call_upload_endpoint( $api_url, $params ), 200 );
          }
        }

    }

    public function user_meta_callback( $user ) {
       return get_user_meta( $user[ 'id' ] );
    }

    /**
    * return shortcode content
    */
    public function dcf_register_shortcode( $atts ) {
        ob_start();
        $err = '';

        if ( is_user_logged_in() ) {
            if ( isset($_POST['dcf_submit'] ) && 'Submit' == $_POST['dcf_submit'] && isset($_POST['dcf_hidden_attr'] ) && !empty( $_POST['dcf_hidden_attr'] ) ) {

                $user_id              = get_current_user_id();
                $user_dropbox_path    = get_user_meta( $user_id, 'dropbox_folders_files', true );
                $sub_folders          = get_user_meta( $user_id, 'sub_folders', true );
                $user_dropbox_path    = ( isset( $sub_folders ) && !empty( $sub_folders ) ) ? $sub_folders : $user_dropbox_path;
                $settings_options   = get_option( 'fflgaurd_settings_options' );
                $access_token       = ( !empty( $settings_options['access_token'] ) ? $settings_options['access_token'] : '' );
                if ( isset( $user_dropbox_path ) && !empty( $user_dropbox_path ) ) {

                    $item_upload_path = $user_dropbox_path .'/'.$_FILES['dcf_file']['name'];
                    $fp = fopen($_FILES['dcf_file']['tmp_name'], 'rb');
                    $size = filesize($_FILES['dcf_file']['tmp_name']);
                    $curl = curl_init();

                    curl_setopt_array($curl, array(
                      CURLOPT_URL => "https://content.dropboxapi.com/2/files/upload",
                      CURLOPT_RETURNTRANSFER => true,
                      CURLOPT_PUT => true,
                      CURLOPT_INFILE => $fp,
                      CURLOPT_INFILESIZE => $size,
                      CURLOPT_CUSTOMREQUEST => "POST",
                      CURLOPT_HTTPHEADER => array(
                        "authorization: Bearer ".$access_token,
                        "content-type: application/octet-stream",
                        "dropbox-api-arg: {\"path\": \"".$item_upload_path."\",\"mode\":{\".tag\":\"add\"},\"autorename\":true}",
                      ),
                    ));

                    $response = curl_exec($curl);
                    $err = curl_error($curl);

                    curl_close($curl);

                    if ( $err ) {
                        echo $err;
                    } else {
                        $decoded_response = json_decode($response,true);

                    }
                }
            }

            if ( isset($_POST['dcf_submit'] ) && 'Submit' == $_POST['dcf_submit'] && isset($_POST['dcf_sub_attr'] ) && !empty( $_POST['dcf_sub_attr'] ) ) {

              $user_id            = get_current_user_id();
              $user_dropbox_path    = get_user_meta( $user_id, 'dropbox_folders_files', true );
              $sub_folders          = get_user_meta( $user_id, 'sub_folders', true );
              $user_dropbox_path    = ( isset( $sub_folders ) && !empty( $sub_folders ) ) ? $sub_folders : $user_dropbox_path;
              $settings_options   = get_option( 'fflgaurd_settings_options' );
              $access_token       = ( !empty( $settings_options['access_token'] ) ? $settings_options['access_token'] : '' );
              if ( isset($_POST['dcf_sub_folder_path'] ) && !empty( $_POST['dcf_sub_folder_path'] ) ) {
                $user_dropbox_path  = $_POST['dcf_sub_folder_path'];

              }
              if ( isset( $user_dropbox_path ) && !empty( $user_dropbox_path ) ) {

                  $item_upload_path = $user_dropbox_path .'/'.$_FILES['dcf_file_sub']['name'];
                  $fp = fopen($_FILES['dcf_file_sub']['tmp_name'], 'rb');
                  $size = filesize($_FILES['dcf_file_sub']['tmp_name']);
                  $curl = curl_init();

                  curl_setopt_array($curl, array(
                    CURLOPT_URL => "https://content.dropboxapi.com/2/files/upload",
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_PUT => true,
                    CURLOPT_INFILE => $fp,
                    CURLOPT_INFILESIZE => $size,
                    CURLOPT_CUSTOMREQUEST => "POST",
                    CURLOPT_HTTPHEADER => array(
                      "authorization: Bearer ".$access_token,
                      "content-type: application/octet-stream",
                      "dropbox-api-arg: {\"path\": \"".$item_upload_path."\",\"mode\":{\".tag\":\"add\"},\"autorename\":true}",
                    ),
                  ));

                  $response = curl_exec($curl);
                  $err = curl_error($curl);

                  curl_close($curl);

                  if ( $err ) {
                      echo $err;
                  } else {
                      $decoded_response = json_decode($response,true);

                  }
              }

            }
            ?>
            <div id="dcf-app"></div>
            <?php
        } else {
            ?>
            <div class="no-result"><?php esc_html_e( 'Login to view.', 'dcf' ); ?></div>
            <?php
        }

        return ob_get_clean();
    }

    public function dropbox_get_dropbox_folders() {

      $api_url_base = 'https://api.dropboxapi.com/2';
      $api_url      = $api_url_base.'/files/list_folder';
      $item_path    = sanitize_text_field( $_POST['path_lower'] );

      if ( isset( $item_path ) && !empty( $item_path ) ) {

        $settings_options   = get_option( 'fflgaurd_settings_options' );
        $access_token       = ( !empty( $settings_options['access_token'] ) ? $settings_options['access_token'] : '' );

        $curl = curl_init();

        curl_setopt_array($curl, array(
          CURLOPT_URL => $api_url,
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 30,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => "POST",
          CURLOPT_POSTFIELDS => "{\n\t\"path\":\"".$item_path."\",\"recursive\":false,\"include_media_info\":true\n}",
          CURLOPT_HTTPHEADER => array(
            "authorization: Bearer {$access_token}",
            "cache-control: no-cache",
            "content-type: application/json",
          ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ( $err ) {
            $error = array('json'=>$err);
            return $error;
        } else {
            $response = json_decode($response,true);
            if($json_error = json_last_error()) {
                $error = array('json'=>$json_error);
                // return $error;
            } else {


              $folder_lists   = $response['entries'];
              $folder_array   = array();
              if ( isset( $folder_lists ) && !empty( $folder_lists ) ) {
                foreach ($folder_lists as $list_value) {

                  if ( 'folder' == $list_value['.tag'] ) {
                    $folder['name']       = $list_value['name'];
                    $folder['path_lower'] = $list_value['path_lower'];
                    array_push($folder_array, $folder);
                  }
                }
              }

              if ( isset( $folder_array ) && !empty( $folder_array ) ) {

                $response = array(
                  'type'    => 'success',
                  'folders' => $folder_array
                );
                wp_send_json( $response );
              } else {
                $response = array(
                  'type'    => 'error',
                  'folders' => array()
                );
                wp_send_json( $response );
              }
            }
        }
      } else {
        $response = array(
          'type'    => 'error',
          'folders' => array()
        );
        wp_send_json( $response );
      }

    }

    /**
    * Rest call back response
    */
    public function call_endpoint( $url, $data=array() ) {

      $settings_options   = get_option( 'fflgaurd_settings_options' );
      $access_token       = ( !empty( $settings_options['access_token'] ) ? $settings_options['access_token'] : '' );

      $user_id              = get_current_user_id();
      $user_dropbox_path    = get_user_meta( $user_id, 'dropbox_folders_files', true );
      $sub_folders          = get_user_meta( $user_id, 'sub_folders', true );
      $user_dropbox_path    = ( isset( $sub_folders ) && !empty( $sub_folders ) ) ? $sub_folders : $user_dropbox_path;

      if( strpos( $data['item_path'], $user_dropbox_path ) !== false ) {

        $curl = curl_init();

        curl_setopt_array($curl, array(
          CURLOPT_URL => $url,
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 30,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => "POST",
          CURLOPT_POSTFIELDS => "{\n\t\"path\":\"".$data['item_path']."\",\"recursive\":false,\"include_media_info\":true\n}",
          CURLOPT_HTTPHEADER => array(
            "authorization: Bearer {$access_token}",
            "cache-control: no-cache",
            "content-type: application/json",
          ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ( $err ) {
            $error = array('json'=>$err);
            return $error;
        } else {
            $response = json_decode($response,true);
            if($json_error = json_last_error()) {
                $error = array('json'=>$json_error);
                return $error;
            } else {
                return $response;
            }
        }
      } else {
        return false;
      }
    }

    /**
    * Rest API callback for file download
    */
    public function call_download_endpoint( $url, $data=array() ){
        $settings_options   = get_option( 'fflgaurd_settings_options' );
        $access_token       = ( !empty( $settings_options['access_token'] ) ? $settings_options['access_token'] : '' );

        $curl = curl_init();

        curl_setopt_array($curl, array(
          CURLOPT_URL => $url,
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 30,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => "POST",
          CURLOPT_POSTFIELDS => "{\n\t\"path\":\"".$data['item_path']."\"\n}",
          CURLOPT_HTTPHEADER => array(
            "authorization: Bearer ".$access_token,
            "content-type: application/json",
          ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ( $err ) {
            $error = array('json'=>$err);
            return $error;
        } else {
            $decoded_response = json_decode($response,true);

            if($json_error = json_last_error()) {
                $error = array('json'=>$json_error);
                return $error;
            } else {
                return $decoded_response;
            }
        }
    }

    /**
    * Rest API for files upload
    */
    public function call_upload_endpoint( $url, $data=array() ) {

        $settings_options   = get_option( 'fflgaurd_settings_options' );
        $access_token       = ( !empty( $settings_options['access_token'] ) ? $settings_options['access_token'] : '' );

        $item_path  = $data['item_path'];
        $item_upload_path = $item_path .'/'.$data['file_name'];

        $curl = curl_init();

        curl_setopt_array($curl, array(
          CURLOPT_URL => "https://content.dropboxapi.com/2/files/upload",
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => "",
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 30,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => "POST",
          // CURLOPT_POSTFIELDS => "{\n\t\"data-binary\":\""."@".$data['file_name']."\"}",
          CURLOPT_HTTPHEADER => array(
            "authorization: Bearer KQaQG7zMG4AAAAAAAAAAE6oF0OK2wNo7mlRoU5UycRn195a5z7NRHr3x4IiMFloJ",
            "cache-control: no-cache",
            "content-type: application/octet-stream",
            "dropbox-api-arg: {\"path\": \"".$item_upload_path."\",\"mode\":{\".tag\":\"add\"},\"autorename\":true}",
          ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ( $err ) {
            $error = array('json'=>$err);
            return $error;
        } else {
            $decoded_response = json_decode($response,true);

            if($json_error = json_last_error()) {
                $error = array('json'=>$json_error);
                return $error;
            } else {
                return $decoded_response;
            }
        }

    }

}