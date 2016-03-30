<?php
class ControllerPaymentSSLCommerce extends Controller {
	public function index() {
		$data['button_confirm'] = $this->language->get('button_confirm');

		$this->load->model('checkout/order');

		$order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);

		$data['merchant'] = $this->config->get('SSLCommerce_merchant');
		$data['trans_id'] = $this->session->data['order_id'];
		$data['amount'] = $this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false);
		
		if ($this->config->get('SSLCommerce_password')) {
			$data['digest'] = md5($this->session->data['order_id'] . $this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false) . $this->config->get('SSLCommerce_password'));
		} else {
			$data['digest'] = '';
		}		
		
                                        $data['SSLCommerce_test'] = $this->config->get('SSLCommerce_test');
		$data['bill_name'] = $order_info['payment_firstname'] . ' ' . $order_info['payment_lastname'];
		$data['bill_addr_1'] = $order_info['payment_address_1'];
		$data['bill_addr_2'] = $order_info['payment_address_2'];
		$data['bill_city'] = $order_info['payment_city'];
		$data['bill_state'] = $order_info['payment_zone'];
		$data['bill_post_code'] = $order_info['payment_postcode'];
		$data['bill_country'] = $order_info['payment_country'];
		$data['bill_tel'] = $order_info['telephone'];
		$data['bill_email'] = $order_info['email'];

		if ($this->cart->hasShipping()) {
			$data['ship_name'] = $order_info['shipping_firstname'] . ' ' . $order_info['shipping_lastname'];
			$data['ship_addr_1'] = $order_info['shipping_address_1'];
			$data['ship_addr_2'] = $order_info['shipping_address_2'];
			$data['ship_city'] = $order_info['shipping_city'];
			$data['ship_state'] = $order_info['shipping_zone'];
			$data['ship_post_code'] = $order_info['shipping_postcode'];
			$data['ship_country'] = $order_info['shipping_country'];
		} else {
			$data['ship_name'] = '';
			$data['ship_addr_1'] = '';
			$data['ship_addr_2'] = '';
			$data['ship_city'] = '';
			$data['ship_state'] = '';
			$data['ship_post_code'] = '';
			$data['ship_country'] = '';
		}

		$data['currency'] = $this->currency->getCode();
		$data['callback'] = $this->url->link('payment/SSLCommerce/callback', '', 'SSL');
                $data['failure'] = $this->url->link('payment/SSLCommerce/failure', '', 'SSL');
                $data['cancel'] = $this->url->link('checkout/cart', '', 'SSL');
		$products = '';
		
		foreach ($this->cart->getProducts() as $product) {
    		$products .= $product['quantity'] . ' x ' . $product['name'] . ', ';
    	}		
		
		$data['detail1_text'] = $products;

		switch ($this->config->get('SSLCommerce_test')) {
			case 'live':
				$status = 'live';
				break;
			case 'successful':
			default:
				$status = 'true';
				break;
			case 'fail':
				$status = 'false';
				break;
		}
		
		$data['options'] = 'test_status=' . $status . ',dups=false,cb_post=false';

		if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/payment/SSLCommerce.tpl')) {
			return $this->load->view($this->config->get('config_template') . '/template/payment/SSLCommerce.tpl', $data);
		} else {
			return $this->load->view('default/template/payment/SSLCommerce.tpl', $data);
		}
	}

	public function callback() {
                                     $SSLCommerce_test = $this->config->get('SSLCommerce_test');
                                     $store_id = $this->config->get('SSLCommerce_merchant');
                                      $store_passwd = $this->config->get('SSLCommerce_password');
                                     // print_r($_POST);exit;
                                      
		if (isset($_POST['tran_id'])) {
			$order_id = $_POST['tran_id'];
                                                                     
		} else {
			$order_id = 0;
		}
                if (isset($_POST['amount'])) {
                                                                     $total=$_POST['amount'];	
                                                                    $val_id = $_POST['val_id']; 
		}else
                {
                    $total='';	
                                                                    $val_id = ''; 
                }
		$this->load->model('checkout/order');
		$order_info = $this->model_checkout_order->getOrder($order_id);
                                        $amount = $this->currency->format($order_info['total'], $order_info['currency_code'], $order_info['currency_value'], false);
                                       
                                        if ($SSLCommerce_test=='successful') { 
                                     $requested_url = ("https://securepay.sslcommerz.com/validator/test/api/validationserverAPI.php
?val_id=".$val_id."&Store_Id=".$store_id."&Store_Passwd=".$store_passwd."&v=1&format=json");
                                        } else{
                                       $requested_url = ("https://securepay.sslcommerz.com/validator/api/validationserverAPI.php?val_id=".$val_id."&Store_Id=".$store_id."&Store_Passwd=".$store_passwd."&v=1&format=json");  
                                        }  
                                        $handle = curl_init();
                                        curl_setopt($handle, CURLOPT_URL, $requested_url);
                                        curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
                                        curl_setopt($handle, CURLOPT_SSL_VERIFYHOST, false);
                                        curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);
                                        $result = curl_exec($handle);
                                        $code = curl_getinfo($handle, CURLINFO_HTTP_CODE);
                                        if($code == 200 && !( curl_errno($handle)))
                                        {
                                            $result = json_decode($result);
                                            $status = $result->status;	
                                            $tran_date = $result->tran_date;
                                            $tran_id = $result->tran_id;
                                            $tran_id = trim(strstr($tran_id, '_',true));
                                            $val_id = $result->val_id;
                                            $amount = intval($result->amount);
                                            $store_amount = $result->store_amount;
                                            $bank_tran_id = $result->bank_tran_id;
                                            $card_type = $result->card_type;
                                            if(($status=='VALID')&&(intval($amount)==intval($total)))
                                            {
                                                 $status = 'success';
                                            }
                                            else
                                            {
                                                 $status = 'failed';
                                            }
                                        }

                                                    $data['breadcrumbs'] = array();

			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('text_home'),
				'href' => $this->url->link('common/home')
			);

			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('text_basket'),
				'href' => $this->url->link('checkout/cart')
			);

			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('text_checkout'),
				'href' => $this->url->link('checkout/checkout', '', 'SSL')
			);

			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('text_failed'),
				'href' => $this->url->link('checkout/success')
			);

			$data['heading_title'] = $this->language->get('text_failed');

			//$data['text_message'] = sprintf($this->language->get('text_failed_message'), $error, $this->url->link('information/contact'));

			$data['button_continue'] = $this->language->get('button_continue');
		//echo 'hi';exit;							
		if ($order_info && $status) {
			$this->language->load('payment/SSLCommerce');
	
			$data['title'] = sprintf($this->language->get('heading_title'), $this->config->get('config_name'));
	
			if (!isset($this->request->server['HTTPS']) || ($this->request->server['HTTPS'] != 'on')) {
				$data['base'] = HTTP_SERVER;
			} else {
				$data['base'] = HTTPS_SERVER;
			}
	
			$data['language'] = $this->language->get('code');
			$data['direction'] = $this->language->get('direction');
	
			$data['heading_title'] = sprintf($this->language->get('heading_title'), $this->config->get('config_name'));
	
			$data['text_response'] = $this->language->get('text_response');
			$data['text_success'] = $this->language->get('text_success');
			$data['text_success_wait'] = sprintf($this->language->get('text_success_wait'), $this->url->link('checkout/success'));
			$data['text_failure'] = $this->language->get('text_failure');
			$data['text_failure_wait'] = sprintf($this->language->get('text_failure_wait'), $this->url->link('checkout/cart'));
	
			if (isset($status) && $status == 'success') {
				$this->load->model('checkout/order');
	
				 $this->model_checkout_order->addOrderHistory($_POST['tran_id'], $this->config->get('config_order_status_id'));
	
				$message = '';
	
				if (isset($_POST['pay_status'])) {
					$message .= 'Payment Status = ' . $_POST['pay_status'] . "\n";
				    }
					
					if (isset($_POST['epw_txnid'])) {
					$message .= 'epw txnid = ' . $_POST['epw_txnid'] . "\n";
				    }
					
					if (isset($_POST['tran_id'])) {
					$message .= 'Your Oder id = ' . $_POST['tran_id'] . "\n";
					
				    }if (isset($_POST['currency'])) {
					$message .= 'Currency Store = ' . $_POST['currency'] . "\n";
				    }
					
					if (isset($_POST['currency_merchant'])) {
					$message .= 'Currency Merchant = ' . $_POST['currency_merchant'] . "\n"; 
				    }
					
					if (isset($_POST['convertion_rate'])) {
					$message .= 'Currency Convertion_rate = ' . $_POST['convertion_rate'] . "\n"; 
				    }
					
					if (isset($_POST['store_amount'])) {
					$message .= 'Reciable Amount After EPW Service = ' . $_POST['store_amount'] . "\n"; 
				    }
					
					if (isset($_POST['pay_time'])) {
					$message .= 'Payment Date = ' . $_POST['pay_time'] . "\n";  
				    }
					
					if (isset($_POST['bank_txn'])) {
					$message .= 'Bank Transaction ID = ' . $_POST['bank_txn'] . "\n";
				    }
					
					if (isset($_POST['card_number'])) {
					$message .= 'Card Number = ' . $_POST['card_number'] . "\n"; 
				    }
					
					if (isset($_POST['card_type'])) {
					$message .= 'Card Type = ' . $_POST['card_type'] . "\n"; 
				    }
					
					if (isset($_POST['ip_address'])) {
					$message .= 'Customer IP Addresss = ' . $_POST['ip_address'] . "\n"; 
				    }
					
					if (isset($_POST['other_currency'])) {
					$message .= 'Currecy = ' . $_POST['other_currency'] . "\n";
				    }
					if (isset($_POST['epw_service_charge_bdt'])) {
					$message .= 'Curreny Charged in BDT = ' . $_POST['epw_service_charge_bdt'] . "\n"; 
				    }
					if (isset($_POST['epw_service_charge_usd'])) {
					$message .= 'Curreny Charged in USD = ' . $_POST['epw_service_charge_usd'] . "\n"; 
				    }
					if (isset($_POST['reason'])) {
					$message .= 'Reason for Failure  = ' . $_POST['reason'] . "\n";
				    }
	
                                                        $this->model_checkout_order->addOrderHistory($order_id, $this->config->get('SSLCommerce_order_status_id'), $message, false);
	$error='';
                                                        $data['text_message'] = sprintf('your payment was successfully received', $error, $this->url->link('information/contact'));
			$data['continue'] = $this->url->link('checkout/success');
                                                        $data['column_left'] = $this->load->controller('common/column_left');
			$data['column_right'] = $this->load->controller('common/column_right');
			$data['content_top'] = $this->load->controller('common/content_top');
			$data['content_bottom'] = $this->load->controller('common/content_bottom');
			$data['footer'] = $this->load->controller('common/footer');
			$data['header'] = $this->load->controller('common/header');

			if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/payment/success.tpl')) {
				$this->response->setOutput($this->load->view($this->config->get('config_template') . '/template/payment/success.tpl', $data));
			} else {
				$this->response->setOutput($this->load->view('default/template/payment/success.tpl', $data));
			}

			}
			else if (isset($status) && $status == 'failed') {
				$this->load->model('checkout/order');
	
				//$this->model_checkout_order->confirm($_POST['tran_id'], $this->config->get('config_order_status_id'));
	
				$message = '';
	
				if (isset($_POST['pay_status'])) {
					$message .= 'Payment Status = ' . $_POST['pay_status'] . "\n";
				    }
					
					if (isset($_POST['epw_txnid'])) {
					$message .= 'epw txnid = ' . $_POST['epw_txnid'] . "\n";
				    }
					
					if (isset($_POST['mer_txnid'])) {
					$message .= 'Your Oder id = ' . $_POST['mer_txnid'] . "\n";
					
				    }if (isset($_POST['currency'])) {
					$message .= 'Currency Store = ' . $_POST['currency'] . "\n";
				    }
					
					if (isset($_POST['currency_merchant'])) {
					$message .= 'Currency Merchant = ' . $_POST['currency_merchant'] . "\n"; 
				    }
					
					if (isset($_POST['convertion_rate'])) {
					$message .= 'Currency Convertion_rate = ' . $_POST['convertion_rate'] . "\n"; 
				    }
					
					if (isset($_POST['store_amount'])) {
					$message .= 'Reciable Amount After  Service = ' . $_POST['store_amount'] . "\n"; 
				    }
					
					if (isset($_POST['pay_time'])) {
					$message .= 'Payment Date = ' . $_POST['pay_time'] . "\n";  
				    }
					
					if (isset($_POST['bank_txn'])) {
					$message .= 'Bank Transaction ID = ' . $_POST['bank_txn'] . "\n";
				    }
					
					if (isset($_POST['card_number'])) {
					$message .= 'Card Number = ' . $_POST['card_number'] . "\n"; 
				    }
					
					if (isset($_POST['card_type'])) {
					$message .= 'Card Type = ' . $_POST['card_type'] . "\n"; 
				    }
					
					if (isset($_POST['ip_address'])) {
					$message .= 'Customer IP Addresss = ' . $_POST['ip_address'] . "\n"; 
				    }
					
					if (isset($_POST['other_currency'])) {
					$message .= 'Currecy = ' . $_POST['other_currency'] . "\n";
				    }
					if (isset($_POST['epw_service_charge_bdt'])) {
					$message .= 'Curreny Charged in BDT = ' . $_POST['epw_service_charge_bdt'] . "\n"; 
				    }
					if (isset($_POST['epw_service_charge_usd'])) {
					$message .= 'Curreny Charged in USD = ' . $_POST['epw_service_charge_usd'] . "\n"; 
				    }
					if (isset($_POST['reason'])) {
					$message .= 'Reason for Failure  = ' . $_POST['reason'] . "\n";
				    }
	$this->model_checkout_order->addOrderHistory($order_id, $this->config->get('SSLCommerce_order_fail_id'), $message, false);
				//$this->model_checkout_order->update($order_id, $this->config->get('SSLCommerce_order_fail_id'), $message, false);
	
				$data['continue'] = $this->url->link('checkout/checkout');
                                                          $data['column_left'] = $this->load->controller('common/column_left');
			$data['column_right'] = $this->load->controller('common/column_right');
			$data['content_top'] = $this->load->controller('common/content_top');
			$data['content_bottom'] = $this->load->controller('common/content_bottom');
			$data['footer'] = $this->load->controller('common/footer');
			$data['header'] = $this->load->controller('common/header');

			if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/payment/Commerce_failure.tpl')) {
				$this->response->setOutput($this->load->view($this->config->get('config_template') . '/template/payment/Commerce_failure.tpl', $data));
			} else {
				$this->response->setOutput($this->load->view('default/template/payment/Commerce_failure.tpl', $data));
			}

			} else {
				$data['continue'] = $this->url->link('checkout/cart');
                                                        $data['column_left'] = $this->load->controller('common/column_left');
			$data['column_right'] = $this->load->controller('common/column_right');
			$data['content_top'] = $this->load->controller('common/content_top');
			$data['content_bottom'] = $this->load->controller('common/content_bottom');
			$data['footer'] = $this->load->controller('common/footer');
			$data['header'] = $this->load->controller('common/header');

			if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/payment/Commerce_failure.tpl')) {
				$this->response->setOutput($this->load->view($this->config->get('config_template') . '/template/payment/Commerce_failure.tpl', $data));
			} else {
				$this->response->setOutput($this->load->view('default/template/common/Commerce_failure.tpl', $data));
			}
	

			}
		}
	}
        public function failure() {
            $this->load->model('checkout/order');
            $this->language->load('payment/SSLCommerce');
	
			$data['title'] = sprintf($this->language->get('heading_title'), $this->config->get('config_name'));
	
			if (!isset($this->request->server['HTTPS']) || ($this->request->server['HTTPS'] != 'on')) {
				$data['base'] = HTTP_SERVER;
			} else {
				$data['base'] = HTTPS_SERVER;
			}
	
			$data['language'] = $this->language->get('code');
			$data['direction'] = $this->language->get('direction');
	
			$data['heading_title'] = sprintf($this->language->get('heading_title'), $this->config->get('config_name'));
	
			$data['text_response'] = $this->language->get('text_response');
			$data['text_success'] = $this->language->get('text_success');
			$data['text_success_wait'] = sprintf($this->language->get('text_success_wait'), $this->url->link('checkout/success'));
			$data['text_failure'] = 'Your payment has been Failure!';
			$data['text_failure_wait'] = sprintf($this->language->get('text_failure_wait'), $this->url->link('checkout/cart'));
             $data['breadcrumbs'] = array();

			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('text_home'),
				'href' => $this->url->link('common/home')
			);

			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('text_basket'),
				'href' => $this->url->link('checkout/cart')
			);

			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('text_checkout'),
				'href' => $this->url->link('checkout/checkout', '', 'SSL')
			);

			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('text_failed'),
				'href' => $this->url->link('checkout/success')
			);

			$data['heading_title'] = $this->language->get('text_failed');
            $order_id = $_POST['tran_id'];
//$order_info = $this->model_checkout_order->getOrder($order_id);
//print_r($order_info);exit;
                    $message = '';
                  
                        
                            if (isset($_POST['error'])) {
                            $message .= 'Reason for Failure  = ' . $_POST['error'] . "\n";
                        }
$this->model_checkout_order->confirm($order_id, $this->config->get('config_order_status_id'));
$this->model_checkout_order->update($order_id, $this->config->get('SSLCommerce_order_fail_id'), $message, false);
$this->model_checkout_order->addOrderHistory($order_id, $this->config->get('SSLCommerce_order_fail_id'), $message, false);

                    $data['continue'] = $this->url->link('checkout/checkout');
            $data['column_left'] = $this->load->controller('common/column_left');
            $data['column_right'] = $this->load->controller('common/column_right');
            $data['content_top'] = $this->load->controller('common/content_top');
            $data['content_bottom'] = $this->load->controller('common/content_bottom');
            $data['footer'] = $this->load->controller('common/footer');
            $data['header'] = $this->load->controller('common/header');

            if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/payment/Commerce_failure.tpl')) {
                    $this->response->setOutput($this->load->view($this->config->get('config_template') . '/template/payment/Commerce_failure.tpl', $data));
            } else {
                    $this->response->setOutput($this->load->view('default/template/payment/Commerce_failure.tpl', $data));
            }
        }
}
