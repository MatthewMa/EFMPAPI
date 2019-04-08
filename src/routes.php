<?php

$app->group('/v1', function() {
    // Demo
    $this->get('/demos', 'DemoController:getDemos');
    $this->post('/demo', 'DemoController:addDemo');
    // User
    $this->get('/users','UserController:getUsers');
    $this->get('/user/{id}','UserController:getUserById');
    // Site
    $this->get('/sites','SiteController:getSites');
    $this->get('/site/{id}','SiteController:getSiteById');
    // Auth
    $this->group('/auth', function() {
        $this->post('/login','UserController:login');
    });

});

