<?php
/**
 * Database handler class for eZZY Wallet.
 *
 * Handles all database table creation and schema management.
 *
 * @package EzzyWallet
 * @since 1.0.0
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class Ezzy_Wallet_DB
 *
 * Manages database operations for the eZZY Wallet plugin.
 *
 * @since 1.0.0
 */
class Ezzy_Wallet_DB {

    /**
     * Database version for schema tracking.
     *
     * @var string
     */
    const DB_VERSION = '1.5.0';

    /**
     * Option name for storing database version.
     *
     * @var string
     */
    const DB_VERSION_OPTION = 'ezzy_wallet_db_version';

    /**
     * Get the accounts table name.
     *
     * @return string Full table name with prefix.
     */
    public static function get_accounts_table_name() {
        global $wpdb;
        return $wpdb->prefix . 'ezzy_wallet_accounts';
    }

    /**
     * Get the transactions table name.
     *
     * @return string Full table name with prefix.
     */
    public static function get_transactions_table_name() {
        global $wpdb;
        return $wpdb->prefix . 'ezzy_wallet_transactions';
    }

    /**
     * Get the notifications table name.
     *
     * @return string Full table name with prefix.
     */
    public static function get_notifications_table_name() {
        global $wpdb;
        return $wpdb->prefix . 'ezzy_wallet_notifications';
    }

    /**
     * Get the notification reads table name.
     *
     * @return string Full table name with prefix.
     */
    public static function get_notification_reads_table_name() {
        global $wpdb;
        return $wpdb->prefix . 'ezzy_wallet_notification_reads';
    }

    /**
     * Create all database tables.
     *
     * Uses dbDelta() for safe table creation and updates.
     *
     * @return void
     */
    public static function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        // Include the upgrade functions for dbDelta().
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        // Create accounts table.
        self::create_accounts_table( $charset_collate );

        // Create transactions table.
        self::create_transactions_table( $charset_collate );

        // Create notifications table.
        self::create_notifications_table( $charset_collate );

        // Create notification reads table.
        self::create_notification_reads_table( $charset_collate );

        // Store the database version.
        update_option( self::DB_VERSION_OPTION, self::DB_VERSION );
    }

    /**
     * Create the wallet accounts table.
     *
     * @param string $charset_collate The database charset collation.
     * @return void
     */
    private static function create_accounts_table( $charset_collate ) {
        $table_name = self::get_accounts_table_name();

        $sql = "CREATE TABLE {$table_name} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT(20) UNSIGNED NOT NULL,
            balance DECIMAL(20,8) NOT NULL DEFAULT 0.00000000,
            total_deposit DECIMAL(20,8) NOT NULL DEFAULT 0.00000000,
            total_transfer DECIMAL(20,8) NOT NULL DEFAULT 0.00000000,
            total_withdrawal DECIMAL(20,8) NOT NULL DEFAULT 0.00000000,
            withdrawals_disabled TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY user_id (user_id),
            KEY idx_user_id (user_id),
            KEY idx_created_at (created_at)
        ) {$charset_collate};";

        dbDelta( $sql );
    }

    /**
     * Create the wallet transactions table.
     *
     * @param string $charset_collate The database charset collation.
     * @return void
     */
    private static function create_transactions_table( $charset_collate ) {
        $table_name = self::get_transactions_table_name();

        $sql = "CREATE TABLE {$table_name} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT(20) UNSIGNED NOT NULL,
            type VARCHAR(50) NOT NULL,
            amount DECIMAL(20,8) NOT NULL,
            description TEXT,
            status VARCHAR(30) NOT NULL DEFAULT 'pending',
            metadata TEXT,
            admin_id BIGINT(20) UNSIGNED DEFAULT NULL,
            admin_note TEXT,
            processed_at DATETIME DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY idx_user_id (user_id),
            KEY idx_type (type),
            KEY idx_user_type (user_id, type),
            KEY idx_status (status),
            KEY idx_created_at (created_at)
        ) {$charset_collate};";

        dbDelta( $sql );
    }

    /**
     * Create the notifications table.
     *
     * @param string $charset_collate The database charset collation.
     * @return void
     */
    private static function create_notifications_table( $charset_collate ) {
        $table_name = self::get_notifications_table_name();

        $sql = "CREATE TABLE {$table_name} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            title VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            type VARCHAR(50) NOT NULL DEFAULT 'info',
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_by BIGINT(20) UNSIGNED NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY idx_is_active (is_active),
            KEY idx_created_at (created_at),
            KEY idx_type (type)
        ) {$charset_collate};";

        dbDelta( $sql );
    }

    /**
     * Create the notification reads tracking table.
     * notification_id is VARCHAR to support both admin IDs and system IDs (tx_123 format).
     *
     * @param string $charset_collate The database charset collation.
     * @return void
     */
    private static function create_notification_reads_table( $charset_collate ) {
        $table_name = self::get_notification_reads_table_name();

        $sql = "CREATE TABLE {$table_name} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT(20) UNSIGNED NOT NULL,
            notification_id VARCHAR(50) NOT NULL,
            read_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            UNIQUE KEY idx_user_notification (user_id, notification_id),
            KEY idx_user_id (user_id),
            KEY idx_notification_id (notification_id)
        ) {$charset_collate};";

        dbDelta( $sql );
    }

    /**
     * Check if database needs upgrade.
     *
     * @return bool True if upgrade needed, false otherwise.
     */
    public static function needs_upgrade() {
        $installed_version = get_option( self::DB_VERSION_OPTION, '0' );
        return version_compare( $installed_version, self::DB_VERSION, '<' );
    }

    /**
     * Run database upgrade if needed.
     *
     * @return void
     */
    public static function maybe_upgrade() {
        if ( self::needs_upgrade() ) {
            self::create_tables();
        }
    }

    /**
     * Drop all plugin tables.
     *
     * Use with caution - this permanently deletes all data.
     *
     * @return void
     */
    public static function drop_tables() {
        global $wpdb;

        $accounts_table           = self::get_accounts_table_name();
        $transactions_table       = self::get_transactions_table_name();
        $notifications_table      = self::get_notifications_table_name();
        $notification_reads_table = self::get_notification_reads_table_name();

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $wpdb->query( "DROP TABLE IF EXISTS {$notification_reads_table}" );
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $wpdb->query( "DROP TABLE IF EXISTS {$notifications_table}" );
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $wpdb->query( "DROP TABLE IF EXISTS {$transactions_table}" );
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $wpdb->query( "DROP TABLE IF EXISTS {$accounts_table}" );

        delete_option( self::DB_VERSION_OPTION );
    }

    /**
     * Check if tables exist.
     *
     * @return bool True if all tables exist, false otherwise.
     */
    public static function tables_exist() {
        global $wpdb;

        $accounts_table           = self::get_accounts_table_name();
        $transactions_table       = self::get_transactions_table_name();
        $notifications_table      = self::get_notifications_table_name();

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $accounts_exists = $wpdb->get_var(
            $wpdb->prepare(
                'SHOW TABLES LIKE %s',
                $accounts_table
            )
        ) === $accounts_table;

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $transactions_exists = $wpdb->get_var(
            $wpdb->prepare(
                'SHOW TABLES LIKE %s',
                $transactions_table
            )
        ) === $transactions_table;

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $notifications_exists = $wpdb->get_var(
            $wpdb->prepare(
                'SHOW TABLES LIKE %s',
                $notifications_table
            )
        ) === $notifications_table;

        return $accounts_exists && $transactions_exists && $notifications_exists;
    }
}
