<?php
/**
 * User: Amir Aslan Aslani
 * Date: 5/25/18
 * Time: 8:10 PM
 */

namespace Dor;

use Dor\Util\{
    ErrorResponse
};

use Dor\Http\{
    Response, Request
};

use Dor\Router\{
    Router, RouterResponseParameter
};

use Illuminate\Database\Capsule\Manager as CapsuleManager;

class Kernel
{
    public static $twig;
    public static $config;
    public static $capsule;

    private $response;
    private $isOnDebugMode = false;

    public function __construct(Config $config){
        // Load config file.
        Kernel::$config = $config;

        // Setup Twig template engine.
        $loader = new \Twig_Loader_Filesystem($config->getViewDirectory());
        Kernel::$twig = new \Twig_Environment(
            $loader,
            [
                'debug' => $config->getContent()['debug_mode']
            ]
        );
        
        // Check and set debug mode.
        if($config->isOnDebugMode()){
            $this->enableDebugMode();
        }
        else{
            $this->disableDebugMode();
        }

        //Setup Illuminate database if there is database config.
        if($config->getContent()['database'] !== false) {
            $capsule = new CapsuleManager();
            $capsule->addConnection($config->getDatabaseConfig());
            $capsule->setAsGlobal();
            $capsule->bootEloquent();
            Kernel::$capsule = $capsule;
        }

        // Load configs
        foreach (glob($config->getConfigsDirectory() . "/*.php") as $filename) {
            include_once($filename);
        }

        // Load models
        foreach (glob($config->getModelDirectory() . "/*.php") as $filename) {
            include_once($filename);
        }
    }

    private function enableDebugMode(){
        $this->isOnDebugMode = true;

        // Whoops error handler added
        $whoops = new \Whoops\Run;
        $whoops->pushHandler(new \Whoops\Handler\PrettyPageHandler);
        $whoops->register();

        // Show all errors and warnings
        error_reporting(E_ALL);
        ini_set('display_errors', 1);

        Kernel::$twig->addExtension(new \Twig_Extension_Debug());
    }

    private function disableDebugMode(){
        $this->isOnDebugMode = false;

        // Hide all errors and warnings
        error_reporting(0);
        ini_set('display_errors', 0);
    }

    public static function createRequestFromCurrent():Request{
        $request = new Request();
        $request->headers = getallheaders();
        $request->host = $_SERVER['HTTP_HOST'];
        $request->uri = explode('?',$_SERVER['REQUEST_URI'])[0];
        $request->requestType = strtolower($_SERVER['REQUEST_METHOD']);
        $request->get = $_GET;
        $request->post = $_POST;
        $request->file = $_FILES;
        return $request;
    }

    public static function getResponse(Request $req):Response{

        $rrp = new RouterResponseParameter();
        $rrp->add('Dor\Http\Request', $req);
        $rrp->add('Illuminate\Database\Capsule\Manager', Kernel::$capsule);

        $controllerDirectory = Kernel::$config->getControllerDirectory();
        $routesDirectory = Kernel::$config->getRoutesDirectory();

        $router = new Router(
            $req,
            $controllerDirectory,
            $routesDirectory,
            '\\Dor\\Controller\\',
            $rrp
        );

        if($router->iterateOverRoutes())
            return Kernel::responsize($router->getResponse());

        // There is no controller for this URI!
        $noAnyControllerResponse = new Response();
        $noAnyControllerResponse->setStatus(Response::STATUS[404]);
        $noAnyControllerResponse->body = Kernel::$twig->render(
            Kernel::$config->getContent()['app']['404_page'],
            array()
        );
        return $noAnyControllerResponse;
    }

    public function sendResponse(Request $request){
        if($this->isOnDebugMode){
            $this->setInnerResponse($request);
        }
        else{
            try {
                $this->setInnerResponse($request);
            }
            catch (\Exception $exception){
                $this->response = new ErrorResponse($exception);
            }
        }

        foreach ($this->response->headers as $header){
            header($header);
        }

        echo $this->response->body;
    }

    private function setInnerResponse(Request $request){
        $this->response = $this::getResponse(
            $request
        );
    }

    private static function responsize($data):Response{
        if($data instanceof Response)
            return $data;
        else if(is_array($data)){

        }
        
    }
}
