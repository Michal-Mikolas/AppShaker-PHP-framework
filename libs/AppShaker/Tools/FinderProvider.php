<?php

namespace AppShaker\Tools;

use \Nette\Utils\Finder;


/**
 * FinderProvider class
 * Zprostředkovává a mírně upravuje práci s Nette Finderem 
 *  
 * @author  Michal Mikoláš <xxxObiWan@gmail.com> 
 * @package AppShaker
 */
 
class FinderProvider extends \Nette\Object implements \IteratorAggregate
{
    /** @var string|array $find */
    protected $find;
    
    /** @var string|array $findFiles */
    protected $findFiles;
    
    /** @var string|array $findDirectories */
    protected $findDirectories;
    
    /** @var string|array $in */
    protected $in;
    
    /** @var string|array $from */
    protected $from;
    
    /** @var bool $childFirst */
    protected $childFirst;
    
    /** @var array $exclude */
    protected $excludes;
    
    /** @var array $filters */
    protected $filters;
    
    /** @var int $limitDepth */
    protected $limitDepth;
    
    /** @var array $sizes */
    protected $sizes;
    
    /** @var array $dates */
    protected $dates;
    
    
    
    /********************* Náhrada za metody třídy Finder *********************/
    /**
     * Nastavuje hodnoty pro find() medotu Nette Finderu
     * @return FinderProvider     
     */
    public function find()
    {
        $this->find = func_get_args();
        
        return $this;
    }
    
    /**
     * Nastavuje hodnoty pro findFiles() medotu Nette Finderu
     * @return FinderProvider     
     */
    public function findFiles()
    {
        $this->findFiles = func_get_args();
        
        return $this;
    }
    
    /**
     * Nastavuje hodnoty pro findDirectories() medotu Nette Finderu
     * @return FinderProvider     
     */
    public function findDirectories()
    {
        $this->findDirectories = func_get_args();
        
        return $this;
    }   
                
    /**
     * Nastavuje hodnoty pro in() medotu Nette Finderu
     * @return FinderProvider     
     */
    public function in()
    {
        $this->in = func_get_args();
        
        return $this;
    } 
    
    /**
     * Nastavuje hodnoty pro from() medotu Nette Finderu
     * @return FinderProvider     
     */
    public function from()
    {
        $this->from = func_get_args();
        
        return $this;
    } 
    
    /**
     * Nastavuje metodu childFirst(), aby byla v koncových metodách zavolána
     * @return FinderProvider     
     */
    public function childFirst()
    {
        $this->childFirst = TRUE;
        
        return $this;
    }
    
    /**
     * Nastavuje hodnoty pro exclude() medotu Nette Finderu
     * @return FinderProvider     
     */
    public function exclude()
    {
        $args = func_get_args();
        if (!is_array($this->excludes)) $this->excludes = array();
        
        // Předáme všechny argumenty do jednoduchého pole $this->exclude
        foreach($args as $arg){
            if (is_string($arg)) {
                //argument je řetězec
                $this->excludes[] = $arg;
            } elseif(is_array($arg)) {
                //argument je pole
                foreach($arg as $subarg){
                    if (is_string($subarg)) {
                        $this->excludes[] = $subarg;
                    }
                }
            }
        }
        
        return $this;
    }
    
    /**
     * Nastavuje hodnoty pro filter() medotu Nette Finderu
     * @return FinderProvider     
     */
    public function filter($callback)
    {
        if (!is_array($this->filters)) $this->filters = array();
        
        $this->filters[] = $callback;
        
        return $this;
    }
    
    /**
     * Nastavuje hodnoty pro limitDepth() medotu Nette Finderu
     * @return FinderProvider     
     */
    public function limitDepth($limit)
    {
        $this->limitDepth = $limit;
        
        return $this;
    }
    
    /**
     * Nastavuje hodnoty pro size() medotu Nette Finderu
     * @return FinderProvider     
     */
    public function size($operator, $size = NULL)
    {
        if (!is_array($this->sizes)) $this->sizes = array();
        
        $this->sizes[] = array($operator, $size);
        
        return $this;
    }
    
    /**
     * Nastavuje hodnoty pro date() medotu Nette Finderu
     * @return FinderProvider     
     */
    public function date($operator, $date = NULL)
    {
        if (!is_array($this->dates)) $this->dates = array();
        
        $this->dates[] = array($operator, $date);
        
        return $this;
    }
    
     
    
    /********************* Metody zasahující mimo FinderProvider *********************/
    /**
     * Vytvoření objektu Finder při pokusu o ArrayAccess
     * @return Finder     
     */
    public function getIterator()
    {
        $finder = NULL;
        
        // 1) find(), findFiles(), findDirectories()
        if ($this->find) {
            $finder = call_user_func_array("\\Nette\\Finder::find", $this->find);
        } elseif($this->findDirectories) {
            $finder = call_user_func_array("\\Nette\\Finder::findDirectories", $this->findDirectories);
        } else {
            $finder = call_user_func_array("\\Nette\\Finder::findFiles", $this->findFiles);
        }
        
        // 2) from(), in()
        if ($this->from) {
            $finder = $finder->from($this->from);
        } elseif($this->in) {
            $finder = $finder->in($this->in);
        } else {
            $finder = $finder->from( app()->get('wwwDir') );
        }
        
        // 3) childFirst()
        if ($this->childFirst) $finder->childFirst();
        
        // 4) exclude()
        if (count($this->excludes)>0) {
            $finder = call_user_func_array(array($finder, 'exclude'), $this->excludes);
        }
        
        // 5) filter()
        if (count($this->filters)>0) {
            foreach($this->filters as $filter){
                $finder = $finder->filter($filter);
            }
        }
        
        // 6) limitDepth()
        if ($this->limitDepth) {
            $finder = $finder->limitDepth($this->limitDepth);
        }
        
        // 7) size()
        if (count($this->sizes)>0) {
            foreach($this->sizes as $size){
                $finder = $finder->size($size[0], $size[1]);
            }
        }
        
        // 8) date()
        if (count($this->dates)>0) {
            foreach($this->dates as $date){
                $finder = $finder->date($date[0], $date[1]);
            }
        }
        
        return $finder;
    } 
    
    /**
     *
     */
    public function include_()
    {
    }
    
    
    
    
    /**
     * Magická metoda __call(), v současné době používaná jen pro volání 
     *      FinderProvider:include().
     * @param string $name
     * @param array $args
     * @return void               
     */         
    public function __call($name, $args)
    {
        if ($name=='include') 
            return call_user_func_array(callback($this, 'include_'), $args);
        else
            throw new \Exception("Method \AppShaker\Application:$name() doesn't exists");
    }         
    
}
