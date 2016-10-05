<?php
//====================================================
//  Файл:           alphabank_interwave.php
//  Назначение:     Контроллер платежей AB для Абмара
//  Разработчик:    InterWave
//  Версия:         1.0
//====================================================
class ControllerPaymentAlphabank extends Controller{
    public function index() {
        $this->load->language('payment/alphabank');
	$data['button_confirm'] = $this->language->get('button_confirm');
	$data['action'] = $this->url->link('payment/alphabank/checkout', '', 'SSL');
        $data['payment_desc']=  $this->language->get('text_description');
	if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/payment/alphabank.tpl')) {
            return $this->load->view($this->config->get('config_template') . '/template/payment/alphabank.tpl', $data);
	} else {
            return $this->load->view('default/template/payment/alphabank.tpl', $data);
	}
    }
    
    public function checkout() {
        $this->load->model('checkout/order');
	$this->load->model('account/order');
	$this->load->model('payment/alphabank');
        
        $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);
        $this->load->model('extension/extension');
        $results = $this->model_extension_extension->getExtensions('total');
	$order_data = array();
	$total = 0;
	$items = array();
	$taxes = $this->cart->getTaxes();
        
        $i = 0;
	foreach ($results as $result) {
            if ($this->config->get($result['code'] . '_status')) {
		$this->load->model('total/' . $result['code']);
		$this->{'model_total_' . $result['code']}->getTotal($order_data['totals'], $total, $taxes);
		if (isset($order_data['totals'][$i])) {
                    if (strstr(strtolower($order_data['totals'][$i]['code']), 'total') === false) {
			$item = new stdClass();
			$item->sku = $order_data['totals'][$i]['code'];
			$item->name = $order_data['totals'][$i]['title'];
			$item->amount = $order_data['totals'][$i]['value'];
			$item->qty = 1;
			$items[] = $item;
                    }
                    $i++;
		}
            }
	}
        
        $ordered_products = $this->model_account_order->getOrderProducts($this->session->data['order_id']);
        foreach ($ordered_products as $product) {
            $item = new stdClass();
            $item->sku = $product['product_id'];
            $item->name = $product['name'];
            $item->amount = $product['price'] * $product['quantity'];
            $item->qty = $product['quantity'];
            $items[] = $item;
	}
        

        $_sgateway = ($this->config->get('alphabank_test'))?'https://test.paymentgate.ru/testpayment/rest/':'https://engine.paymentgate.ru/payment/rest/';
        $_slogin = $this->config->get('alphabank_payment_slogin');
        $_spassword = $this->config->get('alphabank_payment_spassword');
        $_totalcop = $order_info['total']*100;
        $_totalrub = $order_info['total'];
        
        $_qdata = array(
            'userName' => $_slogin,
            'password' => $_spassword,
            'orderNumber' => urlencode($this->session->data['order_id']),
            'amount' => urlencode($_totalcop),
            'returnUrl' => $this->url->link('payment/alphabank/result')
	);
        
        $_responce = $this->model_payment_alphabank->gateway($_sgateway, 'register.do', $_qdata);
        if (isset($_responce['errorCode'])) {
            unset($_SESSION['order_id']);
            $this->session->data['error'] = 'Ошибка #' . $_responce['errorCode'] . ': ' . $_responce['errorMessage'];
            $this->response->redirect($this->url->link('checkout/cart'));
        }else{
            header('Location: '.$_responce['formUrl']);
            exit();
        }
        
    }
    
    public function result(){
        if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['orderId'])){
            $this->load->model('checkout/order');
            $this->load->model('account/order');
            $this->load->model('payment/alphabank');
            $this->load->language('payment/alphabank');
        
            $_sgateway = ($this->config->get('alphabank_test'))?'https://test.paymentgate.ru/testpayment/rest/':'https://engine.paymentgate.ru/payment/rest/';
            $_slogin = $this->config->get('alphabank_payment_slogin');
            $_spassword = $this->config->get('alphabank_payment_spassword');
            $_qdata = array(
                'userName' => $_slogin,
                'password' => $_spassword,
                'orderId' => $_GET['orderId']
            );
            
            $response = $this->model_payment_alphabank->gateway($_sgateway, 'getOrderStatus.do', $_qdata);
            $order_id = (isset($response['OrderNumber']))?$response['OrderNumber']:0;
            if (($response['OrderStatus'] == 1 || $response['OrderStatus'] == 2) && $response['ErrorCode'] == 0) {
                $this->model_checkout_order->addOrderHistory($order_id, $this->config->get('alphabank_order_status_id'));
                $this->response->redirect($this->url->link('payment/alphabank/callback', 'token=' . $this->session->data['token'], 'SSL'));
            }else{
                unset($_SESSION['order_id']);
                $this->session->data['error'] = 'Ошибка проведения платежа #'.$response['ErrorCode'].', Статус операции #'.$response['OrderStatus'];
                $this->response->redirect($this->url->link('checkout/cart'));
            }
        }else{
            unset($_SESSION['order_id']);
            $this->session->data['error'] = 'Сервер получил неверные данные. Попробуйте повторить ваш запрос немного позже.';
            $this->response->redirect($this->url->link('checkout/cart'));
        }
    }

    public function callback() {
        if ($this->session->data['payment_method']['code'] == 'alphabank') {
            $this->cart->clear(); // Очистить корзину
            unset($_SESSION['order_id']);
            echo '<html>' . "\n";
            echo '<head>' . "\n";
            echo '<meta http-equiv="Refresh" content="0; url=' . $this->url->link('checkout/success') . '">' . "\n";
            echo '</head>' . "\n";
            echo '<body>' . "\n";
            echo '<p>Сейчас вы будете перенаправлены обратно в магазин. Если этого не произошло - перейдите по <a href="' . $this->url->link('checkout/success') . '">ссылке</a>!</p>' . "\n";
            echo '</body>' . "\n";
            echo '</html>' . "\n";
            exit();
	}else{
            unset($_SESSION['order_id']);
            $this->session->data['error'] = 'Неверный статус проведения платежа';
            $this->response->redirect($this->url->link('checkout/cart'));
        }
    }
}
?>