<?php

namespace AppShaker;

use \Nette\NeonParser;

/**
 * Config 
 * Třída pro práci s konfigurací aplikace
 *  
 * @author  Michal Mikoláš <xxxObiWan@gmail.com>
 * @package AppShaker    
 */ 
class Config
{    
    /**
     * Načte config ze souboru
     * @param string $file
     * @return Container          
     */         
    public static function fromFile($file)
    {
        if (is_file($file)) { 
            $config_string = file_get_contents($file);
        }
        else throw new \Exception("File '$file' doesn`t exists.");
        
        $neon_parser = new NeonParser();
        $config_array = $neon_parser->parse($config_string);
        
        return $config_array;
    }      
}