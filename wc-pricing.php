<?php

/**
 * Plugin Name: Woocommerce Pricing Plugin
 * Description: This plugin allows you to change the pricing logic of products based on certain conditions and provides an API endpoint to get product prices.
 * Version: 1.0
 * Author: Your Name
 */

if ( ! defined('ABSPATH') ) {
    exit; // Exit if accessed directly
}

if ( ! class_exists( 'WC_Pricing_Plugin' ) ) {

    class WC_Pricing_Plugin {

        public function __construct() {
            // Check if WooCommerce is active
            if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
                return;
            }

            // Hook into the WooCommerce pricing logic
            add_filter( 'woocommerce_product_get_price', array( $this, 'apply_category_discount' ), 10, 2 );
            add_filter( 'woocommerce_product_variation_get_price', array( $this, 'apply_category_discount' ), 10, 2 );

            // Register API endpoint
            add_action( 'rest_api_init', array( $this, 'register_api_endpoints' ) );

            // Add settings page
            add_filter( 'woocommerce_settings_tabs_array', array( $this, 'add_wc_pricing_settings_tab' ), 50, 1 );
            add_action( 'woocommerce_settings_tabs_settings_tab_wc_pricing', array( $this, 'settings_tab' ) );
            add_action( 'woocommerce_update_options_settings_tab_wc_pricing', array( $this, 'update_options' ) );
        }

        /**
         * Register API endpoints for retrieving product prices.
         *
         * This function registers a REST route for retrieving the price of a product.
         * The route is '/product/{id}', where {id} is the ID of the product.
         * The HTTP method is GET.
         * The callback function is 'get_product_price' and it is called on the current instance of the class.
         * The permission callback function is 'check_api_permissions' and it is also called on the current instance of the class.
         */
        public function register_api_endpoints() {
            register_rest_route( 'custom-pricing/v1', '/product/(?P<id>\d+)', array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'get_product_price' ),
                'permission_callback' => array( $this, 'check_api_permissions' ),
            ) );
        }

        /**
         * This function checks the API permissions.
         *
         * It first tries to get the nonce from the 'X-WP-Nonce' header.
         * If the header is empty, it tries to get the nonce from the '_wpnonce' parameter.
         * Finally, it verifies the nonce using the 'wp_verify_nonce' function.
         *
         * @param WP_REST_Request $request The request object.
         *
         * @return bool True if the nonce is valid, false otherwise.
         */
        public function check_api_permissions($request) {
            // Get the nonce from the 'X-WP-Nonce' header
            $nonce = $request->get_header('X-WP-Nonce');

            // If the 'X-WP-Nonce' header is empty
            if (empty($nonce)) {
                // Try to get the nonce from the '_wpnonce' parameter
                $nonce = $request->get_param('_wpnonce');
            }

            // Verify the nonce with the 'wp_verify_nonce' function
            // The 'wp_verify_nonce' function returns true if the nonce is valid, false otherwise
            return wp_verify_nonce($nonce, 'wp_rest');
        }

        /**
         * Retrieves the product price information via API callback.
         *
         * @param WP_REST_Request $request The request parameters.
         * @return array|WP_Error The product price information or a WP_Error object.
         */
        public function get_product_price( $request ) {
            // Sanitize and filter the product ID from the request
            $product_id = ( int ) filter_var( $request['id'], FILTER_SANITIZE_NUMBER_INT );
            // Retrieve the product object
            $product = wc_get_product( $product_id );

            // Check if the product exists
            if ( ! $product ) {
                // If the product does not exist, return a WP_Error object with an 'invalid_product' code and a 'Invalid product ID' message.
                // The HTTP status code for this error is set to 404.
                return new WP_Error( 'invalid_product', 'Invalid product ID', array( 'status' => 404 ) );
            }

            // Get the base price
            $base_price = $product->get_regular_price();

            // Get the sale price if it exists
            $sale_price = $product->get_sale_price() ? $product->get_sale_price() : null;

            // Return the product price information as an array.
            // The array contains the base price and the sale price (if it exists).
            return array(
                'base_price' => $base_price,
                'sale_price' => $sale_price,
            );
        }

        /**
         * Adds a settings page for the WooCommerce Pricing Plugin.
         *
         * @param array $settings_tabs The array of settings tabs.
         * @return array The updated array of settings tabs.
         */
        public function add_wc_pricing_settings_tab( $settings_tabs ) {
            // Add a new settings tab for the WooCommerce Pricing Plugin
            $settings_tabs['settings_tab_wc_pricing'] = __( 'WooCommerce Pricing Plugin', 'wc-pricing-settings' );

            // Return the updated array of settings tabs
            return $settings_tabs;
        }

        /**
         * Display the settings tab for the WooCommerce Pricing plugin.
         *
         * This function generates the HTML for the settings tab and displays it in the WooCommerce admin area.
         * It uses the `woocommerce_admin_fields` function to generate the form fields for the settings.
         *
         * @return void
         */
        public function settings_tab() {
            // Display the settings tab with the generated fields
            woocommerce_admin_fields( $this->settings_fields() );
        }

        /**
         * Update the plugin options by calling the WooCommerce update options function.
         *
         * @return void
         */
        public function update_options() {
            // Call the WooCommerce update options function with the plugin settings fields.
            woocommerce_update_options( $this->settings_fields() );
        }

        /**
         * Generate an array of settings fields for the WooCommerce Pricing plugin.
         *
         * @return array The array of settings fields.
         */
        public function settings_fields() {
            // Create an array to store the settings fields
            $settings = [];

            // Add the main settings field
            $settings[] = array(
                'name' => __( 'WooCommerce Pricing settings', 'text-domain' ),
                'type' => 'title',
                'desc' => __( 'Configure the discount settings for your products.', 'text-domain' ),
                'id'   => 'wc_pricing_settings'
            );

            // Add the discount percentage field
            $settings[] = array(
                'name'     => __( 'Discount percentage', 'text-domain' ),
                'desc_tip' => __( 'Discount Percentage for Products', 'text-domain' ),
                'id'       => 'wc_pricing_discount_percentage',
                'type'     => 'number',
            );

            // Add the discount quantity field
            $settings[] = array(
                'name'     => __( 'Discount quantity', 'text-domain' ),
                'desc_tip' => __( 'Minimum Quantity for Discount', 'text-domain' ),
                'id'       => 'wc_pricing_discount_quantity',
                'type'     => 'number',
            );

            // Add the discount categories field
            $settings[] = array(
                'name'     => __( 'Discount categories', 'text-domain' ),
                'desc_tip' => __( 'Discount applicable for Categories', 'text-domain' ),
                'id'       => 'wc_pricing_discount_categories',
                'type'     => 'multiselect',
                'options'  => $this->discount_categories_options(),
            );

            // Apply filters to the settings array
            return apply_filters( 'wc_pricing_settings', $settings );
        }

        /**
         * Retrieve an array of product category options for use in a form field.
         *
         * @return array An associative array of category IDs as keys and category names as values.
         */
        public function discount_categories_options() {
            // Retrieve all product categories excluding empty ones.
            $categories = get_terms( 'product_cat', array( 'hide_empty' => false ) );
            // Initialize an empty array to store the options.
            $options = array();

            if ( ! empty( $categories ) ) {
                // Iterate over each category and add it to the options array.
                foreach ( $categories as $category ) {
                    $options[$category->term_id] = $category->name;
                }
            }

            return $options;
        }

        /**
         * Find the product in the cart by its ID.
         *
         * @param int $product_id The ID of the product to find.
         * @return string The cart item key if the product is found, empty string otherwise.
         */
        private function find_product_in_cart( $product_id = 0 )
        {
            // Check if the product ID is empty or if we are in the admin area
            if ( empty( $product_id ) || is_admin() ) {
                return '';
            }

            // Iterate over the cart items and check if the product ID matches
            foreach( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {
                if( $cart_item['product_id'] === $product_id ) {
                    return $cart_item_key;
                }
            }

            // Product not found in the cart
            return '';
        }

        /**
         * Apply category discount to the product price.
         *
         * This function checks if the product belongs to any of the selected categories
         * and applies a discount to the price if the quantity is above the threshold.
         *
         * @param float $price The original price of the product.
         * @param WC_Product $product The product object.
         * @return float The updated price after applying the discount.
         */
        public function apply_category_discount( $price, $product ) {
            // If the code is running in the admin area, return the original price
            if ( is_admin() ) {
                return $price;
            }

            // Get the selected categories for the discount
            $selected_categories = get_option( 'wc_pricing_discount_categories', array() );

            // If no categories are selected, return the original price
            if ( empty( $selected_categories ) ) {
                return $price;
            }

            // Get the quantity and percentage for the discount
            $discount_quantity = get_option( 'wc_pricing_discount_quantity', '' );
            $discount_percentage = get_option( 'wc_pricing_discount_percentage', '' );

            // If the quantity or percentage is not set, return the original price
            if ( empty( $discount_quantity ) || empty( $discount_percentage ) ) {
                return $price;
            }

            foreach ( $selected_categories as $discount_category_id ) {
                // Check if the product belongs to any of the selected categories
                if ( has_term( $discount_category_id, 'product_cat', $product->get_id() ) ) {
                    $cart_item_key = $this->find_product_in_cart( $product->get_id() );

                    if ( $cart_item_key ) {
                        $cart_item = WC()->cart->get_cart_item( $cart_item_key );
                        $quantity = $cart_item['quantity'];

                        // Apply the discount if the quantity is above the threshold
                        if ( $quantity >= $discount_quantity ) {
                            $discount = $price * ( $discount_percentage / 100 );
                            $price -= $discount;
                        }
                    }
                }
            }

            return $price;
        }
    }

}

// Instantiate the plugin class
new WC_Pricing_Plugin();