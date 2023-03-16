<?php 



//Model Validator and Formatter

class Validator{

	function __construct($db) {
		$this->db = $db;
  	}
  	
    //Save a DateTime
	function asDateTime($s){
		$d = date('Y-m-d H:i:s', strtotime($s));
		if($s == $d) return $d;
		return false;
  	}
        
	//Show a Date
	function showDate($d,$lang){
		switch($lang):
			case 'fr': return date('d.m.Y', strtotime($d)); break;
			default: return date('Y-m-d', strtotime($d)); break;
		endswitch;
  	}
        
	//Show a DateTime
	function showDateTime($d,$lang){
		switch($lang):
			case 'fr': return date('d.m.Y H:i', strtotime($d)); break;
			default: return date('Y-m-d H:i', strtotime($d)); break;
		endswitch;
  	}
        
	//Show a better human readable DateTime
	function showHumanDateTime($d,$lang){
		switch($lang):
			case 'fr': return date('j M, H:i', strtotime($d)); break;
			default: return date('M jS, H:i', strtotime($d)); break;
		endswitch;
  	}
  	

	function showImage($relsrc,$blobID = []){

		
		
		$blobModel = new Blob($this->db);
		$src = $relsrc;
		$blob = $blobModel->getBlob($blobID);
				
		if($src){
			$src = ABSPATH.'/uploads/'.$src;
			
			 
			$img = ['','',''];
				
				if($blob['params']['link']){
					$img[0] = '<a href="'.$blob['params']['link'].'" '.$blobParamsTarget.'>';
					$img[2] = '</a>';
				}elseif($blob['params']['link_self']){
					$img[0] = '<a href="'.$src.'" '.$blobParamsTarget.'>';
					$img[2] = '</a>';
				}
				
				$img[1] = '<img id="image-'.$blob['id'].'" class="'.$blob['params']['class'].'" src="'.$src.'" alt="'.$blob['content'].'" title="'.$blob['name'].'" />';
			
			return implode($img);
		}
		return false;
		
	}
	
	function showResponsiveImage($relsrc, $args = []){
		
	
		$src = $relsrc;
		
		if($src){
			$relsrc = ABSPATH.'/uploads/'.$src; //used for link_self
			$abssrc = ABSDIR.'/uploads/'.$src;
			if(is_file($abssrc)){
				$tWidth = $args['width'] ? $args['width'] : 640;
				$tHeight = $args['height'] ? $args['height'] : 480;
				$quality = $args['quality'] ? $args['quality'] : 80;
				$classes = $args['classes'];
				$pathinfo = pathinfo($abssrc);
				$thumbAbsSrc = $pathinfo['dirname'].'/'.$pathinfo['filename'].'-'.$tWidth.'x'.$tHeight.'.'.$pathinfo['extension'];
				
				if(!is_file($thumbAbsSrc)){
					$mediaModel = new Media($this->db);
					$thumbAbsSrc = $mediaModel->generateThumbnail($abssrc, $tWidth, $tHeight, $quality);
				}
				
				$thumbSrc = str_replace(ABSDIR, ABSPATH, $thumbAbsSrc);
				
				
				$img = ['','',''];
				
				
				$img[1] = '<img class="'.$classes.'" src="'.$thumbSrc.'" alt="'.$src.'" />';
			}
			 
			
			return implode($img);
		}
		return false;
		
	}
	

	
	function showThumbnail($id,$w,$h,$args = []){
		
		$blobModel = new Blob($this->db);
		$showTitle = $args['showTitle'] != "in_link" ? $args['showTitle'] : false;
		$showDescription = !!$args['showDescription'];
		$enableLinkImg = !!$args['enableLinkImg'];
		$noLink = !!$args['noLink'];
		
		$sql = $this->db->query('SELECT id, name, url, content, value FROM '.TBL.' WHERE id = "'.$id.'" AND type = "media" LIMIT 1;');
		$row = $sql->fetch();
		$urlParts = explode('/',str_replace('//','/',$row['url']));
		$urlParts[sizeof($urlParts)-1] = '_thumb/'.$w.'x'.$h.'/'.$urlParts[sizeof($urlParts)-1];
		$thumbUrl = implode('/',$urlParts);
		
		if(!is_file(ABSDIR.$thumbUrl)){
			$adminModel = new Admin($this->db);
			$adminModel->generateThumbnail(ABSDIR.$row['url'],$w,$h);
		}
		
		if($row){	
			
			$img = '';
			$title = $this->parseMd($row['name'],false);
			$showTitleInLink = $args['showTitle'] == "in_link" ? 'title="'.$title.'" ' : '';
			
			
			if(!!$enableLinkImg):
			$img = '<a '.$showTitleInLink.'class="link-img '.$args['class'].'" href="'. (!empty($row['content']) ? ABSPATH.str_replace('//','/',$blobModel->getURL($row['content'], $lang)) : ABSPATH.str_replace('//','/',$row['url'])).'">';
			elseif(!$noLink):
			$img = '<a '.$showTitleInLink.'class="link-img '.$args['class'].'" href="'. ABSPATH.str_replace('//','/',$row['url']).'">';
			else:
			$img = '';
			endif;
			
				$img .= '<img class="media-thumb-'.$id.'" data-displaymode="'.$row['value'].'" src="'.ABSPATH.$thumbUrl.'" alt="'.$row['name'].'"/>';
				if(in_array($showTitle,['h1','h2','h3','h4','h5','h6'])):
					$img .= '<'.$showTitle.' class="show-image-title">'.$title.'</'.$showTitle.'>';
				endif;
				if($showDescription):
					// not implemented yet
				endif;
				
			if(!$noLink):
				$img .= '</a>';
			else:
				$img .= '';
			endif;
			
			return $img;
		}
		return false;
	}


	function asEmail($s){
		 //supports TLD's up to 10 characters. Doesn't support special characters in domain names.
		 $r = '/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,10})$/'; 
		 return (preg_match($r, $s) ? $s : false);
	}
	
	function asLink($s){
		$r = '/^(?:(?:https?|ftp)://)(?:\S+(?::\S*)?@|\d{1,3}(?:\.\d{1,3}){3}|(?:(?:[a-z\d\x{00a1}-\x{ffff}]+-?)*[a-z\d\x{00a1}-\x{ffff}]+)(?:\.(?:[a-z\d\x{00a1}-\x{ffff}]+-?)*[a-z\d\x{00a1}-\x{ffff}]+)*(?:\.[a-z\x{00a1}-\x{ffff}]{2,6}))(?::\d+)?(?:[^\s]*)?$/iu';
		 return (preg_match($r, $s) ? $s : false);
	}
	
	function asJson($s){
		if(!$s) return '';
		return !!json_decode($s) ? $s : false;
	}

	//Save a Text parameter
	function asParam($s){
		$s = trim($s);
		$s = strip_tags($s);
		$s = htmlspecialchars($s);
		$s = htmlentities($s, ENT_QUOTES);
		$s = str_replace('&amp;quot;','&quot;',$s);
		return $s;
	}

	//Save a Long Text
	function asString($s){
		$s = trim($s);
		$s = htmlentities($s, ENT_QUOTES);
		return $s;
	}
	
	function asStatus($n){
		return (in_array($n,[-1,0,1]) ? $n : false);
	}
		
	//Validates as a URL rewriter
	function asURL($s){  
			if(!$s) return false;
			
			
			//forbidden framework URLs
			$adminModel = new Admin($this->db);
			$checkRoutes = $adminModel->getAllRoutes();
			if(in_array('/'.$s.'[/] GET', $checkRoutes)){
				return false;
			}
			
			//sanitize
			$orig = $s;
			$s = trim($s);
			$s = strip_tags($s);
			$s = htmlspecialchars($s);
			$s = str_replace(["'",'"'],['',''],$s);
			$s = rawurlencode($s);
			
			//TODO: test if url already exists 1) in routes 2) in database
			
			if($s == $orig) return $s;
			return false;
	}
	
	function toFileName($string,$keepAccents = false){
		$string = str_replace(' ', '_', $string); // Replaces all spaces with underscores.
		if($keepAccents == false){
			$string = $this->replaceAccents($string);
			return preg_replace('/[^A-Za-z0-9\-_.]/', '', $string); // Removes special chars.
		}else{
			return str_replace(['#','%','&','{','}','\\','$','!',"'",'"',':','@','<','>','*','?','/','+','`','|','='],'',$string); // Removes special chars.
		}
	}


	private function replaceAccents($s){
		
		$chars = array(
			chr(195).chr(128) => 'A', chr(195).chr(129) => 'A',
			chr(195).chr(130) => 'A', chr(195).chr(131) => 'A',
			chr(195).chr(132) => 'A', chr(195).chr(133) => 'A',
			chr(195).chr(135) => 'C', chr(195).chr(136) => 'E',
			chr(195).chr(137) => 'E', chr(195).chr(138) => 'E',
			chr(195).chr(139) => 'E', chr(195).chr(140) => 'I',
			chr(195).chr(141) => 'I', chr(195).chr(142) => 'I',
			chr(195).chr(143) => 'I', chr(195).chr(145) => 'N',
			chr(195).chr(146) => 'O', chr(195).chr(147) => 'O',
			chr(195).chr(148) => 'O', chr(195).chr(149) => 'O',
			chr(195).chr(150) => 'O', chr(195).chr(153) => 'U',
			chr(195).chr(154) => 'U', chr(195).chr(155) => 'U',
			chr(195).chr(156) => 'U', chr(195).chr(157) => 'Y',
			chr(195).chr(159) => 'ss', chr(195).chr(160) => 'a',
			chr(195).chr(161) => 'a', chr(195).chr(162) => 'a',
			chr(195).chr(163) => 'a', chr(195).chr(164) => 'a',
			chr(195).chr(165) => 'a', chr(195).chr(167) => 'c',
			chr(195).chr(168) => 'e', chr(195).chr(169) => 'e',
			chr(195).chr(170) => 'e', chr(195).chr(171) => 'e',
			chr(195).chr(172) => 'i', chr(195).chr(173) => 'i',
			chr(195).chr(174) => 'i', chr(195).chr(175) => 'i',
			chr(195).chr(177) => 'n', chr(195).chr(178) => 'o',
			chr(195).chr(179) => 'o', chr(195).chr(180) => 'o',
			chr(195).chr(181) => 'o', chr(195).chr(182) => 'o',
			chr(195).chr(182) => 'o', chr(195).chr(185) => 'u',
			chr(195).chr(186) => 'u', chr(195).chr(187) => 'u',
			chr(195).chr(188) => 'u', chr(195).chr(189) => 'y',
			chr(195).chr(191) => 'y',
			// Decompositions for Latin Extended-A
			chr(196).chr(128) => 'A', chr(196).chr(129) => 'a',
			chr(196).chr(130) => 'A', chr(196).chr(131) => 'a',
			chr(196).chr(132) => 'A', chr(196).chr(133) => 'a',
			chr(196).chr(134) => 'C', chr(196).chr(135) => 'c',
			chr(196).chr(136) => 'C', chr(196).chr(137) => 'c',
			chr(196).chr(138) => 'C', chr(196).chr(139) => 'c',
			chr(196).chr(140) => 'C', chr(196).chr(141) => 'c',
			chr(196).chr(142) => 'D', chr(196).chr(143) => 'd',
			chr(196).chr(144) => 'D', chr(196).chr(145) => 'd',
			chr(196).chr(146) => 'E', chr(196).chr(147) => 'e',
			chr(196).chr(148) => 'E', chr(196).chr(149) => 'e',
			chr(196).chr(150) => 'E', chr(196).chr(151) => 'e',
			chr(196).chr(152) => 'E', chr(196).chr(153) => 'e',
			chr(196).chr(154) => 'E', chr(196).chr(155) => 'e',
			chr(196).chr(156) => 'G', chr(196).chr(157) => 'g',
			chr(196).chr(158) => 'G', chr(196).chr(159) => 'g',
			chr(196).chr(160) => 'G', chr(196).chr(161) => 'g',
			chr(196).chr(162) => 'G', chr(196).chr(163) => 'g',
			chr(196).chr(164) => 'H', chr(196).chr(165) => 'h',
			chr(196).chr(166) => 'H', chr(196).chr(167) => 'h',
			chr(196).chr(168) => 'I', chr(196).chr(169) => 'i',
			chr(196).chr(170) => 'I', chr(196).chr(171) => 'i',
			chr(196).chr(172) => 'I', chr(196).chr(173) => 'i',
			chr(196).chr(174) => 'I', chr(196).chr(175) => 'i',
			chr(196).chr(176) => 'I', chr(196).chr(177) => 'i',
			chr(196).chr(178) => 'IJ',chr(196).chr(179) => 'ij',
			chr(196).chr(180) => 'J', chr(196).chr(181) => 'j',
			chr(196).chr(182) => 'K', chr(196).chr(183) => 'k',
			chr(196).chr(184) => 'k', chr(196).chr(185) => 'L',
			chr(196).chr(186) => 'l', chr(196).chr(187) => 'L',
			chr(196).chr(188) => 'l', chr(196).chr(189) => 'L',
			chr(196).chr(190) => 'l', chr(196).chr(191) => 'L',
			chr(197).chr(128) => 'l', chr(197).chr(129) => 'L',
			chr(197).chr(130) => 'l', chr(197).chr(131) => 'N',
			chr(197).chr(132) => 'n', chr(197).chr(133) => 'N',
			chr(197).chr(134) => 'n', chr(197).chr(135) => 'N',
			chr(197).chr(136) => 'n', chr(197).chr(137) => 'N',
			chr(197).chr(138) => 'n', chr(197).chr(139) => 'N',
			chr(197).chr(140) => 'O', chr(197).chr(141) => 'o',
			chr(197).chr(142) => 'O', chr(197).chr(143) => 'o',
			chr(197).chr(144) => 'O', chr(197).chr(145) => 'o',
			chr(197).chr(146) => 'OE',chr(197).chr(147) => 'oe',
			chr(197).chr(148) => 'R',chr(197).chr(149) => 'r',
			chr(197).chr(150) => 'R',chr(197).chr(151) => 'r',
			chr(197).chr(152) => 'R',chr(197).chr(153) => 'r',
			chr(197).chr(154) => 'S',chr(197).chr(155) => 's',
			chr(197).chr(156) => 'S',chr(197).chr(157) => 's',
			chr(197).chr(158) => 'S',chr(197).chr(159) => 's',
			chr(197).chr(160) => 'S', chr(197).chr(161) => 's',
			chr(197).chr(162) => 'T', chr(197).chr(163) => 't',
			chr(197).chr(164) => 'T', chr(197).chr(165) => 't',
			chr(197).chr(166) => 'T', chr(197).chr(167) => 't',
			chr(197).chr(168) => 'U', chr(197).chr(169) => 'u',
			chr(197).chr(170) => 'U', chr(197).chr(171) => 'u',
			chr(197).chr(172) => 'U', chr(197).chr(173) => 'u',
			chr(197).chr(174) => 'U', chr(197).chr(175) => 'u',
			chr(197).chr(176) => 'U', chr(197).chr(177) => 'u',
			chr(197).chr(178) => 'U', chr(197).chr(179) => 'u',
			chr(197).chr(180) => 'W', chr(197).chr(181) => 'w',
			chr(197).chr(182) => 'Y', chr(197).chr(183) => 'y',
			chr(197).chr(184) => 'Y', chr(197).chr(185) => 'Z',
			chr(197).chr(186) => 'z', chr(197).chr(187) => 'Z',
			chr(197).chr(188) => 'z', chr(197).chr(189) => 'Z',
			chr(197).chr(190) => 'z', chr(197).chr(191) => 's',
			//Special characters
			'&'=>'n','/'=>'_',"'"=>'_'
			);
			
			return preg_replace(array('/[^a-zA-Z0-9 -_]/','/[ -]+/','/^-|-$/'),array('','-',''),strtr($s,$chars));
			
	}
  	
  	// Parse custom forms
	function parseForm($html){
		
		//{input name Label} -> text field
		$html = preg_replace(
			'{input ([a-zA-Z\-]+) "([^"]+)"}',
			'<label for="formData[$1]">$2</label>'.
			'<input name="formData[$1]" type="text" class="form-control required {{ errors ? "error" : "" }}" />',
		$html);

		//{email name Label} -> email field
		$html = preg_replace(
			'{email ([a-zA-Z\-]+) "([^"]+)"}',
			'<label for="formData[$1]">$2</label>'.
			'<input name="formData[$1]" type="email" class="form-control required {{ errors ? "error" : "" }}" />',
		$html);

		//{text name Label} or {textarea...} -> textarea
		$html = preg_replace(
			'{text(?:area)? ([a-zA-Z\-]+) "([^"]+)"}',
			'<label for="formData[$1]">$2</label>'.
			'<textarea name="formData[$1]" type="email" class="form-control required {{ errors ? "error" : "" }}"></textarea>',
		$html);

		$html = str_replace(['{','}'],['',''],$html);

		return $html;
	}

	// Main Parse MD
	function parseMd($html,$paragraphs = true){
		
		$html = strip_tags($html);
		
		//Replace internal links with target _blank
		$html = preg_replace_callback('%_\[(.*)\]\(([0-9]+)\)%Us' , function($matches){
			return $this->showBlobLink($matches[2],$matches[1],['target'=>'_blank']);
		},$html);
	
		//Replace internal links
		$html = preg_replace_callback('%\[(.*)\]\(([0-9]+)\)%Us' , function($matches){
			return $this->showBlobLink($matches[2],$matches[1]);
		},$html);
		
		//Replace external links with target blank
		$html = preg_replace('%_\[(.*)\]\((.*)\)%Us','<a class="link-ext" href="\2" target="_blank">\1</a>',$html);
		
		//Replace sup and sub
		$html = preg_replace('%{sup}%Us', '<sup>', $html);
		$html = preg_replace('%{/sup}%Us', '</sup>', $html);
		$html = preg_replace('%{sub}%Us', '<sub>', $html);
		$html = preg_replace('%{/sub}%Us', '</sub>', $html);
		
		
		//Parse rest of Markdown
		include_once(ABSDIR.'/external/parsedown/Parsedown.php');
		$Parsedown = new Parsedown();
		
		if($paragraphs == true):
			$html = $Parsedown->text($html);
		else:
			$html = $Parsedown->line($html);
		endif;
		
		
		// Replace &lt;br /&gt; with <br />
		$html = str_replace('&lt;br /&gt;', '<br />', $html);
		
		return $html;
	}
	
	//Remove MD
	function removeMd($value){
		include_once(ABSDIR.'/external/parsedown/Parsedown.php');

		$parsedown = new Parsedown();
		$html = $parsedown->text($value);

		return strip_tags($html);
	}
	
	//Number format
	
	function asInt($s){
		$i = (int)$s;
		if($i == $s) return $i;
		return false;
	}
	
	function asUnsignedInt($s){
		$i = (int)$s;
		if($i == $s && $i == abs($i)) return $i;
		return false;
	}
	
	function asPriceFormat($price,$lang){
		return number_format($price,2,'.','');
	}

	
	function validateArray(&$array, $asTextType = 'asString'){
		if(in_array($asTextType, ['asString', 'asDateTime', 'asEmail', 'asInt', 'asJson', 'asLink', 'asParam', 'asPriceFormat', 'asStatus', 'asURL', 'asUnsignedInt', 'parseMd'])){
			foreach ( $array as $key => $item ) {
				if(is_array ( $item )){
					$array[$key] = $this->validateArray( $item ); //Recursive part
				}else{
					$array[$key] = $this->$asTextType($item);
				}
			}
			return $array;
		}else{
			return false;
		}
	}
    
	//Show internal link
	function showBlobLink($id,$linkText,$params=[]){
		$blobModel = new Blob($this->db);
		$page = $blobModel->getBlob($id);
		if($page['type'] == 'page'){

			$url = $page['lang'].'/'.$page['url']; //level1
			
			$subPage = $blobModel->getBlob($page['parent']);
			if($subPage['type'] == "page"){
				$url = $subPage['lang'].'/'.$subPage['url'].'/'.$page['url'];
			}

			$pageLevel3 = $blobModel->getBlob($subPage['parent']);
			if($pageLevel3['type'] == "page"){
				$url = $pageLevel3['lang'].'/'.$pageLevel3['url'].'/'.$subPage['url'].'/'.$page['url'];
			}

			$target = $params['target'] ? ' target="'.$params['target'].'"' : '';
			return '<a href="'.$url.'"'.$target.'>'.$linkText.'</a>';
		}else{
			return '[Error - Link reference not found]';
		}
	}



	//Show Excerpt
	function showExcerpt($content, $limit = 50){
		
		//specific filters for Excerpts
		$content = $this->parseMd($content);
		$content = str_replace('<br />', ' ', $content);
		$content = strip_tags($content);
		
		//Cuts content to $limit words
		$cuttext = array_slice(explode(' ', $content), 0, $limit);
		$cuttext = implode(' ',$cuttext);
		$ellipsis = (strlen($cuttext) < strlen($content) ? ' ...' : '');
		return $cuttext.$ellipsis;
	}
  	
}
