# Woocommerce Pricing Plugin
A plugin for WordPress with WooCommerce integration that allows you to change the pricing logic of products by adding a discount 
system that will allow you to automatically apply a discount to products of a certain category if more than a specified amount is 
purchased and provides an API endpoint to get information about product prices.
## Installation
### Method 1: Installing a plugin via the WordPress admin panel
1. Download your plugin:
Go to the official page of your plugin or the GitHub repository and download the plugin files. 
Make sure you download the latest version.

2. Unzip the plugin files:
If the plugin is in a zip format, unzip the file to your computer.

3. Upload the plugin files:
Navigate to your WordPress admin panel, then go to `Plugins > Add New`. 
Click on the `Upload Plugin` button. A file uploader will appear. Choose the plugin zip file that you downloaded. 
Click Install Now. After the installation is successful, click `Activate Plugin`.

4. Activate your plugin:
After the plugin is installed, navigate to `Plugins` in your WordPress admin panel. 
You should see your plugin listed there. Click on the `Activate` link to activate your plugin.

### Method 2: Installing a plugin manually using Git
1. Install Git:
If you haven't installed Git yet, you can download it from the official website [Git](https://git-scm.com/downloads).

2. Open Terminal:
Open your terminal or command prompt.

3. Navigate to your WordPress plugins directory:
Use the `cd` command to navigate to the plugins directory of your WordPress installation. 
The path might vary depending on your system. For example, if your WordPress installation is in the htdocs directory of your xampp or 
wamp server, the command would be: <br/>
```console 
foo@bar:~$ cd /path/to/your/wordpress/installation/wp-content/plugins
```

4. Clone your plugin repository:
Use the git clone command followed by the URL of your plugin repository. 
This will create a new directory with the same name as your repository. For example: <br/>
```console 
foo@bar:~$ git clone https://github.com/jennifer-ross/wc-pricing.git
```

5. Activate your plugin:
After the plugin is installed, navigate to `Plugins` in your WordPress admin panel.
You should see your plugin listed there. Click on the `Activate` link to activate your plugin.

## How to use
Once the plugin is activated, the WooCommerce Pricing Plugin tab will appear in the WooCommerce settings. 
For example url would be: https://your-website.com/wp-admin/admin.php?page=wc-settings&tab=settings_tab_wc_pricing
Open it and configure the discount settings for your products. Once configured, the plugin will automatically 
apply a discount when you add items to your cart based on the parameters you select

## REST API
To use your API, you need to make a GET request to the following URL: <br/>
`https://your-website.com/wp-json/custom-pricing/v1/product/{id}`<br/>
Replace `{id}` with the actual ID of the product you want to get the price for.

Here is an example of how you can do this using cURL in PHP:
```php
$curl = curl_init();

curl_setopt_array($curl, array(
  CURLOPT_URL => "https://your-website.com/wp-json/custom-pricing/v1/product/{id}",
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => "",
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 0,
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => "GET",
  CURLOPT_HTTPHEADER => array(
    "X-WP-Nonce: $nonce"
  ),
));

$response = curl_exec($curl);

curl_close($curl);
echo $response;
```
In this code,` wp_create_nonce('wp_rest')` is used to create a nonce. This nonce is then passed in the header of the request as `X-WP-Nonce`.

Please note that the `wp_create_nonce` function should be used on the server side to create the nonce, and the nonce should be passed to the 
client side. The client side should then pass the nonce back to the server side when making requests.
Remember to replace `{id}` with the actual ID of the product you want to get the price for.

Please note that you need to replace `your-website.com` with the actual URL of your WordPress site. 
Also, this is a basic example. In a production environment, you would need to handle errors and edge cases.

Also, this API endpoint is protected by the `check_api_permissions` function. 
This function should return `true` if the user is authorized to access the endpoint, and `false` otherwise. 
If the function returns `false`, the API will return a 403 Forbidden status code.