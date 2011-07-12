<?php

namespace AppShaker;

/**
 * Container class
 * Úložiště dat, ke kterým je možno přistupovat přes ArrayAccess i přes property 
 * 
 * @author  Michal Mikoláš <xxxObiWan@gmail.com> 
 * @package AppShaker
 *      
 * @todo proč ta zakomentovaná část kódu vyhazuje notice?
 */
class Container /*extends \Nette\Object*/ implements \ArrayAccess, \Iterator
{
    /** @var array $data */
    protected $data = array();
    
    /** @var int $i */
    protected $i = 0;



    /********************* API *********************/
    /**
     * Vytváří vnořený kontejner
     * @param  string $key
     * @return Container
     * @todo vyzkoušet &__get() způsob     
     */
    public function addContainer($key)
    {
        $this->data[$key] = new Container();

        return $this->data[$key];
    }
    
    /**
     * Rekurzivní převod hodnot do asociativního pole
     * @param array $array
     * @return Container          
     */         
    public static function fromArray(array $array)
    {
        $container = new self();
        foreach($array as $key=>$value){
            if (is_array($value)) {
                $container->$key = self::fromArray($value);
            }
            else {
                $container->$key = $value;
            }
        }
        
        return $container;
    }
    
    /**
     * Rekurzivní převod kontejneru na asociativní pole
     * @param Container $data
     * @return array          
     */         
    public function toArray($data = NULL)
    {
        if (is_null($data)) $data = $this->data;
    
        $array = array();
        foreach($data as $key=>$value){
            if (is_object($value)) {
                $array[$key] = $this->toArray($value);
            } else {
                $array[$key] = $value;
            }
        }
        
        return $array;
    }
    
    
    
    /********************* Objektový přístup *********************/
    /**
     * Magická metoda __get
     * @param  string $key
     * @return mixed
     */
    public function __get($key)
    {
        return $this->data[$key];
    }

    /**
     * Magická metoda __set
     * @param  string $key
     * @param  mixed $value
     * @return bool
     */
    public function __set($key, $value)
    {
        return ($this->data[$key] = $value);
    }


    
    /********************* ArrayAccess *********************/
    /**
     * Magická metoda offsetSet
     * @param  string $offset
     * @param  mixed $value
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        if (is_null($offset)) {
            $this->data[] = $value;
        } else {
            $this->data[$offset] = $value;
        }
    }

    /**
     * Magická metoda offsetExists
     * @param  string $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return isset($this->data[$offset]);
    }

    /**
     * Magická metoda offsetUnset
     * @param  string $offset
     * @return void
     */
    public function offsetUnset($offset)
    {
        unset($this->data[$offset]);
    }

    /**
     * Magická metoda offsetGet
     * @param  string $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return isset($this->data[$offset]) ? $this->data[$offset] : null;
    }
    
    
    
    /********************* Iterator *********************/
    /**
     * Nastaví iterátor na první prvek
     * @return void     
     */
    public function rewind()
    {
        $this->i = 0;
    }
    
    /**
     * Vrací aktuální prvek
     * @return mixed     
     */
    public function current()
    {
        $entry = array_slice($this->data, $this->i, 1);
        return array_pop($entry);
    }

    /**
     * Vrací aktuální klíč
     * @return mixed     
     */
    public function key()
    {
        $entry = array_slice($this->data, $this->i, 1);
        $entry = array_flip($entry);
        return array_pop($entry);
    }

    /**
     * Nastaví iterátor na další prvek
     * @return void     
     */
    public function next()
    {
        $this->i++;
    }

    /**
     * Ověří, zda aktuální prvek existuje
     * @return bool     
     */
    public function valid()
    {
        return ($this->i < count($this->data));
    }
}
