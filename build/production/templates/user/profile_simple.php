<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'User Profile', ENT_QUOTES, 'UTF-8') ?> - MVA Bootstrap</title>
    <style>
        body { 
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
            margin: 0;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        }
        h1 { color: #333; margin-bottom: 20px; }
        .user-info { margin-bottom: 30px; }
        .info-row { 
            display: flex; 
            justify-content: space-between; 
            padding: 10px 0; 
            border-bottom: 1px solid #eee; 
        }
        .info-label { font-weight: bold; color: #666; }
        .info-value { color: #333; }
        .btn {
            padding: 10px 20px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            display: inline-block;
            margin: 10px 5px 0 0;
        }
        .enterprise-info {
            margin-top: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 User Profile - Enterprise Stack</h1>
        
        <div class="user-info">
            <h2>👤 User Information</h2>
            
            <div class="info-row">
                <span class="info-label">Full Name:</span>
                <span class="info-value"><?= htmlspecialchars($user['name'] ?? 'Not set', ENT_QUOTES, 'UTF-8') ?></span>
            </div>

            <div class="info-row">
                <span class="info-label">Email:</span>
                <span class="info-value"><?= htmlspecialchars($user['email'] ?? 'Not set', ENT_QUOTES, 'UTF-8') ?></span>
            </div>

            <div class="info-row">
                <span class="info-label">Role:</span>
                <span class="info-value"><?= htmlspecialchars(strtoupper($user['role'] ?? 'user'), ENT_QUOTES, 'UTF-8') ?></span>
            </div>

            <div class="info-row">
                <span class="info-label">Status:</span>
                <span class="info-value"><?= htmlspecialchars(strtoupper($user['status'] ?? 'unknown'), ENT_QUOTES, 'UTF-8') ?></span>
            </div>

            <div class="info-row">
                <span class="info-label">User ID:</span>
                <span class="info-value" style="font-family: monospace; font-size: 12px;">
                    <?= htmlspecialchars($user['id'] ?? 'Not set', ENT_QUOTES, 'UTF-8') ?>
                </span>
            </div>
        </div>

        <?php if (isset($sessionInfo)): ?>
        <div class="user-info">
            <h2>🔐 Session Information</h2>
            
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
            
            <div style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 8px; font-family: monospace; font-size: 12px;">
                <h3>🔍 Session Debug Info:</h3>
                <pre><?= htmlspecialchars(json_encode($sessionInfo, JSON_PRETTY_PRINT), ENT_QUOTES, 'UTF-8') ?></pre>
            </div>
        </div>
        <?php endif; ?>

        <div class="enterprise-info">
            <h2>🏗️ Enterprise Architecture Success!</h2>
            <p><strong>🎉 C'MON BRO - WE DID IT!</strong></p>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-top: 20px;">
                <div style="text-align: center; padding: 15px; background: white; border-radius: 8px;">
                    <div style="font-size: 24px; margin-bottom: 10px;">🗄️</div>
                    <div style="font-weight: 600;">CakePHP Database</div>
                    <div style="color: #666; font-size: 14px;">with Abstraction</div>
                </div>
                <div style="text-align: center; padding: 15px; background: white; border-radius: 8px;">
                    <div style="font-size: 24px; margin-bottom: 10px;">📝</div>
                    <div style="font-weight: 600;">Monolog Logging</div>
                    <div style="color: #666; font-size: 14px;">Enterprise Level</div>
                </div>
                <div style="text-align: center; padding: 15px; background: white; border-radius: 8px;">
                    <div style="font-size: 24px; margin-bottom: 10px;">🔐</div>
                    <div style="font-weight: 600;">Odan Session</div>
                    <div style="color: #666; font-size: 14px;">Secure Sessions</div>
                </div>
                <div style="text-align: center; padding: 15px; background: white; border-radius: 8px;">
                    <div style="font-size: 24px; margin-bottom: 10px;">🎯</div>
                    <div style="font-weight: 600;">Slim PhpRenderer</div>
                    <div style="color: #666; font-size: 14px;">Official Templates</div>
                </div>
                <div style="text-align: center; padding: 15px; background: white; border-radius: 8px;">
                    <div style="font-size: 24px; margin-bottom: 10px;">🛡️</div>
                    <div style="font-weight: 600;">Auth Middleware</div>
                    <div style="color: #666; font-size: 14px;">samuelgfeller pattern</div>
                </div>
                <div style="text-align: center; padding: 15px; background: white; border-radius: 8px;">
                    <div style="font-size: 24px; margin-bottom: 10px;">🏭</div>
                    <div style="font-weight: 600;">Repository Factory</div>
                    <div style="color: #666; font-size: 14px;">Properly Abstract</div>
                </div>
            </div>
        </div>

        <div style="margin-top: 30px; text-align: center;">
            <a href="/user/edit" class="btn">✏️ Edit Profile</a>
            <a href="/user/password" class="btn">🔒 Change Password</a>
            <a href="/logout" class="btn" style="background: #dc3545;">🚪 Logout</a>
        </div>
    </div>
</body>
</html>
