(function( $ ) {
	'use strict';

	function copyToClipboard(text) {
	    var dummy = document.createElement("textarea");
	    // to avoid breaking orgain page when copying more words
	    // cant copy when adding below this code
	    // dummy.style.display = 'none'
	    document.body.appendChild(dummy);
	    //Be careful if you use texarea. setAttribute('value', value), which works with "input" does not work with "textarea". – Eduard
	    dummy.value = text;
	    dummy.select();
	    document.execCommand("copy");
	    document.body.removeChild(dummy);
	}

	function getParameterByName(name, url = window.location.href) {
	    name = name.replace(/[\[\]]/g, '\\$&');
	    var regex = new RegExp('[?&]' + name + '(=([^&#]*)|&|#|$)'),
	        results = regex.exec(url);
	    if (!results) return null;
	    if (!results[2]) return '';
	    return decodeURIComponent(results[2].replace(/\+/g, ' '));
	}
	function forceDownload(url, fileName){
	    var xhr = new XMLHttpRequest();
	    xhr.open("GET", url, true);
	    xhr.responseType = "blob";
	    xhr.onload = function(){
	        var urlCreator = window.URL || window.webkitURL;
	        var imageUrl = urlCreator.createObjectURL(this.response);
	        var tag = document.createElement('a');
	        tag.href = imageUrl;
	        tag.download = fileName;
	        document.body.appendChild(tag);
	        tag.click();
	        document.body.removeChild(tag);
	    }
	    xhr.send();
	}


	jQuery(document).ready(function($) {


		// console.log($('#vcb-gateway-settings').data('settings'))
		const APP = {
			timer: null,
			settings: $('#vcb-gateway-settings').data('settings'),

			checkPayment(){
				if(!this.settings || !this.settings.hasOwnProperty('notify'))
					return;
				const momoBox = $('#vcb-gateway');
				const successHtml = `<div class="vcb-gateway-result"><div class="success-animation">
		 	    		<svg class="checkmark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52"><circle class="checkmark__circle" cx="26" cy="26" r="25" fill="none" /><path class="checkmark__check" fill="none" d="M14.1 27.2l7.1 7.2 16.7-16.8" /></svg>
		 	    		</div><div class="text-center">${this?.settings?.notify?.order_completed}</div></div>`;

				const orderStatus = $('#vcb-gateway-order_status').val();
				if(orderStatus == 'completed'){
					momoBox.html(successHtml);
					return;
				}				

				const this_ = this
				let isFetching = false;
				if(momoBox.length){
					const data = {
						action: 'vcb_gw_waiting_payment',
						nonce: $('#vcb-gateway-nonce').val(),
						order_id: $('#vcb-gateway-order_id').val(),
					}
					let count = 1;

					this.timer = setInterval(() => {
						
						//Prevent send request if time is over 300s
						if(count == 60)
							clearInterval(this_.timer)
						
						if(isFetching)
							return
						isFetching = true
						$.ajax({
							url: this_.settings.ajax_url,
							// url: this.settings.plugin_url + '/admin/api/?sync=' + data.order_id,
							type: 'GET',
							dataType: 'json',
							data,
						})
						.done(function(res) {
							if(res.success){
								console.log(res);
								clearInterval(this_.timer)
								momoBox.html(successHtml);
								Swal.fire({
								  icon: 'success',
								  title: 'Thanh toán thành công',
								  text: `${res.data.comment}`,
								  confirmButtonText: `OK`
								}).then(r => {
									

								})
								if(this_.settings.reload_after_completed == 'true')
									setTimeout(() => {
										if(this_.settings.url_redirect != '')
											window.location.href = this_.settings.url_redirect + `?key=` + getParameterByName('key')
										else
											window.location.reload() 
									}, 2000)
							}
						})
						.always(function() {
							isFetching = false
						});

						count++;
					}, 4000)
					
				}

			},
			init(){
				this.checkPayment();
				$('.download-btn').click(() => {
				  forceDownload($('.qrVietqr').eq(0).attr('src'), 'qr.jpg')
				})
				$('.vcb-gw-coppy, .copy-btn').click(function(event) {
					const text = $(this).data('c');
					if(text)
					{
						copyToClipboard(text);
						Swal.fire({
						  icon: 'success',
						  title: 'Sao chép thành công',
						  confirmButtonText: text
						})
					}
				});
			}
		}

		APP.init();

	});

})( jQuery );
