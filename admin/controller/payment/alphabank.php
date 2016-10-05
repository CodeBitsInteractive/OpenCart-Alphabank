<?php
//====================================================
//  Файл:           alphabank_interwave.php
//  Назначение:     Контроллер платежей AB для Абмара
//  Разработчик:    InterWave
//  Версия:         1.0
//====================================================
class ControllerPaymentAlphabank extends Controller{
    // Массив ошибок
    private $error = array();
    
    // Главный метод
    public function index() {
        $this->load->language('payment/alphabank');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('setting/setting');
        
        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $this->model_setting_setting->editSetting('alphabank', $this->request->post);
            $this->session->data['success'] = $this->language->get('text_success');
            $this->response->redirect($this->url->link('extension/payment', 'token=' . $this->session->data['token'], 'SSL'));
	}
        
        $data['heading_title'] = $this->language->get('heading_title');
        $data['text_edit'] = $this->language->get('text_edit');
	$data['text_enabled'] = $this->language->get('text_enabled');
	$data['text_disabled'] = $this->language->get('text_disabled');
	$data['text_all_zones'] = $this->language->get('text_all_zones');
        $data['text_yes'] = $this->language->get('text_yes');
	$data['text_no'] = $this->language->get('text_no');
        
        $data['entry_slogin'] = $this->language->get('entry_slogin');
	$data['entry_spassword'] = $this->language->get('entry_spassword');
        $data['entry_gatewayurl'] = $this->language->get('entry_gatewayurl');
	$data['entry_test'] = $this->language->get('entry_test');
	$data['entry_total'] = $this->language->get('entry_total');
	$data['entry_order_status'] = $this->language->get('entry_order_status');
	$data['entry_geo_zone'] = $this->language->get('entry_geo_zone');
	$data['entry_status'] = $this->language->get('entry_status');
	$data['entry_sort_order'] = $this->language->get('entry_sort_order');
        
        $data['help_slogin'] = $this->language->get('help_slogin');
        $data['help_spassword'] = $this->language->get('help_spassword');
        $data['help_gatewayurl'] = $this->language->get('help_gatewayurl');
	$data['help_total'] = $this->language->get('help_total');
        
        $data['button_save'] = $this->language->get('button_save');
	$data['button_cancel'] = $this->language->get('button_cancel');
        
        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
	} else {
            $data['error_warning'] = '';
	}
        
        if (isset($this->error['slogin'])) {
            $data['error_slogin'] = $this->error['slogin'];
	} else {
            $data['error_slogin'] = '';
	}
        
        if (isset($this->error['spassword'])) {
            $data['error_spassword'] = $this->error['spassword'];
	} else {
            $data['error_spassword'] = '';
	}
        
        if (isset($this->error['gatewayurl'])) {
            $data['error_gatewayurl'] = $this->error['gatewayurl'];
	} else {
            $data['error_gatewayurl'] = '';
	}
        
        $data['breadcrumbs'] = array();
        
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'token=' . $this->session->data['token'], 'SSL')
	);
        
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_payment'),
            'href' => $this->url->link('extension/payment', 'token=' . $this->session->data['token'], 'SSL')
	);
        
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('payment/alphabank', 'token=' . $this->session->data['token'], 'SSL')
	);
        
        $data['action'] = $this->url->link('payment/alphabank', 'token=' . $this->session->data['token'], 'SSL');
        $data['cancel'] = $this->url->link('extension/payment', 'token=' . $this->session->data['token'], 'SSL');
        
        if (isset($this->request->post['alphabank_slogin'])) {
            $data['alphabank_slogin'] = $this->request->post['alphabank_slogin'];
	} else {
            $data['alphabank_slogin'] = $this->config->get('alphabank_slogin');
	}
        
        if (isset($this->request->post['alphabank_spassword'])) {
            $data['alphabank_spassword'] = $this->request->post['alphabank_spassword'];
	} else {
            $data['alphabank_spassword'] = $this->config->get('alphabank_spassword');
	}

	if (isset($this->request->post['alphabank_test'])) {
            $data['alphabank_test'] = $this->request->post['alphabank_test'];
	} else {
            $data['alphabank_test'] = $this->config->get('alphabank_test');
	}

	if (isset($this->request->post['alphabank_total'])) {
            $data['alphabank_total'] = $this->request->post['alphabank_total'];
	} else {
            $data['alphabank_total'] = $this->config->get('alphabank_total');
	}

	if (isset($this->request->post['alphabank_order_status_id'])) {
            $data['alphabank_order_status_id'] = $this->request->post['alphabank_order_status_id'];
	} else {
            $data['alphabank_order_status_id'] = $this->config->get('alphabank_order_status_id');
	}
        
        $this->load->model('localisation/order_status');
      
        $data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();
	if (isset($this->request->post['alphabank_geo_zone_id'])) {
            $data['alphabank_geo_zone_id'] = $this->request->post['alphabank_geo_zone_id'];
	} else {
            $data['alphabank_geo_zone_id'] = $this->config->get('alphabank_geo_zone_id');
	}
        
        $this->load->model('localisation/geo_zone');

	$data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();

	if (isset($this->request->post['alphabank_status'])) {
            $data['alphabank_status'] = $this->request->post['alphabank_status'];
	} else {
            $data['alphabank_status'] = $this->config->get('alphabank_status');
	}

	if (isset($this->request->post['alphabank_sort_order'])) {
            $data['alphabank_sort_order'] = $this->request->post['alphabank_sort_order'];
	} else {
            $data['alphabank_sort_order'] = $this->config->get('alphabank_sort_order');
	}

	$data['header'] = $this->load->controller('common/header');
	$data['column_left'] = $this->load->controller('common/column_left');
	$data['footer'] = $this->load->controller('common/footer');

	$this->response->setOutput($this->load->view('payment/alphabank.tpl', $data));
    }
    
    // Валидация
    protected function validate() {
        if (!$this->user->hasPermission('modify', 'payment/alphabank')) {
            $this->error['warning'] = $this->language->get('error_permission');
	}

	if (!$this->request->post['alphabank_slogin']) {
            $this->error['slogin'] = $this->language->get('error_slogin');
	}
        
        if (!$this->request->post['alphabank_spassword']) {
            $this->error['spassword'] = $this->language->get('error_spassword');
	}

	return !$this->error;
    }
}
?>