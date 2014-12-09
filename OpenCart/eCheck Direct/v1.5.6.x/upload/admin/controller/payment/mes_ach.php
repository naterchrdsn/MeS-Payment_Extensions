<?php
/**
* Custom Payment Gateway for OpenCart v1.5.6.4
* Using Payment Gateway API v4.12
* For ACH Transactions
* Written 09/20/2014
* ©Merchant e-Solutions 2014
*
* @author nrichardson
* 
*/

class ControllerPaymentMesach extends Controller {
    private $error = array(); 

    public function index() {
        $this->load->language('payment/mes_ach');
        $this->document->setTitle = $this->language->get('heading_title');
        $this->load->model('setting/setting');
        $this->load->model('localisation/geo_zone');
        $this->load->model('localisation/order_status');
        $this->data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();
        $this->data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();
        if (($this->request->server['REQUEST_METHOD'] == 'POST') && ($this->validate())) {
            $this->load->model('setting/setting');
            $this->model_setting_setting->editSetting('mes_ach', $this->request->post);
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
        $this->data['entry_sort_order'] = $this->language->get('entry_sort_order');
        $this->data['entry_profile_id'] = $this->language->get('entry_profile_id');
        $this->data['entry_profile_key'] = $this->language->get('entry_profile_key');
        $this->data['text_fraud_settings'] = $this->language->get('text_fraud_settings');
        $this->data['text_settings'] = $this->language->get('text_settings');
        $this->data['entry_merch_id'] = $this->language->get('entry_merch_id');
        $this->data['entry_cust_id'] = $this->language->get('entry_cust_id');
        $this->data['entry_test'] = $this->language->get('entry_test');
        $this->data['entry_order_status'] = $this->language->get('entry_order_status');
		$this->data['entry_fraud_order_status'] = $this->language->get('entry_fraud_order_status');    
        $this->data['entry_geo_zone'] = $this->language->get('entry_geo_zone');
        $this->data['entry_status'] = $this->language->get('entry_status');
        $this->data['entry_fraud_status'] = $this->language->get('entry_fraud_status');
        $this->data['button_save'] = $this->language->get('button_save');
        $this->data['button_cancel'] = $this->language->get('button_cancel');
        $this->data['tab_general'] = $this->language->get('tab_general');
        if (isset($this->error['ach_warning'])) {
            $this->data['error_ach_warning'] = $this->error['ach_warning'];
        } else {
            $this->data['error_ach_warning'] = '';
        }
        if (isset($this->error['ach_profile_id'])) {
            $this->data['error_ach_profile_id'] = $this->error['ach_profile_id'];
        } else {
            $this->data['error_ach_profile_id'] = '';
        }
        if (isset($this->error['ach_profile_key'])) {
            $this->data['error_ach_profile_key'] = $this->error['ach_profile_key'];
        } else {
            $this->data['error_ach_profile_key'] = '';
        }
        if (isset($this->error['ach_cust_id'])) {
            $this->data['error_ach_cust_id'] = $this->error['ach_cust_id'];
        } else {
            $this->data['error_ach_cust_id'] = '';
        }
        if (isset($this->error['mes_ach_merch_id'])) {
            $this->data['error_ach_merch_id'] = $this->error['mes_ach_merch_id'];
        } else {
            $this->data['error_ach_merch_id'] = '';
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
            'href' => $this->url->link('payment/mes_ach', 'token=' . $this->session->data['token'], 'SSL'),
            'separator' => ' :: '
        );
        $this->data['action'] = $this->url->link('payment/mes_ach&token=' . $this->session->data['token'], 'SSL');
        $this->data['cancel'] = $this->url->link('extension/payment&token=' . $this->session->data['token'], 'SSL');
        if (isset($this->request->post['mes_ach_profile_id'])) {
            $this->data['mes_ach_profile_id'] = $this->request->post['mes_ach_profile_id'];
        } else {
            $this->data['mes_ach_profile_id'] = $this->config->get('mes_ach_profile_id');
        }
        if (isset($this->request->post['mes_ach_profile_key'])) {
            $this->data['mes_ach_profile_key'] = $this->request->post['mes_ach_profile_key'];
        } else {
            $this->data['mes_ach_profile_key'] = $this->config->get('mes_ach_profile_key');
        }
        if (isset($this->request->post['mes_ach_merch_id'])) {
            $this->data['mes_ach_merch_id'] = $this->request->post['mes_ach_merch_id'];
        } else {
            $this->data['mes_ach_merch_id'] = $this->config->get('mes_ach_merch_id');
        }
        if (isset($this->request->post['mes_ach_cust_id'])) {
            $this->data['mes_ach_cust_id'] = $this->request->post['mes_ach_cust_id'];
        } else {
            $this->data['mes_ach_cust_id'] = $this->config->get('mes_ach_cust_id');
        }
        if (isset($this->request->post['mes_ach_test'])) {
            $this->data['mes_ach_test'] = $this->request->post['mes_ach_test'];
        } else {
            $this->data['mes_ach_test'] = $this->config->get('mes_ach_test');
        }
        if (isset($this->request->post['mes_ach_order_status_id'])) {
            $this->data['mes_ach_order_status_id'] = $this->request->post['mes_ach_order_status_id'];
        } else {
            $this->data['mes_ach_order_status_id'] = $this->config->get('mes_ach_order_status_id'); 
        }
		if (isset($this->request->post['mes_ach_fraud_status_id'])) {
			$this->data['mes_ach_fraud_status_id'] = $this->request->post['mes_ach_fraud_status_id'];
		} else {
			$this->data['mes_ach_fraud_status_id'] = $this->config->get('mes_ach_fraud_status_id'); 
		}
        if (isset($this->request->post['mes_ach_geo_zone_id'])) {
            $this->data['mes_ach_geo_zone_id'] = $this->request->post['mes_ach_geo_zone_id'];
        } else {
            $this->data['mes_ach_geo_zone_id'] = $this->config->get('mes_ach_geo_zone_id'); 
        }
        if (isset($this->request->post['ach_sort_order'])) {
            $this->data['ach_sort_order'] = $this->request->post['ach_sort_order'];
        } else {
            $this->data['ach_sort_order'] = $this->config->get('ach_sort_order');
        }
        if (isset($this->request->post['mes_ach_status'])) {
            $this->data['mes_ach_status'] = $this->request->post['mes_ach_status'];
        } else {
            $this->data['mes_ach_status'] = $this->config->get('mes_ach_status');
        }
        if (isset($this->request->post['mes_ach_fraud_status'])) {
            $this->data['mes_ach_fraud_status'] = $this->request->post['mes_ach_fraud_status'];
        } else {
            $this->data['mes_ach_fraud_status'] = $this->config->get('mes_ach_fraud_status');
        }
        $this->template = 'payment/mes_ach.tpl';
        $this->children = array(
            'common/header',    
            'common/footer' 
        );
        $this->response->setOutput($this->render());
    }

    private function validate() {
        if (!$this->user->hasPermission('modify', 'payment/mes_ach')) {
            $this->error['ach_warning'] = $this->language->get('error_ach_permission');
        }
        if (!$this->request->post['mes_ach_profile_id']) {
            $this->error['ach_profile_id'] = $this->language->get('error_ach_profile_id');
        }
        if (!$this->request->post['mes_ach_profile_key']) {
            $this->error['ach_profile_key'] = $this->language->get('error_ach_profile_key');
        }
        if (!$this->request->post['mes_ach_cust_id']) {
            $this->error['ach_cust_id'] = $this->language->get('error_ach_cust_id');
        }
        if($this->request->post['mes_ach_fraud_status'] && !$this->request->post['mes_ach_merch_id']) {
            $this->error['mes_ach_merch_id'] = $this->language->get('error_merch_id');
        }
        if (!$this->error) {
            return TRUE;
        } else {
            return FALSE;
        }   
    }
}
?>