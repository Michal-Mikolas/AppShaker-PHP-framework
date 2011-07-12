<?php

namespace AppShaker\Routing;

include_once __DIR__.'/../Container.php';
include_once __DIR__.'/../Routing/URLRoute.php';
include_once __DIR__.'/../Tools/Reflection.php';
use \AppShaker\Container, 
    \AppShaker\Routing\URLRoute, 
    \AppShaker\Tools\Reflection;


/**
 * Router class
 * Úložistě Routů, zprostředkovává práci s Routami 
 *  
 * @author  Michal Mikoláš <xxxObiWan@gmail.com>
 * @package AppShaker    
 */ 
class Router extends Container
{
    /**
     * Přidá do routeru novou routu, případně přepíše starou se stejným id
     * @param string $mask
     * @param mixed $callback
     * @param string $id
     * @return void                    
     */         
    public function add($mask, $callback, $id = NULL)
    {
        if (!is_null($id)) {
            $this->data[$id] = array(
                'route' => new URLRoute($mask),
                'callback' => $callback, 
            );
        } else {
            $this->data[] = array(
                'route' => new URLRoute($mask), 
                'callback' => $callback,
            );
        }
    }
    
    /**
     * Zjistí, která z rout pasuje na aktuální požadavek, a vrátí 
     *      callback a argumenty první nalezené.
     * @param mixed $request
     * @return array
     */
    public function match($request = NULL)
    {
        $callback = NULL;
        $args = array();
        
        // Najdeme routu, která souhlasí s požadavkem
        foreach($this->data as $dat){
            if ($dat['route']->match($request)) {
                $callback = $dat['callback'];
                $args = $dat['route']->getArgs();
                break;
            }
        }
        
        // Uspořádáme vrácené argumenty podle pořadí argumentů v callbacku
        $args = $this->sortArgs($args, Reflection::getParameters($callback));
        
        // Mapování argumentů do $_GET
        foreach($args as $key=>$arg){
            $_GET[$key] = $arg;
        }

        // Callbback i s argumenty vrátíme
        return array($callback, $args);
    }
    
    /**
     * Seřadí pole argumentů, podle pořadí klíčů v poli $keys
     * @param array $args
     * @param array $keys
     * @return array               
     */
    protected function sortArgs($args, $keys)
    {
        $sorted_args = array();
        
        // Do nového pole naházíme hodnoty pro klíče z $keys
        foreach($keys as $key){
            $sorted_args[$key] = @$args[$key];
        }
        
        // A pak do něho naházíme ten zbytek, co tam ještě není
        foreach($args as $key=>$arg){
            if (!isset($sorted_args[$key])) {
                $sorted_args[$key] = $arg;
            }
        }
        
        return $sorted_args;
    }         
    
    /**
     * Vrátí url, kterou nechá vytvořit příslušnou routu
     * @param string $id  id routy
     * @param array $args
     * @return string               
     */
    public function constructUrl($id, $args = array())
    {
    }                  
}