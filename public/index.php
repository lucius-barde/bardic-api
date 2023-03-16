<?php
/* Config and constants */
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, X-Requested-With");

ini_set('display_errors',0); 

if(is_file('vendor/autoload.php')){
    require_once 'vendor/autoload.php';
}else{
    echo '<h1>Oppidum CMS V3 setup - 1/2</h1>';
    echo '<p>Welcome to the OppidumCMS installation page.</p>';
    echo '<p>First step, the "vendor" folder is missing, please run "<code>composer install</code>" in the "public" folder to install all the dependencies.</p>';
    echo '<p>Then reload this page.</p>';
    exit;
};

if(is_file('config.php')){
    require_once 'config.php';
}else{
    echo '<h1>Oppidum CMS V3 setup - 2/2</h1>';
    echo '<p>The dependencies were successfully loaded.</p>';
    echo '<p>Now, copy-paste the file <b>config.sample.php</b> to <b>config.php.</b></p>';
    echo '<p>Set up your configuration, and <b>copy/paste the database queries</b> from the config file into your SQL database.</p>';
    echo '<p>Then reload this page.</p>';
    exit;
}
session_start();
define("ABSPATH", $config['abspath']);
define("ABSDIR", $config['absdir']);
define("TBL", $config['db']['tbl']);
define("TEMPLATE_DIR", $config['template_dir']);
define("TEMPLATE_ADMIN_DIR", $config['template_admin_dir']);
define("RECAPTCHA_V3_SITE_KEY", $config['recaptcha']['v3_site_key']);

/* Init App */
$app = new \Slim\App(["settings" => $config]);
$container = $app->getContainer();

/* Init Database */
$container['db'] = function ($c) {
    $db = $c['settings']['db'];
    
    try{
    $pdo = new PDO("mysql:host=" . $db['host'] . ";dbname=" . $db['dbname'] . ";charset=utf8mb4",$db['user'], $db['pass']);
	} catch(Exception $e){

	    //echo 'pdo 1 null, trying pdo 2; ';
        
        try{
            $pdo = new PDO("mysql:host=" . $db['host2'] . ";dbname=" . $db['dbname'] . ";charset=utf8mb4",$db['user'], $db['pass']);
        } catch(Exception $e){

            //echo 'pdo 2 null, trying pdo 3; ';
            
            try{
                $pdo = new PDO("mysql:host=" . $db['host3'] . ";dbname=" . $db['dbname'] . ";charset=utf8mb4",$db['user'], $db['pass']);
            } catch(Exception $e){
                echo '<h1>Oops, an error occured !</h1>';
                echo '<p>Database connection failed. PDO: '.var_export($pdo,true).', db.host: '.$db['host'].'</p>';
                echo $e->getMessage();
            }
        }
	}
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    return $pdo;
};

/* Init Template Engine */

$container['view'] = function ($container) {
    $view = new \Slim\Views\Twig(TEMPLATE_DIR, [
        'cache' => false,
        'debug' => true
    ]);

    // Instantiate and add Slim specific extension
    $router = $container->get('router');
    $uri = \Slim\Http\Uri::createFromEnvironment(new \Slim\Http\Environment($_SERVER));
	$view->addExtension(new HelloNico\Twig\DumpExtension());
    //$view->addExtension(new \Slim\Views\TwigExtension($router, $uri));
	//$view->addExtension(new Twig_Extension_Debug());
	//$view->addExtension(new Twig_Extensions_Extension_Text());
    return $view;
};

$container['viewAdmin'] = function ($container) {
    $view = new \Slim\Views\Twig(TEMPLATE_ADMIN_DIR, [
        'cache' => false,
        'debug' => true
    ]);

    // Instantiate and add Slim specific extension
    $router = $container->get('router');
    $uri = \Slim\Http\Uri::createFromEnvironment(new \Slim\Http\Environment($_SERVER));
	$view->addExtension(new HelloNico\Twig\DumpExtension());
    //$view->addExtension(new \Slim\Views\TwigExtension($router, $uri));
	//$view->addExtension(new Twig_Extension_Debug());
	//$view->addExtension(new Twig_Extensions_Extension_Text());
    return $view;
};

/********* Models **********/

//Core models
require_once 'classes/Admin.php';
require_once 'classes/Blob.php';
require_once 'classes/Media.php';
require_once 'classes/Page.php';
require_once 'classes/Validator.php';

//Custom models
require_once 'classes/MetalArch.php';
require_once 'classes/TopioSearch.php';


/********* Controllers *********/

//Core controllers
require_once 'controllers/AdminController.php';
require_once 'controllers/BlobController.php';
require_once 'controllers/MediaController.php';
require_once 'controllers/PageController.php';

//Custom controllers
require_once 'controllers/MetalArchController.php';
require_once 'controllers/TopioSearchController.php';


/* Run App */
$app->run();
