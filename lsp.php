<?php
/**
* Plugin Name: Live Stock Prices
* Description: A simple plugin to show LIVE stock prices.
* Version: 1.0
* Author: Byron Jacobs
* Author URI: https://byronjacobs.co.za
* License: GPLv2 or later
* Text Domain: live-stock-prices
*/

// Add options page for the plugin
function lsp_options_page() {
    add_options_page('Live Stock Prices', 'Live Stock Prices', 'manage_options', 'lsp', 'lsp_options_page_html');
}
add_action('admin_menu', 'lsp_options_page');

// Options page HTML
function lsp_options_page_html() {
    if (!current_user_can('manage_options')) {
        return;
    }

    // Save plugin options
    if (isset($_POST['lsp_options_form_submitted'])) {
        update_option('lsp_api_key', sanitize_text_field($_POST['lsp_api_key']));
        update_option('lsp_tickers', array_map('sanitize_text_field', $_POST['lsp_tickers']));
        update_option('lsp_update_interval', sanitize_text_field($_POST['lsp_update_interval']));
        update_option('lsp_chart_days', sanitize_text_field($_POST['lsp_chart_days']));
    }

    if (isset($_POST['lsp_show_charts'])) {
        update_option('lsp_show_charts', sanitize_text_field($_POST['lsp_show_charts']));
    } else {
        update_option('lsp_show_charts', 'no');
    }
    

    // Load plugin options
    $lsp_api_key = get_option('lsp_api_key');
    $lsp_tickers = get_option('lsp_tickers');
    $lsp_show_charts = get_option('lsp_show_charts');
    $lsp_update_interval = get_option('lsp_update_interval');
    $lsp_chart_days = get_option('lsp_chart_days');
    ?>
    <div class="wrap">
        <h1>Live Stock Prices</h1>
        <p>To use this plugin, you need an API key from <a href="https://iexcloud.io" target="_blank">IEX Cloud</a>. <a href="https://iexcloud.io/cloud-login#/register" target="_blank">Sign up</a> for an account and obtain your API key.</p>
        <p>Use the shortcodes below to display live stock prices on your website:</p>
        <ol>
            <li><strong>[live_stock_prices]</strong> - Displays stock prices with optional charts</li>
            <li><strong>[live_stock_prices_scrolling]</strong> - Displays a scrolling stock ticker</li>
        </ol>
        <p>If you need more features and functionality, please email <a href="mailto:hello@byronjacobs.co.za">hello@byronjacobs.co.za</a>.</p>
       
        <form method="post">
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="lsp_api_key">API Key</label></th>
                    <td><input type="text" name="lsp_api_key" value="<?php echo esc_attr($lsp_api_key); ?>" class="regular-text" /></td>
                </tr>
                <tr>
                    <th scope="row"><label for="lsp_tickers">Stock Tickers</label></th>
                    <td>
                        <div id="lsp-stock-tickers">
                            <?php foreach ($lsp_tickers as $ticker): ?>
                                <div class="lsp-stock-ticker"><input type="text" name="lsp_tickers[]" value="<?php echo esc_attr($ticker); ?>" /><button class="lsp-remove-ticker">Remove</button></div>
                            <?php endforeach; ?>
                        </div>
                        <button id="lsp-add-ticker" type="button">Add Ticker</button>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="lsp_show_charts">Show Charts</label></th>
                    <td><input type="checkbox" name="lsp_show_charts" value="yes"<?php checked($lsp_show_charts, 'yes'); ?> /></td>
                </tr>
                <tr>
                    <th scope="row"><label for="lsp_update_interval">Update Interval</label></th>
                    <td>
                        <select name="lsp_update_interval">
                            <?php $intervals = array('30000' => '30 seconds', '60000' => '1 minute', '120000' => '2 minutes', '300000' => '5 minutes', '600000' => '10 minutes', '900000' => '15 minutes', '1200000' => '20 minutes', '1800000' => '30 minutes', '2400000' => '40 minutes', '2700000' => '45 minutes', '3600000' => '1 hour');
                            foreach ($intervals as $value => $label): ?>
                                <option value="<?php echo esc_attr($value); ?>"<?php selected($lsp_update_interval, $value); ?>><?php echo esc_html($label); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="lsp_chart_days">Chart Days</label></th>
                    <td><input type="number" name="lsp_chart_days" value="<?php echo esc_attr($lsp_chart_days); ?>" min="1" max="30" /></td>
                </tr>
            </table>
            <input type="hidden" name="lsp_options_form_submitted" value="1" />
            <?php submit_button(); ?>
        </form>
    </div>
    <script>
      (function ($) {
        function addStockTicker() {
          const stockTicker = $('<div class="lsp-stock-ticker"><input type="text" name="lsp_tickers[]" /><button class="lsp-remove-ticker">Remove</button></div>');
          stockTicker.find('.lsp-remove-ticker').on('click', function () {
            $(this).closest('.lsp-stock-ticker').remove();
          });
          $('#lsp-stock-tickers').append(stockTicker);
        }

        $('#lsp-add-ticker').on('click', addStockTicker);
        $('.lsp-remove-ticker').on('click', function () {
          $(this).closest('.lsp-stock-ticker').remove();
        });
      })(jQuery);
    </script>
    <?php
}

// Enqueue plugin scripts and styles
function lsp_enqueue_scripts() {
    wp_enqueue_style('lsp-styles', plugins_url('assets/css/lsp-styles.css', __FILE__));
    wp_enqueue_style('lsp-scrolling-styles', plugins_url('assets/css/lsp-scrolling-styles.css', __FILE__)); // Add this line
    wp_enqueue_script('jquery');
    wp_enqueue_script('chart-js', 'https://cdn.jsdelivr.net/npm/chart.js');
    wp_enqueue_script('lsp-scripts', plugins_url('assets/js/lsp-scripts.js', __FILE__), array('jquery', 'chart-js'), '1.0.0', true);
    wp_localize_script('lsp-scripts', 'lspData', array(
        'apiKey' => get_option('lsp_api_key'),
        'tickers' => get_option('lsp_tickers'),
        'showCharts' => get_option('lsp_show_charts'),
        'updateInterval' => get_option('lsp_update_interval'),
        'chartDays' => get_option('lsp_chart_days'),
    ));
}

add_action('wp_enqueue_scripts', 'lsp_enqueue_scripts');

// Add shortcode to display stock prices
function lsp_stock_prices_shortcode() {
    return '<div id="lsp-container" class="lsp-container"></div>';
}
add_shortcode('live_stock_prices', 'lsp_stock_prices_shortcode');

function lsp_scrolling_stock_prices_shortcode() {
    return '<div id="lsp-scrolling-container" class="lsp-scrolling-container"></div>';
}
add_shortcode('scrolling_live_stock_prices', 'lsp_scrolling_stock_prices_shortcode');
