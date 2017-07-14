<?php
/*
Plugin Name: EP Images Base64 encode
Plugin URI: http://earthpeople.se
Description: Will base64 encode all images inside a post content. Useful for offline caching in html5 / webviews.
Author: Peder Fjällström
Version: 0.1.0
Author URI: http://earthpeople.se
*/

class ep_base64_encode_images{
	
	function parse($content = ''){
		return preg_replace_callback(
			'|<img(.*)src=["\'](.*?)["\'](.*)/>|i',
			create_function(
				'$matches',
				'return "<img$matches[1]src=\'".(ep_base64_encode_images::fetchurl($matches[2]))."\'$matches[3]/>";'
			),
			$content
		);
	}
	
	public function fetchurl($url = null, $ttl = 86400){
		if($url){
			$option_name = 'ep_base64_encode_images_'.md5($url);
			$data = get_option($option_name);
			if(isset($data['cached_at']) && (time() - $data['cached_at'] <= $ttl)){
				# serve cache
			}else{
				$ch = curl_init();
				$options = array(
					CURLOPT_URL => $url,
					CURLOPT_RETURNTRANSFER => true,
					CURLOPT_CONNECTTIMEOUT => 10,
					CURLOPT_TIMEOUT => 10
				);
				curl_setopt_array($ch, $options);
				$data['chunk'] = @base64_encode(curl_exec($ch));
				$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
				$data['mime'] = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
				curl_close($ch);
				if($http_code === 200){
					$data['cached_at'] = time();
					update_option($option_name, $data);
				}
			}
		}

		return 'data:'.$data['mime'].';base64,'.$data['chunk'];
	}

}

add_filter('the_content', array('ep_base64_encode_images','parse'));
