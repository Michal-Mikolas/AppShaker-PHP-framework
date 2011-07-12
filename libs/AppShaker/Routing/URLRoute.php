<?php

namespace AppShaker\Routing;

include_once __DIR__.'/../Routing/IRoute.php';
use \AppShaker\Routing\IRoute;

use \Nette\ArrayTools as Arrays;


/**
 * URLRoute
 * Třída, reprezentující jeden tvar URL
 * 
 * @author  Michal Mikoláš <xxxObiWan@gmail.com> 
 * @package AppShaker   
 */ 
class URLRoute implements IRoute
{
    /** @var string $mask  maska souty ve tvaru např 'url://clanky/<id>' */
    protected $mask;
    
    /** @var string $compiledMask  zkompilovaná maska do regexp výrazu */
    protected $compiledMask;
    
    /** @var array $maskKeys  názvy všech <klíčů> v masce */
    protected $maskKeys;


    
    /********************* API, které využívá Router *********************/
    /**
     * Konstruktor
     * @param string $mask  maska pro URL     
     */         
    public function __construct($mask)
    {
        $this->mask = $mask;
    }    
    
    /**
     * Testuje, zda tato routa odpovídá aktuálnímu / zadanému požadavku
     * @param string $url
     * @return bool     
     * @todo $url má být celá URL, nebo jen 'route' část?          
     */         
    public function match($url = NULL)
    {
        if (is_null($url)) $url = @$_GET['route'];
        
        if (!$this->compiledMask) {
            $this->compileMask($this->mask);
        }
        
        return (bool)preg_match($this->compiledMask, $url);
    }
    
    /**
     * Vrací nalezené <argumenty> v URL i s jejich hodnotou
     * @param string $url
     * @return array()              
     */
    public function getArgs($url = NULL)
    {
        // Inicializace
        if (is_null($url)) $url = @$_GET['route'];
        
        if (!$this->compiledMask) {
            $this->compileMask($this->mask);
        }
        
        // Vytáhnem hodnoty z URL
        $matches = array();
        preg_match_all($this->compiledMask, $url, $matches);
        
        // a přiřadíme je do nového pole pod správným klíčem
        $args = array();
        foreach($this->maskKeys as $i=>$key){
            $args[$key] = $matches[$i+1][0];
        }
        
        // Navíc přidáme ještě hodnoty z query_stringu
        $query_string = preg_replace('#^[^?]+\\?(.+)(\\#.*)?$#', '\\1', $_SERVER['REQUEST_URI']);
        parse_str($query_string, $get);
        $args = Arrays::mergeTree($args, $get);
        
        return $args;
    } 
    
    
    
    /********************* API, využívající Application *********************/
    /**
     * Vytvoří pro tuto routu URL, podle zadaných argumentů
     * (známé argumenty přiřadí pod jejich <název>, neznámé dá za otazník)
     * @param array $args
     * @return string               
     */
    public function constructUrl($args = array())
    {
    }
    
    
    
    /********************* Třídní (privátní) metody *********************/
    /**
     * Převede zadanou masku do regexp tvaru, vytáhne z ní a uloží názvy argumentů
     * @param string $mask
     * @return void          
     */         
    protected function compileMask($mask)
    {
        /*
         * $mask vypadající třeba takto:
         * url://clanky/<action>/<id>.html
         * 
         * se převede na regexp:
         * #^clanky/([^/]+)/([^/]+).html(\\?.*)?$#
         */         
        
        // Regexp
        $this->compiledMask = preg_replace('#^url://#i', '', $mask);
        if ($this->compiledMask != '') {
            $this->compiledMask = preg_replace('#<[^>]*>#', '([^/]+)', $this->compiledMask);
            $this->compiledMask = '#^' . $this->compiledMask . '/?(\\?.*)?$#';
        } else {
            $this->compiledMask = '#^[^?]*(\\?.*)?$#';
        }
        
        // Mask keys
        $keys = array();
        preg_match_all('#<[^>]*>#', $mask, $keys);
        $this->maskKeys = $keys[0];
        foreach($this->maskKeys as $i=>$value) 
            $this->maskKeys[$i] = trim($this->maskKeys[$i], '<>');
    }
}