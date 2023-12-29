<?php
/*
Plugin Name: MVP Log
Description: A Minimum Viable Product for logging to a custom table.
Version: 1.0
Author: Rick Hurst
*/

namespace MVPLog;

// Activation Hook
register_activation_hook(__FILE__, function () {
    global $wpdb;
    $table_name = $wpdb->prefix . 'mvp_log';

    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        datetime datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        message text NOT NULL,
        variable text,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
});

// Log Function
function log_message($message, $variable = null) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'mvp_log';

    $data = array(
        'datetime' => current_time('mysql'),
        'message' => $message,
        'variable' => var_export($variable, true),
    );

    $wpdb->insert($table_name, $data);
}

// Usage: apply_filters('mvp_log_message', $message_to_log, $variable_to_log);
add_filter('mvp_log_message', function ($message, $variable = null) {
    log_message($message, $variable);
}, 10, 2);

function schedule_cleanup() {
    // WP Cron Job for Daily Cleanup (Only in WP Admin context, user logged in)
    if (\is_admin() && \is_user_logged_in() && !\wp_next_scheduled('mvp_log_daily_cleanup')) {
        \wp_schedule_event(time(), 'daily', 'mvp_log_daily_cleanup');
    }
}
add_action('init', '\MVPLog\schedule_cleanup');

add_action('mvp_log_daily_cleanup', function () {
    global $wpdb;
    $table_name = $wpdb->prefix . 'mvp_log';

    // Delete records older than 7 days
    $seven_days_ago = date('Y-m-d H:i:s', strtotime('-7 days'));
    $wpdb->query("DELETE FROM $table_name WHERE datetime < '$seven_days_ago'");

    // If total records exceed 100,000, delete the oldest records
    $total_records = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");

    if ($total_records > 100000) {
        $records_to_delete = $total_records - 100000;
        $oldest_records = $wpdb->get_results("SELECT id FROM $table_name ORDER BY datetime ASC LIMIT $records_to_delete");

        foreach ($oldest_records as $record) {
            $wpdb->delete($table_name, array('id' => $record->id));
        }
    }
});

// CLI Command to Reset Table
if (defined('WP_CLI') && \WP_CLI) {
    class MVP_Log_CLI_Command
    {
        /**
         * Reset the MVP Log table by truncating it.
         *
         * ## EXAMPLES
         *
         *     wp mvplog reset
         *
         * @when after_wp_load
         */
        public function reset($args, $assoc_args)
        {
            global $wpdb;
            $table_name = $wpdb->prefix . 'mvp_log';
            $wpdb->query("TRUNCATE TABLE $table_name");
            \WP_CLI::success('MVP Log table has been reset.');
        }
    }

    \WP_CLI::add_command('mvplog', '\MVPLog\MVP_Log_CLI_Command');
}
