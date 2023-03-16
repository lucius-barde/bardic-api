<?php 
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

/* PAGE CONTROLLER */
$urlsQueryText .= 'SELECT id, url, parent, lang FROM '.TBL.' WHERE type = "page" AND status > -1 AND url IS NOT NULL AND url != "" ';
$urlsQuery = $container['db']->query($urlsQueryText);


$rewriters = ($urlsQuery->fetchAll());
$blobModel = new Blob($container->db);
$siteParamsQuery = $blobModel->getSiteProperties();
$siteParams = $siteParamsQuery['params'];

foreach($rewriters as $rewriter){
	//If a subpage, generate complete url
	if($rewriter['parent'] != '0'):
	
		$parentPage = array_search($rewriter['parent'], array_column($rewriters, 'id'));
		
		//If is 2nd level page
		if(isset($rewriters[$parentPage]['url'])):
		
			if($rewriters[$parentPage]['parent'] == '0'):
				$rewriter['url'] = $rewriters[$parentPage]['url'] . '/' . $rewriter['url'];
			else:
				//If is a 3rd level page
				$gParentPage = array_search($rewriters[$parentPage]['parent'], array_column($rewriters, 'id'));
				$rewriter['url'] = $rewriters[$gParentPage]['url'] . '/' . $rewriters[$parentPage]['url'] .'/'. $rewriter['url'];
			endif;
		endif;
	endif;
	
	/** MAP URL REWRITINGS **/
	$app->get('/'.$rewriter['lang'].'/'.$rewriter['url'].'[/]', function(Request $q, Response $r, $args){
		//The rewrite logic
        $path = $q->getUri()->getPath();
		$rewritePath = rtrim($path, '/'); //removes last trailing slash
        
        //check if is subpage
		$blobModel = new Blob($this->db);
        $rewritePathArr = array_values(array_filter(explode('/',$rewritePath))); 
        
        //Creates rewritePath according to languages (subpage to control for 3rd levels)
        
     
        $rewritePath = $rewritePathArr[sizeof($rewritePathArr)-1];
        //does the rewrite logic
		$rewriteQuery = $this->db->query('SELECT id FROM '.TBL.' WHERE type = "page" AND status > -1 AND url = "'.$rewritePath.'" LIMIT 1;');
		$rewriteRow = $rewriteQuery->fetch();
		$id = $rewriteRow['id'];
		
		
		//Display Blob (the following code should be the same as in /blob/{id}/)
		$adminModel = new Admin($this->db);
		$validate = new Validator($this->db);
		$pageModel = new Page($this->db);
		$blob = $blobModel->getBlob($id,$lang);
		
		$site = $blobModel->getSiteProperties(); 
			
		if(!!$blob && $blob['type'] == 'page'):
			
			//Redirect to "/" if is home page
			if($pageModel->isHome($blob['id'],$blob['lang']) && $blob['lang'] == $site['params']['default_language']):
				return $r->withStatus(302)->withHeader('Location', ABSPATH);
			endif;
			
			//Redirect to 404 if inactive
			if(!isset($_SESSION['id']) && $blob['status'] < 1):
				$r->getBody()->write("<h3>404 - Not Found</h3>");
				return $r->withStatus(404);
			endif;
			
	
			$params = [ 
				"ABSPATH" => ABSPATH,
				"ABSDIR" => ABSDIR,
				//"RECAPTCHA_V3_SITE_KEY" => RECAPTCHA_V3_SITE_KEY,
				"action" => "read",
				"blob" => $blob,
				"blobModel" => $blobModel,
				"elements" => $pageModel->getPageElements($id),
				//'i18n'=>$adminModel->getTranslations($site['params']['default_language']),
				"isHome" => $pageModel->isHome($blob['id'],$blob['lang']),
				"session" => $_SESSION,
				"site" => $site,
				"sitemap" => $adminModel->getSitemap(),
				"translations" => $pageModel->getTranslations($blob['translation_of']),
				"type" => $blob['type'],
				"validate" => $validate
			];
			
			
			//$r = $this->view->render($r, "standard.html.twig", $params); //display pages in frontend template
			return $r->withJson($params); //display pages in JSON
		else:
			return $r->withStatus(404)->withJson(['status'=>'error','statusText'=>'404 - Page not found']);
		endif;  

	})->setName('page-'.$rewriter['id']);
}


$app->get('/', function(Request $q, Response $r, $args){
	
	$adminModel = new Admin($this->db);
	$validate = new Validator($this->db);
	$pageModel = new Page($this->db);
	$blobModel = new Blob($this->db);

	$site = $blobModel->getSiteProperties(); 
	
	$blob = $pageModel->getHomePage(true,$site['params']['default_language']);
	$id = $blob['id'];
	

	if(!!$blob && $blob['type'] == 'page'):
		
		//Redirect to 404 if inactive
		if(!isset($_SESSION['id']) && $blob['status'] < 1):
			$r->getBody()->write("<h3>404 - Not Found</h3>");
			return $r->withStatus(404);
		endif;
		

		$params = [ 
			"ABSPATH" => ABSPATH,
			"ABSDIR" => ABSDIR,
			//"RECAPTCHA_V3_SITE_KEY" => RECAPTCHA_V3_SITE_KEY,
			"action" => "read",
			"blob" => $blob,
			"blobModel" => $blobModel,
			"elements" => $pageModel->getPageElements($id),
			//'i18n'=>$adminModel->getTranslations($site['params']['default_language']),
			"isHome" => true,
			"session" => $_SESSION,
			"site" => $site,
			"sitemap" => $adminModel->getSitemap(),
			"translations" => $pageModel->getTranslations($blob['translation_of']),
			"type" => $blob['type'],
			"validate" => $validate
		];
		//$r = $this->view->render($r, "standard.html.twig", $params); //display front page in frontend template
		return $r->withJson($params);  //display front page in JSON
	else:
		return $r->withStatus(404)->withJson(['status'=>'error','statusText'=>'404 - Page not found']);
	endif;  
	
})->setName('homePage');
