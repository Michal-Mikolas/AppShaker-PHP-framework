<?php

namespace AppShaker\Services;


/**
 * @author  Michal Mikoláš <xxxObiWan@gmail.com> 
 * @package AppShaker
 * @see http://forum.nette.org/cs/7504-simpleauthenticator-v-nette2#p56619   
 */
class DibiProxy
{
    /** @var DibiProxy $instance  singleton */
    protected static $instance;
    
    /**
     * Konstruktor
     */         
    private function __construct()
    {
    }
    
    /**
     * Singleton
     * @return DibiProxy     
     */
    public static function getInstance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new DibiProxy();
        }
        
        return self::$instance;
    } 
    
    /**
     * Zprostředkování statických metod Dibi
     * @param string $name
     * @param array $args
     * @return mixed               
     */
    public function __call($name, $args)
    {
        return call_user_func_array("\\Dibi::$name", $args);
    }                 
} 