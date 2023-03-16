<?php
class Blob{
	
	function __construct($db) {
		$this->db = $db;
  	}
  	
  	public function addBlob($post){
	
  		if(!empty($post)){
				
			if(!empty($post['rawParams'])){
				$post['params'] = json_decode($post['rawParams'],true);
				unset($post['rawParams']);
				
			}
				
					
			//remove empty params
			foreach($post['params'] as $key => $param){
				if($param === null || $param === ""){
					unset($post['params'][$key]);
				}
			}
			
			//check if params are all empty, then returns null instead of empty array
			if(sizeof($post['params']) == 1 && $post['params'][array_key_first($post['params'])] == ""){
				$post['params'] = '';
			}else{
				$post['params'] = json_encode($post['params']);
				if(!$post['params']){return ['status'=>'error','statusCode'=>500,'statusText'=>'update_blob_params_exception'];}
			} 
			
			
			try{
				
				//create url if not exists
				if(!$post['url']){$post['url'] = $post['type'].'-'.time();}
				
				
					
			
				$sql = $this->db->prepare('INSERT INTO '.TBL.' (id,type,name,url,content,parent,status,edited,lang,translation_of,params) VALUES (NULL,:type, :name, :url, :content, :parent, :status, :edited, :lang, :translation_of, :params);');
				$newdata = [
					':type' => $post['type'],
					':name' => $post['name'],
					':url' => $post['url'],
					':content' => $post['content'],
					':parent' => $post['parent'],
					':status' => $post['status'],
					':edited' => ($post['edited'] ? $post['edited'] : date('Y-m-d H:i:s')), //current time, except if we create a blob with a custom edition time (rare cases)
					':params' => $post['params'],
					':lang' => $post['lang'],
					':translation_of' => $post['translation_of'] ? $post['translation_of'] : null
				];
				$sql->execute($newdata);
			} catch(PDOException $exception){ 
				return ['status'=>'error','statusCode'=>500,'statusText'=>'add_blob_pdo_exception','statusDetails'=>$exception];
			} 
			if(!$sql) return ['status'=>'error','statusCode'=>500,'statusText'=>'add_blob_database_error'];
		}
		return ['status'=>'success','statusCode'=>200,'statusText'=>'add_blob_success','data'=>$newdata];
	}
	

	public function cleanOrderValues($id){

		//Sets all order values to even numbers: first = 0, second = 2, third = 4 etc. Example: 0,1,3,4,6,8 => 0,2,4,6,8,10
		//This way, OppidumCMS allows to use the odd number temporarily to say "I want to move element 0 after element 2, or before element 4":
		//thus from 0,2,4 we give element 0 the order value 3, so at update time we have 2,3,4, and after cleanOrderValues 2,3,4 is changed to 0,2,4 again.
		//It is best to include all deleted elements (with status -1, 0 etc.) in the cleanup for more security.

		$sql = $this->db->prepare('SELECT *,  JSON_EXTRACT(params, "$.order") AS contentOrder FROM '.TBL.' WHERE parent = :id ORDER BY CAST(contentOrder AS SIGNED) ASC;');
		$sql->execute([':id'=>$id]);
		$rows = $sql->fetchAll();
		foreach($rows as $rowIndex => $row){
			$rows[$rowIndex]['params'] = json_decode($rows[$rowIndex]['params'],true);
			$rows[$rowIndex]['params']['order'] = (int)($rowIndex * 2);
			$rows[$rowIndex]['contentOrder'] = (int)($rowIndex * 2);
			$rows[$rowIndex]['params'] = json_encode($rows[$rowIndex]['params']);
			$cleanOrderValueSql = $this->db->prepare('UPDATE '.TBL.' SET params = :params WHERE id = :subid LIMIT 1;');
			$cleanOrderValueSql->execute([':subid'=>$rows[$rowIndex]['id'], ':params'=>$rows[$rowIndex]['params']]); 
		}

		return true;
	}

	public function deleteBlob($id){
		$sql = $this->db->prepare('DELETE FROM '.TBL.' WHERE id = :id;');
		$sql->execute([':id'=>$id]);
		if(!$sql) return ['status'=>'error','statusText'=>'Invalid request'];
		return ['status'=>'success'];
	}
	
  	public function getAllBlobs($args = []){
		$validate = new Validator($this->db);
		$paged = (int)$args['paged'] ? $args['paged'] : false; //number of elements by page
		$page = (int)$args['page'] ? $args['page'] : false; //current page number
		$status = $args['status'] ? implode(',',$args['status']) : false;
		$langs = $args['lang'] ? implode(',',$args['lang']) : false;
		$orderby = $validate->asParam($args['orderby']) ? $args['orderby'] : false;
		$order = $args['order'] == 'DESC' ? 'DESC' : 'ASC';
		$parent = (int)$args['parent'] ? $args['parent'] : false;
		$searchTerm = $validate->asParam($args['searchTerm']) ? $args['searchTerm'] : false;
		$onlyCount = (bool)$args['onlyCount'] ? $args['onlyCount'] : false; //returns the total amount without page limit (used to calculate the total number of pages)
		$limit = (int)$args['limit'] ? $args['limit'] : false; //only used if not paged
		if(is_array($args['type'])){
			$type = $validate->validateArray($args['type'],'asParam');
		}else{
			$type = $validate->asParam($args['type']) ? $args['type'] : false;
		}
		
		/* TO FINISH
		$filterArgs = [$byType,$byStatus,$byParent];
		foreach($filterArgs as $key => $arg){
			if($key == 0){}
		}*/
		
		if($searchTerm){
			$byType = 'WHERE (url LIKE "%'.$searchTerm.'%" OR id = "'.$searchTerm.'" OR name LIKE "%'.$searchTerm.'%" OR content LIKE "%'.$searchTerm.'%" OR params LIKE "%'.$searchTerm.'%") ';
			if($type){
				$byType .= ' AND type = "'.$type.'" ';
			}
			
		}elseif(is_array($type)){
			$byType = $type ? 'WHERE type IN("'.implode('","',$type).'") ' : '';
		}else{
			$byType = $type ? 'WHERE type = "'.$type.'" ' : '';
		}
		
		$byStatus = $status ? ($byType ? 'AND' : 'WHERE').' status IN('.$status.') ' : '';
		
		$byLang = $langs ? ($byType ? 'AND' : 'WHERE').' lang IN('.$langs.') ' : ''; 
		

		if($byType or $byStatus){
			$byParent = $parent ? 'AND parent = '.$parent.' ' : '';
		}else{
			$byParent = $parent ? 'WHERE parent = '.$parent.' ' : '';
		}

		if($byType or $byStatus or $byParent){
			$byLang = is_array($lang) ? 'AND lang IN("'.implode('","',$lang).'") ' : '';
		}else{
			$byLang = is_array($lang) ? 'WHERE lang IN("'.implode('","',$lang).'") ' : '';
		}

		$orderByClause = $orderby && $order ? ' ORDER BY '.$orderby.' '.$order : '';
		
		$pagingLimit = $paged && $page ? ' LIMIT '.(($page-1) * $paged).','.$paged : ($limit ? ' LIMIT '.$limit : '' );
		
		if($onlyCount){
			
			$sql = $this->db->query('SELECT COUNT(id) AS count FROM '.TBL.' '.$byType.$byStatus.$byParent.$byLang);
			$row = $sql->fetch();
			return (int)$row['count'];	
			
			
		}
		
		$sql = $this->db->query('SELECT * FROM '.TBL.' '.$byType.$byStatus.$byParent.$orderByClause.$pagingLimit);
		
		
		
		$row = $sql->fetchAll();
		
		if($args['admin'] == true){
			$adminModel = new Admin($this->db);
			foreach($row as $key=>$blob){
				$row[$key]['admin']['canEdit'] = $adminModel->canEdit($blob['id']);
				$row[$key]['admin']['canDelete'] = $adminModel->canDelete($blob['id']);
			}
		}
		
		if($args['tree'] == true){
			foreach($row as $key => $blob){
				$row[$key]['children'] = $this->getAllBlobs(['type'=>$type,'parent'=>$blob['id'],'tree'=>true]);
			}
		}
		
		return $row;
	}
	
	
  	
  	public function getBlob($id,$args = []){
		$sql = $this->db->prepare('SELECT * FROM '.TBL.' WHERE id = :id LIMIT 1;');
		$sql->execute([':id'=>$id]);
		$row = $sql->fetch();
		if(!$args['rawParams']){
		$row['params'] = json_decode($row['params'],true);
		}
		return $row;
	}
	
	public function getBlobParent($id){
		$sql = $this->db->prepare('SELECT parent FROM '.TBL.' WHERE id = :id LIMIT 1;');
		$sql->execute([':id'=>$id]);
		$row = $sql->fetch();
		return $row['parent'];
	}
	 
	public function getBlobStatus($id){
		$sql = $this->db->prepare('SELECT status FROM '.TBL.' WHERE id = :id LIMIT 1;');
		$sql->execute([':id'=>$id]);
		$row = $sql->fetch();
		return $row['status'];
	}
	 
	public function getBlobType($id){
		$sql = $this->db->prepare('SELECT type FROM '.TBL.' WHERE id = :id LIMIT 1;');
		$sql->execute([':id'=>$id]);
		$row = $sql->fetch();
		return $row['type'];
	}
	
	public function getDefaultParams($type){
		$params = json_decode(file_get_contents(ABSDIR.'/modules/'.$type.'.json'),true);
		return $params;
	}


			
	public function getSiteProperties(){
  		global $config;
		$site = $config['site'];
  		return $site;
  	}
  	
	
	public function setBlobStatus($id,$s){
  		if(!empty($id)){
	  		$sql = $this->db->prepare('UPDATE '.TBL.' SET status = :s WHERE id = :id OR parent = :id;');

	  		$sql->execute([':s' => $s, ':id' => $id]);
			if(!$sql)  return ['status'=>'error','statusCode'=>400,'statusText'=>'set_blob_status_error_bad_request'];
	  		if($sql->rowCount() == 0) return ['status'=>'error','statusCode'=>404,'statusText'=>'set_blob_status_error_blob_doesnt_exist'];
	  		return ['status'=>'success','statusCode'=>200,'statusText'=>'set_blob_status_success'];
  		}
  		return false;
  	}
	
  	public function updateBlob($id,$post){
  		if(!empty($post)){
			

			//remove empty params
			foreach($post['params'] as $key => $param){
				if($param === null || $param === ""){
					unset($post['params'][$key]);
				}
				if(gettype($param) == "array"){
					foreach($param as $subkey => $subparam){
						if($subparam == null || $param === ""){
							unset ($post['params'][$key][$subkey]);
						}
						elseif(gettype($subkey) == "integer"){
							$newsubkey = $post['paramsKeys'][$key][$subkey];
							$post['params'][$key][$newsubkey] = $post['params'][$key][$subkey];
							unset ($post['params'][$key][$subkey]);
						}
					}
				}
				
			}
			
			//check if params are all empty, then returns null instead of empty array
			if(sizeof($post['params']) == 1 && $post['params'][array_key_first($post['params'])] == ""){
				$post['params'] = '';
			}else{
				$post['params'] = json_encode($post['params']);
				if(!$post['params']){return ['status'=>'error','statusCode'=>500,'statusText'=>'update_blob_params_exception'];}
			} 
			
			
			try{
				$sql = $this->db->prepare('UPDATE '.TBL.' SET name = :name, url = :url, content = :content, parent = :parent, status = :status, edited = :edited, lang = :lang, translation_of = :translation_of, params = :params WHERE id = :id AND type = :type LIMIT 1;');
				$sql->execute([
					':id' => $id,
					':type' => $post['type'],
					':name' => $post['name'],
					':url' => $post['url'],
					':content' => $post['content'],
					':parent' => $post['parent'],
					':status' => $post['status'],
					':edited' => ($post['edited'] ? $post['edited'] : date('Y-m-d H:i:s')),
					':lang'=>$post['lang'],
					':translation_of'=>$post['translation_of'] ? $post['translation_of'] : NULL,
					':params' => $post['params']
				]);
			} 
			catch(PDOException $exception){ 
				return ['status'=>'error','statusCode'=>500,'statusText'=>'update_blob_pdo_exception','statusDetails'=>$exception];
			} 
			if(!$sql) return ['status'=>'error','statusCode'=>500,'statusText'=>'update_blob_database_error'];
			
			$returnSql = $this->db->prepare('SELECT * FROM '.TBL.' WHERE id = :id;');
			$returnSql->execute([':id'=>$id]);
			$updatedData = $returnSql->fetch();
			return ['status'=>'success', 'statusCode'=>200, 'data'=>$updatedData];
			
		}else {
			return ['status'=>'error','statusCode'=>500,'statusText'=>'update_blob_empty_post_error'];
		}
	}
	
}
