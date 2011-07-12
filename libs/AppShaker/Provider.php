<?php

namespace AppShaker;

include_once __DIR__.'/Routing/Router.php';
include_once __DIR__.'/Routing/IRoute.php';
include_once __DIR__.'/Templating/PresenterFileTemplate.php';
include_once __DIR__.'/Tools/FinderProvider.php';  /** @todo přeřadit jinam, sem se moc nehodí */
include_once __DIR__.'/DIContainer.php';
include_once __DIR__.'/Services/DibiProxy.php';
include_once __DIR__.'/Config.php';
use \AppShaker\Routing\Router, 
    \AppShaker\Routing\UrlRoute,
    \AppShaker\Templating\PresenterFileTemplate, 
    \AppShaker\Tools\FinderProvider, 
    \AppShaker\DIContainer, 
    \AppShaker\Services\DibiProxy,
    \AppShaker\Config;
    
use \Nette\Config\Config as ArrayHash, 
    \Nette\Finder, 
    \Nette\ArrayTools as Arrays, 
    \Nette\Debug as Debugger, 
    \Nette\Environment, 
    \Nette\Loaders\RobotLoader,
    \Nette\Caching\FileStorage;

/**
 * Provider 
 * Zprostředkuje všechny části frameworku pomocí jedné třídy
 *  
 * @author  Michal Mikoláš <xxxObiWan@gmail.com>
 * @package AppShaker    
 */ 
class Provider
{    
    /** 
     * @var array $startups
     * @todo předělat na onStartup[]      
     */
    public $onStartup;
  
    /** @var Application $instance */
    protected static $instance;
    
    /** @var array $args  argumenty, naposledy předané funkci application() */
    protected $args;
    
    /** @var Router $router  kontejner na routy */
    protected $router;
    
    /** @var Container $config */
    protected $config;
    
    /** @var DIContainer $diContainer  kontejner pro (továrničky na) služby */
    protected $diContainer;
    
    
    
    /**
     * Konstruktor 
     * private kvůli singletonu
     */         
    private function __construct()
    {
        // 1) Inicializace třídních proměnných
        $this->args = array();
        $this->router = new Router();
        $this->config = new Container();
        $this->onStartup = array();
        
        
        // 2) Detect application variables
        $this->detectPaths();

        
        // 3) Debugger
        /** 
         * @todo připojit k DebugBaru TimerPanel, StopWatch (nastavit 
         *       jako službu) atd. 
         */
        Debugger::enable(Debugger::DEVELOPMENT, FALSE);
        
        $log_directory = $this->get('tempDir') . '/log';
        if (file_exists($this->get('tempDir')) && !file_exists($log_directory)) {
            if (mkdir($log_directory, 0777)) {
                chmod($log_directory, 0777);
            } else {
                trigger_error("Missing log directory $log_directory", \E_USER_ERROR);
            }
        }
        
        Debugger::$logDirectory = $log_directory;
        
        
        // 4) Registrace základních služeb
        $this->diContainer = new DIContainer();
        
        // Dibi
        $this->diContainer->addService('dibi', function(){
            return DibiProxy::getInstance();
        });
        
        // RobotLoader
        $www_dir = $this->config['wwwDir'];
        $temp_dir = $this->config['tempDir'];
        $this->diContainer->addService('robotloader', function() use ($www_dir, $temp_dir) {
            $loader = new \Nette\Loaders\RobotLoader();
            $loader->addDirectory( $www_dir );
            $loader->ignoreDirs.= ', AppShaker';
            $loader->setCacheStorage( new \Nette\Caching\FileStorage($temp_dir) );
            return $loader;
        });
        $this->diContainer->getService('robotloader')->register();
    }
    
    /**
     * Vrací instanci třídy (Singleton)
     * @return Provider
     */         
    public static function getInstance()
    {
        // Vytvoření instance
        if (self::$instance==NULL) {
            self::$instance = new self(); 
        }
        
        return self::$instance;
    }
    
    /**
     * Slouží k předání, nebo získání parametrů
     * @param array $args     
     * @return array
     */         
    public function args(array $args = NULL)
    {
        if (is_array($args)) 
            $this->args = $args;
            
        return $this->args;
    }
    
    /**
     * Automatická detekce cest v aplikaci
     * @return void     
     */
    protected function detectPaths()
    {
        // wwwDir
        $www_dir = preg_replace('#[\\/][^\\/]*$#', '', $_SERVER['SCRIPT_FILENAME']);
        $this->set('wwwDir', $www_dir);
        
        Environment::setVariable('wwwDir', $this->get('wwwDir'));
        
        // tempDir
        $temp_dir = $this->get('wwwDir') . '/temp';
        $this->set('tempDir', $temp_dir);
        
        if (file_exists($this->get('tempDir'))) {
            if (!is_writable($this->get('tempDir'))) {
                trigger_error("Temp directory ".$this->get('tempDir')." isn't writable", \E_USER_ERROR);
            }
        } else {
            if (@mkdir($this->get('tempDir'), 0777)) {
                chmod($this->get('tempDir'), 0777);
            } else {
                trigger_error("Missing temp directory ".$this->get('tempDir')."", \E_USER_ERROR);
            }
        }
        
        Environment::setVariable('tempDir', $this->get('tempDir'));
        
        //basePath
        if (isset($_GET['route'])) {
            $route_regexp = preg_quote(@$_GET['route'], '#');
            $base_path = preg_replace("#$route_regexp.*$#", '', $_SERVER['REQUEST_URI']);
        } else {
            $base_path = $_SERVER['REQUEST_URI'];
        }
        $base_path = rtrim($base_path, '/');
        $this->set('basePath', $base_path);
    }
    
    /**
     * Automatická detekce proměnných aplikace
     * @return void     
     */
    protected function detectVariables()
    {
        // basePath
        $base_path;
        
        // mod_rewrite
        $mod_rewrite = FALSE;
        if (function_exists('apache_get_modules')) {
          $modules = apache_get_modules();
          $mod_rewrite = in_array('mod_rewrite', $modules);
        }
        $this->set('mod_rewrite', $mod_rewrite);
    }                  
    
    

    /********************* User API - Config *********************/
    /**
     * Přidává prostředí, využívané v konfiguračním souboru   
     * @param string $name
     * @param string $parent
     * @return void 
     * 
     * @todo domyslet návrh téhle funkce                   
     * @todo možná tahle funkce nebude ani třeba     
     */         
    public function environment($name, $parent)
    {
        throw new \Exception("Function `Application:environment()` isn't implemented yet");
    }
    
    /**
     * Nastavení / změna hodnoty v konfiguraci aplikace    
     * @param string $key
     * @param string $value
     * @return void     
     */         
    public function set($key, $value)
    {
        $this->config[$key] = $value;
    }
    
    /**
     * Vrací hodnotu z nastavení aplikace    
     * @param string $key
     * @return mixed     
     */         
    public function get($key)
    {
        if (isset($this->config[$key]))
            return $this->config[$key];
        else 
            return NULL;
    }
    
    /**
     * Přetížená metoda __get
     * @param string $key
     * @return mixed          
     */         
    public function __get($key)
    {
        return $this->get($key);
    }
    
    /**
     * Přetížená metoda __set
     * @param string $key
     * @param mixed $value
     * @return void               
     */
    public function __set($key, $value)
    {
        return $this->set($key, $value);
    } 
    
    /**
     *
     */
    public function loadConfig($path)
    {
        /** 
         * @todo předělat na systém, ve kterém bude možno používat plného 
         *        rozhraní Findera: 
         *        app()->include('*.php')->from('app')->size('>', 1000)
         *        ...předá se objekt AppShaker\Tools\Includator :-) 
         *        P.S. možná je to blbost a nepůjde to, každopádně se podívat 
         *        na register_shutdown_function(), jestli by se nedala použít                           
         */
        $files = Finder::findFiles($path)->from($this->get('wwwDir'));
        
        /** @todo má tenhle příkaz cenu? neřadí už to Finder automaticky? */
        @sort($files);
        
        /** @todo automatická detekce prostředí? */
        foreach($files as $file) {
            $config = Config::fromFile($file);
            $this->config = Container::fromArray( Arrays::mergeTree($this->config->toArray(), $config) );
        }
    }
    
    
    
    /********************* User API - Filesystem *********************/
    /**
     * Načte PHP soubory, odpovídající zadanému názvu / masce    
     * @return void     
     */
    public function include_()
    {
        $finder_provider = new FinderProvider();
        $finder_provider = call_user_func_array(array($finder_provider, 'findFiles'), $this->args);
        return $finder_provider->include();
    }
    
    /**
     * Zprostředkovává nalezení souborů a adresářů
     * @return FinderProvider     
     */
    public function find()
    {
        $finder_provider = new FinderProvider();
        return call_user_func_array(array($finder_provider, 'find'), $this->args);
    }
    
    /**
     * Zprostředkovává nalezení souborů
     * @return FinderProvider     
     */
    public function findFiles()
    {
        $finder_provider = new FinderProvider();
        return call_user_func_array(array($finder_provider, 'findFiles'), $this->args);
    }
    
    /**
     * Zprostředkovává nalezení adresářů
     * @return FinderProvider     
     */
    public function findDirectories()
    {
        $finder_provider = new FinderProvider();
        return call_user_func_array(array($finder_provider, 'findDirectories'), $this->args);
    }   
                
    /**
     * Zprostředkovaná metoda in() FinderProvideru
     * @return FinderProvider     
     */
    public function in()
    {
        $args = func_get_args();
        
        $finder_provider = new FinderProvider();
        return call_user_func_array(array($finder_provider, 'in'), $args);
    } 
    
    /**
     * Zprostředkovaná metoda from() FinderProvideru
     * @return FinderProvider     
     */
    public function from()
    {
        $args = func_get_args();
        
        $finder_provider = new FinderProvider();
        return call_user_func_array(array($finder_provider, 'from'), $args);
    } 
    
    /**
     * Zprostředkovaná metoda childFirst() FinderProvideru
     * @return FinderProvider     
     */
    public function childFirst()
    {
        $finder_provider = new FinderProvider();
        return $finder_provider->childFirst();
    }
    
    /**
     * Zprostředkovaná metoda exclude() FinderProvideru
     * @return FinderProvider     
     */
    public function exclude()
    {
        $args = func_get_args();
        
        $finder_provider = new FinderProvider();
        return call_user_func_array(array($finder_provider, 'exclude'), $args);
    }
    
    /**
     * Zprostředkovaná metoda filter() FinderProvideru
     * @return FinderProvider     
     */
    public function filter($callback)
    {
        $finder_provider = new FinderProvider();
        return $finder_provider->callback($callback);
    }
    
    /**
     * Zprostředkovaná metoda limitDepth() FinderProvideru
     * @return FinderProvider     
     */
    public function limitDepth($limit)
    {
        $finder_provider = new FinderProvider();
        return $finder_provider->limitDepth($limit);
    }
    
    /**
     * Zprostředkovaná metoda size() FinderProvideru
     * @return FinderProvider     
     */
    public function size($operator, $size = NULL)
    {
        $finder_provider = new FinderProvider();
        return $finder_provider->size($operator, $size);
    }
    
    /**
     * Zprostředkovaná metoda date() FinderProvideru
     * @return FinderProvider     
     */
    public function date($operator, $date = NULL)
    {
        $finder_provider = new FinderProvider();
        return $finder_provider->date($operator, $date);
    }                 
    


    /********************* User API - Routování *********************/
    /**
     * Zkontroluje, jestli zadaný řetězec vyhovuje aktuálnímu požadavku. 
     * Pokud ano, zavolá zadaný callback. 
     * @param mixed $callback             
     * @return void
     */         
    public function match($callback)
    {
        $this->router->add($this->args[0], $callback, @$this->args[1]);
    }
    
    /**
     * Generuje odkaz za pomoci registrovaných rout
     * - relativní odkaz vzhledem k basePath, pokud je ve tvaru: url://cokoliv
     * - absolutní odkaz, pokud je ve tvaru: (http|https)://cokoliv
     * - relativní odkaz tvořený routou, pokud je ve tvaru: id_routy, args=NULL
     * @param string $key  relativní|absolutní url, nebo id routy
     * @param array $args
     * @return string                               
     */         
    public function link($key, $args = array())
    {
    }
    
    /**
     * Zavolá vybranou funkci/metodu
     * @param mixed $callback
     * @param array $args
     * @return mixed
     * 
     * @todo zvážit volání pomocí app('helloWorld')->call()                         
     */         
    public function call($callback, $args = array())
    {
        if (is_string($callback) && preg_match('#^[^:]+:[^:]+$#', $callback)) {
            $callback_array = explode(':', $callback);
            $object = new $callback_array[0];
            $method = $callback_array[1];
            return call_user_func_array(array($object, $method), $args);
        } else {
            return call_user_func_array($callback, $args);
        }
    }
    
    /**
     * Nastavení, nebo získání parametrů 
     *      (většinou z URL, v případě CLI - z příkazové řádky)
     * @param string $name
     * @param mixed $value
     * @return mixed               
     */
    public function param($name, $value = NULL)
    {
        /** 
         * @todo doplnit do Router:match nastavení všech parametrů, 
         *       které v požadavku přišly (které *Route vrátil v getArgs())
         */
        if (!is_null($value)) $_GET[$name] = $value;
        
        return @$_GET[$name];
    }
    
    /**
     * @param string $key  klíč routy, nebo url adresa pro přesměrování
     *         
     * @todo dodělat
     */
    public function redirect($key, $args){
    }
    
    /**
     * @todo dodělat
     */
    public function isActive($key, $args){
    }                               
    
    
    
    /********************* User API - Služby *********************/
    /**
     * Zprostředkuje přístup ke službám
     * @param string $name
     * @param mixed $service
     * @return mixed               
     */         
    public function service($name, $service = NULL)
    {
        if (!is_null($service)) {
            $this->diContainer->addService($name, $service);
        } else {
            return $this->diContainer->getService($name);
        }
    }
    
    
    
    /********************* User API - Ostatní *********************/
    /**
     * Spustí celou aplikaci
     * @return void
     * 
     * @todo podívat se, jestli by nešla použít funkce register_shutdown_function()               
     */
    public function run()
    {
        // Nalezneme souhlasící routu
        $callback = $this->router->match();
        
        // Zavolání všech $this->startups
        foreach($this->onStartup as $startup){
            $this->call($startup);
        }
        
        // Zavoláme callback souhlasící routy
        if ($callback[0]) 
            $this->call($callback[0], $callback[1]);
    }         
    
    /**
     * Vytváří šablonu ze zadaného souboru
     * @param string|array $files
     * @return PresenterFileTemplate     
     */         
    public function template($files = NULL)
    {
        // Výběr souboru
        $file = $files;
        
        if (is_array($files)) {
            foreach($files as $value){
                if (file_exists($this->get('wwwDir') . "/$value")) {
                    $file = $value;
                    break;
                }
            }
        }
        
        // Vytvoření objektu šablony
        $template = new PresenterFileTemplate($this->get('wwwDir') . "/$file");
        
        // Vrácení nové šablony
        return $template;
    }
    
    /**
     * Magická metoda __call(), v současné době používaná jen pro volání 
     *      Application:include().
     * @param string $name
     * @param array $args
     * @return void               
     */         
    public function __call($name, $args)
    {
        if ($name=='include') 
            return call_user_func_array(callback($this, 'include_'), $args);
        else
            throw new \Exception("Method \AppShaker\Application:$name() doesn't exists");
    }
      
}