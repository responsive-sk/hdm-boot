<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'User Profile', ENT_QUOTES, 'UTF-8') ?> - MVA Bootstrap</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        .header {
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            padding: 30px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .logo h1 {
            color: #333;
            font-size: 28px;
            font-weight: 600;
        }
        .user-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .user-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 18px;
        }
        .profile-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }
        .card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            padding: 30px;
        }
        .card h2 {
            color: #333;
            margin-bottom: 20px;
            font-size: 24px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #eee;
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .info-label {
            font-weight: 600;
            color: #666;
        }
        .info-value {
            color: #333;
        }
        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .status-active {
            background: #e8f5e8;
            color: #2d5a2d;
        }
        .status-admin {
            background: #fff3cd;
            color: #856404;
        }
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: transform 0.2s;
        }
        .btn:hover {
            transform: translateY(-2px);
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        .session-debug {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            margin-top: 20px;
            font-family: 'Courier New', monospace;
            font-size: 12px;
        }
        .session-debug h3 {
            margin-bottom: 10px;
            font-family: inherit;
        }
        .actions {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }
        @media (max-width: 768px) {
            .profile-grid {
                grid-template-columns: 1fr;
            }
            .header {
                flex-direction: column;
                gap: 20px;
                text-align: center;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">
                <h1>🚀 MVA Bootstrap</h1>
                <p>Enterprise User Profile</p>
            </div>
            <div class="user-info">
                <div class="user-avatar">
                    <?= strtoupper(substr($user->getName() ?? 'U', 0, 1)) ?>
                </div>
                <div>
                    <div style="font-weight: 600;"><?= htmlspecialchars($user->getName() ?? 'Unknown', ENT_QUOTES, 'UTF-8') ?></div>
                    <div style="color: #666; font-size: 14px;"><?= htmlspecialchars($user->getEmail() ?? 'unknown@example.com', ENT_QUOTES, 'UTF-8') ?></div>
                </div>
            </div>
        </div>

        <div class="profile-grid">
            <div class="card">
                <h2>👤 User Information</h2>
                
                <div class="info-row">
                    <span class="info-label">Full Name:</span>
                    <span class="info-value"><?= htmlspecialchars($user->getName() ?? 'Not set', ENT_QUOTES, 'UTF-8') ?></span>
                </div>
                
                <div class="info-row">
                    <span class="info-label">Email:</span>
                    <span class="info-value"><?= htmlspecialchars($user->getEmail() ?? 'Not set', ENT_QUOTES, 'UTF-8') ?></span>
                </div>
                
                <div class="info-row">
                    <span class="info-label">Role:</span>
                    <span class="info-value">
                        <span class="status-badge status-admin">
                            <?= htmlspecialchars(strtoupper($user->getRole() ?? 'user'), ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    </span>
                </div>
                
                <div class="info-row">
                    <span class="info-label">Status:</span>
                    <span class="info-value">
                        <span class="status-badge status-active">
                            <?= htmlspecialchars(strtoupper($user->getStatus() ?? 'unknown'), ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    </span>
                </div>
                
                <div class="info-row">
                    <span class="info-label">User ID:</span>
                    <span class="info-value" style="font-family: monospace; font-size: 12px;">
                        <?= htmlspecialchars($user->getId()->toString() ?? 'Not set', ENT_QUOTES, 'UTF-8') ?>
                    </span>
                </div>

                <div class="actions">
                    <a href="/user/edit" class="btn btn-primary">✏️ Edit Profile</a>
                    <a href="/user/password" class="btn btn-secondary">🔒 Change Password</a>
                </div>
            </div>

            <div class="card">
                <h2>🔐 Session Information</h2>
                
                <?php if (isset($sessionInfo)): ?>
                    <div class="info-row">
                        <span class="info-label">Session ID:</span>
                        <span class="info-value" style="font-family: monospace; font-size: 12px;">
                            <?= htmlspecialchars(substr($sessionInfo['session_id'] ?? 'unknown', 0, 16), ENT_QUOTES, 'UTF-8') ?>...
                        </span>
                    </div>
                    
                    <div class="info-row">
                        <span class="info-label">Session Started:</span>
                        <span class="info-value">
                            <?= $sessionInfo['session_started'] ? '✅ Yes' : '❌ No' ?>
                        </span>
                    </div>
                    
                    <div class="info-row">
                        <span class="info-label">User Data:</span>
                        <span class="info-value">
                            <?= isset($sessionInfo['user_data']) ? '✅ Loaded' : '❌ Missing' ?>
                        </span>
                    </div>
                <?php endif; ?>

                <div class="actions">
                    <form method="POST" action="/logout" style="display: inline;">
                        <?= $csrf->getHiddenInput('logout') ?>
                        <button type="submit" class="btn btn-danger">🚪 Logout</button>
                    </form>
                </div>

                <?php if (isset($sessionInfo) && is_array($sessionInfo)): ?>
                    <div class="session-debug">
                        <h3>🔍 Session Debug Info:</h3>
                        <pre><?= htmlspecialchars(json_encode($sessionInfo, JSON_PRETTY_PRINT), ENT_QUOTES, 'UTF-8') ?></pre>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card" style="margin-top: 30px;">
            <h2>🏗️ Enterprise Architecture</h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-top: 20px;">
                <div style="text-align: center; padding: 20px; background: #f8f9fa; border-radius: 8px;">
                    <div style="font-size: 24px; margin-bottom: 10px;">🗄️</div>
                    <div style="font-weight: 600;">CakePHP Database</div>
                    <div style="color: #666; font-size: 14px;">Query Builder + ORM</div>
                </div>
                <div style="text-align: center; padding: 20px; background: #f8f9fa; border-radius: 8px;">
                    <div style="font-size: 24px; margin-bottom: 10px;">📝</div>
                    <div style="font-weight: 600;">Monolog Logging</div>
                    <div style="color: #666; font-size: 14px;">Enterprise Logging</div>
                </div>
                <div style="text-align: center; padding: 20px; background: #f8f9fa; border-radius: 8px;">
                    <div style="font-size: 24px; margin-bottom: 10px;">🔐</div>
                    <div style="font-weight: 600;">Odan Session</div>
                    <div style="color: #666; font-size: 14px;">Secure Sessions</div>
                </div>
                <div style="text-align: center; padding: 20px; background: #f8f9fa; border-radius: 8px;">
                    <div style="font-size: 24px; margin-bottom: 10px;">✅</div>
                    <div style="font-weight: 600;">CakePHP Validator</div>
                    <div style="color: #666; font-size: 14px;">Input Validation</div>
                </div>
                <div style="text-align: center; padding: 20px; background: #f8f9fa; border-radius: 8px;">
                    <div style="font-size: 24px; margin-bottom: 10px;">🛡️</div>
                    <div style="font-weight: 600;">CSRF Protection</div>
                    <div style="color: #666; font-size: 14px;">Security First</div>
                </div>
                <div style="text-align: center; padding: 20px; background: #f8f9fa; border-radius: 8px;">
                    <div style="font-size: 24px; margin-bottom: 10px;">📁</div>
                    <div style="font-weight: 600;">Proper Paths</div>
                    <div style="color: #666; font-size: 14px;">No ../.. hacks</div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
