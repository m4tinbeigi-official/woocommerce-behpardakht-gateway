<?php
/**
 * Plugin Name: درگاه پرداخت به پرداخت ملت (ووکامرس)
 * Description: درگاه پرداخت به پرداخت ملت برای ووکامرس 💳🌐
 * Version: 1.2.0
 * Author: Rick Sanchez
 * License: GPLv2 or later
 * WC requires at least: 4.0
 * WC tested up to: 8.0
 */

if (!defined('ABSPATH')) {
    exit;
}

add_filter('woocommerce_payment_gateways', 'add_behpardakht_gateway');
function add_behpardakht_gateway($gateways) {
    $gateways[] = 'WC_Gateway_Behpardakht';
    return $gateways;
}

add_action('plugins_loaded', 'initialize_behpardakht_gateway');
function initialize_behpardakht_gateway() {
    if (!class_exists('WC_Payment_Gateway')) return;

    class WC_Gateway_Behpardakht extends WC_Payment_Gateway {

        public function __construct() {
            $this->id = 'behpardakht';
            $this->method_title = __('درگاه پرداخت به پرداخت ملت', 'woocommerce');
            $this->method_description = __('درگاه پرداخت امن و سریع برای خرید از فروشگاه‌های ووکامرس 💰', 'woocommerce');
            $this->icon = ''; 
            $this->has_fields = false;

            $this->init_form_fields();
            $this->init_settings();

            $this->terminal_id = $this->get_option('terminal_id');
            $this->username = $this->get_option('username');
            $this->password = $this->get_option('password');
            $this->test_mode = $this->get_option('test_mode') === 'yes';

            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        }

        public function init_form_fields() {
            $this->form_fields = array(
                'enabled' => array(
                    'title' => __('فعال/غیرفعال کردن', 'woocommerce'),
                    'type' => 'checkbox',
                    'label' => __('فعال‌سازی درگاه پرداخت به پرداخت ملت', 'woocommerce'),
                    'default' => 'yes'
                ),
                'terminal_id' => array(
                    'title' => __('شماره پایانه', 'woocommerce'),
                    'type' => 'text',
                    'default' => ''
                ),
                'username' => array(
                    'title' => __('نام کاربری', 'woocommerce'),
                    'type' => 'text',
                    'default' => ''
                ),
                'password' => array(
                    'title' => __('رمز عبور', 'woocommerce'),
                    'type' => 'password',
                    'default' => ''
                ),
                'test_mode' => array(
                    'title' => __('حالت تست', 'woocommerce'),
                    'type' => 'checkbox',
                    'label' => __('فعال کردن حالت تست', 'woocommerce'),
                    'default' => 'yes'
                ),
                'currency' => array(
                    'title' => __('واحد پولی', 'woocommerce'),
                    'type' => 'select',
                    'options' => array(
                        'rial' => __('ریال', 'woocommerce'),
                        'toman' => __('تومان', 'woocommerce')
                    ),
                    'default' => 'rial'
                )
            );
        }

        public function process_payment($order_id) {
            $order = wc_get_order($order_id);
            $amount = ($this->get_option('currency') === 'toman') ? $order->get_total() * 10 : $order->get_total();
            $callback_url = add_query_arg('wc-api', 'wc_gateway_behpardakht', home_url('/'));
            $payment_url = $this->get_payment_url($amount, $callback_url);

            return array(
                'result'   => 'success',
                'redirect' => $payment_url
            );
        }

        private function get_payment_url($amount, $callback_url) {
            return 'https://bpm.shaparak.ir/pgwchannel/startpay.mellat?amount=' . $amount . '&callback=' . urlencode($callback_url);
        }

        public function handle_callback() {
            if (!isset($_POST['ResCode']) || $_POST['ResCode'] !== '0') {
                wc_add_notice(__('پرداخت ناموفق بود.', 'woocommerce'), 'error');
                wp_redirect(wc_get_checkout_url());
                exit;
            }

            $order_id = $_POST['SaleOrderId'];
            $order = wc_get_order($order_id);
            $order->payment_complete();
            wc_add_notice(__('پرداخت با موفقیت انجام شد.', 'woocommerce'));
            wp_redirect($this->get_return_url($order));
            exit;
        }
    }

    add_action('woocommerce_api_wc_gateway_behpardakht', array(new WC_Gateway_Behpardakht(), 'handle_callback'));
}
