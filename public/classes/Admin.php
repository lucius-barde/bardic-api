<?php
class Admin{
	
	
	function __construct($db) {
		$this->db = $db;
	}
	
	
	
	public function canEdit($id){
		if($_SESSION['id']){return true;}
		return false;
	}
	
	
	
	public function canDelete($id){
		if($_SESSION['id']){return true;}
		return false;
	}
	
	public function getBlobTypeList(){
		
		$sql = $this->db->prepare('SELECT DISTINCT type FROM '.TBL.' WHERE status >= 0 ORDER BY type;');
		$sql->execute();
		$rows = $sql->fetchAll();
		$blobModel = new Blob($this->db);
		foreach($rows as $key => $row){
			$rows[$key]['defaultParams'] = $blobModel->getDefaultParams($row['type']); 
			$rows[$key]['key'] = $key;
		}
		return $rows;
	}

	public function getDefaultBlobsTypeList(){
		$blobModel = new Blob($this->db);
		$defaultBlobList = ['page','paragraph'];
		foreach($defaultBlobList as $blobType){
			$returnList[$blobType]['defaultParams'] = $blobModel->getDefaultParams($blobType); 
			$returnList[$blobType]['key'] = $blobType; 
		}
		return $returnList;

	}
	
	private function getPageContent($parentID,$args = []){ //equivalent to Page::getPageElements() but for sitemap
		$sql = $this->db->prepare('SELECT *, JSON_EXTRACT(params, "$.order") AS contentOrder FROM '.TBL.' WHERE type != "page" AND parent = :parent ORDER BY contentOrder;');
		$sql->execute([':parent'=>$parentID]);
		$rows = $sql->fetchAll();
		$content = $rows;
		
	
		
		// optional admin params
		if($args['admin'] == true){
			$adminModel = new Admin($this->db);
			foreach($rows as $key=>$row){
				$content[$key]['admin']['canEdit'] = $adminModel->canEdit($blob['id']);
				$content[$key]['admin']['canDelete'] = $adminModel->canDelete($blob['id']);
			}
		}
		
		
		return $content;
	}

	public function getPageUrl($id){

		$blobModel = new Blob($this->db);
		$sql = $this->db->prepare('SELECT type, url, parent,lang FROM '.TBL.' WHERE id = :id LIMIT 1;');
		$sql->execute([':id'=>$id]);
		$level3 = $sql->fetch();

		if($level3['type'] == "page"){ //if the currently selected element is a page
			
			$level2 = $blobModel->getBlob($level3['parent']);
			if($level2['type'] == "site"){
				$url = $level3['lang'].'/'.$level3['url'];
				return $url; //page level 1

			}elseif($level2['type'] == "page"){
				$level1 = $blobModel->getBlob($level2['parent']);
				if($level1['type'] == "site"){
					$url = $level2['lang'].'/'.$level2['url'].'/'.$level3['url'];
					return $url; //page level 2
				}
				elseif($level1['type'] == "page"){
					$url = $level1['lang'].'/'.$level1['url'].'/'.$level2['url'].'/'.$level3['url'];
					return $url; //page level 3
				}

			}
		}else{	//if the currently selected element is NOT a page

			$level4 = $level3; //add a recursion level

			$level3 = $blobModel->getBlob($level4['parent']);
			if($level3['type'] == "site"){
				
				$url = $level3['lang'].'/'.$level3['url'];
				return $url; //page level 1

			}elseif($level3['type'] == "page"){
				$level2 = $blobModel->getBlob($level3['parent']);
				
				if($level2['type'] == "site"){
					$url = $level3['lang'].'/'.$level3['url'];
					return $url; //page level 1

				}elseif($level2['type'] == "page"){
					$level1 = $blobModel->getBlob($level2['parent']);
					if($level1['type'] == "site"){
						$url = $level2['lang'].'/'.$level2['url'].'/'.$level3['url'];
						return $url; //page level 2
					}
					elseif($level1['type'] == "page"){
						$url = $level1['lang'].'/'.$level1['url'].'/'.$level2['url'].'/'.$level3['url'];
						return $url; //page level 3
					}

				}

			}

		}

	}
	
	
	public function getSitemap($args = []){
		$blobModel = new Blob($this->db);
		$siteRoot = $blobModel->getSiteProperties();
		$siteRoot['children'] = $this->getSitemapTree($siteRoot,$args);
		return $siteRoot;
	}
	
	
	
	private function getSitemapTree($siteRoot,$args = []){
		
		$parentID = $siteRoot['id'] ? $siteRoot['id'] : 0;

		// query
		if($args['langFilter']){$langCondition = ' AND lang = "'.$args['langFilter'].'"';}
		if($args['withBlocks'] == true){
			$types = '"page", "link", "block", "blog"'; //for the admin sitemap
		}else{
			$types = '"page", "link", "blog"'; //for the public navbars
		}

		$sql = $this->db->prepare('SELECT * FROM '.TBL.' WHERE type IN ('.$types.') AND parent = :parent'.$langCondition.' ORDER BY json_extract(params,"$.order");');
		$sql->execute([':parent'=>$parentID]);
		$row = $sql->fetchAll();
		foreach($row as $key=>$entry){
			$row[$key]['params'] = json_decode($row[$key]['params'],true);
		}
		
		// optional admin params
		if($args['admin'] == true){
			$adminModel = new Admin($this->db);
			foreach($row as $key=>$page){
				$row[$key]['admin']['canEdit'] = $adminModel->canEdit($blob['id']);
				$row[$key]['admin']['canDelete'] = $adminModel->canDelete($blob['id']);
			}
		}
		
		// TODO: order by order param
		
		// tree logic
		
		foreach($row as $key=>$page){
			$row[$key]['elements'] = $this->getPageContent($page['id'],$args);
			$row[$key]['children'] = $this->getSitemapTree($page,$args);
		}
		
		return $row;
	}
	
	
	public function getTranslations($lang){

		$modules = $this->getBlobTypeList();

		if($lang){
			$adminI18n = json_decode(file_get_contents(ABSDIR.'/languages/admin_'.$lang.'.json'),true);
			$moduleI18n = ['_modules'=>[]];
			foreach($modules as $module){
				$moduleI18n['_modules'][$module['type']] = $module['defaultParams']['i18n'][$lang];
			}
		}else{
			$adminI18n = json_decode(file_get_contents(ABSDIR.'/languages/admin_en.json'),true);
			$moduleI18n = ['_modules'=>[]];
			foreach($modules as $module){
				$moduleI18n['_modules'][$module['type']] = $module['defaultParams']['i18n'][$lang];
			}
		}

		$i18n = array_merge($adminI18n,$moduleI18n);
		return $i18n;
	}


	function login($login,$password){
		global $config;
		//Verify password
		if($login == $config['user']['login'] && $password == $config['user']['password']){
			return ['status'=>'ok','login'=>$login];
		}
		return ['error'=>'user.warning.incorrectPasswordForUsername'];
  	}
  	
  	function createUserBySignup($new){
		//$newLogin = $new['login'];
		$newPassword = password_hash($new['password'],PASSWORD_BCRYPT);
		$params = [
			'password'=>$newPassword,
			'level'=>0,
			'status'=>0
		];
		$params = json_encode($params);

		$userVerify = $this->db->prepare('SELECT * FROM '.TBL.' WHERE type = "user" AND url = :email LIMIT 1;');
		$userVerify->execute([':email'=>$new['email']]);
		$userAlreadyExists = $userVerify->fetch();
		if(!!$userAlreadyExists){
			return false;
		}else{
			
			$sql = $this->db->prepare('INSERT INTO '.TBL.' (id,type,url,name,content,parent,status,author,edited,params) VALUES (NULL, "user", :url, :name, "", 0, 0, 0, NOW(),:params)');
			$sql->execute([':name'=>$new['login'],':url'=>$new['email'], ':params'=>$params]);
			
			if(!!$sql):
				return true;
			else:
				return false;
			endif;
		}

		
	}
  	
  	function userExists($login){
  		$sql = $this->db->query('SELECT * FROM '.TBL.' WHERE type = "user" AND content LIKE "%'.$login.'%"');
  		$row = $sql->fetch();
  		if(sizeof($row) > 0){
			return $row;
		}
		return false;
	}
		
	public function getAllRoutes(){
		global $app;
		$routes = $app->getContainer()->router->getRoutes();
		$output = [];
		foreach ($routes as $route) {
			$output[$route->getName()] = $route->getName().' : '.$route->getPattern(). ' '. implode(', ',$route->getMethods());
		}
		sort($output); //sort and preserve array keys
		return $output;
	}
	
	
}
