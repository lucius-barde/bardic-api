<?php 
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

/* ADMIN CONTROLLER */


$app->get('/admin[/]', function (Request $q, Response $r, array $args) {
	
	if(!isset($_SESSION['id'])){
		return $r->withStatus(302)->withHeader('Location', ABSPATH.'/admin/login/');
	} else {
		return $r->withStatus(302)->withHeader('Location', ABSPATH.'/admin/dashboard/');
	}
	
})->setName('admin');

$app->get('/admin/dashboard/[{page:[0-9]+}/]', function (Request $q, Response $r, array $args) {
	
	if(!isset($_SESSION['id'])){
		return $r->withStatus(302)->withHeader('Location', ABSPATH.'/admin/login/');
	}
		
	$blobModel = new Blob($this->db);
	$siteObject = $blobModel->getSiteProperties();
	$site = $siteObject['params'];
	$validate = new Validator($this->db);
	
	//search
	$searchTerm = $validate->asParam($_GET['searchTerm']);
	$searchType = $validate->asParam($_GET['type']);
	
	if($searchTerm){
		$searchStatus = [-2,-1,0,1];
	}else{
		$searchStatus = [0,1];
	}
	
	
	//dashboard order
	$orderby = $validate->asParam($_GET['orderby']) ? $validate->asParam($_GET['orderby']) : $site['admin']['orderby'];
	$order = in_array($_GET['order'],['ASC','DESC']) ? $validate->asParam($_GET['order']) : $site['admin']['order'];
		
	if($searchTerm){
		
		//get blobs for selected page
		$blobs = $blobModel->getAllBlobs(['admin'=>true,'status'=>$searchStatus,'searchTerm'=>$searchTerm,'type'=>$searchType,'orderby'=>$orderby,'order'=>$order,'limit'=>100]);
		
	}else{
			
		//dashboard paging
		$paged = 10;
		$page = (int)$args['page'] > 0 ? (int)$args['page'] : 1;
		
		
		//get total of blobs
		$blobCount = $blobModel->getAllBlobs(['admin'=>true,'status'=>[0,1],'orderby'=>$orderby,'order'=>$order,'onlyCount'=>true]);
		
		//get total of pages
		$pageCount = ceil($blobCount / $paged);
		
		//get blobs for selected page
		$blobs = $blobModel->getAllBlobs(['admin'=>true,'status'=>[0,1],'orderby'=>$orderby,'order'=>$order,'paged'=>$paged,'page'=>$page]);
	}	
	
	//get all kinds of blobs used on the site
	$adminModel = new Admin($this->db);
	$blobTypes = $adminModel->getBlobTypeList();
	$defaultBlobTypes = $adminModel->getDefaultBlobsTypeList();
	
	
	$params = [
		'ABSPATH'=>ABSPATH,
		'ABSDIR'=>ABSDIR,
		'action'=>'admin',
		'blobs'=>$blobs,
		'blobCount'=>$blobCount,
		'blobTypes'=>$blobTypes, //existing in database
		'defaultBlobTypes'=>$defaultBlobTypes, //existing in modules folder
		'get'=>$validate->validateArray($_GET,'asParam'),
		'getOrderBy'=>($validate->asParam($_GET['orderby']) ? '?orderby='.$validate->asParam($_GET['orderby']) : false),
		'getOrder'=>(in_array($_GET['order'],['ASC','DESC']) ? '&order='.$validate->asParam($_GET['order']) : false),
		'i18n'=>$adminModel->getTranslations($siteObject['params']['default_language']),
		'paged'=>$paged,
		'page'=>$page,
		'pageCount'=>$pageCount,
		'searchTerm'=>$searchTerm,
		'site'=>$siteObject,
		'type'=>$searchType,
		'session'=>$_SESSION
	];
	
	$r = $this->viewAdmin->render($r, "standard.html.twig", $params);
	return $r;
	//return $r->withJson($params);
	
})->setName('adminDashboard');

$app->get('/admin/dashboard/type/{type}/[{page:[0-9]+}/]', function (Request $q, Response $r, array $args) {
	
	
	if(!isset($_SESSION['id'])){
		return $r->withStatus(302)->withHeader('Location', ABSPATH.'/admin/login/');
	}
	
	$validate = new Validator($this->db);
	$type = $validate->asParam($args['type']);
	
	$blobModel = new Blob($this->db);
	$siteObject = $blobModel->getSiteProperties();
	$site = $siteObject['params'];
	
	
	//dashboard paging
	$paged = 50;
	$page = (int)$args['page'] > 0 ? (int)$args['page'] : 1;
	
	//dashboard order
	$orderby = $validate->asParam($_GET['orderby']) ? $validate->asParam($_GET['orderby']) : $site['admin']['orderby'];
	$order = in_array($_GET['order'],['ASC','DESC']) ? $validate->asParam($_GET['order']) : $site['admin']['order'];
	
	//get total of blobs
	$blobCount = $blobModel->getAllBlobs(['admin'=>true,'status'=>[0,1],'type'=>$type,'orderby'=>$orderby,'order'=>$order,'onlyCount'=>true]);
	
	//get total of pages
	$pageCount = ceil($blobCount / $paged);
	
	//get blobs for selected page
	$blobs = $blobModel->getAllBlobs(['admin'=>true,'status'=>[0,1],'type'=>$type,'orderby'=>$orderby,'order'=>$order,'paged'=>$paged,'page'=>$page]);
	
	//get all kinds of blobs used on the site
	$adminModel = new Admin($this->db);
	$blobTypes = $adminModel->getBlobTypeList();
	$defaultBlobTypes = $adminModel->getDefaultBlobsTypeList();
	
	
	$params = [
		'ABSPATH'=>ABSPATH,
		'ABSDIR'=>ABSDIR,
		'action'=>'adminDashboardByType',
		'blobs'=>$blobs,
		'blobCount'=>$blobCount,
		'blobTypes'=>$blobTypes,
		'defaultBlobTypes'=>$defaultBlobTypes, //existing in modules folder
		'getOrderBy'=>($validate->asParam($_GET['orderby']) ? '?orderby='.$validate->asParam($_GET['orderby']) : false),
		'getOrder'=>(in_array($_GET['order'],['ASC','DESC']) ? '&order='.$validate->asParam($_GET['order']) : false),
		'i18n'=>$adminModel->getTranslations($siteObject['params']['default_language']),
		'paged'=>$paged,
		'page'=>$page,
		'pageCount'=>$pageCount,
		'type'=>$type,
		'site'=>$siteObject,
		'session'=>$_SESSION
	];
	$r = $this->viewAdmin->render($r, "standard.html.twig", $params);
	return $r;
	//return $r->withJson($params);
	
})->setName('adminDashboardByType');

$app->get('/admin/create/{type:[a-z]+}[/]', function (Request $q, Response $r, array $args) {
	
	if(!isset($_SESSION['id'])){
		return $r->withStatus(302)->withHeader('Location', ABSPATH.'/admin/login/');
	}
	
	
	$blobModel = new Blob($this->db);
	$adminModel = new Admin($this->db);
	$validate = new Validator($this->db);
	$site = $blobModel->getSiteProperties();
	$translationBlobs = $blobModel->getAllBlobs([ 'admin'=>true,'lang'=>[$site['params']['default_lang']] ]); //used in field translation_of
	$blobTypes = $adminModel->getBlobTypeList();
	$defaultBlobTypes = $adminModel->getDefaultBlobsTypeList();
	$blobParentList = $blobModel->getAllBlobs( ['admin'=>true,'type'=>['page','block']] );
	$type = $validate->asParam($args['type']);
	
	$get = [];
	$get['parent'] = $validate->asInt($_GET['parent']);
	$get['callback'] = $validate->asParam($_GET['callback']);
	$tmpLang = $blobModel->getBlob($get['parent']);
	$get['parentLang'] = $tmpLang['lang'];
	$get['params'] = $blobModel->getDefaultParams($type);
	$get['countSiblings'] = $blobModel->getAllBlobs(['parent'=>$get['parent'], 'onlyCount'=>true]); //used to put the default "order" value to the last element index + 1

	
	$params = [
		'ABSPATH'=>ABSPATH,
		'ABSDIR'=>ABSDIR,
		'action'=>'adminCreate',
		'blobParentList' => $blobParentList,
		'blobTypes' => $blobTypes,
		'defaultBlobTypes'=>$defaultBlobTypes, //existing in modules folder
		'get'=>$get,
		'i18n'=>$adminModel->getTranslations($site['params']['default_language']),
		'translationBlobs'=>$translationBlobs,
		'site'=>$site,
		'type'=>$type,
		'session'=>$_SESSION
	];
	$r = $this->viewAdmin->render($r, "standard.html.twig", $params);
	return $r;
	//return $r->withJson($params);
	
})->setName('adminCreate');


$app->get('/admin/{id}/edit/[status/{status}/]', function (Request $q, Response $r, array $args) {
	
	
	if(!isset($_SESSION['id'])){
		return $r->withStatus(302)->withHeader('Location', ABSPATH.'/admin/login/');
	}
	

	$adminModel = new Admin($this->db);
	$id = (int)$args['id'];
	$status = (int)$args['status'];

	
	if(!$adminModel->canEdit($id)){return $r->withStatus(403);}
	
	$blobModel = new Blob($this->db);
	//$blob = $blobModel->getBlob($id,['rawParams'=>true]);
	$blob = $blobModel->getBlob($id,['rawParams'=>false]);
	$blobParentList = $blobModel->getAllBlobs( ['admin'=>true,'type'=>['page','block']] );
	$site = $blobModel->getSiteProperties();
	$blobTypes = $adminModel->getBlobTypeList();
	$defaultBlobTypes = $adminModel->getDefaultBlobsTypeList();
	$translationBlobs = $blobModel->getAllBlobs([ 'admin'=>true,'lang'=>[$site['params']['default_lang']] ]); //used in field translation_of
	
	//Pre-fill the edit form
	$validate = new Validator($this->db);
	$get = [];
	$get['params'] = $blobModel->getDefaultParams($blob['type']);
	$get['countSiblings'] = $blobModel->getAllBlobs(['parent'=>$get['parent'], 'onlyCount'=>true]); //used to put the default "order" value to the last element index + 1
	$get['callback'] = $validate->asParam($_GET['callback']);
	
	$params = [
		'ABSPATH'=>ABSPATH,
		'ABSDIR'=>ABSDIR,
		'action'=>'adminEdit',
		'blob'=>$blob,
		'blobParentList'=>$blobParentList,
		'blobTypes' => $blobTypes,
		'defaultBlobTypes'=>$defaultBlobTypes, //existing in modules folder
		'get'=>$get,
		'translationBlobs'=>$translationBlobs,
		'i18n'=>$adminModel->getTranslations($site['params']['default_language']),
		'site'=>$site,
		'session'=>$_SESSION,
		'status'=>$status,
		'validate'=>$validate
	];
	$r = $this->viewAdmin->render($r, "standard.html.twig", $params);
	
})->setName('adminEdit');



$app->get('/admin/recycle/[{page:[0-9]+}/]', function (Request $q, Response $r, array $args) {
	
	
	if(!isset($_SESSION['id'])){
		return $r->withStatus(302)->withHeader('Location', ABSPATH.'/admin/login/');
	}
	
	$adminModel = new Admin($this->db);
	$blobModel = new Blob($this->db);
	$validate = new Validator($this->db);
	
	//dashboard paging
	$paged = 50;
	$page = (int)$args['page'] > 0 ? (int)$args['page'] : 1;
	
	//dashboard order
	$orderby = $validate->asParam($_GET['orderby']) ? $validate->asParam($_GET['orderby']) : 'edited';
	$order = in_array($_GET['order'],['ASC','DESC']) ? $validate->asParam($_GET['order']) : 'DESC';
	
	//get total of blobs
	$blobCount = $blobModel->getAllBlobs(['admin'=>true,'status'=>[-1],'orderby'=>'edited','order'=>'DESC','onlyCount'=>true]);
	
	//get total of pages
	$pageCount = ceil($blobCount / $paged);
	
	//get blobs for selected page
	$blobs = $blobModel->getAllBlobs(['admin'=>true,'orderby'=>$orderby,'order'=>$order,'status'=>[-1],'paged'=>$paged,'page'=>$page]);
	
	$siteObject = $blobModel->getSiteProperties();


	$blobTypes = $adminModel->getBlobTypeList();
	$defaultBlobTypes = $adminModel->getDefaultBlobsTypeList();

	$params = [
		'ABSPATH'=>ABSPATH,
		'ABSDIR'=>ABSDIR,
		'action'=>'adminRecycle',
		'blobs'=>$blobs,
		'blobTypes' => $blobTypes,
		'defaultBlobTypes'=>$defaultBlobTypes, //existing in modules folder
		'getOrderBy'=>($validate->asParam($_GET['orderby']) ? '?orderby='.$validate->asParam($_GET['orderby']) : false),
		'getOrder'=>(in_array($_GET['order'],['ASC','DESC']) ? '&order='.$validate->asParam($_GET['order']) : false),
		'paged'=>$paged,
		'page'=>$page,
		'pageCount'=>$pageCount,
		'i18n'=>$adminModel->getTranslations($siteObject['params']['default_language']),
		'site'=>$siteObject,
		'session'=>$_SESSION
	];
	$r = $this->viewAdmin->render($r, "standard.html.twig", $params);
	return $r;
	//return $r->withJson($params);
	
})->setName('adminRecycle');




$app->get('/admin/sitemap[/]', function (Request $q, Response $r, array $args) {
	
		
	if(!isset($_SESSION['id'])){
		return $r->withStatus(302)->withHeader('Location', ABSPATH.'/admin/login/');
	}
	
	$adminModel = new Admin($this->db);
	$blobModel = new Blob($this->db);
	$validate = new Validator($this->db);

	$langFilter = $validate->asParam($_GET['langFilter']);

	$sitemap = $adminModel->getSitemap(['langFilter'=>$langFilter,'withBlocks'=>true]);
	$blobTypes = $adminModel->getBlobTypeList();
	$defaultBlobTypes = $adminModel->getDefaultBlobsTypeList();

	$siteObject = $blobModel->getSiteProperties();
	
	$params = [
		'ABSPATH'=>ABSPATH,
		'ABSDIR'=>ABSDIR,
		'action'=>'adminSitemap',
		'langFilter'=>$langFilter,
		'blobTypes'=>$blobTypes,
		'defaultBlobTypes'=>$defaultBlobTypes,
		'i18n'=>$adminModel->getTranslations($siteObject['params']['default_language']),
		'sitemap'=>$sitemap,
		'site'=>$siteObject,
		'session'=>$_SESSION
	];
	
	$r = $this->viewAdmin->render($r, "standard.html.twig", $params);
	return $r;
	//return $r->withJson($params);
	
})->setName('adminSitemap');


$app->get('/admin/table/[{page:[0-9+]}/]', function (Request $q, Response $r, array $args) {
	
	if(!isset($_SESSION['id'])){
		return $r->withStatus(302)->withHeader('Location', ABSPATH.'/admin/login/');
	}
	
	$blobModel = new Blob($this->db);
	
	//dashboard paging
	$paged = 50;
	$page = (int)$args['page'] > 0 ? (int)$args['page'] : 1;
	
	//get total of blobs
	$blobCount = $blobModel->getAllBlobs(['admin'=>true,'status'=>[0,1],'onlyCount'=>true]);
	
	//get total of pages
	$pageCount = ceil($blobCount / $paged);
	
	//get blobs for selected page
	$blobs = $blobModel->getAllBlobs(['admin'=>true,'status'=>[0,1],'orderby'=>'id','order'=>'ASC','paged'=>$paged,'page'=>$page]);
	
	$adminModel = new Admin($this->db);
	$siteObject = $blobModel->getSiteProperties();
	
	$params = [
		'ABSPATH'=>ABSPATH,
		'ABSDIR'=>ABSDIR,
		'action'=>'adminTable',
		'blobs'=>$blobs,
		'blobCount'=>$blobCount,
		'i18n'=>$adminModel->getTranslations($siteObject['params']['default_language']),
		'paged'=>$paged,
		'page'=>$page,
		'pageCount'=>$pageCount,
		'session'=>$_SESSION,
		'site'=>$siteObject,
	];
	
	$r = $this->viewAdmin->render($r, "standard.html.twig", $params);
	return $r;
	//return $r->withJson($params);
	
})->setName('adminTable');


$app->get('/admin/uploads[/[{subdir:.*}/]]', function (Request $q, Response $r, array $args) {
	
	if(!isset($_SESSION['id'])){
		return $r->withStatus(302)->withHeader('Location', ABSPATH.'/admin/login/');
	}
	
	$blobModel = new Blob($this->db);
	$mediaModel = new Media($this->db);
	$blobs = $blobModel->getAllBlobs(['admin'=>true]);
	
	$subdir = '/'.$args['subdir'];
	
	//get directory
	$media = $mediaModel->getMedia($subdir);
	$adminModel = new Admin($this->db);
	$siteObject = $blobModel->getSiteProperties();
	
		
	$params = [
		'ABSPATH'=>ABSPATH,
		'ABSDIR'=>ABSDIR,
		'action'=>'adminUploads',
		'blobs'=>$blobs,
		'dir'=>$subdir,
		'i18n'=>$adminModel->getTranslations($siteObject['params']['default_language']),
		'media'=>$media,
		'site'=>$siteObject,
		'session'=>$_SESSION
	];
	$r = $this->viewAdmin->render($r, "standard.html.twig", $params);
	return $r;
	//return $r->withJson($params);
	
})->setName('adminUploads');




/** GET LOGIN FORM **/

$app->get('/admin/login[/]', function (Request $q, Response $r, $args) {
	
	//Check if users are allow to signup
	$blobModel = new Blob($this->db);
	$site = $blobModel->getSiteProperties();
    //Build view
    $params = [
		'ABSDIR'=>ABSDIR,
		'ABSPATH'=>ABSPATH,
		'action'=>'userLogin',
		'site'=>$site,
		'session' => $_SESSION
	];
	
    $r = $this->viewAdmin->render($r, "standard.html.twig", $params);
	return $r;
})->setName('adminLogin');



//Admin API request - Get All Routes (to verify constraint problems)
$app->get('/admin/getAllRoutes[/]', function (Request $q, Response $r, array $args) {
	
	if(!isset($_SESSION['id'])){return $r->withStatus(302)->withHeader('Location', ABSPATH.'/admin/login/');}
	
	$adminModel = new Admin($this->db);
	$allRoutes = $adminModel->getAllRoutes();
    return $r->withJson($allRoutes);
    
})->setName('adminGetAllRoutes');



/** LOG IN **/
$app->post('/admin/login[/]', function (Request $q, Response $r) {
	
	
	//Check if users are allow to signup
	$blobModel = new Blob($this->db);
	$siteParamsQuery = $blobModel->getSiteProperties();
	$siteParams = $siteParamsQuery['params'];
	$allowUserSignup = $siteParams['allowUserSignup'];
	
	
    $post = $q->getParsedBody();
    $adminModel = new Admin($this->db);
    $validate = new Validator($this->db);
    $callback = $validate->asParam($post['callback']);
    $user = $adminModel->login($validate->asParam($post['userAdmin']['login']),$validate->asParam($post['userAdmin']['password']));
    if(!!$user && !$user['error']):
    
    	//Log something somewhere here.
    	$_SESSION['id'] = session_id(); //TO REMOVE ?
    	$_SESSION['login'] = $user['login'];
    	$_SESSION['status'] = 1; //TO REMOVE IN ALL APP
		$_SESSION['params']['level'] = 3; //TO REMOVE IN ALL APP
    	  
    	 
    	if($callback){
			return $r->withStatus(302)->withHeader('Location', ABSPATH.'/'.$callback.'/');
		} else {
			return $r->withStatus(200)->withJson(['status'=>'success','statusText'=>'user_login_success']);
		}
		
		
    elseif($user['error']):
		
		if($post['callbackIfError'] == true){
			
			///// Copy-paste from GET user/login
			//Check if users are allow to signup
			$blobModel = new Blob($this->db);
			$siteParamsQuery = $blobModel->getSiteProperties();
			$siteParams = $siteParamsQuery['params'];
			$allowUserSignup = $siteParams['allowUserSignup'];
			//Build view
			$params = [
				'ABSDIR'=>ABSDIR,
				'ABSPATH'=>ABSPATH,
				'action'=>'userLogin',
				'allowUserSignup'=>$allowUserSignup,
				'session' => $_SESSION,
				'status'=>'warning',
				'statusText'=>'user_login_incorrect_username'
			];
			///// End. TODO: route with $app->map() to avoid this copy/paste.
			
		}else{
			
			return $r->withStatus(200)->withJson(['status'=>'warning','statusText'=>'user_login_incorrect_username']);
			
		}
			
		$r = $this->viewAdmin->render($r, "standard.html.twig", $params);
		return $r;
		
    else:
    
		return $r->withStatus(500)->withJson(['status'=>'error','statusText'=>'user_login_unknown_error']);
		
    endif;
    
    $params = [
		'ABSDIR'=>ABSDIR,
		'ABSPATH'=>ABSPATH,
		'action'=>'userLogin',
		'session'=>$_SESSION
	];
    $r = $this->viewAdmin->render($r, "standard.html.twig", $params);
    
	return $r;
})->setName('adminPostLogin');



/** LOG OUT **/

$app->map(['GET', 'POST'], '/admin/logout[/]', function (Request $q, Response $r) {
	session_destroy();
	unset($_SESSION);
	if($q->getMethod() == "POST"){
		$post = $q->getParsedBody();
		$validate = new Validator();
		$callback = $validate->asURL($post['callback']);
		if(isset($callback)){
		return $r->withStatus(302)->withHeader('Location', $callback);
		}
		return $r->withStatus(200)->withJson(['status'=>'success','statusText'=>'user_logout_success']);
	}else{
		return $r->withStatus(302)->withHeader('Location', ABSPATH);
	}
})->setName('adminLogout');


