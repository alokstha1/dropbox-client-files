<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$settings_options = get_option( 'fflgaurd_settings_options' );
$user_id            = get_current_user_id();
$user_dropbox_path    = get_user_meta( $user_id, 'dropbox_folders_files', true );


?>

<div class="wrap">

    <h1><?php _e( 'Dropbox Client Files Setting', 'dcf' ); ?></h1>
    <?php settings_errors(); ?>

    <div id="dcf-setting">

        <form method="POST" action="options.php" id="dcf-setting-form">
            <?php
            if ( function_exists( 'wp_nonce_field' ) ) {
                wp_nonce_field( 'dcf_nonce_feeds', 'validate_submit' );
            }
            ?>

            <h3 class="hndle">
                <span><?php _e( 'Plugin options', 'dcf' ); ?></span>
            </h3>

            <table class="form-table">
                <tbody>
                    <tr>
                        <th>
                            <label for="dcf-browse-unavailable-message">
                                <?php _e( 'Browse Unavailable Message', 'dcf' ); ?>
                            </label>
                        </th>
                        <td>
                            <textarea rows="10" cols="50" class="large-text code" id="dcf-browse-unavailable-message" name="fflgaurd_settings_options[unavailable_message]"><?php echo ( isset( $settings_options['unavailable_message'] ) && !empty( $settings_options['unavailable_message'] ) ) ? esc_attr( $settings_options['unavailable_message'] ) : 'Browse Unavailable Message'; ?></textarea>
                        </td>
                    </tr>

                    <tr>
                        <th>
                            <label for="dcf-upload-unavailable-message">
                                <?php _e( 'Upload Unavailable Message', 'dcf' ); ?>
                            </label>
                        </th>
                        <td>
                            <textarea rows="10" cols="50" class="large-text code" id="dcf-upload-unavailable-message" name="fflgaurd_settings_options[upload_unavailable_message]"><?php echo ( isset( $settings_options['upload_unavailable_message'] ) && !empty( $settings_options['upload_unavailable_message'] ) ) ? esc_attr( $settings_options['upload_unavailable_message'] ) : 'Upload Unavailable Message'; ?></textarea>
                        </td>
                    </tr>

                    <tr>
                        <th>
                            <label for="dcf-access-token">
                                <?php _e( 'Access Token', 'dcf' ); ?>
                            </label>
                        </th>
                        <td>
                            <input class="regular-text" type="text" name="fflgaurd_settings_options[access_token]" id="dcf-access-token" value="<?php echo ( isset( $settings_options['access_token'] ) && !empty( $settings_options['access_token'] ) ) ? esc_attr( $settings_options['access_token'] ) : ''; ?>" />
                        </td>
                    </tr>

                    <tr>
                        <th>
                            <label for="dcf-parent-folder">
                                <?php _e( 'Parent Folder', 'dcf' ); ?>
                            </label>
                        </th>
                        <td>
                            <input class="regular-text" type="text" name="fflgaurd_settings_options[parent_folder]" id="dcf-access-token" value="<?php echo ( isset( $settings_options['parent_folder'] ) && !empty( $settings_options['parent_folder'] ) ) ? esc_attr( $settings_options['parent_folder'] ) : ''; ?>" />
                        </td>
                    </tr>
                </tbody>
            </table>

            <?php settings_fields( 'fflgaurd_settings_options' ); ?>
            <p class="submit">
                <?php submit_button( __( 'Save Changes', 'dcf' ), 'primary', 'submit_options', false ); ?>
            </p>

        </form>

    </div>

</div>