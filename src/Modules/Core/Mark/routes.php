<?php

declare(strict_types=1);

use HdmBoot\Modules\Core\Mark\Actions\Web\MarkLoginPageAction;
use HdmBoot\Modules\Core\Mark\Actions\Web\MarkLoginSubmitAction;
use Slim\App;

/**
 * Mark Module Routes.
 * 
 * Defines routes for the Mark system (super user management).
 * All routes use mark.db database.
 */
return function (App $app): void {
    
    // === MARK AUTHENTICATION ROUTES ===
    
    // Mark login page - GET /mark
    $app->get('/mark', MarkLoginPageAction::class)
        ->setName('mark.login.page');
    
    // Mark login form submission - POST /mark/login
    $app->post('/mark/login', MarkLoginSubmitAction::class)
        ->setName('mark.login.submit');
    
    // Mark logout - POST /mark/logout
    $app->post('/mark/logout', function ($request, $response) {
        // Clear mark session
        $session = $request->getAttribute('session');
        if ($session) {
            unset($session['mark_user_id']);
            unset($session['mark_user_email']);
            unset($session['mark_user_role']);
            unset($session['mark_login_time']);
        }
        
        return $response
            ->withHeader('Location', '/mark')
            ->withStatus(302);
    })->setName('mark.logout');
    
    // === MARK DASHBOARD ROUTES ===
    
    // Mark dashboard - GET /mark/dashboard
    $app->get('/mark/dashboard', function ($request, $response) {
        // Simple dashboard for now
        $html = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <title>Mark Dashboard - HDM Boot</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .header { background: #dc3545; color: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
        .content { background: #f8f9fa; padding: 20px; border-radius: 8px; }
        .btn { padding: 10px 20px; background: #dc3545; color: white; text-decoration: none; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>🔴 Mark System Dashboard</h1>
        <p>Super User Administration Panel</p>
    </div>
    
    <div class="content">
        <h2>Welcome to Mark System</h2>
        <p>You are successfully logged into the Mark system.</p>
        
        <h3>Available Actions:</h3>
        <ul>
            <li>User Management</li>
            <li>System Configuration</li>
            <li>Database Administration</li>
            <li>Security Monitoring</li>
        </ul>
        
        <form method="POST" action="/mark/logout" style="margin-top: 20px;">
            <button type="submit" class="btn">Logout</button>
        </form>
    </div>
</body>
</html>
HTML;
        
        $response->getBody()->write($html);
        return $response->withHeader('Content-Type', 'text/html');
    })->setName('mark.dashboard');
};
