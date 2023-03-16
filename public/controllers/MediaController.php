<?php 
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;



$app->post('/media/fileUpload[/]', function (Request $q, Response $r, $args) {

	if(!isset($_SESSION['id'])){return $r->withStatus(403);}
	
	$adminModel = new Admin($this->db);
	$mediaModel = new Media($this->db);
	$validate = new Validator($this->db);
	
	$files = $q->getUploadedFiles();
	$userID = $_SESSION['user_id'];

	$post = $q->getParsedBody();
	$subdir = $validate->asString($post['subdir']);
	
	
	foreach ($files as $uploadedFile) {
		
		//check if extension whitelist
		$extension = pathinfo($validate->toFileName($uploadedFile->getClientFilename()), PATHINFO_EXTENSION);
		$allowed = ["doc","docx","epub","flac","jfif","jpeg","jpg","mp3","ods","odt","ogg","pdf","png","ppt","pptx","svg","webp","xls","xlsx"]; //api controller, media model, dropzone, must be identical
		if(!in_array(strtolower($extension), $allowed)) {
			$r->getBody()->write('Extension '.$extension.' not allowed');
			return $r->withStatus(400)->withJson(['status'=>'error','statusText'=>'Unsupported file type: '.$extension]);
		}
		
		//move to file folder
        if ($uploadedFile->getError() === UPLOAD_ERR_OK) {
			$filename = $mediaModel->moveUploadedFile(ABSDIR.'/uploads'.$subdir, $uploadedFile);
        }

        //TODO: comment récupérer 'ABSDIR.'/uploads/tmp/'.$_SESSION['id'].'/'.$_SESSION['creerAnnonceUniqueFormID']' dans le return, ou le rajouter dans $files ?
        
		return $r->withStatus(200);
    }
	
	

})->setName('mediaFileUpload');



$app->post('/media/mkdir[/]', function (Request $q, Response $r, array $args) {
	if(!isset($_SESSION['id'])){
		return $r->withStatus(403);
	}
	$mediaModel = new Media($this->db);
	$validate = new Validator($this->db);
	$post = $q->getParsedBody();
	$src = $validate->asString($args['src']);
    
    $mkdirName = $validate->toFileName($_POST['mkdir-name'],['keepUpperCase'=>true]);
    $callback = $validate->asParam($_POST['mkdir-parent']);
    
    mkdir(ABSDIR.'/uploads/'.$callback.'/'.$mkdirName);
    
	$folder = str_replace(['///','//'],['/','/'],'/admin/uploads/'.$callback.'/');
	return $r->withStatus(302)->withHeader('Location', ABSPATH.$folder);
    
})->setName('mediaMkdir');



$app->get('/media/rmdir/[{src:.*}/]', function (Request $q, Response $r, array $args) {
	if(!isset($_SESSION['id'])){
		return $r->withStatus(403);
	}
	$mediaModel = new Media($this->db);
	$validate = new Validator($this->db);
	$post = $q->getParsedBody();
	$src = $validate->asString($args['src']);
    
    $callback = $validate->asParam($_GET['callback']);
    
	$deleteMedia = rmdir(ABSDIR.'/uploads/'.$src);
	$status = !!$deleteMedia ? 'success' : 'error';
	
	if($status == 'success' && $callback){
		return $r->withStatus(302)->withHeader('Location', ABSPATH.'/'.$callback);
	}else{
		return $r->withStatus(500)->withJson(['status'=>$status,'statusText'=>'Error: folder must be empty to be deleted.']);
	}
})->setName('mediaRmdir');




$app->get('/media/delete/[{src:.*}/]', function (Request $q, Response $r, array $args) {
	if(!isset($_SESSION['id'])){
		return $r->withStatus(403);
	}
	$mediaModel = new Media($this->db);
	$validate = new Validator($this->db);
	$post = $q->getParsedBody();
	$src = $validate->asString($args['src']);
    $callback = $validate->asParam($_GET['callback']);
    
	$deleteMedia = $mediaModel->deleteMedia($src);
	$status = !!$deleteMedia ? 'success' : 'error';
	
	if($status == 'success' && $callback){
		return $r->withStatus(302)->withHeader('Location', ABSPATH.'/'.$callback);
	}else{
		return $r->withStatus(500)->withJson(['status'=>$status,'callback'=>$callback]);
	}
})->setName('deleteMedia');

$app->post('/media/refreshFolderView[/]', function (Request $q, Response $r, array $args) {
	
	/*if(!isset($_SESSION['id'])){
		return $r->withStatus(403);
	}*/

	$mediaModel = new Media($this->db);
	$validate = new Validator($this->db);
	$post = $q->getParsedBody();
	$dir = $validate->asString($post['dir']);
	$media = $mediaModel->getMedia($dir);
	foreach($media as $medium){
		if($medium['type'] == 'image/jpeg' and !file_exists(ABSDIR.'/uploads/'.$dir.'/_thumb/64x64/'.$medium['name'])){
			$mediaModel->generateThumbnail(ABSDIR.'/uploads/'.$dir.$medium['name'],64,64,90);
		}
	}
	
	return $r->withStatus(200)->withJson(['debug'=>ABSDIR.'/uploads/'.$medium['name'],'status'=>'success','media'=>$media]);

})->setName('mediaRefreshFolderView');



$app->post('/media/moveTo[/]', function (Request $q, Response $r, $args) {

	if(!isset($_SESSION['id'])){return $r->withStatus(403);}
	
	$adminModel = new Admin($this->db);
	$mediaModel = new Media($this->db);
	$validate = new Validator($this->db);
	
	$post = $q->getParsedBody();
	$from = $post['from'];
	$to = $post['to'];

	$post = $q->getParsedBody();
	$subdir = $validate->asString($post['subdir']);

	$moveMedia = $mediaModel->moveMedia($from,$to); //"from" and "to" should be relative paths inside /uploads.

	return $r->withStatus(200)->withJson($moveMedia);
	
})->setName('mediaMoveTo');