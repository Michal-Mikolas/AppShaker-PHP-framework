<?php

namespace AppShaker\Tools;


/**
 * Reflection class
 * Třída pro práci s reflexemi
 *  
 * @author  Michal Mikoláš <xxxObiWan@gmail.com>
 * @package AppShaker 
 */
class Reflection
{
    /**
     * Zjišťuje jednoduchý seznam názvů parametrů funkcí/metod
     * @param mixed $callback
     * @return array          
     */         
    public static function getParameters($callback)
    {
        $reflection = self::reflectionFunctionFactory($callback);
        $parameters = $reflection->getParameters();
        
        foreach($parameters as $key=>$value){
            $parameters[$key] = $parameters[$key]->name;
        }
        
        return $parameters;
    }
    
    /**
     * Vytváří objekt, pro práci s reflexemi podle toho, jaký callback je mu předán
     * @param mixed $callback
     * @return mixed          
     */         
    protected static function reflectionFunctionFactory($callback) 
    {
        if (is_string($callback) && preg_match('#^[^:]+:[^:]+$#', $callback)) {
            $callback = explode(':', $callback);
            $callback[0] = new $callback[0];
        }
        
        if (is_array($callback)) {
            // must be a class method
            list($class, $method) = $callback;
            return new \ReflectionMethod($class, $method);
        }
    
        // class::method syntax
        if (is_string($callback) && strpos($callback, "::") !== false) {
            list($class, $method) = explode("::", $callback);
            return new \ReflectionMethod($class, $method);
        }
    
        // objects as functions (PHP 5.3+)
        if (version_compare(PHP_VERSION, "5.3.0", ">=") && method_exists($callback, "__invoke")) {
            return new \ReflectionMethod($callback, "__invoke");
        }
    
        // assume it's a function
        return new \ReflectionFunction($callback);
    } 
} 