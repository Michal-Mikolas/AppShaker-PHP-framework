<?php
include 'libs/AppShaker/loader.php';


app('url://')->match(function(){
    echo '
        <h1>Congratulation!</h1> 
        <p>AppShaker framework seems to work fine.</p>
        <p>For documentation and more, see <a href="http://wiki.webshake.cz/doku.php?id=public:appshaker:start" target="_blank">our wiki</a>.</p>
    ';
});


app()->run();

