<?php
/**
 * Plugin Name: Network Site Details
 * Plugin URI: https://it.telkomuniversity.ac.id/
 * Description: Displays additional details like post count, last accessed date, and visitor count for each subsite in a WordPress multisite network. Also supports exporting data and displays them on a dashboard with charts.
 * Version: 1.0
 * Author: Rihansen Purba, Ryan Gusman Banjarnahor, Zafran, Muhammad Kafaby
 * Author URI: https://github.com/rihansen11/NetworkSiteDetails
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: network-site-details
 * Domain Path: 
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Include PhpSpreadsheet manually
require_once plugin_dir_path(__FILE__) . 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Hook to add the dashboard page in Network Admin.
add_action( 'network_admin_menu', 'nsd_add_dashboard_menu' );

/**
 * Add a dashboard page to display post count, views, and percentages.
 */
function nsd_add_dashboard_menu() {
    add_menu_page(
        __( 'Site Details Dashboard', 'network-site-details' ),
        __( 'Dashboard', 'network-site-details' ),
        'manage_network',
        'nsd-dashboard',
        'nsd_render_dashboard_page',
        'dashicons-chart-bar',
        6
    );
}

/**
 * Set a daily cookie to track visitors.
 */
function nsd_set_visitor_cookie() {
    $cookie_name = 'nsd_visitor_' . gmdate('Y-m-d');
    if (!isset($_COOKIE[$cookie_name])) {
        setcookie($cookie_name, '1', time() + 86400, "/"); // 1 day
        $visitor_count = get_option('nsd_visitor_count_last_three_months', 0);
        update_option('nsd_visitor_count_last_three_months', $visitor_count + 1);
    }
}
add_action('init', 'nsd_set_visitor_cookie');

/**
 * Enqueue Chart.js locally and add inline script for chart rendering.
 */
add_action( 'admin_enqueue_scripts', 'nsd_enqueue_chartjs' );
function nsd_enqueue_chartjs( $hook ) {
    if ( $hook !== 'toplevel_page_nsd-dashboard' ) {
        return;
    }
    wp_enqueue_script( 'chartjs', plugins_url( 'assets/js/chart.min.js', __FILE__ ), array(), '4.4.0', true );
    // Jangan tambahkan inline script di sini!
}

function nsd_get_site_data_for_js() {
    // This function should be called inside nsd_render_dashboard_page
    global $site_data;
    return [
        'labels' => array_column( $site_data, 'name' ),
        'post_counts' => array_column( $site_data, 'post_count' ),
        'visitor_counts' => array_column( $site_data, 'visitor_count' ),
        'post_label' => esc_js( __( 'Post Count', 'network-site-details' ) ),
        'visitor_label' => esc_js( __( 'Visitor Count', 'network-site-details' ) ),
    ];
}

function nsd_get_chartjs_inline_script( $data ) {
    $labels = wp_json_encode( $data['labels'] );
    $post_counts = wp_json_encode( $data['post_counts'] );
    $visitor_counts = wp_json_encode( $data['visitor_counts'] );
    $post_label = $data['post_label'];
    $visitor_label = $data['visitor_label'];
    return "document.addEventListener('DOMContentLoaded', function() {\n\nvar postCtx = document.getElementById('postCountChart').getContext('2d');\nvar postCountChart = new Chart(postCtx, {\n    type: 'bar',\n    data: {\n        labels: $labels,\n        datasets: [{\n            label: '$post_label',\n            data: $post_counts,\n            backgroundColor: 'rgba(75, 192, 192, 0.2)',\n            borderColor: 'rgba(75, 192, 192, 1)',\n            borderWidth: 1\n        }]\n    },\n    options: { scales: { y: { beginAtZero: true } } }\n});\n\nvar visitorCtx = document.getElementById('visitorCountChart').getContext('2d');\nvar visitorCountChart = new Chart(visitorCtx, {\n    type: 'bar',\n    data: {\n        labels: $labels,\n        datasets: [{\n            label: '$visitor_label',\n            data: $visitor_counts,\n            backgroundColor: 'rgba(153, 102, 255, 0.2)',\n            borderColor: 'rgba(153, 102, 255, 1)',\n            borderWidth: 1\n        }]\n    },\n    options: { scales: { y: { beginAtZero: true } } }\n});\n\n});";
}

/**
 * Render the dashboard page with charts and tables.
 */
function nsd_render_dashboard_page() {
    
    // Get the search query from the URL with nonce verification and unslash
    $search_query = '';
    if ( isset( $_GET['nsd_search_query'] ) && isset( $_GET['_wpnonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'nsd_search_action' ) ) {
        $search_query = sanitize_text_field( wp_unslash( $_GET['nsd_search_query'] ) );
    }

    $sites = get_sites();
    $site_data = [];
    $total_posts = 0;
    $total_visitors = 0;

    foreach ($sites as $site) {
        switch_to_blog( $site->blog_id );
    
        $post_count = wp_count_posts()->publish;
        $visitor_count = get_option( 'nsd_visitor_count_last_three_months', 0 );
        $site_name = get_bloginfo( 'name' );
        $site_domain = $site->domain;
        $site_path = $site->path;
    
        // Get last updated post date
        $last_updated_post = nsd_get_last_updated_post_date();
    
        // Get last login user email
        $last_login_email = nsd_get_last_login_user_email();
    
        $site_data[] = [
            'name' => $site_name,
            'domain' => $site_domain,
            'path' => $site_path,
            'post_count' => $post_count,
            'visitor_count' => $visitor_count,
            'last_updated_post' => $last_updated_post,
            'last_login_email' => $last_login_email,
        ];
    
        $total_posts += $post_count;
        $total_visitors += $visitor_count;
        restore_current_blog();
    }
    // Sort the site data by post_count and visitor_count in descending order
    usort($site_data, function($a, $b) {
        return $b['post_count'] <=> $a['post_count'] ?: $b['visitor_count'] <=> $a['visitor_count'];
    });

    // Enqueue Chart.js dan inject data chart hanya di halaman dashboard
    wp_enqueue_script( 'chartjs', plugins_url( 'assets/js/chart.min.js', __FILE__ ), array(), '4.4.0', true );
    wp_add_inline_script( 'chartjs', nsd_get_chartjs_inline_script([
        'labels' => array_column( $site_data, 'name' ),
        'post_counts' => array_column( $site_data, 'post_count' ),
        'visitor_counts' => array_column( $site_data, 'visitor_count' ),
        'post_label' => esc_js( __( 'Post Count', 'network-site-details' ) ),
        'visitor_label' => esc_js( __( 'Visitor Count', 'network-site-details' ) ),
    ]) );

    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'Multisite Dashboard', 'network-site-details' ); ?></h1>
        <p><?php esc_html_e( 'Overview of post counts and visitor data across all subsites.', 'network-site-details' ); ?></p>
        
        <!-- Export Button -->
        <div style="display: flex; gap: 10px;">
            <a href="<?php echo esc_url(admin_url('admin-post.php?action=nsd_export_to_excel')); ?>" class="button button-primary">
                <?php esc_html_e('Export to Excel', 'network-site-details'); ?>
            </a>
        </div>
        
        <!-- Display Data -->
        <h2><?php esc_html_e( 'Subsite Data', 'network-site-details' ); ?></h2>

        <?php
        // Debug: tampilkan pesan jika site_data kosong
        if (empty($site_data)) {
            echo '<div style="color:red">Tidak ada data site ditemukan! Pastikan ada subsite di jaringan multisite Anda.</div>';
        }
        ?>

        <table class="widefat fixed" cellspacing="0" id="network-site-details-table">
    <thead>
        <tr>
            <th><?php esc_html_e( 'Subsite', 'network-site-details' ); ?></th>
            <th>
                <?php esc_html_e( 'Post Count', 'network-site-details' ); ?>
                <button onclick="sortTable(1, 'desc')">🡹</button>
                <button onclick="sortTable(1, 'asc')">🡻</button>
            </th>
            <th>
                <?php esc_html_e( 'Visitor Count (Last 3 Months)', 'network-site-details' ); ?>
                <button onclick="sortTable(2, 'desc')">🡹</button>
                <button onclick="sortTable(2, 'asc')">🡻</button>
            </th>
            <th><?php esc_html_e( 'Last Updated Post Date', 'network-site-details' ); ?></th>
            <th><?php esc_html_e( 'Last Login User Email', 'network-site-details' ); ?></th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ( $site_data as $site ) : ?>
        <tr>
            <td><a href="<?php echo esc_url( 'https://' . $site['domain'] . $site['path'] ); ?>" target="_blank"><?php echo esc_html( $site['name'] ); ?></a></td>
            <td><?php echo esc_html( $site['post_count'] ); ?></td>
            <td><?php echo esc_html( $site['visitor_count'] ); ?></td>
            <td><?php echo esc_html( $site['last_updated_post'] ); ?></td>
            <td><?php echo esc_html( $site['last_login_email'] ); ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<script>
function sortTable(columnIndex, order) {
    var table = document.getElementById("network-site-details-table");
    var rows = Array.from(table.rows).slice(1);
    rows.sort(function(a, b) {
        var cellA = a.cells[columnIndex].innerText;
        var cellB = a.cells[columnIndex].innerText;
        var valueA = isNaN(cellA) ? cellA : parseInt(cellA);
        var valueB = isNaN(cellB) ? cellB : parseInt(cellB);
        if (order === 'asc') {
            return valueA > valueB ? 1 : -1;
        } else {
            return valueA < valueB ? 1 : -1;
        }
    });
    rows.forEach(function(row) {
        table.tBodies[0].appendChild(row);
    });
}
</script>

        <h2><?php esc_html_e( 'Post Count Chart', 'network-site-details' ); ?></h2>
        <canvas id="postCountChart" width="400" height="200"></canvas>

        <h2><?php esc_html_e( 'Visitor Count Chart', 'network-site-details' ); ?></h2>
        <canvas id="visitorCountChart" width="400" height="200"></canvas>
    </div>
    <?php
}

/**
 * Export site data to an Excel file.
 */
add_action('admin_post_nsd_export_to_excel', 'nsd_export_to_excel');

function nsd_export_to_excel() {
    if (!current_user_can('manage_network')) {
        wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'network-site-details' ) );
    }

    $sites = get_sites();
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // Header
    $sheet->setCellValue('A1', 'Subsite');
    $sheet->setCellValue('B1', 'Post Count');
    $sheet->setCellValue('C1', 'Visitor Count (Last 3 Months)');
    $sheet->setCellValue('D1', 'Last Updated Post Date');
    $sheet->setCellValue('E1', 'Last Login User Email');

    $row = 2; // Start from the second row
    foreach ($sites as $site) {
        switch_to_blog($site->blog_id);

        $subsite_name = get_bloginfo('name');
        $post_count = wp_count_posts()->publish;
        $visitor_count = get_option('nsd_visitor_count_last_three_months', 0);
        $last_updated_post = nsd_get_last_updated_post_date();
        $last_login_email = nsd_get_last_login_user_email();

        $sheet->setCellValue("A{$row}", $subsite_name);
        $sheet->setCellValue("B{$row}", $post_count);
        $sheet->setCellValue("C{$row}", $visitor_count);
        $sheet->setCellValue("D{$row}", $last_updated_post);
        $sheet->setCellValue("E{$row}", $last_login_email);

        $row++;
        restore_current_blog();
    }

    // Prepare file for download
    $filename = 'network-sites-details.xlsx';
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header("Content-Disposition: attachment;filename={$filename}");
    header('Cache-Control: max-age=0');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
    exit;
}

/**
 * Get the date of the most recently updated post.
 */
function nsd_get_last_updated_post_date() {
    global $wpdb;

    $query = $wpdb->prepare("
        SELECT post_modified
        FROM {$wpdb->posts}
        WHERE post_status = %s
        ORDER BY post_modified DESC
        LIMIT 1
    ", 'publish');

    $last_modified = $wpdb->get_var( $query );

    return $last_modified ? date_i18n( 'Y-m-d H:i:s', strtotime( $last_modified ) ) : esc_html__( 'No Posts', 'network-site-details' );
}

/**
 * Track last visitor email per subsite on login.
 */
function nsd_track_last_visitor_email($user_login, $user) {
    // Only track for logged-in users
    if (is_multisite()) {
        $blog_id = get_current_blog_id();
        update_option('nsd_last_visitor_email', $user->user_email);
    }
}
add_action('wp_login', 'nsd_track_last_visitor_email', 10, 2);

/**
 * Track last visitor email per subsite on each page view (logged-in users only).
 */
function nsd_track_last_visitor_email_on_view() {
    if ( is_user_logged_in() && is_multisite() ) {
        $user = wp_get_current_user();
        if ( $user && $user->user_email ) {
            update_option( 'nsd_last_visitor_email', $user->user_email );
        }
    }
}
add_action( 'init', 'nsd_track_last_visitor_email_on_view', 20 );

/**
 * Get the email of the last visitor (logged-in user) who accessed the subsite.
 */
function nsd_get_last_login_user_email() {
    $last_visitor_email = get_option('nsd_last_visitor_email');
    return $last_visitor_email ? esc_html($last_visitor_email) : esc_html__('No Logins', 'network-site-details');
}
?>