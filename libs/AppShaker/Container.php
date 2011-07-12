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
class Container /*extends \Nette\Object*/ implements \ArrayAccess
{
    /** @var array $data */
    protected $data = array();



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
}
