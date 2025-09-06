<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://dominhhai.com/
 * @since      1.0.0
 *
 * @package    Vcb_Mh
 * @subpackage Vcb_Mh/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Vcb_Mh
 * @subpackage Vcb_Mh/admin
 * @author     Đỗ Minh Hải <minhhai27121994@gmail.com>
 */
class Vcb_Mh_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;
	private $cron_table = 'vcb_gateway_cron';
	private $transaction_table = 'vcb_gateway_transactions';
	private $key_e = 'zZW5zZSB0vdSdsbCxzaqsazaa';
	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		
	
		$this->plugin_name = $plugin_name;
		$this->version = $version;

		$settings = get_option('vcb_gw_settings', false);
		$settings = json_decode($settings, true);
		$this->settings = $settings;

		add_filter( 'woocommerce_payment_gateways', [$this, 'misha_add_gateway_class'] );
		add_action( 'plugins_loaded', [$this, 'misha_init_gateway_class'] );

		add_action('admin_menu', [$this, 'add_admin_pages']);
		add_action('wp_ajax_vcb_gw_reset_all_data', [$this, 'vcb_gw_reset_all_data']);


		add_action('wp_ajax_vcb_gw_get_transactions', [$this, 'vcb_gw_get_transactions']);
		add_action('wp_ajax_vcb_gw_login', [$this, 'vcb_gw_login']);


		add_action('wp_ajax_vcb_gw_sync_transactions', [$this, 'vcb_gw_sync_transactions']);
		add_action('wp_ajax_nopriv_vcb_gw_sync_transactions', [$this, 'vcb_gw_sync_transactions']);

		add_action('wp_ajax_vcb_gw_waiting_payment', [$this, 'vcb_gw_waiting_payment']);
		add_action('wp_ajax_nopriv_vcb_gw_waiting_payment', [$this, 'vcb_gw_waiting_payment']);


		add_action('wp_ajax_vcb_gw_get_option', [$this, 'get_option']);
		add_action('wp_ajax_vcb_gw_save_option', [$this, 'save_option']);

	}

	public function vcb_gw_reset_all_data(){
		if(!current_user_can('administrator'))
			return;

		global $wpdb;
		$sql = "DELETE FROM $this->transaction_table";
		$wpdb->query($sql);
		$sql = "DELETE FROM $this->cron_table";
		$wpdb->query($sql);
		// delete_option('vcb_gw_profile');
		// delete_option('vcb_gw_login');
		echo json_encode(['success' => true, 'msg' => 'Đã xóa toàn bộ dữ liệu']);
		die();
	}

	

	public function vcb_gw_waiting_payment(){
		
		$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : '';
		$nonce = isset($_GET['nonce']) ? $_GET['nonce'] : '';
		if($order_id)
		{
			global $wpdb;
			
			$last_sync = $wpdb->get_row("SELECT time FROM $this->cron_table ORDER BY id DESC LIMIT 1;", ARRAY_A);
			
			if($last_sync) {

				$now = time();
				$last = strtotime($last_sync['time']);

				if(isset($_GET['cron']))
				{
						$this->vcb_gw_sync_transactions();
						die();
				}

				if(($now - $last) >= 20){
					$this->vcb_gw_sync_transactions();
				}

				
			}
			else{
					$this->vcb_gw_sync_transactions();
			}

			$sql = "SELECT * FROM $this->transaction_table WHERE order_id = $order_id AND is_paid = 1";
			$recordExist = $wpdb->get_row($sql, ARRAY_A);

			$order = wc_get_order($order_id);
			if($recordExist)
			{
				echo json_encode(['success' => true, 'msg' => 'Thanh toán thành công', 'data' => $recordExist]);
				die();
			}
			if($order->get_status() == 'completed'){
				echo json_encode(['success' => true, 'msg' => 'Thanh toán thành công', 'data' => ['comment' => '']]);
				die();
			}
		}

		echo json_encode(['success' => false]);
		die();
	}

	

	public function vcb_gw_sync_transactions(){
		global $wpdb;

		$date = date("Y-m-d H:i:s");
		$sql = "INSERT INTO `$this->cron_table` (`time`, `json`) VALUES ('$date', '');";
		$wpdb->query($sql);


		$auth = get_option('vcb_gw_login');
		
		if($auth)
		{

			$auth = $this->decode_($auth);
			$response = $this->curl_vcb($auth);
			// debug($response);
			$response = json_decode($response, true);
			if(isset($response['success']) && $response['success'] && sizeof( $response['results'])){
				$transactions = $response['results'];	
				foreach ($transactions as $key => $t) {
					$sql = "SELECT * FROM $this->transaction_table WHERE tranId = '$t[Reference]'";
					$recordExist = $wpdb->get_row($sql);

					if(!$recordExist) {
						$tranData = $this->buildTransactionData($t);
						if($tranData)
							$wpdb->insert($this->transaction_table, $tranData);
					}

					
				}
			}
			
		}


	}



	public function buildTransactionData($t, $balance = 0){
		if($t['CD'] == '-')
			return;
		$t['Amount'] = intval(str_replace(',', '', $t['Amount']));
		$tranData = [
			'tranId' => $t['Reference'],
			'partnerName' => '',
			'amount' => $t['Amount'],
			'comment' => $t['Description'],
			'description' => $t['TransactionDate'],
			'partnerId' => '',
			'status' => 999,
			'ownerName' => '',
			'ownerNumber' => '',
			'ackTime' => date("Y-m-d H:i:s"),
			'ipAddress' => 'N/A',
		];
		// debug($this->settings);
		$regexFind = $this->getInbetweenStrings($this->settings['prefix'],$this->settings['subfix'],$t['Description']);
		

		if($regexFind && $regexFind[0] != '')
		{


			$tranData['order_id'] = $regexFind[0];
		
			$order = wc_get_order($tranData['order_id']);
			if(!$order)
				return $tranData;
			$total = $order->get_total();
			if(isset($this->settings['currency_rate']) && $this->settings['currency_rate'])
			{
				$total = intval($this->settings['currency_rate'] * $total);
			}
			

			if($total && $total <= $t['Amount'])
			{
				$tranData['is_paid'] = 1;
				$time = time();
				$date = date('Y-m-d H:i:s');
				
				$order->update_status( $this->settings['order_status'] );
				$order->add_order_note( 'Thanh toán thành công: ' . number_format($t['Amount']) . ' - ' . $t['Description']);

				$products = []; 
				$items = $order->get_items();
				
				foreach ( $items as $item ) {
					$products[] =  $item->get_name() . ' - Số lượng: ' .$item->get_quantity();
				}


				$html = 'Thanh toán thành công: <b>'.number_format($total).'</b> '.PHP_EOL;
				$html .= 'Sản phẩm: <b>'.implode(', ', $products).'</b> '.PHP_EOL;
				$html .= 'Mã Đơn hàng: <b>'.$tranData['order_id'].'</b> '.PHP_EOL;
				$html .= 'Người gửi: <b>'.$t['Description'].'</b> '.PHP_EOL;
				$html .= 'Số dư Vietcombank hiện tại: <b>'.$balance.'</b> '.PHP_EOL;
				$this->send_telegram_notify($html);
				$this->lark_send($t['Amount'], $t['Description']);

			}


		}
		else
			$this->send_telegram_notify("+" . number_format($t['Amount']) . " - " . $t['Description']);

		return $tranData;
	}

	public function vcb_gw_login(){
		if(!current_user_can('administrator'))
			return;
		
		$step = isset($_POST['step']) ? $_POST['step'] : '';
		$auth = isset($_POST['auth']) ? $_POST['auth'] : '';
		if($step == 1){
			$response = $this->curl_vcb_new($auth, 'get_otp');
			echo $response;
			die();
		}
		if($step == 2){
			$response = $this->curl_vcb_new($auth, 'import_otp');
			echo $response;
			die();
		}


		// if($auth['phone'] && $auth['password']){
			
		// 	$response = $this->curl_vcb($auth, true);
		// 	$response_de = json_decode($response, true);

		// 	if(isset($response_de['success']) && $response_de['success']){
		// 		$data = $this->encode_($auth);
		// 		$vcb_gw_login = get_option( 'vcb_gw_login', true );
		// 		if(!$vcb_gw_login)
		// 			add_option( 'vcb_gw_login', $data, '', false );	
		// 		else
		// 			update_option( 'vcb_gw_login', $data, '', false );	

		// 	}
		// 	echo $response;

		// }
		// else
		// 	echo json_encode(['success' => false, 'msg' => 'Đăng nhập thất bại']);

		die();
	}

	public function vcb_gw_get_transactions(){
		global $wpdb;

		$page = isset($_POST['page']) ? htmlspecialchars($_POST['page']) : 1;
		$per_page = isset($_POST['per_page']) ? intval($_POST['per_page']) : 20;
		$filters = isset($_POST['filters']) ? $_POST['filters'] : '';

		$whereArr = [];

		$where = '';
		$offset = ($page - 1) * $per_page;
		if(sizeof($whereArr) > 0)
			$where = "WHERE (" . implode(" AND ", $whereArr) . ")";

		$sql = "SELECT SQL_CALC_FOUND_ROWS *  FROM  $this->transaction_table $where  ORDER BY ackTime DESC LIMIT $offset, $per_page ";
		$records = $wpdb->get_results( $sql );
		$total_records = $wpdb->get_var("SELECT FOUND_ROWS() as total");

		$pagination = [
	            	'total' => intval($total_records),
	            	'page' 	=> intval($page),
	            	'per_page' => intval($per_page),
	            	'max_page'  => ceil($total_records / $per_page)
        ];
        if(defined('VCB_MH_FALSE'))
        	$records = [];

		echo json_encode([
			'data' => $records, 
			'pagination' => $pagination, 
			'success' => true
		]);
		die();
	}

	public function get_option($key = '')
	{
		if($key == '')
			$key = $_POST['key'];
		$data = get_option($key, false);
		echo json_encode(['success' => 1, 'msg' => 'Thành công!', 'data' => $data ? json_decode($data) : null]);
		die();
	}

	public function save_option($key = '', $data = '')
	{
		if(!current_user_can('administrator'))
			return;
		
		if($key == ''){
			$data = $_POST['data'];
			$key = $_POST['key'];
		}
		if(!$data || !$key)
		{
			echo json_encode(['success' => 0, 'msg' => 'Có lỗi xảy ra!']);
			die();
		}
		$option = get_option($key, false);
		$data_ = json_encode($data);

		if($key == 'vcb_gw_login'){
			$data_ = $this->encode_($data);
		}

		if( $option ) {
		   update_option($key, $data_);
		}else {
		
		   add_option( $key, $data_, false);
		}
		$data = get_option($key, false);
		echo json_encode(['success' => 1, 'msg' => 'Lưu dữ liệu thành công!', 'data' => json_decode($data)]);
		die();
	}


	public function misha_add_gateway_class( $gateways ) {
		$gateways[] = 'Vcb_Gateway_MH'; // your class name is here
		return $gateways;
	}

	public function misha_init_gateway_class() {
		
			include "vcb-gateway.php";
	}

	public function add_admin_pages()
	{
		if(current_user_can( 'administrator' )){
			
			$icon = VCB_MH_URL . 'public/images/VCB.png';
			// $icon = plugin_dir_url( __FILE__ ) . 'images/theme_option.png';
			add_menu_page(
		        __( 'Vietcombank GateWay', 'textdomain' ),
		        'Vietcombank MH',
		        'manage_woocommerce',
		        'vcb_gateway_mh',
		        [$this, 'admin_template'],
		        $icon,
		        110
		    );
		}
	}

	public function curl_vcb_new($auth, $path){


		date_default_timezone_set('Asia/Ho_Chi_Minh');

		$curl = curl_init();
		$unixRefDate = time() + 8*3600;
		$unixDate = $unixRefDate-(3600*24*1);
		$begin = date('d/m/Y', $unixDate);
		$end = date('d/m/Y', $unixRefDate);

		
		curl_setopt_array($curl, array(
		  CURLOPT_URL => 'https://dominhhai.com/vietcombank-nodejs/' . $path,
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => '',
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 0,
		  CURLOPT_SSL_VERIFYPEER => false,
		  CURLOPT_SSL_VERIFYHOST => false,
		  CURLOPT_FOLLOWLOCATION => true,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => 'POST',
		  CURLOPT_POSTFIELDS =>'{
		    "begin": "'.$begin.'",
		    "end": "'.$end.'",
		    "otp": "'.$auth['otp'].'",
		    "username": "'.$auth['phone'].'",
		    "password": "'.$auth['password'].'",
		    "accountNumber": "'.$this->settings['account']['phone'].'"
		}',
		  CURLOPT_HTTPHEADER => array(
		    'Content-Type: application/json'
		  ),
		));

		$response = curl_exec($curl);
		return $response;
	}

	public function curl_vcb($auth, $login = false){
		date_default_timezone_set('Asia/Ho_Chi_Minh');
		$curl = curl_init();
		$unixRefDate = time() + 8*3600;
		$unixDate = $unixRefDate-(3600*24*1);
		$begin = date('d/m/Y', $unixDate);
		$end = date('d/m/Y', $unixRefDate);

		
		curl_setopt_array($curl, array(
		  CURLOPT_URL => 'https://dominhhai.com/vietcombank-nodejs/',
		  CURLOPT_RETURNTRANSFER => true,
		  CURLOPT_ENCODING => '',
		  CURLOPT_MAXREDIRS => 10,
		  CURLOPT_TIMEOUT => 0,
		  CURLOPT_SSL_VERIFYPEER => false,
		  CURLOPT_SSL_VERIFYHOST => false,
		  CURLOPT_FOLLOWLOCATION => true,
		  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
		  CURLOPT_CUSTOMREQUEST => 'POST',
		  CURLOPT_POSTFIELDS =>'{
		    "begin": "'.$begin.'",
		    "end": "'.$end.'",
		    "username": "'.$auth['phone'].'",
		    "password": "'.$auth['password'].'",
		    "accountNumber": "'.$this->settings['account']['phone'].'"
		}',
		  CURLOPT_HTTPHEADER => array(
		    'Content-Type: application/json'
		  ),
		));

		$response = curl_exec($curl);
		if (curl_errno($curl)) {
    $error_msg = curl_error($curl);
			print_r($error_msg);
}
		return $response;
	}

	public function send_telegram_notify($html){

		if($this->settings['telegram'] == 'true' && $this->settings['telegram_token'] && $this->settings['telegram_group'])
		{
			$botToken = $this->settings['telegram_token'];
			$website = "https://telegrambot.minhhai27121994.workers.dev/bot".$botToken;
			$chatId = $this->settings['telegram_group']; 
			$params = [
			    'chat_id'=>$chatId,  
			    'text'=> get_site_url() . PHP_EOL . $html,
			    'parse_mode' => 'html'
			];
			$ch = curl_init($website . '/sendMessage');
			curl_setopt($ch, CURLOPT_HEADER, false);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, ($params));
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			$result = curl_exec($ch);
			curl_close($ch);
		}
		

	}

	public function lark_send($amount, $description) {
		  if(isset($this->settings['lark_hook']) && $this->settings['lark_hook']){
		  		$time = date('d/m/Y h:i');
		  		$msg = array(
		  		  "msg_type" => "interactive",
		  		  "card" => array(
		  		    "config" => array(
		  		      "wide_screen_mode" => true
		  		    ),
		  		    "elements" => array(
		  		      array(
		  		        "tag" => "markdown",
		  		        "content" => "Thông báo tự động từ hệ thống. Số tài khoản **[" . $this->settings['account']['phone'] . "]**"
		  		      ),
		  		      array(
		  		        "tag" => "markdown",
		  		        "content" => "Bạn vừa nhận được <font color='red'>**+" . number_format($amount, 0, ',', '.') . "đ**</font> vào tài khoản"
		  		      ),
		  		      array(
		  		        "tag" => "markdown",
		  		        "content" => "**Số tiền**: <font color='red'>**+" . number_format($amount, 0, ',', '.') . "đ**</font>\n**Chi tiết:** " . $description . "\n**Thời gian**: " . $time
		  		      )
		  		    ),
		  		    "header" => array(
		  		      "template" => "green",
		  		      "title" => array(
		  		        "content" => "🔥🔥🔥 Biến động số dư tài khoản Vietcombank - " . $this->settings['account']['phone'],
		  		        "tag" => "plain_text"
		  		      )
		  		    )
		  		  )
		  		);

		      $curl = curl_init();
			curl_setopt_array($curl, array(
			  CURLOPT_URL => $this->settings['lark_hook'],
			  CURLOPT_RETURNTRANSFER => true,
			  CURLOPT_ENCODING => '',
			  CURLOPT_MAXREDIRS => 10,
			  CURLOPT_TIMEOUT => 0,
			  CURLOPT_FOLLOWLOCATION => true,
			  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			  CURLOPT_CUSTOMREQUEST => 'POST',
			  CURLOPT_POSTFIELDS =>json_encode($msg),
			  CURLOPT_HTTPHEADER => array(
			    'Content-Type: application/json'
			  ),
			));

			$response = curl_exec($curl);

			curl_close($curl);
			return $response;
		  }
  }

	private function encode_($license){
		$lic_encode =  base64_encode(json_encode($license));
		$lic_encode = str_split($lic_encode, 50);
		$lic_encode[0] = $this->key_e . $lic_encode[0];
		$lic_encode[2] = $this->key_e . $lic_encode[2];
		$lic_encode = implode('', $lic_encode);
		$lic_encode = strrev($lic_encode);
		return $lic_encode;
	}

	private function decode_($license){
		$lic_encode = strrev($license);
		$lic_encode = explode($this->key_e, $lic_encode);
		$lic_encode = implode('', $lic_encode);
		$lic_encode =  base64_decode($lic_encode);
		return json_decode($lic_encode, true);
	}

	public function getInbetweenStrings($start, $end, $str){
		$start = strtolower($start);
		$end = strtolower($end);
		$str = strtolower($str);
	    $matches = array();
	    $regex = "/$start([a-zA-Z0-9]*)$end/";
	    preg_match_all($regex, $str, $matches);
	    return $matches[1];
	}



	public function admin_template()
	{
		// echo VCB_MH_URL;
		// wp_redirect( VCB_MH_URL .'admin/' );
		 require_once plugin_dir_path( __FILE__ ) . 'partials/' .$this->plugin_name . '-admin-display.php';
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Vcb_Mh_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Vcb_Mh_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/vcb-mh-admin.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Vcb_Mh_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Vcb_Mh_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		// wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/vcb-mh-admin.js', array( 'jquery' ), $this->version, false );

	}

}
