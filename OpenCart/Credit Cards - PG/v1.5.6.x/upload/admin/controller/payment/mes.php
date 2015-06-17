<?php
/**
* Custom Payment Gateway for OpenCart v1.5.6.4
* Using Payment Gateway API v4.12
* For Credit Card Transactions
* Written 09/20/2014
* ©Merchant e-Solutions 2014
*
* @author nrichardson
* 
*/

class ControllerPaymentMes extends Controller {
	private $error = array(); 

	public function index() {
		$this->load->language('payment/mes');
		$this->document->setTitle = $this->language->get('heading_title');
		$this->load->model('setting/setting');
		$this->load->model('localisation/geo_zone');
		$this->load->model('localisation/order_status');
		$this->data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();
		$this->data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();
		if (($this->request->server['REQUEST_METHOD'] == 'POST') && ($this->validate())) {
			$this->load->model('setting/setting');
			$this->model_setting_setting->editSetting('mes', $this->request->post);
			$this->session->data['success'] = $this->language->get('text_success');
			$this->redirect($this->url->link('extension/payment', 'token=' . $this->session->data['token'], 'SSL'));
		}
		$this->data['heading_title'] = $this->language->get('heading_title');
		$this->data['text_enabled'] = $this->language->get('text_enabled');
		$this->data['text_disabled'] = $this->language->get('text_disabled');
		$this->data['text_all_zones'] = $this->language->get('text_all_zones');
		$this->data['text_none'] = $this->language->get('text_none');
		$this->data['text_yes'] = $this->language->get('text_yes');
		$this->data['text_no'] = $this->language->get('text_no');
		$this->data['text_authorization'] = $this->language->get('text_authorization');
		$this->data['text_fraud_settings'] = $this->language->get('text_fraud_settings');
		$this->data['text_settings'] = $this->language->get('text_settings');
		$this->data['text_sale'] = $this->language->get('text_sale');
		$this->data['entry_sort_order'] = $this->language->get('entry_sort_order');
		$this->data['entry_profile_id'] = $this->language->get('entry_profile_id');
		$this->data['entry_profile_key'] = $this->language->get('entry_profile_key');
		$this->data['entry_test'] = $this->language->get('entry_test');
		$this->data['entry_transaction'] = $this->language->get('entry_transaction');
		$this->data['entry_order_status'] = $this->language->get('entry_order_status');
		$this->data['entry_fraud_order_status'] = $this->language->get('entry_fraud_order_status');
		$this->data['entry_geo_zone'] = $this->language->get('entry_geo_zone');
		$this->data['entry_status'] = $this->language->get('entry_status');
		$this->data['entry_fraud_status'] = $this->language->get('entry_fraud_status');
        $this->data['entry_merch_id'] = $this->language->get('entry_merch_id');
		$this->data['button_save'] = $this->language->get('button_save');
		$this->data['button_cancel'] = $this->language->get('button_cancel');
		$this->data['tab_general'] = $this->language->get('tab_general');
 		if (isset($this->error['warning'])) {
			$this->data['error_warning'] = $this->error['warning'];
		} else {
			$this->data['error_warning'] = '';
		}
 		if (isset($this->error['profile_id'])) {
			$this->data['error_profile_id'] = $this->error['profile_id'];
		} else {
			$this->data['error_profile_id'] = '';
		}
 		if (isset($this->error['profile_key'])) {
			$this->data['error_profile_key'] = $this->error['profile_key'];
		} else {
			$this->data['error_profile_key'] = '';
		}
 		if (isset($this->error['mes_merch_id'])) {
			$this->data['error_merch_id'] = $this->error['mes_merch_id'];
		} else {
			$this->data['error_merch_id'] = '';
		}
		$this->data['breadcrumbs'] = array();
		$this->data['breadcrumbs'][] = array(
			'text' => 'Home',
			'href' => $this->url->link('common/home', 'token=' . $this->session->data['token'], 'SSL'),
			'separator' => false
		);
		$this->data['breadcrumbs'][] = array(
			'text' => 'Payment',
			'href' => $this->url->link('extension/payment', 'token=' . $this->session->data['token'], 'SSL'),
			'separator' => ' :: '
		);
		$this->data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('payment/mes', 'token=' . $this->session->data['token'], 'SSL'),
			'separator' => ' :: '
		);
        $this->data['action'] = $this->url->link('payment/mes&token=' . $this->session->data['token'], 'SSL');
        $this->data['cancel'] = $this->url->link('extension/payment&token=' . $this->session->data['token'], 'SSL');
		if (isset($this->request->post['mes_profile_id'])) {
			$this->data['mes_profile_id'] = $this->request->post['mes_profile_id'];
		} else {
			$this->data['mes_profile_id'] = $this->config->get('mes_profile_id');
		}
		if (isset($this->request->post['mes_profile_key'])) {
			$this->data['mes_profile_key'] = $this->request->post['mes_profile_key'];
		} else {
			$this->data['mes_profile_key'] = $this->config->get('mes_profile_key');
		}
		if (isset($this->request->post['mes_test'])) {
			$this->data['mes_test'] = $this->request->post['mes_test'];
		} else {
			$this->data['mes_test'] = $this->config->get('mes_test');
		}
		if (isset($this->request->post['mes_method'])) {
			$this->data['mes_transaction'] = $this->request->post['mes_transaction'];
		} else {
			$this->data['mes_transaction'] = $this->config->get('mes_transaction');
		}
		if (isset($this->request->post['mes_order_status_id'])) {
			$this->data['mes_order_status_id'] = $this->request->post['mes_order_status_id'];
		} else {
			$this->data['mes_order_status_id'] = $this->config->get('mes_order_status_id'); 
		}
		if (isset($this->request->post['mes_fraud_status_id'])) {
			$this->data['mes_fraud_status_id'] = $this->request->post['mes_fraud_status_id'];
		} else {
			$this->data['mes_fraud_status_id'] = $this->config->get('mes_fraud_status_id'); 
		}
		if (isset($this->request->post['mes_geo_zone_id'])) {
			$this->data['mes_geo_zone_id'] = $this->request->post['mes_geo_zone_id'];
		} else {
			$this->data['mes_geo_zone_id'] = $this->config->get('mes_geo_zone_id'); 
		}
		if (isset($this->request->post['mes_sort_order'])) {
			$this->data['mes_sort_order'] = $this->request->post['mes_sort_order'];
		} else {
			$this->data['mes_sort_order'] = $this->config->get('mes_sort_order');
		}
		if (isset($this->request->post['mes_status'])) {
			$this->data['mes_status'] = $this->request->post['mes_status'];
		} else {
			$this->data['mes_status'] = $this->config->get('mes_status');
		}
		if (isset($this->request->post['mes_fraud_status'])) {
			$this->data['mes_fraud_status'] = $this->request->post['mes_fraud_status'];
		} else {
			$this->data['mes_fraud_status'] = $this->config->get('mes_fraud_status');
		}
        if (isset($this->request->post['mes_merch_id'])) {
            $this->data['mes_merch_id'] = $this->request->post['mes_merch_id'];
        } else {
            $this->data['mes_merch_id'] = $this->config->get('mes_merch_id');
        }
		$this->template = 'payment/mes.tpl';
		$this->children = array(
			'common/header',	
			'common/footer'	
		);
		$this->response->setOutput($this->render());
	}

	private function validate() {
		if(!$this->user->hasPermission('modify', 'payment/mes')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}
		if(!$this->request->post['mes_profile_id']) {
			$this->error['profile_id'] = $this->language->get('error_profile_id');
		}
		if(!$this->request->post['mes_profile_key']) {
			$this->error['profile_key'] = $this->language->get('error_profile_key');
		}
		if(!$this->request->post['mes_profile_key']) {
			$this->error['profile_key'] = $this->language->get('error_profile_key');
		}
		if($this->request->post['mes_fraud_status'] && !$this->request->post['mes_merch_id']) {
			$this->error['mes_merch_id'] = $this->language->get('error_merch_id');
		}
		if (!$this->error) {
			return TRUE;
		} else {
			return FALSE;
		}	
	}
}
?>