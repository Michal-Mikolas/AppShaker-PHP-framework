<?php

namespace AppShaker\Routing;


/**
 * IRoute interface
 * Rozhraní pro routy
 *  
 * @author  Michal Mikoláš <xxxObiWan@gmail.com>
 * @package AppShaker    
 */ 
interface IRoute
{
  	/**
     * Konstruktor
     * @param mixed $mask  maska pro požadavek     
     */         
    public function __construct($mask);
    
    /**
  	 * Testuje, zda tato routa odpovídá aktuálnímu / zadanému požadavku
  	 * @param mixed $request 
  	 * @return bool
  	 */
  	public function match($request = NULL);
  
  	/**
     * Pokud není logické vytvářet URL (např v CLI aplikaci), 
     *      měla by vyhazovat vyjímku
     * @param array $args
     * @return string   
  	 */
  	public function constructUrl($args);
  	
  	/**
     * Vrací nalezené <argumenty> v URL a $_GET parametry i s jejich hodnotou
     * @param string $url
     * @return array()              
     * @todo zahrnout do $args i hodnoty za otazníkem v URL
  	 */
    public function getArgs($url = NULL);       	
}