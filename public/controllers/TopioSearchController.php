<?php 
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;



$app->get('/topiosearch/hsuter[/]', function (Request $q, Response $r, array $args) {
	
	$validate = new Validator($this->db);
	$dictionaryModel = new TopioSearch();
    $blobModel = new Blob($this->db);
    
    $keepAccents = true;
	$term = $validate->toFileName($_GET['term'],$keepAccents);
    unset($keepAccents);
    $term = ucfirst($term);

//	$cachefile = ABSDIR.'/cache/topiosearch/'.$term[0].'/topiosearch--hsuter--'.$term.'.json';
  

    $getHsuterWord = $this->db->prepare('SELECT * FROM `api_oppidumweb_public` WHERE url = "topiosearch--hsuter--'.$term.'" AND edited > DATE_SUB(NOW(), INTERVAL 1 MONTH) LIMIT 1;');
    $getHsuterWord->execute();
    $cachefile = $getHsuterWord->fetch();


	if(empty($cachefile)){

        //hsuter remote

        $firstLetter = strtoupper($term[0]);
        
        //firstletter is defined on henrysuter.ch:
        switch($firstLetter){
            case "E": $firstLetter = "D"; break;
            case "G": $firstLetter = "F"; break;
            case "I":
            case "J":
            case "K":
            case "L":
            case "M": $firstLetter = "H"; break;
            case "O":
            case "P": $firstLetter = "N"; break;
            case "R":
            case "S": $firstLetter = "Q"; break;
            case "U":
            case "V":
            case "W":
            case "X":
            case "Y":
            case "Z": $firstLetter = "T"; break;
            
        }
        
        $url = 'http://henrysuter.ch/glossaires/patois'.$firstLetter.'0.html';
            
        //fetch data from website    
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $dictHTML = curl_exec($ch);
        curl_close($ch);     
        $termJSON = $dictionaryModel->parseFileHSuter($dictHTML,$term);

        if(!$termJSON){
            return $r->withStatus(404)->withJson(['status'=>'error', 'term'=>$term, 'url'=>$url,'statusText'=>'This keyword doesn\'t match with any word in the database']);
        }else{


            $urlForApi = 'topiosearch--hsuter--';
            
            $termJSON['params']['copyright'] = '&copy; <a href="http://www.henrysuter.ch">henrysuter.ch</a>';
            $termJSON['params']['origin'] = $url;
            $urlForApi .= $termJSON['params']['term'];
            $termJSON['url'] = $urlForApi;

            if(!is_dir(ABSDIR.'/cache/topiosearch/'.$termJSON['params']['term'][0])){
                mkdir(ABSDIR.'/cache/topiosearch/'.$termJSON['params']['term'][0]);
                if(!is_dir(ABSDIR.'/cache/topiosearch/'.$termJSON['params']['term'][0])){
                    return $r->withStatus(500)->withJson(['status'=>'error','statusText'=>'Unable to create the cache folder, please check the permissions on the server', 'term' => $term]);
                }
            }

            $termJSON['edited'] = date('Y-m-d H:i:s');
            $termJSON['lang'] = "fr";
            $termJSON['parent'] = 0;
            $termJSON['status'] = 1;

            ksort($termJSON);
            ksort($termJSON['params']);

            
            //Save in topio database - Params
            $termJSON['type'] = "topiosearch";
            $termJSON['name'] = $termJSON['url'];
            $termJSON['content'] = $termJSON['params']['term'];

            $insertEntry = $blobModel->addBlob($termJSON); 
            $termJSON['params']['db_insert_callback'] = $insertEntry;
            $termJSON['params']['source'] = "remote";

            
            return $r->withStatus(200)->withJson($termJSON);

        }


    }else{

        //hsuter local
        $localdata = $cachefile;
        if(!!$localdata){
            $localdata['params'] = json_decode($localdata['params'],true);
            $localdata['params']['source'] = 'local';
            $localdata['params']['fetchDateDiff'] = time() - $localdata['edited'];
            return $r->withStatus(200)->withJson($localdata);
        }else{
            return $r->withStatus(500)->withJson(['status'=>'error','statusText'=>'Error: this entry exists in the dictionary, but the content is corrupted.', 'term' => $term]);
        }

    }

	
})->setName('topioSearchHsuter');


$app->get('/topiosearch/hsuternames[/]', function (Request $q, Response $r, array $args) {
	
	$validate = new Validator($this->db);
	$dictionaryModel = new TopioSearch();
    $blobModel = new Blob($this->db);
    
    $keepAccents = true;
	$term = $validate->toFileName($_GET['term'],$keepAccents);
    unset($keepAccents);
    $term = ucfirst($term);
	

//    $cachefile = ABSDIR.'/cache/topiosearch/'.$term[0].'/topiosearch--hsuternames--'.$term.'.json';
	
        
    $getHsuterNamesWord = $this->db->prepare('SELECT * FROM `api_oppidumweb_public` WHERE url = "topiosearch--hsuternames--'.$term.'" AND edited > DATE_SUB(NOW(), INTERVAL 1 MONTH) LIMIT 1;');
    $getHsuterNamesWord->execute();
    $cachefile = $getHsuterNamesWord->fetch();

	if(empty($cachefile)){
       
        //hsuternames remote
        $secondLetter = strtoupper($term[1]);
        $firstLetter = strtoupper($term[0]);
        $firstLetters = strtoupper($term[0]).strtolower($term[1]);
        
        //firstletter is defined on henrysuter.ch:
        if(in_array($firstLetters,['Aa','Ab','Ac','Ad','Ae','Af','Ag'])){$page = 'A0';}
        elseif(in_array($firstLetters,['Ai','Aj','Al','Am','An','Ao','Ap'])){$page = 'A1';}
        elseif(in_array($firstLetters,['Ar','As'])){$page = 'A2';}
        elseif(in_array($firstLetters,['At','Au','Av','Ay','Az'])){$page = 'A3';}
        
        elseif(in_array($firstLetters,['Ba'])){$page = 'B0';}
        elseif(in_array($firstLetters,['Be'])){$page = 'B1';}
        elseif(in_array($firstLetters,['Bi','Bl'])){$page = 'B2';}
        elseif(in_array($firstLetters,['Bo'])){$page = 'B3';}
        elseif(in_array($firstLetters,['Br','Bu'])){$page = 'B4';}

        elseif(in_array($firstLetters,['Ca','Ce'])){$page = 'C0';}
        elseif(in_array($firstLetters,['Ch'])){$page = 'C1';}
        elseif(in_array($firstLetters,['Ci','Cl'])){$page = 'C2';}
        elseif(in_array($firstLetters,['Co'])){$page = 'C3';}
        elseif(in_array($firstLetters,['Cp','Cr','Cu','Cy'])){$page = 'C4';}
        
        elseif(in_array($firstLetters,['Da','De'])){$page = 'D0';}
        elseif(in_array($firstLetters,['Dg','Di','Dj','Do','Dr','Du','Dy','Dz'])){$page = 'D1';}
        
        elseif(in_array($firstLetters,['Ea','Eb','Ec','Ed'])){$page = 'E0';}
        elseif(in_array($firstLetters,['Ef','Eg','Eh','Ei','El','Em','En','Eo','Ep','Eq','Er'])){$page = 'E1';}
        elseif(in_array($firstLetters,['Es','Et','Eu','Ev','Ex','Ey','Ez'])){$page = 'E2';}
        
        elseif(in_array($firstLetters,['Fa','Fe','Fi'])){$page = 'F0';}
        elseif(in_array($firstLetters,['Fl','Fo','Fr','Fu'])){$page = 'F1';}

        elseif(in_array($firstLetters,['Ga','Ge','Gi'])){$page = 'G0';}
        elseif(in_array($firstLetters,['Gl','Go','Gr','Gu','Gy'])){$page = 'G1';}
        
        elseif(in_array($firstLetter,['H'])){$page = 'H0';}

        elseif(in_array($firstLetter,['I'])){$page = 'I0';}
        
        elseif(in_array($firstLetter,['J'])){$page = 'J0';}

        elseif(in_array($firstLetter,['K'])){$page = 'K0';}

        elseif(in_array($firstLetters,['La'])){$page = 'L0';}
        elseif(in_array($firstLetters,['Le','Lh','Li','Lo','Lu','Ly'])){$page = 'L1';}

        elseif(in_array($firstLetters,['Ma'])){$page = 'M0';}
        elseif(in_array($firstLetters,['Me','Mi'])){$page = 'M1';}
        elseif(in_array($firstLetters,['Mo','Mu','My'])){$page = 'M2';}
        
        elseif(in_array($firstLetter,['N'])){$page = 'N0';}

        elseif(in_array($firstLetter,['O'])){$page = 'O0';}
        
        elseif(in_array($firstLetters,['Pa'])){$page = 'P0';}
        elseif(in_array($firstLetters,['Pe','Pf','Ph'])){$page = 'P1';}
        elseif(in_array($firstLetters,['Pi','Pl'])){$page = 'P2';}
        elseif(in_array($firstLetters,['Po','Pr','Pu','Py'])){$page = 'P3';}
        
        elseif(in_array($firstLetter,['Q'])){$page = 'Q0';}
        
        elseif(in_array($firstLetters,['Ra','Rb','Re','Rh'])){$page = 'R0';}
        elseif(in_array($firstLetters,['Ri','Ro','Ru'])){$page = 'R1';}
        
        elseif(in_array($firstLetters,['Sa'])){$page = 'S0';}
        elseif(in_array($firstLetters,['Sc','Se','Si','So','Sp','St','Su','Sy'])){$page = 'S1';}

        elseif(in_array($firstLetters,['Ta','Tc','Te'])){$page = 'T0';}
        elseif(in_array($firstLetters,['Th','Ti','To'])){$page = 'T1';}
        elseif(in_array($firstLetters,['Tr','Ts','Tu','Ty','Tz'])){$page = 'T2';}

        elseif(in_array($firstLetter,['U'])){$page = 'U0';}
        
        elseif(in_array($firstLetters,['Va'])){$page = 'V0';}
        elseif(in_array($firstLetters,['Ve'])){$page = 'V1';}
        elseif(in_array($firstLetters,['Vi','Vo','Vu','Vy'])){$page = 'V2';}
        
        elseif(in_array($firstLetter,['W'])){$page = 'W0';}
        elseif(in_array($firstLetter,['X'])){$page = 'X0';}
        elseif(in_array($firstLetter,['Y'])){$page = 'Y0';}
        elseif(in_array($firstLetter,['Z'])){$page = 'Z0';}


        $url = 'http://henrysuter.ch/glossaires/topo'.$page.'.html';
            
        //fetch data from website    
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $dictHTML = curl_exec($ch);
        curl_close($ch);     
        
        $termJSON = $dictionaryModel->parseFileHSuter($dictHTML,$term);
        
        if(!$termJSON){
            return $r->withStatus(404)->withJson(['status'=>'error', 'term'=>$term, 'url'=>$url,'statusText'=>'This keyword doesn\'t match with any word in the database']);
        }else{


            $urlForApi = 'topiosearch--hsuternames--';
            
            $termJSON['params']['copyright'] = '&copy; <a href="http://www.henrysuter.ch">henrysuter.ch</a>';
            $termJSON['params']['origin'] = $url;
            $urlForApi .= $termJSON['params']['term'];
            $termJSON['url'] = $urlForApi;

            if(!is_dir(ABSDIR.'/cache/topiosearch/'.$termJSON['params']['term'][0])){
                mkdir(ABSDIR.'/cache/topiosearch/'.$termJSON['params']['term'][0]);
                if(!is_dir(ABSDIR.'/cache/topiosearch/'.$termJSON['params']['term'][0])){
                    return $r->withStatus(500)->withJson(['status'=>'error','statusText'=>'Unable to create the cache folder, please check the permissions on the server', 'term' => $term]);
                }
            }
            $termJSON['edited'] = date('Y-m-d H:i:s');
            $termJSON['lang'] = "fr";
            $termJSON['parent'] = 0;
            $termJSON['status'] = 1;

            ksort($termJSON);
            ksort($termJSON['params']);

            
            //Save in topio database - Params
            $termJSON['type'] = "topiosearch";
            $termJSON['name'] = $termJSON['url'];
            $termJSON['content'] = $termJSON['params']['term'];

            $insertEntry = $blobModel->addBlob($termJSON); 
            $termJSON['params']['db_insert_callback'] = $insertEntry;
            $termJSON['params']['source'] = "remote";

            

            return $r->withStatus(200)->withJson($termJSON);
        }

    }else{
        
        //hsuternames local
        $localdata = $cachefile;
        if(!!$localdata){
            $localdata['params'] = json_decode($localdata['params'],true);
            $localdata['params']['source'] = 'local';
            $localdata['params']['fetchDateDiff'] = time() - $localdata['edited'];
            return $r->withStatus(200)->withJson($localdata);
        }else{
            return $r->withStatus(500)->withJson(['status'=>'error','statusText'=>'Error: this entry exists in the dictionary, but the content is corrupted.', 'term' => $term]);
        }


       


    }

        




})->setName('topioSearchHsuterNames');



$app->get('/topiosearch/topio[/]', function (Request $q, Response $r, array $args) {
	
	$validate = new Validator($this->db);
    $blobModel = new Blob($this->db);
	$dictionaryModel = new TopioSearch();
   
    $keepAccents = true;
	$term = $validate->toFileName($_GET['term'],$keepAccents);
    unset($keepAccents);

    $term = str_replace('_', ' ', $term);
    $term = ucfirst($term);
    
    $getTopioWord = $this->db->prepare('SELECT * FROM `api_oppidumweb_public` WHERE url = "topiosearch--topio--'.$term.'" AND edited > DATE_SUB(NOW(), INTERVAL 1 MONTH) LIMIT 1;');
    $getTopioWord->execute();
    $cachefile = $getTopioWord->fetch();
    
    if(empty($cachefile)){
        //topio remote

        $url = 'https://www.topio.ch/dico.php';
        
        //fetch data from website    
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $dictHTML = curl_exec($ch);
        curl_close($ch);     
        
        $termJSON = $dictionaryModel->parseFileTopio($dictHTML,$term);
        
        if($termJSON['status'] == "error"){ 
            return $r->withStatus(404)->withJson(['status'=>'error','url'=>$url,'statusText'=>$termJSON['statusText']]);
        }elseif(!$termJSON){
            return $r->withStatus(404)->withJson(['status'=>'error', 'url'=>$url, 'term'=>$term, 'statusText'=>'This keyword doesn\'t match with any word in the database']);
        }else{
    
            $urlForApi = 'topiosearch--topio--';
            $termJSON['params']['copyright'] = '&copy; <a href="https://www.topio.ch">topio.ch</a>';
            
            $termJSON['params']['origin'] = $url;
            $urlForApi .= $termJSON['params']['term'];
            $termJSON['url'] = $urlForApi;
    
            if(!is_dir(ABSDIR.'/cache/topiosearch/'.$termJSON['params']['term'][0])){
                mkdir(ABSDIR.'/cache/topiosearch/'.$termJSON['params']['term'][0]);
                if(!is_dir(ABSDIR.'/cache/topiosearch/'.$termJSON['params']['term'][0])){
                    return $r->withStatus(500)->withJson(['status'=>'error','statusText'=>'Unable to create the cache folder, please check the permissions on the server', 'term' => $term]);
                }
            }

            $termJSON['edited'] = date('Y-m-d H:i:s');
            $termJSON['lang'] = "fr";
            $termJSON['parent'] = 0;
            $termJSON['status'] = 1;
            
            ksort($termJSON);
            ksort($termJSON['params']);

            
            //Save in topio database - Params
            $termJSON['type'] = "topiosearch";
            $termJSON['name'] = $termJSON['url'];
            $termJSON['content'] = $termJSON['params']['term'];

            $insertEntry = $blobModel->addBlob($termJSON); 
            $termJSON['params']['db_insert_callback'] = $insertEntry;
            $termJSON['params']['source'] = "remote";
    
            return $r->withStatus(200)->withJson($termJSON);
    
        }
    

    }else{

        //topio local
        $localdata = $cachefile;
        if(!!$localdata){
            $localdata['params'] = json_decode($localdata['params'],true);
            $localdata['params']['source'] = 'local';
            $localdata['params']['fetchDateDiff'] = time() - $localdata['edited'];
            return $r->withStatus(200)->withJson($localdata);
        }else{
            return $r->withStatus(500)->withJson(['status'=>'error','statusText'=>'Error: this entry exists in the dictionary, but the content is corrupted.', 'term' => $term]);
        }

    }

    

			

    

	
})->setName('topioSearch');



