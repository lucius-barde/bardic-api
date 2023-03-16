<?php
class Page{
	function __construct($db) {
		$this->db = $db;
	}
	
	
    public function isHome($id,$lang){
		return $id == $this->getHomePage(false,$lang) ? true : false;
	}
	
  	public function getHomePage($withContent = true,$lang){
		$blobModel = new Blob($this->db);
		$site = $blobModel->getSiteProperties();
		$home = $site['params']['homelink'];
		$sql1 = $this->db->prepare('SELECT id FROM '.TBL.' WHERE url = :home LIMIT 1;');
		$sql1->execute([':home'=>$home]);
		$homeID = $sql1->fetch();
		$sql = $this->db->prepare('SELECT * FROM '.TBL.' WHERE type = "page" AND lang = :lang AND (id = :homeID or translation_of = :homeID);');
		$sql->execute([':lang'=>$lang, ':homeID'=>$homeID['id']]);
		$row = $sql->fetch();
		$row['params'] = json_decode($row['params'],true);
		if(!!$withContent):
			return $row;
		else:
			return $row['id'];
		endif;
	}
	
	
  	public function getPageElements($id,$limit = NULL, $type = NULL){
		$typeStr = !$type ? '!= "page" AND type != "link"' : '= "'.$type.'"'; //TODO: replace by a list of modules with the scope PageElement
		$limitStr = $limit > 0 ? 'LIMIT '.$limit : '';
  		$sql = $this->db->prepare('SELECT *, JSON_EXTRACT(params, "$.order") AS contentOrder FROM '.TBL.' WHERE parent = :id AND type '.$typeStr.' AND status > -1 ORDER BY contentOrder '.$limitStr.';');
		$sql->execute([':id'=>$id]);
		$rows = $sql->fetchAll();
		//decode params
		foreach($rows as $key=>$row){
			
			$rows[$key]['params'] = json_decode($rows[$key]['params'],true);
			
			//handles listings
			if(is_array($rows[$key]['params']['listing'])){
				$blobModel = new Blob($this->db);
				$rows[$key]['listing'] = $blobModel->getAllBlobs(['type'=>$rows[$key]['params']['listing']['type'],'orderby'=>$rows[$key]['params']['listing']['orderby'],'order'=>$rows[$key]['params']['listing']['order']]);
				foreach($rows[$key]['listing'] as $listingkey => $ref){
					$rows[$key]['listing'][$listingkey]['params'] =  json_decode($rows[$key]['listing'][$listingkey]['params'],true);
				}
			}
			
			//specific modules require specific loaded contents
			if($row['type'] == "gallery"){
				$galleryModel = new Gallery($this->db);
				//$content[$rows[$key]['params']['order']]['_external']['images'] = $galleryModel->getGallery($rows[$key]['params']);
				$rows[$key]['_external']['images'] = $galleryModel->getGallery($rows[$key]['params']);

			}
	
		}
		//apply order and return page elements
		return $rows;
  	}
     
    public function getTranslations($tr){
		$sql = $this->db->prepare('SELECT * FROM '.TBL.' WHERE (translation_of = :tr) AND status > -1;');
		$sql->execute([':tr'=>$tr]);
		$rows = $sql->fetchAll();
		foreach($rows as $key => $row){
			$rows[$key]['params'] = json_decode($row['params'],true);
		}
		return $rows;
	}
  	
	
}
