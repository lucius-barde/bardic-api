<?php
class TopioSearch{
	
	function __construct() {
	}

	function getInnerHTML(DOMNode $element) {
		$innerHTML = ""; 
		$children  = $element->childNodes;

		foreach ($children as $child) 
		{ 
			$innerHTML .= $element->ownerDocument->saveHTML($child);
		}

		return $innerHTML; 
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
	
	
	
	public function implodeHTML($obj){
		foreach($obj as $element){
			$txt .= $element['html'];
		}
		return $txt;
	}
	
	
	
	public function parseFileHSuter($html,$term){
		
		// BEGIN parse html
			

			$validate = new Validator($this->db);
		
			$domHandler = new DOMDocument();
			$domHandler->loadHTML($html);
			$xpath = new DomXPath($domHandler);

            //transform the term into the name attribute (as close as it is done on HSuter)
            $term = str_replace('%20', ' ', $term);
            $term = html_entity_decode($term);
            $term = strtolower($term);
			
			$elements = $xpath->query('//a[contains(@name, "'.$term.'")]');
			

            foreach ($elements as $key => $a) { //class domNode ?
				$dt = $a->parentNode;
				$dd = $dt->nextSibling->nextSibling;
				
				$output['params']['key'] = $key;
				$output['params']['term'] = ucfirst($this->getInnerHTML($a));
				$meta = strip_tags($this->getInnerHTML($dt));
				$meta = explode('[',$meta);
				$output['params']['meta']['alt'] = explode(', ',str_replace("\n\t"," ",trim($meta[0])));
				$output['params']['meta']['type'] = str_replace(']','',$meta[1]);
				
				$output['params']['definition'] = $this->getInnerHTML($dd);
				$output['params']['definition'] = str_replace(['<t>','</t>'],['<mark>','</mark>'],$output['params']['definition']);
				$output['params']['definition'] = str_replace("\n\t"," ", $output['params']['definition']);
                $output['params']['definition'] = preg_replace('/(<a href=\\".+\\">)(.+)(<\/a>)/i','${2}',$output['params']['definition']);
				break;
			}
			return $output;
		// END parse html
		
	}
	
	
	public function parseFileTopio($html,$term){
		
		if(!$html){
			return ['err'=>'empty_html'];
		}

		// BEGIN parse html
			
			$validate = new Validator($this->db);
		
			// get topio data
			$domHandler = new DOMDocument();
			$domHandler->loadHTML($html);
			$xpath = new DomXPath($domHandler); 


			$elements = $xpath->query('//div[@id="contenu"]//table//td');
			if(!$elements->length or $elements->length == 0){
				return ['status'=>'error','statusText'=>'Error: unable to get data from Topio.ch'];
			}
			// populate array
			$table = []; //will be the array format
			$tmpElement = []; //will be the XPath transmitted (to get the html with children nodes)

			foreach($elements as $i=> $el){
				//$tmpInnerHTML =  $this->getInnerHTML($el); //TODO: innerHTML should be fully taken, use this as a base.
				$table[$i] = $this->elementToObj($el); 
				$tmpElement[$i] = $el;
			} 
			
			// search term in array


			foreach($table as $key => $td){
				if($td['html'] == $term){
					$output['params']['key'] = $key;
					$output['params']['term'] = $td['html'];
					$output['params']['meta'] = ['alt'=>null,'type'=>null];
                    if(!!$tmpElement[$key+1]){
                        $output['params']['definition'] = $this->getInnerHTML($tmpElement[$key+1]);
                    }else{
                        $output['params']['definition'] = '(Une erreur s\'est produite en essayant de récupérer la définition sur le site Topio.)';
                    }
					
				}
			}
			
			return $output;
			
		// END parse html
	}
}
