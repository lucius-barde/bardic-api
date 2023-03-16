<?php 
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

$app->get('/metalarch/getBand/{id}[/]', function (Request $q, Response $r, array $args) {
	
	$id = (int)$args['id'];
	if(!$id){return $r->withStatus(400)->withJson(['status'=>'error','statusText'=>'Bad request (invalid ID)']);}

	$metalArchModel = new MetalArch();
	$bandRequest = $metalArchModel->getBand($id);
	if($bandRequest['status'] == "error"){
		return $r->withStatus($bandRequest['statusCode'])->withJson($bandRequest);
	}else{
		return $r->withStatus(200)->withJson($bandRequest);
	}
	
})->setName('metalArchGetBand');

$app->get('/metalarch/getBandLocalData/{id}[/]', function (Request $q, Response $r, array $args) {
	
	$id = (int)$args['id'];
	$onlyLocalBandRequests = true;
	if(!$id){return $r->withStatus(400)->withJson(['status'=>'error','statusText'=>'Bad request (invalid ID)']);}

	$metalArchModel = new MetalArch();
	$bandRequest = $metalArchModel->getBand($id,$onlyLocalBandRequests);

	if($bandRequest['status'] == "error"){
		return $r->withStatus($bandRequest['statusCode'])->withJson($bandRequest);
	}else{
		return $r->withStatus(200)->withJson($bandRequest);
	}
	
})->setName('metalArchGetBandLocalData');

$app->get('/metalarch/getLatestBands/{number}[/]', function (Request $q, Response $r, array $args) {

	$number = (int)$args['number'];
	if(!$number){return $r->withStatus(400)->withJson(['status'=>'error','statusText'=>'Bad request (invalid number)']);}

	$cachedir = ABSDIR.'/cache/metalarch/';
	$bands = [];
	$metalArchModel = new MetalArch();
	$onlyLocalBandRequests = true;


	if ($handleCacheFolder = opendir($cachedir)) {
		while (false !== ($entry = readdir($handleCacheFolder))) {
			if(is_dir($cachedir.'/'.$entry) and !in_array($entry,['.','..'])){
				if ($handleBandFolder = opendir($cachedir.'/'.$entry)) {
					while (false !== ($bandEntry = readdir($handleBandFolder))) {
						$pathinfo = pathinfo($bandEntry);
						if(is_file($cachedir.'/'.$entry.'/'.$bandEntry) and $pathinfo['extension'] == "txt"){
							$tmpBandData = $metalArchModel->getBand($entry,$onlyLocalBandRequests);
							if(!!$tmpBandData['name']){
								$bands[$entry] = [];
								$bands[$entry]['id'] = $entry; //needed, because key disappears later when using usort()
								$bands[$entry]['fetchedDateDiff'] = $tmpBandData['params']['fetchDateDiff'];
								$bands[$entry]['data'] = $tmpBandData;
							}
						}
					}
					closedir($handleBandFolder);
				}

			}
		}
		closedir($handleCacheFolder);
	}

	//sort by suboption "edited"
	usort($bands, function ($a, $b) {
		return $a['fetchedDateDiff'] <=> $b['fetchedDateDiff']; //$a <=> $b = ASC, $b <=> $a = DESC
	});
	
	$bands = array_slice($bands, 0, $number);

	return $r->withStatus(200)->withJson($bands);

})->setName('metalArchGetLatestBands');


$app->get('/metalarch/getAllBands[/]', function (Request $q, Response $r, array $args) {

	$cachedir = ABSDIR.'/cache/metalarch/';
	$bands = [];
	$metalArchModel = new MetalArch();
	$onlyLocalBandRequests = true;


	if ($handleCacheFolder = opendir($cachedir)) {
		while (false !== ($entry = readdir($handleCacheFolder))) {
			if(is_dir($cachedir.'/'.$entry) and !in_array($entry,['.','..'])){
				if ($handleBandFolder = opendir($cachedir.'/'.$entry)) {
					while (false !== ($bandEntry = readdir($handleBandFolder))) {
						$pathinfo = pathinfo($bandEntry);
						if(is_file($cachedir.'/'.$entry.'/'.$bandEntry) and $pathinfo['extension'] == "txt"){
							$tmpBandData = $metalArchModel->getBand($entry,$onlyLocalBandRequests);
							if(!!$tmpBandData['name']){
								$bands[$entry] = [];
								$bands[$entry]['id'] = $entry; //needed, because key disappears later when using usort()
								$bands[$entry]['fetchedDateDiff'] = $tmpBandData['params']['fetchDateDiff'];
								$bands[$entry]['data'] = $tmpBandData;
							}
						}
					}
					closedir($handleBandFolder);
				}

			}
		}
		closedir($handleCacheFolder);
	}

	//sort by suboption "edited"
	usort($bands, function ($a, $b) {
		return $a['data']['name'] <=> $b['data']['name']; //$a <=> $b = ASC, $b <=> $a = DESC
	});
	

	return $r->withStatus(200)->withJson($bands);

})->setName('metalArchGetAllBands');


$app->get('/metalarch/search/byName/{name}[/]', function (Request $q, Response $r, array $args) {

	$validate = new Validator($this->db);
	//$post = $q->getParsedBody();
	//$name = $validate->asString($post['name']);
	$name = $validate->asString($args['name']);
	if(!$name){return $r->withStatus(400)->withJson(['status'=>'error','statusText'=>'Bad request (invalid search term)']);}

	$cachedir = ABSDIR.'/cache/metalarch/';
	$bands = [];
	$metalArchModel = new MetalArch();
	$onlyLocalBandRequests = true;
	
	//copy pasted from getLatestBands
	if ($handleCacheFolder = opendir($cachedir)) {
		while (false !== ($entry = readdir($handleCacheFolder))) {
			if(is_dir($cachedir.'/'.$entry) and !in_array($entry,['.','..'])){
				if ($handleBandFolder = opendir($cachedir.'/'.$entry)) {
					while (false !== ($bandEntry = readdir($handleBandFolder))) {
						$pathinfo = pathinfo($bandEntry);
						if(is_file($cachedir.'/'.$entry.'/'.$bandEntry) and $pathinfo['extension'] == "txt"){
							
							$tmpBandData = $metalArchModel->getBand($entry,$onlyLocalBandRequests);
							if(
								$tmpBandData['name'] == $name ||
								strtolower($tmpBandData['name']) == strtolower($name) ||
								strtoupper($tmpBandData['name']) == strtoupper($name) ||
								ucfirst($tmpBandData['name']) == ucfirst($name) ||
								ucwords($tmpBandData['name']) == ucwords($name) ||
								soundex($tmpBandData['name']) == soundex($name) ||
								metaphone($tmpBandData['name']) == metaphone($name)

							){
								$bands[$entry] = [];
								$bands[$entry]['id'] = $entry;
								$bands[$entry]['data'] = $tmpBandData;
							}
						}
					}
					closedir($handleBandFolder);
				}

			}
		}
		closedir($handleCacheFolder);
	}
	
	//sort by suboption "id"
	usort($bands, function ($a, $b) {
		return $a['id'] <=> $b['id']; //$a <=> $b = ASC, $b <=> $a = DESC
	});

	return $r->withStatus(200)->withJson($bands);

})->setName('metalArchSearchByName');