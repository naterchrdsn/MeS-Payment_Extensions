<?php
/**
* Custom Payment Gateway for OpenCart v2
* Using Payment Gateway API v4.11
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

		$this->document->setTitle($this->language->get('heading_title'));
		
		$this->load->model('setting/setting');
			
		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('mes_ach', $this->request->post);				
			
			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('extension/payment', 'token=' . $this->session->data['token'], 'SSL'));
		}

		$data['heading_title'] = $this->language->get('heading_title');
		$data['text_edit'] = $this->language->get('text_edit');
		$data['text_enabled'] = $this->language->get('text_enabled');
		$data['text_disabled'] = $this->language->get('text_disabled');
		$data['text_all_zones'] = $this->language->get('text_all_zones');
		$data['text_none'] = $this->language->get('text_none');
		$data['text_yes'] = $this->language->get('text_yes');
		$data['text_no'] = $this->language->get('text_no');
        $data['entry_cust_id'] = $this->language->get('entry_cust_id');
		$data['entry_profile_id'] = $this->language->get('entry_profile_id');
		$data['entry_profile_key'] = $this->language->get('entry_profile_key');
		$data['entry_test'] = $this->language->get('entry_test');
		$data['entry_review_status'] = $this->language->get('entry_review_status');
		$data['entry_completed_status'] = $this->language->get('entry_completed_status');	
		$data['entry_geo_zone'] = $this->language->get('entry_geo_zone');
		$data['entry_status'] = $this->language->get('entry_status');
		$data['entry_sort_order'] = $this->language->get('entry_sort_order');
		$data['entry_merch_id'] = $this->language->get('entry_merch_id');
		$data['help_ach_test'] = $this->language->get('help_test');
		$data['help_ach_merch_id'] = $this->language->get('help_merch_id');
		$data['help_ach_review'] = $this->language->get('help_review');
		$data['button_save'] = $this->language->get('button_save');
		$data['button_cancel'] = $this->language->get('button_cancel');
		$data['tab_general'] = $this->language->get('tab_general');
		$data['tab_extras'] = $this->language->get('tab_extras');

 		if (isset($this->error['ach_warning'])) {
			$data['error_ach_warning'] = $this->error['ach_warning'];
		} else {
			$data['error_ach_warning'] = '';
		}

 		if (isset($this->error['ach_profile_id'])) {
			$data['error_ach_profile_id'] = $this->error['ach_profile_id'];
		} else {
			$data['error_ach_profile_id'] = '';
		}
		
 		if (isset($this->error['ach_profile_key'])) {
			$data['error_ach_profile_key'] = $this->error['ach_profile_key'];
		} else {
			$data['error_ach_profile_key'] = '';
		}

        if (isset($this->error['ach_cust_id'])) {
            $data['error_ach_cust_id'] = $this->error['ach_cust_id'];
        } else {
            $data['error_ach_cust_id'] = '';
        }
        
		

		$data['breadcrumbs'] = array();

   		$data['breadcrumbs'][] = array(
       		'href'      => $this->url->link('common/home', 'token=' . $this->session->data['token'], 'SSL'),
       		'text'      => $this->language->get('text_home'),
      		'separator' => FALSE
   		);

   		$data['breadcrumbs'][] = array(
       		'href'      => $this->url->link('extension/payment', 'token=' . $this->session->data['token'], 'SSL'),
       		'text'      => $this->language->get('text_payment'),
      		'separator' => ' :: '
   		);

   		$data['breadcrumbs'][] = array(
       		'href'      => $this->url->link('payment/mes_ach', 'token=' . $this->session->data['token'], 'SSL'),
       		'text'      => $this->language->get('heading_title'),
      		'separator' => ' :: '
   		);
				
		$data['action'] = $this->url->link('payment/mes_ach', 'token=' . $this->session->data['token'], 'SSL');
		
		$data['cancel'] = $this->url->link('extension/payment', 'token=' . $this->session->data['token'], 'SSL');

		if (isset($this->request->post['mes_ach_profile_id'])) {
			$data['mes_ach_profile_id'] = $this->request->post['mes_ach_profile_id'];
		} else {
			$data['mes_ach_profile_id'] = $this->config->get('mes_ach_profile_id');
		}
		
		if (isset($this->request->post['mes_ach_profile_key'])) {
			$data['mes_ach_profile_key'] = $this->request->post['mes_ach_profile_key'];
		} else {
			$data['mes_ach_profile_key'] = $this->config->get('mes_ach_profile_key');
		}
		
		if (isset($this->request->post['mes_ach_test'])) {
			$data['mes_ach_test'] = $this->request->post['mes_ach_test'];
		} else {
			$data['mes_ach_test'] = $this->config->get('mes_ach_test');
		}
		
        if (isset($this->request->post['mes_ach_cust_id'])) {
            $data['mes_ach_cust_id'] = $this->request->post['mes_ach_cust_id'];
        } else {
            $data['mes_ach_cust_id'] = $this->config->get('mes_ach_cust_id');
        }

		if (isset($this->request->post['mes_ach_review_status_id'])) {
			$data['mes_ach_review_status_id'] = $this->request->post['mes_ach_review_status_id'];
		} else {
			$data['mes_ach_review_status_id'] = $this->config->get('mes_ach_review_status_id');
		}

		if (isset($this->request->post['mes_ach_completed_status_id'])) {
			$data['mes_ach_completed_status_id'] = $this->request->post['mes_ach_completed_status_id'];
		} else {
			$data['mes_ach_completed_status_id'] = $this->config->get('mes_ach_completed_status_id');
		}

		if (isset($this->request->post['mes_ach_merch_id'])) {
			$data['mes_ach_merch_id'] = $this->request->post['mes_ach_merch_id'];
		} else {
			$data['mes_ach_merch_id'] = $this->config->get('mes_ach_merch_id'); 
		} 

		$this->load->model('localisation/order_status');
		
		$data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();
		
		if (isset($this->request->post['mes_ach_geo_zone_id'])) {
			$data['mes_ach_geo_zone_id'] = $this->request->post['mes_ach_geo_zone_id'];
		} else {
			$data['mes_ach_geo_zone_id'] = $this->config->get('mes_ach_geo_zone_id'); 
		} 
		
		$this->load->model('localisation/geo_zone');
										
		$data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();
		
		if (isset($this->request->post['mes_ach_status'])) {
			$data['mes_ach_status'] = $this->request->post['mes_ach_status'];
		} else {
			$data['mes_ach_status'] = $this->config->get('mes_ach_status');
		}
		
		if (isset($this->request->post['mes_ach_sort_order'])) {
			$data['mes_ach_sort_order'] = $this->request->post['mes_ach_sort_order'];
		} else {
			$data['mes_ach_sort_order'] = $this->config->get('mes_ach_sort_order');
		}
		
		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');
		
		$this->response->setOutput($this->load->view('payment/mes_ach.tpl', $data));
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
	
		if (!$this->error) {
			return TRUE;
		} else {
			return FALSE;
		}	
	}
}
?>