<?php
/**
 * AppShaker framework
 * 
 * @author  Michal Mikoláš <xxxObiWan@gmail.com>
 */ 

include_once __DIR__.'/3rdParty/Nette/loader.php';
include_once __DIR__.'/3rdParty/dibi/dibi.php';
include_once __DIR__.'/3rdParty/dibi/DibiTableX.php';

include_once __DIR__.'/Provider.php';
use \AppShaker\Provider;


/**
 * Zprostředkovává přístup k funkcím celého frameworku
 * @return Provider 
 */ 
function app(){
    $args = func_get_args();
    
    $pquery_provider = Provider::getInstance();
    $pquery_provider->args($args);
    
    return $pquery_provider;
}