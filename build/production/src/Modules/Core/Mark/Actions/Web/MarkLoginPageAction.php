<?php

declare(strict_types=1);

namespace HdmBoot\Modules\Core\Mark\Actions\Web;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Mark Login Page Action.
 *
 * Displays the mark login page for super users.
 * Uses mark.db for authentication.
 */
final class MarkLoginPageAction
{
    public function __invoke(
        ServerRequestInterface $request,
        ResponseInterface $response
    ): ResponseInterface {
        // Check if already logged in as mark
        $session = $request->getAttribute('session');
        if (is_array($session) && isset($session['mark_user_id'])) {
            return $response
                ->withHeader('Location', '/mark/dashboard')
                ->withStatus(302);
        }

        // Get any error messages
        $queryParams = $request->getQueryParams();
        $error = isset($queryParams['error']) && is_string($queryParams['error']) ? $queryParams['error'] : null;
        $redirect = isset($queryParams['redirect']) && is_string($queryParams['redirect']) ? $queryParams['redirect'] : '/mark/dashboard';

        // Render mark login template
        $html = $this->renderMarkLoginTemplate($error, $redirect);

        $response->getBody()->write($html);

        return $response->withHeader('Content-Type', 'text/html');
    }

    private function renderMarkLoginTemplate(?string $error, string $redirect): string
    {
        $errorHtml = $error ? '<div class="alert alert-danger">' . htmlspecialchars($error) . '</div>' : '';

        return <<<HTML
            <!DOCTYPE html>
            <html lang="en">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Mark System Login - HDM Boot</title>
                <style>
                    body { font-family: Arial, sans-serif; background: #f5f5f5; margin: 0; padding: 20px; }
                    .container { max-width: 400px; margin: 50px auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
                    .logo { text-align: center; margin-bottom: 30px; }
                    .logo h1 { color: #dc3545; margin: 0; font-size: 24px; }
                    .logo p { color: #666; margin: 5px 0 0 0; font-size: 14px; }
                    .form-group { margin-bottom: 20px; }
                    label { display: block; margin-bottom: 5px; font-weight: bold; color: #333; }
                    input[type="email"], input[type="password"] { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 16px; box-sizing: border-box; }
                    input[type="email"]:focus, input[type="password"]:focus { outline: none; border-color: #dc3545; }
                    .btn { width: 100%; padding: 12px; background: #dc3545; color: white; border: none; border-radius: 4px; font-size: 16px; cursor: pointer; }
                    .btn:hover { background: #c82333; }
                    .alert { padding: 10px; margin-bottom: 20px; border-radius: 4px; }
                    .alert-danger { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
                    .footer { text-align: center; margin-top: 20px; font-size: 12px; color: #666; }
                    .warning { background: #fff3cd; color: #856404; padding: 10px; border-radius: 4px; margin-bottom: 20px; border: 1px solid #ffeaa7; }
                </style>
            </head>
            <body>
                <div class="container">
                    <div class="logo">
                        <h1>🔴 MARK SYSTEM</h1>
                        <p>Super User Access</p>
                    </div>

                    <div class="warning">
                        <strong>⚠️ Restricted Access:</strong> This is the Mark system login for super users only.
                    </div>

                    {$errorHtml}

                    <form method="POST" action="/mark/login">
                        <input type="hidden" name="redirect" value="{$redirect}">
                        
                        <div class="form-group">
                            <label for="email">Email:</label>
                            <input type="email" id="email" name="email" required placeholder="mark@responsive.sk">
                        </div>

                        <div class="form-group">
                            <label for="password">Password:</label>
                            <input type="password" id="password" name="password" required placeholder="Enter your mark password">
                        </div>

                        <button type="submit" class="btn">Login to Mark System</button>
                    </form>

                    <div class="footer">
                        <p>HDM Boot Protocol v2.0 | Mark System</p>
                        <p><a href="/login" style="color: #666;">← Regular User Login</a></p>
                    </div>
                </div>
            </body>
            </html>
            HTML;
    }
}
