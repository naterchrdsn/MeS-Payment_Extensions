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

class ModelPaymentMesach extends Model {
  public function getMethod($address) {
	 $this->load->language('payment/mes_ach');
	 if($this->config->get('mes_ach_status')) {
    $query = $this->db->query("SELECT * FROM " . DB_PREFIX . "zone_to_geo_zone WHERE geo_zone_id = '" . (int)$this->config->get('mes_ach_geo_zone_id') . "' AND country_id = '" . (int)$address['country_id'] . "' AND (zone_id = '" . (int)$address['zone_id'] . "' OR zone_id = '0')");
    if(!$this->config->get('mes_ach_geo_zone_id')) {
      $status = TRUE;
    } elseif($query->num_rows) {
      $status = TRUE;
    } else {
     	$status = FALSE;
     }	
   } else {
			$status = FALSE;
	 }
	 $method_data = array();
	 if($status) {  
    $method_data = array( 
      'code'         => 'mes_ach',
      'title'        => $this->language->get('text_title'),
      'sort_order'   => $this->config->get('ach_sort_order')
    );
   }
   return $method_data;
   }
}
?>