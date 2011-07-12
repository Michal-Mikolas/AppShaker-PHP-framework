<?php

namespace AppShaker;


/**
 * DIContainer class
 * Implementace Dependency Injection kontejneru na služby
 * 
 * @author  Michal Mikoláš <xxxObiWan@gmail.com> 
 * @package AppShaker
 */
class DIContainer
{
    /** @var array $creators  úložiště továrniček na služby */
    protected $creators;
    
    /** @var array $services  úložiště vytvořených služeb */
    protected $services;
    
    
    /**
     * Konstruktor
     */         
    public function __construct()
    {
        $this->creators = array();
        $this->services = array();
    }
    
    /**
     * Vytváří službu z továrničky
     * @param string $name
     * @return mixed          
     */         
    public function getService($name)
    {
        $name = strtolower($name);
        
        // Kontrola existence služby
        if (!isset($this->services[$name]) and !isset($this->creators[$name])) 
            throw new \Exception("Service '$name' doesn't exists.");
                     
        // Vytvoření služby
        if (!isset($this->services[$name])) {
            $this->services[$name] = call_user_func_array($this->creators[$name], array($this));
            unset($this->creators[$name]);
        }
                     
        return $this->services[$name];
    }
    
    /**
     * Přidává továrničku na službu
     * @param string $name
     * @param mixed $creator          
     */         
    public function addService($name, $creator)
    {
        $name = strtolower($name);
        
        if (isset($this->services[$name]) or isset($this->creators[$name])) 
            throw new \Exception("Can't overload service '$name'.");
                     
        $this->creators[$name] = $creator;
    }
    
    /**
     * Magická metoda __get()
     * @param string $name
     * @return mixed          
     */
    public function __get($name)
    {
        return $this->getService($name);
    }         
}
