<?php

declare(strict_types=1);

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\App;

return function (App $app): void {
    // Home page - enhanced version with paths info
    $app->get('/', function (ServerRequestInterface $request, ResponseInterface $response) {
        $container = $this->get(\DI\Container::class);
        $pathHelper = $container->get(\MvaBootstrap\Shared\Helpers\SecurePathHelper::class);
        
        $html = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MVA Bootstrap Application</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; background: #f5f5f5; }
        .container { max-width: 900px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #333; border-bottom: 2px solid #007bff; padding-bottom: 10px; }
        .status { background: #d4edda; color: #155724; padding: 15px; border-radius: 4px; margin: 20px 0; }
        .feature { background: #f8f9fa; padding: 15px; border-radius: 4px; margin: 20px 0; }
        .feature h3 { margin-top: 0; color: #007bff; }
        .paths { background: #e7f3ff; padding: 15px; border-radius: 4px; margin: 20px 0; }
        .path-item { background: white; margin: 5px 0; padding: 8px; border-left: 3px solid #007bff; font-family: monospace; font-size: 0.9em; }
        .next-steps { background: #fff3cd; color: #856404; padding: 15px; border-radius: 4px; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 MVA Bootstrap Application</h1>
        
        <div class="status">
            ✅ <strong>Application is running successfully!</strong><br>
            Your modular PHP application with secure paths is ready for development.
        </div>
        
        <div class="feature">
            <h3>🔒 Secure Path Management</h3>
            <p>The application now includes secure path handling with the <code>responsive-sk/slim4-paths</code> package.</p>
            
            <div class="paths">
                <h4>📁 Allowed Directories:</h4>';
                
        foreach ($pathHelper->getAllowedDirectories() as $dir) {
            $html .= '<div class="path-item">' . htmlspecialchars($dir) . '</div>';
        }
        
        $html .= '
            </div>
        </div>
        
        <div class="feature">
            <h3>📦 Features Implemented</h3>
            <ul>
                <li>✅ Modular architecture with Core and Optional modules</li>
                <li>✅ Secure path handling and file operations</li>
                <li>✅ DI Container with PHP-DI</li>
                <li>✅ Environment configuration</li>
                <li>✅ Logging with Monolog</li>
                <li>✅ Session management</li>
                <li>✅ Database connection (SQLite)</li>
                <li>✅ Route organization system</li>
            </ul>
        </div>
        
        <div class="next-steps">
            <h3>🛠 Next Steps</h3>
            <ol>
                <li>Create the <strong>User</strong> core module with authentication</li>
                <li>Create the <strong>Security</strong> core module with authorization</li>
                <li>Optionally create the <strong>Article</strong> module</li>
                <li>Set up database migrations</li>
                <li>Implement JWT authentication</li>
                <li>Add middleware for security</li>
            </ol>
        </div>
        
        <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; color: #666; text-align: center;">
            <small>MVA Bootstrap Application v1.0.0 | Secure Paths Enabled</small>
        </div>
    </div>
</body>
</html>';

        $response->getBody()->write($html);
        return $response->withHeader('Content-Type', 'text/html');
    })->setName('home');
};
