<?php
/**
 * Wallet Users List Table for eZZY Wallet.
 *
 * Displays all wallet accounts in a WP_List_Table format.
 *
 * @package EzzyWallet
 * @since 1.0.0
 */

// Prevent direct file access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Include WP_List_Table if not already loaded.
if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Class Ezzy_Wallet_Users_List_Table
 *
 * Extends WP_List_Table to display wallet users.
 *
 * @since 1.0.0
 */
class Ezzy_Wallet_Users_List_Table extends WP_List_Table {

    /**
     * Constructor.
     */
    public function __construct() {
        parent::__construct(
            array(
                'singular' => __( 'Wallet Account', 'ezzy-wallet' ),
                'plural'   => __( 'Wallet Accounts', 'ezzy-wallet' ),
                'ajax'     => false,
            )
        );
    }

    /**
     * Get table columns.
     *
     * @return array Column definitions.
     */
    public function get_columns() {
        return array(
            'user_id'              => __( 'User ID', 'ezzy-wallet' ),
            'username'             => __( 'Username', 'ezzy-wallet' ),
            'email'                => __( 'Email', 'ezzy-wallet' ),
            'balance'              => __( 'Balance', 'ezzy-wallet' ),
            'total_deposit'        => __( 'Total Deposit', 'ezzy-wallet' ),
            'total_transfer'       => __( 'Total Transfer', 'ezzy-wallet' ),
            'total_withdrawal'     => __( 'Total Withdrawal', 'ezzy-wallet' ),
            'withdrawals_disabled' => __( 'Withdrawals Disabled', 'ezzy-wallet' ),
            'actions'              => __( 'Actions', 'ezzy-wallet' ),
        );
    }

    /**
     * Get sortable columns.
     *
     * @return array Sortable columns.
     */
    public function get_sortable_columns() {
        return array(
            'user_id'          => array( 'user_id', true ),
            'username'         => array( 'username', false ),
            'balance'          => array( 'balance', false ),
            'total_deposit'    => array( 'total_deposit', false ),
            'total_withdrawal' => array( 'total_withdrawal', false ),
        );
    }

    /**
     * Prepare items for display.
     *
     * @return void
     */
    public function prepare_items() {
        global $wpdb;

        $per_page     = 20;
        $current_page = $this->get_pagenum();

        // Set column headers.
        $this->_column_headers = array(
            $this->get_columns(),
            array(),
            $this->get_sortable_columns(),
        );

        // Get search term.
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $search = isset( $_REQUEST['s'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) : '';

        // Get sorting.
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $orderby = isset( $_REQUEST['orderby'] ) ? sanitize_sql_orderby( wp_unslash( $_REQUEST['orderby'] ) ) : 'user_id';
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $order = isset( $_REQUEST['order'] ) && 'desc' === strtolower( $_REQUEST['order'] ) ? 'DESC' : 'ASC';

        // Validate orderby.
        $valid_orderby = array( 'user_id', 'username', 'balance', 'total_deposit', 'total_withdrawal' );
        if ( ! in_array( $orderby, $valid_orderby, true ) ) {
            $orderby = 'user_id';
        }

        // Build query.
        $accounts_table = Ezzy_Wallet_DB::get_accounts_table_name();
        $users_table    = $wpdb->users;

        // Base query.
        $base_query = "FROM {$accounts_table} AS wa
                       INNER JOIN {$users_table} AS u ON wa.user_id = u.ID";

        // Search condition.
        $where = '';
        if ( ! empty( $search ) ) {
            $search_like = '%' . $wpdb->esc_like( $search ) . '%';
            $where       = $wpdb->prepare(
                ' WHERE u.user_login LIKE %s OR u.user_email LIKE %s',
                $search_like,
                $search_like
            );
        }

        // Count total items.
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $total_items = $wpdb->get_var( "SELECT COUNT(*) {$base_query}{$where}" );

        // Handle orderby for username (it's in users table).
        if ( 'username' === $orderby ) {
            $orderby_clause = 'u.user_login';
        } else {
            $orderby_clause = "wa.{$orderby}";
        }

        // Get items.
        $offset = ( $current_page - 1 ) * $per_page;

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $query = $wpdb->prepare(
            "SELECT wa.*, u.user_login, u.user_email
             {$base_query}{$where}
             ORDER BY {$orderby_clause} {$order}
             LIMIT %d OFFSET %d",
            $per_page,
            $offset
        );

        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        $this->items = $wpdb->get_results( $query, ARRAY_A );

        // Set pagination.
        $this->set_pagination_args(
            array(
                'total_items' => $total_items,
                'per_page'    => $per_page,
                'total_pages' => ceil( $total_items / $per_page ),
            )
        );
    }

    /**
     * Default column handler.
     *
     * @param array  $item        Row data.
     * @param string $column_name Column name.
     * @return string Column content.
     */
    public function column_default( $item, $column_name ) {
        switch ( $column_name ) {
            case 'user_id':
                return esc_html( $item['user_id'] );

            case 'username':
                return esc_html( $item['user_login'] );

            case 'email':
                return esc_html( $item['user_email'] );

            case 'balance':
            case 'total_deposit':
            case 'total_transfer':
            case 'total_withdrawal':
                return $this->format_currency( $item[ $column_name ] );

            case 'withdrawals_disabled':
                if ( ! empty( $item['withdrawals_disabled'] ) ) {
                    return '<span class="ezzy-status ezzy-status-disabled">' . esc_html__( 'Yes', 'ezzy-wallet' ) . '</span>';
                }
                return '<span class="ezzy-status ezzy-status-enabled">' . esc_html__( 'No', 'ezzy-wallet' ) . '</span>';

            case 'actions':
                return $this->get_actions_column( $item );

            default:
                return '';
        }
    }

    /**
     * Format currency value.
     *
     * @param string $value Decimal value.
     * @return string Formatted currency.
     */
    private function format_currency( $value ) {
        $settings = get_option( 'ezzy_wallet_settings', array() );
        $currency = isset( $settings['default_currency'] ) ? $settings['default_currency'] : 'USD';

        $symbols = array(
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
        );

        $symbol = isset( $symbols[ $currency ] ) ? $symbols[ $currency ] : '$';

        return '<span class="ezzy-currency">' . esc_html( $symbol . number_format( (float) $value, 2 ) ) . '</span>';
    }

    /**
     * Get actions column content.
     *
     * @param array $item Row data.
     * @return string Actions HTML.
     */
    private function get_actions_column( $item ) {
        $edit_url = add_query_arg(
            array(
                'page'    => 'ezzy-wallet-users',
                'action'  => 'edit',
                'user_id' => $item['user_id'],
            ),
            admin_url( 'admin.php' )
        );

        $edit_url = wp_nonce_url( $edit_url, 'ezzy_wallet_edit_user_' . $item['user_id'] );

        $delete_url = add_query_arg(
            array(
                'page'    => 'ezzy-wallet-users',
                'action'  => 'delete',
                'user_id' => $item['user_id'],
            ),
            admin_url( 'admin.php' )
        );

        $delete_url = wp_nonce_url( $delete_url, 'ezzy_wallet_delete_user_' . $item['user_id'] );

        return sprintf(
            '<a href="%s" class="button button-small">%s</a> <a href="%s" class="ezzy-delete-user-btn" data-username="%s" onclick="return ezzyConfirmDeleteUser(event, this);">%s</a>',
            esc_url( $edit_url ),
            esc_html__( 'Edit', 'ezzy-wallet' ),
            esc_url( $delete_url ),
            esc_attr( $item['username'] ),
            esc_html__( 'Delete', 'ezzy-wallet' )
        );
    }

    /**
     * Message when no items found.
     *
     * @return void
     */
    public function no_items() {
        esc_html_e( 'No wallet accounts found.', 'ezzy-wallet' );
    }

    /**
     * Extra table navigation (search box).
     *
     * @param string $which Position (top/bottom).
     * @return void
     */
    protected function extra_tablenav( $which ) {
        if ( 'top' === $which ) {
            ?>
            <div class="alignleft actions">
                <?php
                // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                $search = isset( $_REQUEST['s'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) : '';
                ?>
                <input type="search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="<?php esc_attr_e( 'Search by username or email...', 'ezzy-wallet' ); ?>" />
                <?php submit_button( __( 'Search', 'ezzy-wallet' ), 'secondary', 'search_submit', false ); ?>
            </div>
            <?php
        }
    }
}

/**
 * Class Ezzy_Wallet_Users
 *
 * Handles the Wallet Users admin page.
 *
 * @since 1.0.0
 */
class Ezzy_Wallet_Users {

    /**
     * List table instance.
     *
     * @var Ezzy_Wallet_Users_List_Table
     */
    private $list_table;

    /**
     * Constructor.
     */
    public function __construct() {
        add_action( 'admin_init', array( $this, 'init_list_table' ) );
    }

    /**
     * Initialize the list table.
     *
     * @return void
     */
    public function init_list_table() {
        $this->list_table = new Ezzy_Wallet_Users_List_Table();
    }

    /**
     * Render the Wallet Users page.
     *
     * @return void
     */
    public function render_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have permission to access this page.', 'ezzy-wallet' ) );
        }

        // Check if editing a user.
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $action = isset( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : '';

        if ( 'edit' === $action ) {
            $this->render_edit_page();
            return;
        }

        if ( 'delete' === $action ) {
            $this->handle_delete_user();
            // Redirect back to users list after deletion
            return;
        }

        // Prepare and display list table.
        $this->list_table->prepare_items();

        include EZZY_WALLET_PLUGIN_DIR . 'admin/partials/wallet-users-table.php';
    }

    /**
     * Handle user deletion.
     *
     * @return void
     */
    private function handle_delete_user() {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $user_id = isset( $_GET['user_id'] ) ? absint( $_GET['user_id'] ) : 0;

        if ( ! $user_id ) {
            wp_die( esc_html__( 'Invalid user ID.', 'ezzy-wallet' ) );
        }

        // Verify nonce
        if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'ezzy_wallet_delete_user_' . $user_id ) ) {
            wp_die( esc_html__( 'Security check failed.', 'ezzy-wallet' ) );
        }

        // Don't allow deleting yourself
        if ( $user_id === get_current_user_id() ) {
            wp_redirect( add_query_arg(
                array(
                    'page'    => 'ezzy-wallet-users',
                    'deleted' => 'self_error',
                ),
                admin_url( 'admin.php' )
            ) );
            exit;
        }

        global $wpdb;

        // Delete from wallet accounts table
        $accounts_table = Ezzy_Wallet_DB::get_accounts_table_name();
        $wpdb->delete( $accounts_table, array( 'user_id' => $user_id ), array( '%d' ) );

        // Delete from transactions table
        $transactions_table = Ezzy_Wallet_DB::get_transactions_table_name();
        $wpdb->delete( $transactions_table, array( 'user_id' => $user_id ), array( '%d' ) );
        // Also delete transactions where user was recipient
        $wpdb->delete( $transactions_table, array( 'recipient_id' => $user_id ), array( '%d' ) );

        // Delete from notifications table
        $notifications_table = Ezzy_Wallet_DB::get_notifications_table_name();
        $wpdb->delete( $notifications_table, array( 'user_id' => $user_id ), array( '%d' ) );

        // Delete all ezzy_wallet user meta
        $wpdb->query( $wpdb->prepare(
            "DELETE FROM {$wpdb->usermeta} WHERE user_id = %d AND meta_key LIKE 'ezzy_wallet_%'",
            $user_id
        ) );

        // Delete profile image file if exists
        $profile_image_path = get_user_meta( $user_id, 'ezzy_wallet_profile_image_path', true );
        if ( ! empty( $profile_image_path ) && file_exists( $profile_image_path ) ) {
            @unlink( $profile_image_path );
        }

        // Log the deletion
        if ( class_exists( 'Ezzy_Wallet_Security' ) ) {
            Ezzy_Wallet_Security::log_audit_event( 'wallet_user_deleted', get_current_user_id(), array(
                'deleted_user_id' => $user_id,
            ) );
        }

        // Redirect with success message
        wp_redirect( add_query_arg(
            array(
                'page'    => 'ezzy-wallet-users',
                'deleted' => 'success',
            ),
            admin_url( 'admin.php' )
        ) );
        exit;
    }

    /**
     * Render the edit user page.
     *
     * @return void
     */
    private function render_edit_page() {
        $edit_handler = new Ezzy_Wallet_User_Edit();
        $edit_handler->render_page();
    }
}
