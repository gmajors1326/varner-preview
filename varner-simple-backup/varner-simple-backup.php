<?php
/**
 * Plugin Name: Varner Simple Backup
 * Description: Lightweight one-site backup with scheduler (database + uploads) and manual download. Built for Varner Equipment.
 * Version: 1.0.0
 * Author: OpenCode
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// ─────────────────────────────────────────────────────────────
// Helpers
// ─────────────────────────────────────────────────────────────

function varner_sb_backups_dir() {
    return trailingslashit( WP_CONTENT_DIR ) . 'varner-backups';
}

function varner_sb_backups_url() {
    return trailingslashit( content_url() ) . 'varner-backups';
}

function varner_sb_ensure_dir() {
    $dir = varner_sb_backups_dir();
    if ( ! file_exists( $dir ) ) {
        wp_mkdir_p( $dir );
    }
    return is_dir( $dir ) && is_writable( $dir );
}

function varner_sb_get_schedule() {
    return get_option( 'varner_sb_schedule', 'daily' ); // daily | weekly | none
}

function varner_sb_get_includes() {
    return array(
        'themes'   => (bool) get_option( 'varner_sb_include_themes', false ),
        'plugins'  => (bool) get_option( 'varner_sb_include_plugins', false ),
        'uploads'  => true, // always
    );
}

// ─────────────────────────────────────────────────────────────
// Activation / Deactivation
// ─────────────────────────────────────────────────────────────

register_activation_hook( __FILE__, function() {
    varner_sb_ensure_dir();
    varner_sb_reschedule();
} );

register_deactivation_hook( __FILE__, function() {
    varner_sb_unschedule();
} );

function varner_sb_unschedule() {
    $timestamp = wp_next_scheduled( 'varner_sb_cron_backup' );
    if ( $timestamp ) {
        wp_unschedule_event( $timestamp, 'varner_sb_cron_backup' );
    }
}

function varner_sb_reschedule() {
    varner_sb_unschedule();
    $schedule = varner_sb_get_schedule();
    if ( $schedule === 'none' ) {
        return;
    }
    $recurrence = $schedule === 'weekly' ? 'weekly' : 'daily';
    if ( ! wp_next_scheduled( 'varner_sb_cron_backup' ) ) {
        wp_schedule_event( time() + HOUR_IN_SECONDS, $recurrence, 'varner_sb_cron_backup' );
    }
}

add_filter( 'cron_schedules', function( $schedules ) {
    if ( ! isset( $schedules['weekly'] ) ) {
        $schedules['weekly'] = array( 'interval' => WEEK_IN_SECONDS, 'display' => __( 'Once Weekly' ) );
    }
    return $schedules;
} );

add_action( 'varner_sb_cron_backup', 'varner_sb_run_backup' );

// ─────────────────────────────────────────────────────────────
// Backup routine
// ─────────────────────────────────────────────────────────────

function varner_sb_run_backup() {
    $ok = varner_sb_ensure_dir();
    if ( ! $ok ) {
        return;
    }

    $timestamp = current_time( 'Ymd-His' );
    $zip_path  = varner_sb_backups_dir() . "/varner-backup-{$timestamp}.zip";

    if ( ! class_exists( 'ZipArchive' ) ) {
        error_log( 'Varner Simple Backup: ZipArchive not available.' );
        return;
    }

    $zip = new ZipArchive();
    if ( $zip->open( $zip_path, ZipArchive::CREATE | ZipArchive::OVERWRITE ) !== true ) {
        error_log( 'Varner Simple Backup: cannot create zip at ' . $zip_path );
        return;
    }

    // Add DB dump
    $sql = varner_sb_dump_db();
    $zip->addFromString( 'database.sql', $sql );

    $include = varner_sb_get_includes();

    // Add uploads directory (always)
    $uploads = wp_get_upload_dir();
    $uploads_dir = trailingslashit( $uploads['basedir'] );
    varner_sb_zip_folder( $zip, $uploads_dir, 'uploads/' );

    if ( $include['themes'] ) {
        $themes_dir = trailingslashit( WP_CONTENT_DIR ) . 'themes/';
        varner_sb_zip_folder( $zip, $themes_dir, 'themes/' );
    }

    if ( $include['plugins'] ) {
        $plugins_dir = trailingslashit( WP_CONTENT_DIR ) . 'plugins/';
        varner_sb_zip_folder( $zip, $plugins_dir, 'plugins/' );
    }

    $zip->close();
}

// Restore from a backup zip
function varner_sb_run_restore( $zip_file ) {
    if ( ! file_exists( $zip_file ) || ! class_exists( 'ZipArchive' ) ) {
        return new WP_Error( 'missing_zip', 'Backup file not found or ZipArchive unavailable.' );
    }

    $tmp_dir = varner_sb_backups_dir() . '/tmp-' . wp_generate_password( 6, false );
    wp_mkdir_p( $tmp_dir );

    $zip = new ZipArchive();
    if ( $zip->open( $zip_file ) !== true ) {
        return new WP_Error( 'open_zip', 'Unable to open backup zip.' );
    }
    $zip->extractTo( $tmp_dir );
    $zip->close();

    // Restore database
    $sql_path = $tmp_dir . '/database.sql';
    if ( file_exists( $sql_path ) ) {
        $sql = file_get_contents( $sql_path );
        $db_result = varner_sb_import_sql( $sql );
        if ( is_wp_error( $db_result ) ) {
            varner_sb_rrmdir( $tmp_dir );
            return $db_result;
        }
    }

    // Restore uploads/themes/plugins if present
    $targets = array(
        'uploads' => trailingslashit( WP_CONTENT_DIR ) . 'uploads/',
        'themes'  => trailingslashit( WP_CONTENT_DIR ) . 'themes/',
        'plugins' => trailingslashit( WP_CONTENT_DIR ) . 'plugins/',
    );

    foreach ( $targets as $dir => $dest ) {
        $src = $tmp_dir . '/' . $dir . '/';
        if ( is_dir( $src ) ) {
            varner_sb_copy_dir( $src, $dest );
        }
    }

    varner_sb_rrmdir( $tmp_dir );
    return true;
}

function varner_sb_import_sql( $sql ) {
    global $wpdb;
    // Prefer mysqli_multi_query for speed
    $mysqli = $wpdb->dbh;
    if ( ! $mysqli || ! method_exists( $mysqli, 'multi_query' ) ) {
        return new WP_Error( 'mysqli', 'MySQLi not available for import.' );
    }
    if ( ! $mysqli->multi_query( $sql ) ) {
        return new WP_Error( 'import_failed', 'Import failed: ' . $mysqli->error );
    }
    // flush results
    while ( $mysqli->more_results() ) {
        $mysqli->next_result();
    }
    return true;
}

function varner_sb_copy_dir( $src, $dst ) {
    $src = untrailingslashit( $src );
    $dst = untrailingslashit( $dst );
    wp_mkdir_p( $dst );
    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator( $src, FilesystemIterator::SKIP_DOTS ),
        RecursiveIteratorIterator::SELF_FIRST
    );
    foreach ( $it as $file ) {
        $target = $dst . str_replace( $src, '', $file->getPathname() );
        if ( $file->isDir() ) {
            wp_mkdir_p( $target );
        } else {
            copy( $file->getPathname(), $target );
        }
    }
}

function varner_sb_rrmdir( $dir ) {
    if ( ! is_dir( $dir ) ) return;
    $it = new RecursiveDirectoryIterator( $dir, FilesystemIterator::SKIP_DOTS );
    $files = new RecursiveIteratorIterator( $it, RecursiveIteratorIterator::CHILD_FIRST );
    foreach ( $files as $file ) {
        $file->isDir() ? rmdir( $file->getPathname() ) : unlink( $file->getPathname() );
    }
    rmdir( $dir );
}

function varner_sb_dump_db() {
    global $wpdb;
    $out = "-- Varner Simple Backup SQL\n";
    $out .= "-- Generated: " . current_time( 'mysql' ) . "\n\n";

    $tables = $wpdb->get_col( 'SHOW TABLES' );
    foreach ( $tables as $table ) {
        $create = $wpdb->get_row( "SHOW CREATE TABLE `{$table}`", ARRAY_N );
        if ( isset( $create[1] ) ) {
            $out .= "DROP TABLE IF EXISTS `{$table}`;\n";
            $out .= $create[1] . ";\n\n";
        }

        $rows = $wpdb->get_results( "SELECT * FROM `{$table}`", ARRAY_A );
        foreach ( $rows as $row ) {
            $vals = array();
            foreach ( $row as $val ) {
                if ( is_null( $val ) ) {
                    $vals[] = 'NULL';
                } else {
                    $vals[] = "'" . esc_sql( $val ) . "'";
                }
            }
            $out .= "INSERT INTO `{$table}` VALUES (" . implode( ',', $vals ) . ");\n";
        }
        if ( ! empty( $rows ) ) {
            $out .= "\n";
        }
    }
    return $out;
}

function varner_sb_zip_folder( ZipArchive $zip, $folder, $base = '' ) {
    $folder = untrailingslashit( $folder );
    if ( ! is_dir( $folder ) ) return;

    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator( $folder, FilesystemIterator::SKIP_DOTS )
    );

    foreach ( $files as $file ) {
        $filePath     = $file->getRealPath();
        $relativePath = $base . ltrim( str_replace( $folder, '', $filePath ), '\\/' );
        if ( $file->isDir() ) {
            $zip->addEmptyDir( $relativePath );
        } else {
            $zip->addFile( $filePath, $relativePath );
        }
    }
}

// ─────────────────────────────────────────────────────────────
// Admin UI
// ─────────────────────────────────────────────────────────────

add_action( 'admin_menu', function() {
    add_menu_page(
        'Varner Backup',
        'Varner Backup',
        'manage_options',
        'varner-backup',
        'varner_sb_render_admin',
        'dashicons-database-export',
        59
    );
} );

function varner_sb_render_admin() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $dir_ok   = varner_sb_ensure_dir();
    $schedule = varner_sb_get_schedule();
    $includes = varner_sb_get_includes();
    $backups  = glob( varner_sb_backups_dir() . '/*.zip' );
    $backups  = $backups ? array_reverse( $backups ) : array();

    ?>
    <div class="wrap">
        <h1 style="margin-bottom:14px;">Varner Backup</h1>
        <p>Backs up database + uploads into <code><?php echo esc_html( varner_sb_backups_dir() ); ?></code>. Keep a copy offsite.</p>

        <?php if ( ! $dir_ok ) : ?>
            <div class="notice notice-error"><p>Backup directory is not writable: <?php echo esc_html( varner_sb_backups_dir() ); ?></p></div>
        <?php endif; ?>

        <h2>Run Backup Now</h2>
        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
            <?php wp_nonce_field( 'varner_sb_run' ); ?>
            <input type="hidden" name="action" value="varner_sb_run">
            <button class="button button-primary" <?php disabled( ! $dir_ok ); ?>>Run Backup</button>
        </form>

        <h2 style="margin-top:24px;">Schedule</h2>
        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
            <?php wp_nonce_field( 'varner_sb_schedule' ); ?>
            <input type="hidden" name="action" value="varner_sb_save_schedule">
            <select name="varner_sb_schedule" id="varner_sb_schedule">
                <option value="daily" <?php selected( $schedule, 'daily' ); ?>>Daily</option>
                <option value="weekly" <?php selected( $schedule, 'weekly' ); ?>>Weekly</option>
                <option value="none" <?php selected( $schedule, 'none' ); ?>>Disabled</option>
            </select>
            <label style="margin-left:12px;">
                <input type="checkbox" name="varner_sb_include_themes" value="1" <?php checked( $includes['themes'], true ); ?>> Include themes
            </label>
            <label style="margin-left:12px;">
                <input type="checkbox" name="varner_sb_include_plugins" value="1" <?php checked( $includes['plugins'], true ); ?>> Include plugins
            </label>
            <button class="button">Save</button>
        </form>

        <h2 style="margin-top:24px;">Backups</h2>
        <div class="notice notice-warning" style="margin:12px 0 18px;">
            <p><strong>Warning:</strong> Restoring will overwrite the current database and files (uploads, and themes/plugins if included). Download a copy before restoring and ensure you have an offsite backup.</p>
        </div>
        <?php if ( empty( $backups ) ) : ?>
            <p>No backups found.</p>
        <?php else : ?>
            <table class="widefat fixed striped" style="max-width:800px;">
                <thead><tr><th>File</th><th>Size</th><th>Actions</th></tr></thead>
                <tbody>
                <?php foreach ( $backups as $file ) :
                    $name = basename( $file );
                    $size = size_format( filesize( $file ), 2 );
                    $url  = add_query_arg( array(
                        'action' => 'varner_sb_download',
                        'file'   => rawurlencode( $name ),
                        '_wpnonce' => wp_create_nonce( 'varner_sb_download_' . $name ),
                    ), admin_url( 'admin-post.php' ) );
                    $restore_url = add_query_arg( array(
                        'action' => 'varner_sb_restore',
                        'file'   => rawurlencode( $name ),
                        '_wpnonce' => wp_create_nonce( 'varner_sb_restore_' . $name ),
                    ), admin_url( 'admin-post.php' ) );
                    $delete_url = add_query_arg( array(
                        'action' => 'varner_sb_delete',
                        'file'   => rawurlencode( $name ),
                        '_wpnonce' => wp_create_nonce( 'varner_sb_delete_' . $name ),
                    ), admin_url( 'admin-post.php' ) );
                ?>
                    <tr>
                        <td><?php echo esc_html( $name ); ?></td>
                        <td><?php echo esc_html( $size ); ?></td>
                        <td style="display:flex; gap:6px; flex-wrap: wrap;">
                            <a class="button" href="<?php echo esc_url( $url ); ?>">Download</a>
                            <a class="button button-primary" href="<?php echo esc_url( $restore_url ); ?>" onclick="return confirm('Restore this backup? This will overwrite database and files.');">Restore</a>
                            <a class="button button-secondary" href="<?php echo esc_url( $delete_url ); ?>" onclick="return confirm('Delete this backup file?');">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    <?php
}

// ─────────────────────────────────────────────────────────────
// Admin actions
// ─────────────────────────────────────────────────────────────

add_action( 'admin_post_varner_sb_run', function() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( 'Unauthorized' );
    }
    check_admin_referer( 'varner_sb_run' );
    varner_sb_run_backup();
    wp_safe_redirect( wp_get_referer() );
    exit;
} );

add_action( 'admin_post_varner_sb_save_schedule', function() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( 'Unauthorized' );
    }
    check_admin_referer( 'varner_sb_schedule' );
    $schedule = isset( $_POST['varner_sb_schedule'] ) ? sanitize_text_field( wp_unslash( $_POST['varner_sb_schedule'] ) ) : 'daily';
    if ( ! in_array( $schedule, array( 'daily', 'weekly', 'none' ), true ) ) {
        $schedule = 'daily';
    }
    $include_themes  = isset( $_POST['varner_sb_include_themes'] ) ? 1 : 0;
    $include_plugins = isset( $_POST['varner_sb_include_plugins'] ) ? 1 : 0;
    update_option( 'varner_sb_schedule', $schedule );
    update_option( 'varner_sb_include_themes',  $include_themes );
    update_option( 'varner_sb_include_plugins', $include_plugins );
    varner_sb_reschedule();
    wp_safe_redirect( wp_get_referer() );
    exit;
} );

add_action( 'admin_post_varner_sb_download', function() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( 'Unauthorized' );
    }
    $file = isset( $_GET['file'] ) ? basename( sanitize_text_field( wp_unslash( $_GET['file'] ) ) ) : '';
    if ( ! $file ) {
        wp_die( 'Missing file.' );
    }
    check_admin_referer( 'varner_sb_download_' . $file );
    $path = varner_sb_backups_dir() . '/' . $file;
    if ( ! file_exists( $path ) ) {
        wp_die( 'File not found.' );
    }

    header( 'Content-Type: application/zip' );
    header( 'Content-Disposition: attachment; filename=' . basename( $path ) );
    header( 'Content-Length: ' . filesize( $path ) );
    readfile( $path );
    exit;
} );

add_action( 'admin_post_varner_sb_restore', function() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( 'Unauthorized' );
    }
    $file = isset( $_GET['file'] ) ? basename( sanitize_text_field( wp_unslash( $_GET['file'] ) ) ) : '';
    if ( ! $file ) {
        wp_die( 'Missing file.' );
    }
    check_admin_referer( 'varner_sb_restore_' . $file );
    $path = varner_sb_backups_dir() . '/' . $file;
    if ( ! file_exists( $path ) ) {
        wp_die( 'File not found.' );
    }

    $result = varner_sb_run_restore( $path );
    if ( is_wp_error( $result ) ) {
        wp_die( 'Restore failed: ' . esc_html( $result->get_error_message() ) );
    }
    wp_safe_redirect( wp_get_referer() );
    exit;
} );

add_action( 'admin_post_varner_sb_delete', function() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( 'Unauthorized' );
    }
    $file = isset( $_GET['file'] ) ? basename( sanitize_text_field( wp_unslash( $_GET['file'] ) ) ) : '';
    if ( ! $file ) {
        wp_die( 'Missing file.' );
    }
    check_admin_referer( 'varner_sb_delete_' . $file );
    $path = varner_sb_backups_dir() . '/' . $file;
    if ( file_exists( $path ) ) {
        unlink( $path );
    }
    wp_safe_redirect( wp_get_referer() );
    exit;
} );
