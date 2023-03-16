<?php 
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;




$app->post('/{type:[a-z]+}/create[/]', function (Request $q, Response $r, array $args) {

	
	if(!isset($_SESSION['id'])){return $r->withStatus(403);}
	
	$blobModel = new Blob($this->db);
	$validate = new Validator($this->db);
	$post = $q->getParsedBody();

	if($args['type'] != $post['type']){
		return $r->withStatus(404)->withJson(['status'=>'error','statusText'=>'400 - Bad Request - Element type mismatch']);
	}

	$module = $blobModel->getDefaultParams($post['type']); 
	if(!$module){
		return $r->withStatus(404)->withJson(['status'=>'error','statusText'=>'404 - Module not found']);
	}
	

	$callback = $validate->asParam($post['callback']);
	$create = $blobModel->addBlob($post);
	
	//clean order of siblings
	$blobModel->cleanOrderValues($post['parent']);

	if(($callback == "admin" || $callback == "adminSitemap") && $create['status'] != "error"){
		if($callback == "adminSitemap"){$callback = "admin/sitemap";}
		return $r->withStatus($create['statusCode'])->withHeader('Location',ABSPATH.'/'.$callback.'/'); 
	}
	
	return $r->withStatus($create['statusCode'])->withJson($create);
	
})->setName('createBlob');



$app->get('/{type:[a-z]+}/{id:[0-9]+}/delete[/]', function (Request $q, Response $r, array $args) {
	
	if(!isset($_SESSION['id'])){return $r->withStatus(403);}
	
	$blobModel = new Blob($this->db);
	$module = $blobModel->getDefaultParams($args['type']);
	if(!$module){
		return $r->withStatus(404)->withJson(['status'=>'error','statusText'=>'404 - Not Found']);
	}

	$adminModel = new Admin($this->db);
	$id = (int)$args['id'];
	
	if(!$adminModel->canDelete($id)){return $r->withStatus(403);}
	
	
	$status = $blobModel->getBlobStatus($id);
	
	$validate = new Validator($this->db);
	$callback = $validate->asParam($_GET['callback']);
	
	if($status == -1){
		
		$parent = $blobModel->getBlobParent($id);

		$delete = $blobModel->deleteBlob($id);
		if(!$delete){return $r->withStatus(500)->withJson(['status'=>'error','statusText'=>'delete_blob_error']);}
		$statusText = 'delete_blob_success';

		//clean order of siblings
		$blobModel->cleanOrderValues($parent);
		
	}
	else{
		$delete = $blobModel->setBlobStatus($id,'-1');
		if($delete['status'] == "error"){return $r->withStatus(500)->withJson(['status'=>'error','statusText'=>$delete['statusText']]);}
		$statusText = 'move_to_trash_success';
	}


	//callback
	if($callback == "adminRecycle"){
		return $r->withStatus(302)->withHeader('Location', ABSPATH.'/admin/recycle/?status=delete_success&id='.$id.'&statusField='.$status);
	} elseif($callback == "adminSitemap"){
		return $r->withStatus(302)->withHeader('Location', ABSPATH.'/admin/sitemap/?status=delete_success&id='.$id.'&statusField='.$status);
	} elseif($callback == "admin"){
		return $r->withStatus(302)->withHeader('Location', ABSPATH.'/admin/dashboard/?status=delete_success&id='.$id.'&statusField='.$status);
	} else {
		return $r->withStatus(200)->withJson(['status'=>'success','statusText'=>$statusText]); 
	}
	
	
})->setName('deleteBlob');





$app->post('/{type:[a-z]+}/{id:[0-9]+}/update[/]', function (Request $q, Response $r, array $args) {
	 
	if(!isset($_SESSION['id'])){return $r->withStatus(403);}
	

	
	$blobModel = new Blob($this->db);
	$post = $q->getParsedBody();

	if($args['type'] != $post['type']){
		return $r->withStatus(404)->withJson(['status'=>'error','statusText'=>'400 - Bad Request - Element type mismatch']);
	}

	$module = $blobModel->getDefaultParams($post['type']); 
	if(!$module){
		return $r->withStatus(404)->withJson(['status'=>'error','statusText'=>'404 - Module not found']);
	}
	
	$validate = new Validator($this->db);
	
	$id = (int)$args['id'];
	$callback = $validate->asParam($post['callback']);
	$update = $blobModel->updateBlob($id,$post);
	
	if($update['status'] == "error"){
		return $r->withStatus($update['statusCode'])->withJson($update);
	}
	
	if(($callback == "admin" || $callback == "adminSitemap") && $update['status'] != "error"){
		if($callback == "adminSitemap"){$callback = "admin/sitemap";}
		return $r->withStatus($update['statusCode'])->withHeader('Location',ABSPATH.'/'.$callback.'/'); 
	}
	
	
	return $r->withStatus($update['statusCode'])->withJson($update);
	
	
})->setName('updateBlob');

//Get Blob - unsafe base, display passwords in fields
$app->get('/{type:[a-z]+}/{id:[0-9]+}[/]', function (Request $q, Response $r, array $args) {
	
	$blobModel = new Blob($this->db);
	$module = $blobModel->getDefaultParams($args['type']);

	if(!$module){
		return $r->withStatus(404)->withJson(['status'=>'error','statusText'=>'404 - Not Found']);
	}

	if($module['private'] && !isset($_SESSION['id'])){
		return $r->withStatus(404)->withJson(['status'=>'error','statusText'=>'403 - Forbidden']);
	}

	$id = (int)$args['id'];
	$blob = $blobModel->getBlob($id);
	
	if($blob['status'] == 0 && !isset($_SESSION['id'])){
		return $r->withStatus(401)->withJson(['status'=>'error','statusText'=>'401 - This element is private']);
	}
	
	if($blob['status'] == -1){
		return $r->withStatus(410)->withJson(['status'=>'error','statusText'=>'410 - This element was deleted']);
	}
	
	if($blob['type'] == $args['type']){
		return $r->withStatus(200)->withJson($blob);
	}
	
	return $r->withStatus(404)->withJson(['status'=>'error','statusText'=>'404 - Not Found']);
	
})->setName('apiGetBlob');


// Update single blob field
/*
$app->post('/{type}/{id:[0-9]+}/updateField[/]', function(Request $q, Response $r){
	
	if(!isset($_SESSION['id'])){return $r->withStatus(302)->withHeader('Location', ABSPATH.'/user/login/');}
	
	
	$validate = new Validator($this->db);
	$post = $q->getParsedBody();
	$id = (int)$post['id'];
	$field = $validate->asParam($post['field']);
	$value = $post['value'];
	
	$adminModel = new Admin($this->db);	
	if(!$adminModel->canEdit($id)){return $r->withStatus(403);}
	
	switch($field){
		case 'type': case 'url': $output = $validate->asURL($value); break;
		case 'name': case 'content': $output = $validate->asString($value); $output = html_entity_decode($output, ENT_QUOTES); break;
		case 'author': case 'parent': case 'translation_of': $output = $validate->asUnsignedInt($value); break; //todo: asExistingBlob
		case 'status': $output = $validate->asStatus($value); break;
		case 'edited': $output = $validate->asDateTime($value); break; //TODO: as date
		case 'params': $output = $validate->asJson($value); break;
		case 'lang': $output = $validate->asParam($value); break;
	}
	
	if($output === false){
		return $r->withStatus(403)->withJson(['status'=>'error','statusText'=>'invalid_parameter','value'=>$value, 'output'=>$output]);
	} else {
		$blobModel = new Blob($this->db);
		try{
			$sql = $this->db->prepare('UPDATE '.TBL.' SET '.$field.' = :value WHERE id = :id;');
			$sql->execute([':value'=>$output,':id'=>$id]);
			return $r->withStatus(200)->withJson(['status'=>'success','value'=>$value,'output'=>$output,'id'=>$id,'field'=>$field]);
		} catch(PDOException $e){
			return $r->withStatus(500)->withJson(['status'=>'error','statusText'=>$e->getMessage()]);
		}
		
	} 
	
	
})->setName('apiUpdateBlob');*/