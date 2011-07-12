<?php

namespace AppShaker\Templating;

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
            $this->template->__set($name, $value);
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
        
        $this->template->render();
        
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
        ob_start();
        $this->render();
        $output = ob_get_clean();
        
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
 