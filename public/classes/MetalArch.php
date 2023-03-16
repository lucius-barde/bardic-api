<?php
class MetalArch{
	
	function __construct() {
	}

	public function elementToObj($element) {
		$obj = array( "tag" => $element->tagName );
		foreach ($element->attributes as $attribute) {
			$obj[$attribute->name] = $attribute->value;
		}
		//for text elements
		if(in_array($element->tagName, ['dd'])){
			
			foreach ($element->childNodes as $subElement) {
				if ($subElement->nodeType == XML_TEXT_NODE) {
					$obj["html"][]['html'] = $subElement->wholeText;
				}
				else {
					$obj["html"][] = $this->elementToObj($subElement);
				}
			}
			
		}else{
		//for other elements
		foreach ($element->childNodes as $subElement) {
			if ($subElement->nodeType == XML_TEXT_NODE) {
				$obj["html"] .= $subElement->wholeText;
			}
			else {
				$obj["children"][] = $this->elementToObj($subElement);
			}
		}
		}
		return $obj;
	}
	
	public function geocode($location,$country){
		
		if(!$location or !$country){ return false; }
		
		//get geocode from an external provider
		 
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, 'https://nominatim.openstreetmap.org/search.php?q='.rawurlencode($location).',%20'.rawurlencode($country).'&format=jsonv2');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']); 
		if(isset($_SERVER['HTTP_REFERER'])){
			curl_setopt($ch, CURLOPT_REFERER, $_SERVER['HTTP_REFERER']);
		}
		$output = curl_exec($ch);
		curl_close($ch);
		return json_decode($output,true); 
		
	}

	public function getBand($id,$onlyLocal = false){

		$cachedir = ABSDIR.'/cache/metalarch/'.$id;
		$cachefile = ABSDIR.'/cache/metalarch/'.$id.'/'.$id.'.txt';
		
		if(!is_dir($cachedir)){
			mkdir($cachedir);
		}

		if(!is_dir($cachedir)){
			if(!$id){return (['status'=>'error','statusCode'=>500, 'statusText'=>'Unable to create the cache folder, please check the permissions on the server.']);}
		}

			
		//check datediff
		
		if(is_file($cachefile)){
			$tmpJSON = json_decode(file_get_contents($cachefile),true);
			$fetchDateDiff = time() - $tmpJSON['edited'];
		}

		// max storage period before reloading remote source: 1 month
		if(!$onlyLocal and ($fetchDateDiff > 2630000 or !$fetchDateDiff or !is_file($cachefile))){
			
				
			//fetch data from website    
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, "https://www.metal-archives.com/band/view/id/".$id);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			$bandHTML = curl_exec($ch);
			curl_close($ch);     
			
			
			$bandJSON = $this->parseFile($bandHTML);
			if(!$bandJSON){
				return['status'=>'error','statusCode'=>204,'statusText'=>'No data available with the requested ID'];
			}
			
			//get images
			$ch2 = curl_init($bandJSON['params']['logo']);
			error_reporting(E_ALL);
			ini_set('display_errors',1);
			$fp = fopen(ABSDIR.'/cache/metalarch/'.$id.'/'.$id.'-logo.png', 'wb');
			curl_setopt($ch2, CURLOPT_FILE, $fp);
			curl_setopt($ch2, CURLOPT_HEADER, 0);
			curl_exec($ch2);
			curl_close($ch2);
			fclose($fp);
			$ch3 = curl_init($bandJSON['params']['photo']);
			$fp = fopen(ABSDIR.'/cache/metalarch/'.$id.'/'.$id.'-photo.jpg', 'wb');
			curl_setopt($ch3, CURLOPT_FILE, $fp);
			curl_setopt($ch3, CURLOPT_HEADER, 0);
			curl_exec($ch3);
			curl_close($ch3);
			fclose($fp);
						
			$bandJSON['params']['logo'] = '/cache/metalarch/'.$id.'/'.$id.'-logo.png';
			$bandJSON['params']['photo'] = '/cache/metalarch/'.$id.'/'.$id.'-photo.jpg';
			
			$bandJSON['params']['bandId'] = $id;
			$bandJSON['url'] = 'metalarch-'.$id;
			$bandJSON['params']['fetchDateDiff'] = 0;
			
			
			//geocode the city, region
			$coords = $this->geocode($bandJSON['params']['location'],$bandJSON['params']['countryOfOrigin']); 
			if($coords[0]){
				$bandJSON['params']['coordinates']['lat'] = $coords[0]['lat'];
				$bandJSON['params']['coordinates']['lng'] = $coords[0]['lon'];
			}
			
			$bandJSON['edited'] = time();
			$bandJSON['params']['copyright'] = '&copy; <a href="https://www.metal-archives.com">Metal-Archives.com</a>';
			
			ksort($bandJSON);
			ksort($bandJSON['params']);
			file_put_contents($cachefile, json_encode($bandJSON));
			
			
			
			//check if data has been written successfully in cache	
			if(is_file($cachefile)){
				
				
				$bandJSON['params']['source'] = 'remote';
				ksort($bandJSON);
				ksort($bandJSON['params']);
				return $bandJSON;
				
			}else{
				return (['status'=>'error','statusCode'=>500,'statusText'=>'Unable to write the cache file, please check the permissions on the website.','source'=>'remote']);
			}
			
			
		}else{
			//get data from cache (assuming stored in JSON)
			$bandJSON = json_decode(file_get_contents($cachefile),true);
			$bandJSON['params']['source'] = 'local';
			$bandJSON['params']['fetchDateDiff'] = $fetchDateDiff;
			ksort($bandJSON);
			ksort($bandJSON['params']);
			if(!$bandJSON['params']['bandId']){
				return ['status'=>'error','statusCode'=>204,'statusText'=>'no_data'];
			}
			return $bandJSON;
		}	
	}
	
	
	public function implodeHTML($obj){
		foreach($obj as $element){
			$txt .= $element['html'];
		}
		return $txt;
	}
	
	
	public function parseFile($bandHTML){
		
		// BEGIN parse html
			
		
			$domHandler = new DOMDocument();
			$domHandler->loadHTML($bandHTML);
			
	
			// #ID of band_info tag
			$bandInfo = $this->elementToObj($domHandler->getElementById('band_info'));
			if($bandInfo['children'][0]['class'] == "band_name"){
				//$bandJSON['bandName'] = $bandInfo['children'][0]['children'][0]['html'];
				$bandJSON['name'] = $bandInfo['children'][0]['children'][0]['html'];
			}
			
			
			// #ID of band_stats tag
			$bandStats = $this->elementToObj($domHandler->getElementById('band_stats'));
			if($bandStats['children'][0]['tag'] == "dl"){
				
				$bandStatsLeft = $bandStats['children'][0]['children'];
				foreach($bandStatsLeft as $key => $stat){
					
					//country of origin
					if($bandStatsLeft[$key-1]['html'] == 'Country of origin:'){
						$bandJSON['params']['countryOfOrigin'] = $this->implodeHTML($stat['html']); 
					}
					//location
					if($bandStatsLeft[$key-1]['html'] == 'Location:'){
						$locationArr = $this->implodeHTML($stat['html']); 
						$locationArr = str_replace(['(early)','(mid)','(later)',' / '],['','','',';'],$locationArr); //alows exploxding on both / and ;
						$locationArr = explode(';',$locationArr);
						foreach($locationArr as $locationKey => $aLocation){
							$locationArr[$locationKey] = str_replace('N/A','',$aLocation);
						}
						$bandJSON['params']['location'] = trim($locationArr[0]);
						$bandJSON['params']['location2'] = trim($locationArr[1]);
						$bandJSON['params']['location3'] = trim($locationArr[2]);
					}
					//status
					if($bandStatsLeft[$key-1]['html'] == 'Status:'){
						$bandJSON['params']['status'] = $this->implodeHTML($stat['html']); 
					}
					//formed in
					if($bandStatsLeft[$key-1]['html'] == 'Formed in:'){
						$bandJSON['params']['formedIn'] = $this->implodeHTML($stat['html']); 
					}
					
				}
				
				
				$bandStatsRight = $bandStats['children'][1]['children'];
				foreach($bandStatsRight as $key => $stat){
				
					//genre
					if($bandStatsRight[$key-1]['html'] == 'Genre:'){
						$bandJSON['params']['genre'] = $this->implodeHTML($stat['html']); 
					}
					//lyricalThemes
					if($bandStatsRight[$key-1]['html'] == 'Lyrical themes:'){
						$bandJSON['params']['lyricalThemes'] = $this->implodeHTML($stat['html']); 
					}
					//lastLabel
					if($bandStatsRight[$key-1]['html'] == 'Last label:'){
						$bandJSON['params']['lastLabel'] = $this->implodeHTML($stat['html']); 
					}
					//currentLabel
					if($bandStatsRight[$key-1]['html'] == 'Current label:'){
						$bandJSON['params']['currentLabel'] = $this->implodeHTML($stat['html']); 
					}
					
				}
				
				$bandStatsLower = $bandStats['children'][2]['children'];
				foreach($bandStatsLower as $key => $stat){
					//yearsActive
					if($bandStatsLower[$key-1]['html'] == 'Years active:'){
						$bandJSON['params']['yearsActive'] = $this->implodeHTML($stat['html']); 
					}
				}
				
			}
			
			// #ID of logo tag
			$bandLogo = $this->elementToObj($domHandler->getElementById('logo'));
			if(!!$bandLogo['href']){
				$bandJSON['params']['logo'] = $bandLogo['href'];
			}
			
			// #ID of photo tag
			$bandPhoto = $this->elementToObj($domHandler->getElementById('photo'));
			if(!!$bandPhoto['href']){
				$bandJSON['params']['photo'] = $bandPhoto['href'];
			}

            //Unused mandatory OPCMS Fields
            $bandJSON['parent'] = 0;
            $bandJSON['status'] = 1;
            $bandJSON['lang'] = "en";
			
	
		// END parse html
		return $bandJSON;
	}
		
	
}
