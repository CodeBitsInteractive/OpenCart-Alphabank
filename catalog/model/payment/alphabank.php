<?php
class ModelPaymentAlphabank extends Model {
	public function getMethod($address, $total) {
		$this->load->language('payment/alphabank');

		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "zone_to_geo_zone WHERE geo_zone_id = '" . (int)$this->config->get('twocheckout_geo_zone_id') . "' AND country_id = '" . (int)$address['country_id'] . "' AND (zone_id = '" . (int)$address['zone_id'] . "' OR zone_id = '0')");

		if ($this->config->get('alphabank_total') > 0 && $this->config->get('alphabank_total') > $total) {
			$status = false;
		} elseif (!$this->config->get('alphabank_geo_zone_id')) {
			$status = true;
		} elseif ($query->num_rows) {
			$status = true;
		} else {
			$status = false;
		}

		$method_data = array();

		if ($status) {
			$method_data = array(
				'code'       => 'alphabank',
				'title'      => $this->language->get('text_title'),
				'terms'      => '',
				'sort_order' => $this->config->get('alphabank_sort_order')
			);
		}

		return $method_data;
	}
        
        public function gateway($gateway, $method, $data){
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL=>$gateway.$method,
                CURLOPT_RETURNTRANSFER=>true,
                CURLOPT_POST=>true,
                CURLOPT_POSTFIELDS=>http_build_query($data)
            ));
            
            $responce = curl_exec($curl);
            $responce = json_decode($responce, true);
            curl_close($curl);
            return $responce;
        }
}