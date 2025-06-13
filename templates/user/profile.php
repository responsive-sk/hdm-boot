<?php
// Ensure $user is always defined to avoid PHPStan/strict warnings
if (!isset($user)) {
    $user = [];
}

function getUserValue($user, $key, $default = null) {
    if (is_object($user)) {
        $method = 'get' . ucfirst($key);
        if (method_exists($user, $method)) {
            return $user->$method();
        }
    } elseif (is_array($user)) {
        return $user[$key] ?? $default;
    }
    return $default;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'User Profile', ENT_QUOTES, 'UTF-8') ?> - MVA Bootstrap</title>
    <style>
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: linear-gradient(120deg, #232526 0%, #414345 100%);
            min-height: 100vh;
            height: 100vh;
            margin: 0;
            color: #f3f3f3;
            overflow-x: hidden;
        }
        .container {
            width: 100vw;
            min-height: 100vh;
            height: 100vh;
            margin: 0;
            padding: 0 0 40px 0;
            background: rgba(34, 34, 40, 0.92);
            border-radius: 0;
            box-shadow: none;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-start;
        }
        .profile-header {
            width: 100vw;
            max-width: none;
            margin: 0 0 32px 0;
            padding: 48px 0 48px 0;
            background: linear-gradient(90deg, #7b4397 0%, #232526 100%);
            box-shadow: 0 4px 24px 0 rgba(31, 38, 135, 0.18);
            display: flex;
            flex-direction: row;
            align-items: center;
            justify-content: center;
            text-align: left;
        }
        .avatar {
            margin: 0 36px 0 0;
        }
        .profile-header-info {
            display: flex;
            flex-direction: column;
            justify-content: center;
            text-align: left;
        }
        .profile-header-info h1 {
            font-size: 2.3rem;
            margin: 0 0 8px 0;
            color: #e1bee7;
        }
        .profile-header-info p {
            margin: 0;
            color: #bdbdbd;
            font-size: 1.15rem;
        }
        .info-list {
            margin: 32px 0 0 0;
            padding: 0;
            list-style: none;
        }
        .info-list li {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid #33343a;
        }
        .info-list li:last-child {
            border-bottom: none;
        }
        .info-label {
            color: #b39ddb;
            font-weight: 500;
        }
        .info-value {
            color: #fff;
            font-family: inherit;
        }
        .actions {
            margin-top: 32px;
            display: flex;
            gap: 16px;
        }
        .btn {
            padding: 10px 22px;
            border: none;
            border-radius: 7px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            background: linear-gradient(135deg, #7b4397 0%, #dc2430 100%);
            color: #fff;
            transition: box-shadow 0.2s, transform 0.2s;
            box-shadow: 0 2px 8px rgba(123, 67, 151, 0.15);
        }
        .btn:hover {
            box-shadow: 0 4px 16px rgba(123, 67, 151, 0.25);
            transform: translateY(-2px);
        }
        .session {
            margin-top: 40px;
            background: #292933;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 2px 8px rgba(44, 19, 59, 0.15);
        }
        .session h2 {
            color: #b39ddb;
            margin-top: 0;
        }
        .session pre {
            background: #232526;
            color: #bdbdbd;
            border-radius: 6px;
            padding: 12px;
            font-size: 0.95rem;
            overflow-x: auto;
        }
        .enterprise {
            margin-top: 40px;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 18px;
        }
        .enterprise-card {
            background: #232526;
            border-radius: 10px;
            padding: 18px 12px;
            text-align: center;
            color: #bdbdbd;
            box-shadow: 0 1px 4px rgba(44, 19, 59, 0.10);
        }
        .enterprise-card .icon {
            font-size: 1.7rem;
            margin-bottom: 8px;
        }
        .enterprise-card .title {
            color: #b39ddb;
            font-weight: 600;
            margin-bottom: 2px;
        }
        .main-content {
            display: flex;
            flex-direction: row;
            gap: 40px;
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
        }
        .main-left, .main-right {
            flex: 1 1 0;
            min-width: 0;
        }
        .main-left {
            display: flex;
            flex-direction: column;
            gap: 32px;
        }
        .main-right {
            display: flex;
            flex-direction: column;
            gap: 32px;
        }
        @media (max-width: 900px) {
            .profile-header {
                flex-direction: column;
                align-items: center;
                text-align: center;
                padding: 32px 0 32px 0;
            }
            .avatar {
                margin: 0 0 18px 0;
            }
            .profile-header-info {
                display: block;
                text-align: center;
            }
            .main-content {
                flex-direction: column;
                gap: 0;
                max-width: 98vw;
            }
            .main-left, .main-right {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="profile-header">
            <div class="avatar">
                <?= strtoupper(substr(getUserValue($user, 'name', 'U'), 0, 1)) ?>
            </div>
            <div class="profile-header-info">
                <h1><?= htmlspecialchars(getUserValue($user, 'name', 'Unknown'), ENT_QUOTES, 'UTF-8') ?></h1>
                <p><?= htmlspecialchars(getUserValue($user, 'email', 'unknown@example.com'), ENT_QUOTES, 'UTF-8') ?></p>
            </div>
        </div>
        <div class="main-content">
            <div class="main-left">
                <ul class="info-list">
                    <li><span class="info-label">Role:</span><span class="info-value"><?= htmlspecialchars(strtoupper(getUserValue($user, 'role', 'user')), ENT_QUOTES, 'UTF-8') ?></span></li>
                    <li><span class="info-label">Status:</span><span class="info-value"><?= htmlspecialchars(strtoupper(getUserValue($user, 'status', 'unknown')), ENT_QUOTES, 'UTF-8') ?></span></li>
                    <li><span class="info-label">User ID:</span><span class="info-value" style="font-family: monospace; font-size: 13px;">
    <?= htmlspecialchars($sessionInfo['user_id'] ?? 'Not set', ENT_QUOTES, 'UTF-8') ?>
</span></li>
                </ul>
                <?php if (isset($user)) : ?>
                <pre style="background:#232526;color:#bdbdbd;padding:8px 12px;border-radius:6px;font-size:12px;overflow-x:auto;">DEBUG $user: <?= htmlspecialchars(print_r($user, true), ENT_QUOTES, 'UTF-8') ?></pre>
                <?php endif; ?>
                <div class="actions">
                    <a href="/user/edit" class="btn">✏️ Edit Profile</a>
                    <a href="/user/password" class="btn" style="background: linear-gradient(135deg, #232526 0%, #7b4397 100%);">🔒 Change Password</a>
                    <form method="POST" action="/logout" style="display: inline; margin: 0;">
                        <?php if (isset($csrf)): ?>
                            <?= $csrf->getHiddenInput('logout') ?>
                        <?php endif; ?>
                        <button type="submit" class="btn" style="background: linear-gradient(135deg, #dc2430 0%, #232526 100%);">🚪 Logout</button>
                    </form>
                </div>
            </div>
            <div class="main-right">
                <div class="session">
                    <h2>🔐 Session Information</h2>
                    <?php if (isset($sessionInfo)): ?>
                        <ul class="info-list">
                            <li><span class="info-label">Session ID:</span><span class="info-value" style="font-family: monospace; font-size: 13px;">
                                <?= htmlspecialchars(substr($sessionInfo['session_id'] ?? 'unknown', 0, 16), ENT_QUOTES, 'UTF-8') ?>...
                            </span></li>
                            <li><span class="info-label">Session Started:</span><span class="info-value">
                                <?= $sessionInfo['session_started'] ? '✅ Yes' : '❌ No' ?>
                            </span></li>
                            <li><span class="info-label">User Data:</span><span class="info-value">
                                <?= isset($sessionInfo['user_data']) ? '✅ Loaded' : '❌ Missing' ?>
                            </span></li>
                        </ul>
                        <pre><?= htmlspecialchars(json_encode($sessionInfo, JSON_PRETTY_PRINT), ENT_QUOTES, 'UTF-8') ?></pre>
                    <?php endif; ?>
                </div>
                <div class="enterprise">
                    <div class="enterprise-card">
                        <div class="icon">🗄️</div>
                        <div class="title">CakePHP Database</div>
                        <div>Query Builder + ORM</div>
                    </div>
                    <div class="enterprise-card">
                        <div class="icon">📝</div>
                        <div class="title">Monolog Logging</div>
                        <div>Enterprise Logging</div>
                    </div>
                    <div class="enterprise-card">
                        <div class="icon">🔐</div>
                        <div class="title">Odan Session</div>
                        <div>Secure Sessions</div>
                    </div>
                    <div class="enterprise-card">
                        <div class="icon">✅</div>
                        <div class="title">CakePHP Validator</div>
                        <div>Input Validation</div>
                    </div>
                    <div class="enterprise-card">
                        <div class="icon">🛡️</div>
                        <div class="title">CSRF Protection</div>
                        <div>Security First</div>
                    </div>
                    <div class="enterprise-card">
                        <div class="icon">📁</div>
                        <div class="title">Proper Paths</div>
                        <div>No ../.. hacks</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
