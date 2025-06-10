<?php

declare(strict_types=1);

use MvaBootstrap\Modules\Core\Security\Actions\Web\LoginPageAction;
use MvaBootstrap\Modules\Core\Security\Actions\Web\LoginSubmitAction;
use MvaBootstrap\Modules\Core\Security\Actions\Web\LogoutAction;
use MvaBootstrap\Modules\Core\User\Actions\Web\ProfilePageAction;
use MvaBootstrap\Shared\Middleware\UserAuthenticationMiddleware;
use Slim\App;

return function (App $app): void {
    // Login page
    $app->get('/login', LoginPageAction::class)->setName('login');
    
    // Login form submission
    $app->post('/login', LoginSubmitAction::class)->setName('login-submit');
    
    // Logout (both GET and POST for flexibility)
    $app->get('/logout', LogoutAction::class)->setName('logout-get');
    $app->post('/logout', LogoutAction::class)->setName('logout');
    
    // Profile page (protected)
    $app->get('/profile', ProfilePageAction::class)
        ->setName('profile')
        ->add(UserAuthenticationMiddleware::class);
};
