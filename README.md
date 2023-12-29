# MVP Log WordPress Plugin

## Description

The MVP Log WordPress plugin provides a simple way to log messages to a custom table in the WordPress database. It includes a daily cleanup routine to manage the log entries, ensuring that records older than 7 days are deleted, and the total number of records does not exceed 100,000. Additionally, a WP-CLI command is included to reset (truncate) the log table.

The intention of this plugin is a light-touch logging option where a more feature-rich plugin is overkill. There is no admin page - use the `wp_mvp_log` table to view/search logs.

## Installation

1. Upload the `mvp-log` directory to the `wp-content/plugins/` directory.
2. Activate the "MVP Log" plugin through the 'Plugins' menu in WordPress.

## Usage

### Logging Messages

You can log messages using the `log_message` function provided by the plugin. Example:

```php
// Log a message and optional variable
\MVPLog\log_message('This is a logged message', $some_variable);

// Use the provided filter (handy failsafe option if you aren't sure if MVP Log is installed/activated)
apply_filters('mvp_log_message', 'This is a logged message', $some_variable);
