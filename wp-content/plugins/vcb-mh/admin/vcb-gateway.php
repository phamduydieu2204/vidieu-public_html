<?php 
	
	class Vcb_Gateway_MH extends WC_Payment_Gateway {

	 		/**
	 		 * Class constructor, more about it in Step 3
	 		 */
	 		public $vcb_gw_settings = [];
	 		public function __construct() {

	 			$vcb_gw_settings = get_option('vcb_gw_settings', true);
	 			// print_r($vcb_gw_settings);
	 			if(!$vcb_gw_settings)
	 				return;
	 			$this->vcb_gw_settings = json_decode($vcb_gw_settings, true);
	 			$this->id = 'vcb-gateway-mh'; // payment gateway plugin ID
 				$this->icon = VCB_MH_URL . "public/images/VCB.png"; // URL of the icon that will be displayed on checkout page near your gateway name
 				$this->has_fields = true; // in case you need a custom credit card form
 				$this->method_title = 'Vietcombank Gateway MH';
 				$this->method_description = 'Thanh toán online với Vietcombank'; // will be displayed on the options page
 				// gateways can support subscriptions, refunds, saved payment methods,
 				// but in this tutorial we begin with simple payments
 				$this->supports = array(
 					'products'
 				);

 				// Method with all the options fields
 				$this->init_form_fields();

 				// Load the settings.
 				$this->init_settings();
	 			
 				
 				$this->title = $this->vcb_gw_settings['notify']['payment_gateway_label'];
 				$this->description = $this->vcb_gw_settings['notify']['method_description'];
 				$this->enabled = $this->get_option( 'enabled' );

 				// This action hook saves the settings
 				add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

 				// We need custom JavaScript to obtain a token
 				add_action( 'wp_enqueue_scripts', array( $this, 'payment_scripts' ) );
 			
				 add_action( 'woocommerce_thankyou', array( $this, 'thankyou_page' ), 4 );

	 		}
	
			/**
	 		 * Plugin options, we deal with it in Step 3 too
	 		 */
	 		public function init_form_fields(){

	 			$this->form_fields = array(
				'enabled' => array(
					'title'       => 'Bật/Tắt',
					'label'       => 'Bật thanh toán Vietcombank',
					'type'        => 'checkbox',
					'description' => '',
					'default'     => 'no'
				),
				'title' => array(
				    'title' => __( 'Tên Cổng Thanh Toán', 'woocommerce' ),
				    'type' => 'text',
				    'description' => __( 'Tên cổng thanh toán mà người dùng sẽ thấy khi thanh toán', 'woocommerce' ),
				    'default' => 'Thanh toán online với Vietcombank, dành cho chuyển tiền nội địa và liên ngân hàng',
				    'desc_tip'      => true,
				),
				'description' => array(
				    'title' => __( 'Mô Tả Cho Khách', 'woocommerce' ),
				    'type' => 'textarea',
				    'description' => __( 'Đoạn mô tả giúp khách hiểu rõ hơn cách thức thanh toán', 'woocommerce' ),
				    'default' => 'Hãy mở App Ngân hàng của bạn lên và nhấn Đặt Hàng để quét mã thanh toán'
				),
				
			);

		
		 	}

			/**
			 * You will need it if you want your custom credit card form, Step 4 is about it
			 */
			public function payment_fields() {


					
					echo '<div> '.$this->vcb_gw_settings['notify']['method_description'].'</div>';
					
			
					 
			}

			/*
			 * Custom CSS and JS, in most cases required only when you decided to go with a custom credit card form
			 */
		 	public function payment_scripts() {

				// // we need JavaScript to process a token only on cart/checkout pages, right?
				// 	if ( ! is_cart() && ! is_checkout() ) {
				// 		return;
				// 	}

				// 	// if our payment gateway is disabled, we do not have to enqueue JS too
				// 	if ( 'no' === $this->enabled ) {
				// 		return;
				// 	}

		
		 	}

			/*
	 		 * Fields validation, more in Step 5
			 */
			public function validate_fields() {

				return true;

			}

			/*
			 * We're processing the payments here, everything about it is in Step 5
			 */
			public function process_payment( $order_id ) {
				$order = wc_get_order( $order_id );
				            
				    // Mark as on-hold (we're awaiting the payment)
				    // $order->update_status( 'on-hold', __( 'Awaiting offline payment', 'wc-gateway-offline' ) );
					if(isset($this->vcb_gw_settings['create_order_status']) && $this->vcb_gw_settings['create_order_status'])
						    $order->update_status( $this->vcb_gw_settings['create_order_status'] );
				            
				    // Reduce stock levels
				    $order->reduce_order_stock();
				            
				    // Remove cart
				    WC()->cart->empty_cart();
				            
				    // Return thankyou redirect
				    return array(
				        'result'    => 'success',
				        'redirect'  => $this->get_return_url( $order )
				    );

			}

			/*
			 * In case you need a webhook, like PayPal IPN etc
			 */
			public function webhook() {

			
						
		 	}


		 	
		 	
		 	public function thankyou_page( $order_id ) {
		 	    global $wpdb;
		 	    $order = new WC_Order($order_id);
		 	    	

		 	    if($order->get_payment_method() != $this->id) return;

		 	  
		 	    
		 	    $total = $order->get_total();

		 	    $sotien = 'Số tiền:';
		 	    if(isset($this->vcb_gw_settings['currency_rate']) && $this->vcb_gw_settings['currency_rate'])
		 	    {
		 	    	$total = intval($total * $this->vcb_gw_settings['currency_rate']);
		 	    	$sotien = 'Số tiền quy đổi VNĐ:';
		 	    }

		 	    $phone = $this->vcb_gw_settings['account']['phone'];
		 	    $note = $this->vcb_gw_settings['prefix'] . $order_id . $this->vcb_gw_settings['subfix'];
		 	    $qrCode = "https://api.vietqr.io/970436/".$this->vcb_gw_settings['account']['phone']."/".$total."/".$note."/qr_only.jpg";
		 	    // $qrCode = "https://api.vietqr.io/970436/".$this->vcb_gw_settings['account']['phone']."/".$total."/".$note."/print.jpg?accountName={$this->vcb_gw_settings['account']['name']}";
		 	    $sql = "SELECT * FROM vcb_gateway_transactions WHERE order_id = $order_id AND is_paid = 1";
		 	    $isCompleted = $wpdb->get_row($sql, ARRAY_A);
		 	
		 	    ?>
		 	    	<!-- <script src="https://js.pusher.com/7.0.3/pusher.min.js"></script> -->
		 	    	<script src="<?php echo VCB_MH_URL ?>/public/js/sweetalert2.all.min.js" ></script>
		 	    	<link rel="stylesheet" href="<?php echo VCB_MH_URL ?>/public/css/sweetalert2.css"/>

		 	    	<div id="vcb-gateway">
		 	    		<?php if ($isCompleted): ?>
		 	    			<div class="vcb-gateway-result"><div class="success-animation">
			 	    		<svg class="checkmark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52"><circle class="checkmark__circle" cx="26" cy="26" r="25" fill="none" /><path class="checkmark__check" fill="none" d="M14.1 27.2l7.1 7.2 16.7-16.8" /></svg>
			 	    		</div><div class="text-center"><?php echo $this->vcb_gw_settings['notify']['order_completed'] ?></div></div>
		 	    		<?php else: ?>
		 	    		<div id="payment-info">
	 	    			    <div id="left-col">
	 	    			        <span><b>Bước 1:</b> Mở Ví điện tử/Ngân hàng</span>
	 	    			        <div class="flex-center-box">
	 	    			            <span><b>Bước 2:</b> Chọn</span>
	 	    			            <img src="<?php echo VCB_MH_URL  ?>/public/images//qr.svg" />
	 	    			            <span>và quét mã</span>
	 	    			        </div>
	 	    			        <img class="qrVietqr" src="<?php echo  $qrCode ?>" />
	 	    			        <div class="download-btn mt-none">
	 	    			        	<img class="" width="30" src="<?php echo VCB_MH_URL  ?>/public/images/download.svg" />
	 	    			        	Tải xuống
	 	    			        </div>
	 	    			        <div class="">
	 	    			        	
	 	    			        </div>
	 	    			        <span><b>Bước 3:</b> Xác Nhận Chuyển Khoản</span>
	 	    			    </div>
	 	    			    <div id="right-col">
	 	    			        <div class="anMoblie">
	 	    			            <div style="font-weight: bold;">THÔNG TIN CHUYỂN KHOẢN</div>
	 	    			            <div style="font-size: small; line-height: 1, 2em;">
	 	    			                Hỗ trợ Ví điện tử MoMo/ZaloPay<br />
	 	    			                Hoặc ứng dụng ngân hàng để chuyển khoản nhanh 24/7
	 	    			            </div>
	 	    			        </div>

	 	    			        <div class="anPc">
	 	    			            <div style="margin: auto;"><span style="color: red; font-weight: bold;">Cách 1:</span> <b>QUÉT MÃ THANH TOÁN</b></div>
	 	    			            <img class="qrVietqr" src="<?php echo  $qrCode ?>" />
	 	    			            <div class="download-btn mt-none">
	 	    			        	<img class="" width="30" src="<?php echo VCB_MH_URL  ?>/public/images/download.svg" />
	 	    			        	Tải xuống
	 	    			        </div>
	 	    			            <div class="taiMaQrCode">
	 	    			                <span><b>Bước 1:</b> Chụp màn hình điện thoại, hoặc tải xuống mã QR</span>
	 	    			            </div>
	 	    			            <span><b>Bước 2:</b> Mở Ví điện tử/Ngân hàng</span>
	 	    			            <div class="flex-center-box">
	 	    			                <b>Bước 3:</b> Chọn
	 	    			                <img src="<?php echo VCB_MH_URL  ?>/public/images//qr.svg" />
	 	    			                và chọn hình đã chụp ở Bước 1 (Trong thư viện ảnh)
	 	    			            </div>
	 	    			            <span><b>Bước 4:</b> Xác Nhận Chuyển Khoản</span>
	 	    			            <div style="border: 1px dashed #e2e2e2; width: 100%; border-width: 0 0 1px 0;"><br /></div>
	 	    			            <div style="padding-top: 20px; margin: auto;"><span style="color: red; font-weight: bold;">Cách 2:</span> <b>CHUYỂN KHOẢN THỦ CÔNG</b></div>
	 	    			        </div>

	 	    			        <div>
	 	    			            <div style="font-weight: bold; font-size: large;"><?php echo $this->vcb_gw_settings['account']['name']  ?></div>
	 	    			            <table>
	 	    			                <tbody>
	 	    			                    <tr>
	 	    			                        <th>Ngân Hàng:</th>
	 	    			                        <th>
	 	    			                            <img src="<?php echo VCB_MH_URL  ?>/public/images/970436.svg" />
	 	    			                        </th>
	 	    			                    </tr>
	 	    			                    <tr>
	 	    			                        <th>Tài Khoản:</th>
	 	    			                        <th>
	 	    			                            <span> <?php echo $this->vcb_gw_settings['account']['phone']  ?> </span><button class="copy-btn" data-c="<?php echo $this->vcb_gw_settings['account']['phone'] ?>"><img width="30" src="<?php echo VCB_MH_URL  ?>/public/images/copy.svg" alt="" /></button>
	 	    			                        </th>
	 	    			                    </tr>
	 	    			                    <tr>
	 	    			                        <th>Số Tiền:</th>
	 	    			                        <th>
	 	    			                            <?php echo number_format($total) ?> ₫ <button class="copy-btn" data-c="<?php echo $total ?>"><img width="30" src="<?php echo VCB_MH_URL  ?>/public/images/copy.svg" alt="" /></button>
	 	    			                        </th>
	 	    			                    </tr>
	 	    			                    <tr>
	 	    			                        <th>Nội Dung<span style="color: red;">*</span>:</th>
	 	    			                        <th>
	 	    			                            <span class="noiDungCopy" id="nd"><?php echo $note ?></span><button class="copy-btn" data-c="<?php echo $note ?>"><img width="30" src="<?php echo VCB_MH_URL  ?>/public/images/copy.svg" alt="" /></button>

	 	    			                            <!--  -->
	 	    			                        </th>
	 	    			                    </tr>
	 	    			                </tbody>
	 	    			            </table>
	 	    			        </div>
	 	    			        <div>
	 	    			            <div style="background: #f3f3f3; padding: 5px; margin: 10px 0 0 0; border-radius: 5px; max-width: 320px;">Vui lòng <span style="color:red; font-weight: bold; ">nhập chính xác nội dung, thanh toán xong vui lòng không tắt trình duyệt cho tới khi đơn hàng được xác nhận.</span></div>
	 	    			        </div>
		         	    	    <div class="acb-gw-is-mb text-center">
		         	    	    	<div class="">
		        	 	    	    	<img src="<?php echo VCB_MH_URL ?>/public/images/loading.svg" class="momo-loading">
		         	    	    	</div>
		         	    	    </div>
	 	    			    </div>
	 	    			</div>

		 	    		
		 	    	    
		 	    	   
		 	    	    <input type="hidden" id="vcb-gateway-nonce" value="<?php echo wp_create_nonce( 'check-payment_'.$order_id ); ?>">
		 	    	    <input type="hidden" id="vcb-gateway-order_id" value="<?php echo $order_id; ?>">
		 	    	    <input type="hidden" id="vcb-gateway-order_status" value="<?php echo $order->get_status(); ?>">
		 	    	    <input type="hidden" id="vcb-gateway-settings" data-settings='<?php echo json_encode([
		 	    	    	'ajax_url' => admin_url('admin-ajax.php'),
		 	    	    	'plugin_url' => VCB_MH_URL, 
		 	    	    	'notify' => $this->vcb_gw_settings['notify'],
		 	    	    	'reload_after_completed' => $this->vcb_gw_settings['reload_after_completed'],
		 	    	    	'url_redirect' => isset($this->vcb_gw_settings['url_redirect']) && $this->vcb_gw_settings['url_redirect'] ? $this->vcb_gw_settings['url_redirect'] : '',
		 	    	    	]) ?>'>
		 	    	    <?php endif ?>
		 	    	</div>

		 	    	
		 	    <?php
		 	    
		 	}
		}

?>