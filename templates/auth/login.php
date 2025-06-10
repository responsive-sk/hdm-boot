<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'Login', ENT_QUOTES, 'UTF-8') ?> - MVA Bootstrap</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .login-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            padding: 40px;
            width: 100%;
            max-width: 400px;
        }
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo h1 {
            color: #333;
            font-size: 28px;
            font-weight: 600;
        }
        .logo p {
            color: #666;
            margin-top: 5px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
        }
        input[type="email"], input[type="password"] {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        input[type="email"]:focus, input[type="password"]:focus {
            outline: none;
            border-color: #667eea;
        }
        .btn {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
        }
        .btn:hover {
            transform: translateY(-2px);
        }
        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .alert-error {
            background: #fee;
            color: #c33;
            border: 1px solid #fcc;
        }
        .alert-success {
            background: #efe;
            color: #363;
            border: 1px solid #cfc;
        }
        .alert-info {
            background: #eef;
            color: #336;
            border: 1px solid #ccf;
        }
        .security-info {
            margin-top: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
            font-size: 14px;
            color: #666;
        }
        .security-info h3 {
            color: #333;
            margin-bottom: 10px;
            font-size: 16px;
        }
        .security-info ul {
            margin-left: 20px;
        }
        .security-info li {
            margin-bottom: 5px;
        }
        .links {
            text-align: center;
            margin-top: 20px;
        }
        .links a {
            color: #667eea;
            text-decoration: none;
        }
        .links a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <h1>🔐 MVA Bootstrap</h1>
            <p>Secure Login with Odan Session + CakePHP Validator</p>
        </div>

        <?php if (isset($flash['error']) && $flash['error']): ?>
            <div class="alert alert-error">
                <?= htmlspecialchars($flash['error'], ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <?php if (isset($flash['success']) && !empty($flash['success'])): ?>
            <div class="alert alert-success">
                <?php if (is_array($flash['success'])): ?>
                    <?php foreach ($flash['success'] as $message): ?>
                        <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?><br>
                    <?php endforeach; ?>
                <?php else: ?>
                    <?= htmlspecialchars($flash['success'], ENT_QUOTES, 'UTF-8') ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($flash['info']) && !empty($flash['info'])): ?>
            <div class="alert alert-info">
                <?php if (is_array($flash['info'])): ?>
                    <?php foreach ($flash['info'] as $message): ?>
                        <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?><br>
                    <?php endforeach; ?>
                <?php else: ?>
                    <?= htmlspecialchars($flash['info'], ENT_QUOTES, 'UTF-8') ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($flash['error']) && !empty($flash['error'])): ?>
            <div class="alert alert-error">
                <?php if (is_array($flash['error'])): ?>
                    <?php foreach ($flash['error'] as $message): ?>
                        <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?><br>
                    <?php endforeach; ?>
                <?php else: ?>
                    <?= htmlspecialchars($flash['error'], ENT_QUOTES, 'UTF-8') ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-error">
                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="/login">
            <?= $csrf->getHiddenInput('login') ?>
            
            <div class="form-group">
                <label for="email">Email Address</label>
                <input 
                    type="email" 
                    id="email" 
                    name="email" 
                    value="<?= htmlspecialchars($email ?? '', ENT_QUOTES, 'UTF-8') ?>"
                    required 
                    autocomplete="email"
                    placeholder="admin@example.com"
                >
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    required 
                    autocomplete="current-password"
                    placeholder="Enter your password"
                >
            </div>

            <button type="submit" class="btn">
                🔑 Login Securely
            </button>
        </form>

        <div class="links">
            <a href="/">← Back to Home</a>
        </div>

        <div class="security-info">
            <h3>🛡️ Security Features</h3>
            <ul>
                <li>✅ CSRF Protection (Odan Session)</li>
                <li>✅ CakePHP Validation</li>
                <li>✅ Login Throttling Protected</li>
                <li>✅ Secure Password Hashing</li>
                <li>✅ Proper Paths System</li>
            </ul>
            <p><strong>Test Account:</strong> admin@example.com / Password123</p>
        </div>
    </div>
</body>
</html>
