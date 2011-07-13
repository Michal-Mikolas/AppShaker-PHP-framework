<?php
include '../../libs/AppShaker/loader.php';
use Nette\Forms\Form, 
    Nette\String;


// 1) Load config
app()->loadConfig('config.neon');


// 2) Connect to database
Dibi::connect( app()->database );   //data z configu - ekvivalent pro app()->get('database')


// 3) Write application
/**
 * Společné akce, které je potřeba spouštět vždy (např. naplnění menu), 
 *      dáváme do app()->onStartup[] 
 */ 
app()->onStartup[] = function(){
    app()->template()->articles = Dibi::query("SELECT * FROM `articles` ORDER BY `id` DESC")->fetchAll();
};

/**
 * Zobrazení detailu článku
 */ 
app('url://clanky/<name_slug>.html')->match(function($name_slug){
    app()->template('templates/articles.show.latte')
        ->set('article', Dibi::fetch("SELECT * FROM `articles` WHERE `name_slug`=%s", $name_slug))
        ->render();   //pro vykreslení šablony lze použít jak $template->render(), tak i echo $template
});

/**
 * Přidání nového článku
 */ 
app('url://admin/pridat-clanek.html')->match(function(){
    $template = app()->template('templates/admin.articles.create.latte');
    
    // Vytvoříme formulář
    $form = new Form();
    $form->addText('name', 'Název: ')
        ->addRule(Form::FILLED, 'Název musí být vyplněn.');
    $form->addTextarea('content_html', 'Obsah: ')
        ->addRule(Form::FILLED, 'Obsah musí být vyplněn.');
    $form->addSubmit('send', 'Vytvořit');
    $form->setAction( app()->basePath . '/admin/pridat-clanek.html' );
    $template['articleForm'] = $form;
    
    // Vytvoření článku
    if ($form->isSubmitted() && $form->isValid()) {
        $values = $form->getValues();
        $values['name_slug'] = String::webalize($values['name']);
        Dibi::query("INSERT INTO `articles` ", $values);
        
        if (Dibi::affectedRows()) {
            $template->flashMessage('Povedlo se, článek byl vytvořen!');
            app()->redirect('');
        } else {
            $template->flashMessage('Něco se porouchalo, článek nebyl vytvořen.', 'error');
        }
    }
    
    $template->render();
});

/** 
 * Homepage - nejobecnější routa (vyhoví všemu), a tak ji dáme nakonec 
 */
app('url://')->match(function(){
    $template = app()->template('templates/articles.home.latte');
    echo $template;
});


// 4) Run the application
app()->run();

