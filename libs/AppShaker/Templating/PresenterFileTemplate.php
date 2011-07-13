<?php

namespace AppShaker\Templating;

use \AppShaker\Container;

use \Nette\Json,
    \Nette\Application\Presenter,  
    \Nette\Templates\FileTemplate, 
    \Nette\Application\IControl;



/** 
 * ZeroPresenter class
 * Nulový / prázdný presenter, existuje jen proto, že Nette Presenter je abstract
 * 
 * @author  Michal Mikoláš <xxxObiWan@gmail.com> 
 * @package AppShaker
 */
class ZeroPresenter extends Presenter {}



/**
 * PresenterFileTemplate
 * Mírně upravená verze Nette šablon pro samostatné používání bez MVC. 
 * PresenterFileTemplate je vlastně spojením presenteru a šablony :-)
 * 
 * @author  Michal Mikoláš <xxxObiWan@gmail.com> 
 * @package AppShaker
 * 
 * @todo tuším, že pro novou verzi Nette půjde PresenterFileTemplate 
 *      napsat daleko jednodušeji
 */
class PresenterFileTemplate implements \ArrayAccess
{
    /** @var stdClass $payload  úložiště pro obsah snippetů*/
    protected $payload;
    
    /** @var ZeroPresenter $presenter  presenter, na který přes __call() přesměrovávám některé požadavky */
    protected $presenter;
    
    /** @var FileTemplate $template  načtená šablona */
    protected $template;
    
    /** @var array $statics  úložiště pro hodnoty společné všem šablonám */
    protected static $statics;
    
    
    /**
     * Konstruktor
     * @var string $file  cesta k šabloně     
     */         
    public function __construct($file = NULL)
    {
        // Payload
        $this->payload = new \stdClass();
        
        // Presenter & Control
        $this->presenter = new ZeroPresenter();
        
        // Template
        $file = rtrim($file, '/\\');
        if ($file and $file !== app()->get('wwwDir')) {
            $this->template = new FileTemplate($file);        
            
            $this->template->control = $this;   //'$this' sice není Presenter, ale pomocí __call() předává požadavky na $this->presenter
            $this->template->presenter = $this;
            
            $this->template->registerFilter(new \Nette\Templates\LatteFilter);
            $this->template->registerHelper('escape', '\Nette\Templates\TemplateHelpers::escapeHtml');
            $this->template->registerHelper('escapeJs', '\Nette\Templates\TemplateHelpers::escapeJs');
            $this->template->registerHelper('escapeCss', '\Nette\Templates\TemplateHelpers::escapeCss');
    
            $this->template->registerHelper('escape', '\Nette\Templates\TemplateHelpers::escapeHtml');
            $this->template->registerHelper('escapeUrl', 'rawurlencode');
            $this->template->registerHelper('stripTags', 'strip_tags');
            $this->template->registerHelper('nl2br', 'nl2br');
            $this->template->registerHelper('substr', 'iconv_substr');
            $this->template->registerHelper('repeat', 'str_repeat');
            $this->template->registerHelper('replaceRE', '\Nette\String::replace');
            $this->template->registerHelper('implode', 'implode');
            $this->template->registerHelper('number', 'number_format');
            $this->template->registerHelperLoader('Nette\Templates\TemplateHelpers::loader');
    
            $this->template->registerHelper('ucfirst', function($text){ return ucfirst($text); });
            $this->template->registerHelper('lcfirst', function($text){ return lcfirst($text); });
        }
        else $this->template = NULL;
        
        // Statics
        if (!is_array(self::$statics)) { 
            self::$statics = array(
                'controls' => array(),
                'variables' => array(), 
            );
        }
    }
    
    
    /********************* Šablonové metody *********************/
    /**
     * Uložení hodnoty do šablony
     * @param string $name
     * @param mixed $value
     * @return PresenterFileTemplate              
     */         
    public function set($name, $value)
    {
        if ($this->template) {
            $this->template->$name = $value;
        } else {
            self::$statics['variables'][$name] = $value;
        }
        
        return $this;
    }
    
    /**
     * Uložení hodnoty do šablony
     * @param string $name
     * @param mixed $value
     * @return void              
     */  
    public function __set($name, $value)
    {
        $this->set($name, $value);
    }
    
    /**
     * Získání hodnoty šablonové proměnné
     * @param string $name
     * @return mixed              
     */  
    public function __get($name)
    {
        return $this->template->__get($name);
    }
    
    /**
     * Vykreslí šablonu, při ajaxovém požadavku pošle na výstup JSON 
     *      s obsahem invalidovaných snippetů
     * @return void     
     */
    public function render()
    {
        // Default detected variables
        $this->template->basePath = app()->basePath;
        
        // Flash messages
        $this->template->flashes = array();
        if (isset($_SESSION['AppShaker']['flashes'])) {
            foreach($_SESSION['AppShaker']['flashes'] as $key=>$flash){
                if ($flash->expires < time()) {
                    unset($_SESSION['AppShaker']['flashes'][$key]);
                } else {
                    $this->template->flashes[] = $flash;
                }
            }
        }        
        
        // "Static" variables and controls
        foreach(self::$statics['variables'] as $key=>$value){
            if (!isset($this->template->$key)) {
                $this->template->$key = $value;
            }
        }
        foreach(self::$statics['controls'] as $key=>$value){
            if (!isset($this->template[$key])) {
                $this->template[$key] = $value;
            }
        }
        
        // Render
        $this->template->render();
        
        // Snippets support
        if ($this->isAjax()) {
            echo Json::encode($this->payload);
        }
    }

    /**
     * Vrací obsah šablony tak, jak by se vykreslila metodou render()
     * @return string     
     */         
    public function __toString()
    {
        try{
            ob_start();
            $this->render();
            $output = ob_get_clean();
        } catch (\Exception $e) {
            ob_start();
            dump($e);
            $output = ob_get_clean();
        }
        
        return $output;
    }
    
    
    /********************* Upravené metody Presenteru *********************/
    /**
     * Přetížená metoda z Nette Presenteru    
     * @return PresenterFileTemplate
     */         
    public function getPresenter() 
    { 
        return $this; 
    }
    
    /**
     * Přetížená metoda z Nette Presenteru    
     * @return PresenterFileTemplate
     */   
    public function getControl() 
    { 
        return $this; 
    }
    
    /**
     * Přetížená metoda z Nette Presenteru    
     * @return stdClass
     */   
    public function getPayload() 
    { 
        return $this->payload; 
    }
    
    /**
     * Zprostředkovává přístup ke komponentám / widgetům
     * @param string $name     
     * @return IControl
     */         
    public function offsetGet($name)
    {
        if ($this->presenter[$name])
            return $this->presenter[$name];
        else
            return self::$static['controls'][$name];
    }
    
    /**
     * Zprostředkovává přístup ke komponentám / widgetům
     * @param string $name
     * @param IControl $value
     * @return void         
     */         
    public function offsetSet($name, $value)
    {
        
        return $this->presenter[$name] = $value;
    }
    
    /**
     * Zprostředkovává přístup ke komponentám / widgetům
     * @param string $name
     * @return bool     
     */         
    public function offsetExists($name)
    {
        return isset($this->presenter[$name]);
    }
    
    /**
     * Zprostředkovává přístup ke komponentám / widgetům
     * @param string $name
     * @return void     
     */         
    public function offsetUnset($name)
    {
        unset($this->presenter[$name]);
    }
    
    /**
     * Náhrada za původní Nette\Application\UI\Control::flashMessage()
     * @param string $message
     * @param string $type
     * @return void               
     */
    public function flashMessage($message, $type = 'info')
    {
        $flash = new Container();
        $flash->message = $message;
        $flash->type = $type;
        $flash->expires = time() + 5;
        
        $_SESSION['AppShaker']['flashes'][] = $flash;
    }                   
    
    
    /********************* Původní metody Presenteru *********************/
    /**
     * Volání metod Nette Presenteru
     * @return mixed
     */
    public function __call($name, $args)
    {
        return call_user_func_array(callback($this->presenter, $name), $args);
    }
} 
 