<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= htmlspecialchars($description ?? 'HDM Boot Blog - Articles about Hexagonal Architecture, Domain-Driven Design, and modern PHP development with enterprise patterns.') ?>">
    <meta name="keywords" content="HDM Boot, PHP blog, Hexagonal Architecture, DDD, Domain-Driven Design, enterprise PHP, software architecture">
    <meta name="author" content="HDM Boot Team">
    <meta name="robots" content="index, follow">

    <!-- Open Graph -->
    <meta property="og:type" content="<?= isset($article) ? 'article' : 'website' ?>">
    <meta property="og:title" content="<?= htmlspecialchars(is_string($title ?? null) ? $title : 'HDM Boot Blog') ?>">
    <meta property="og:description" content="<?= htmlspecialchars($description ?? 'HDM Boot Blog - Articles about modern PHP development') ?>">
    <meta property="og:site_name" content="HDM Boot Blog">

    <title><?= htmlspecialchars(is_string($title ?? null) ? $title : 'HDM Boot Blog') ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 800px; margin: 0 auto; padding: 20px; }
        header { background: #2563eb; color: white; padding: 1rem 0; margin-bottom: 2rem; }
        header h1 { text-align: center; }
        nav { text-align: center; margin: 1rem 0; }
        nav a { color: white; text-decoration: none; margin: 0 1rem; padding: 0.5rem 1rem; border-radius: 4px; }
        nav a:hover { background: rgba(255,255,255,0.1); }
        .article { background: white; border: 1px solid #e5e7eb; border-radius: 8px; padding: 1.5rem; margin-bottom: 1.5rem; }
        .article h2 { color: #1f2937; margin-bottom: 0.5rem; }
        .article h2 a { color: inherit; text-decoration: none; }
        .article h2 a:hover { color: #2563eb; }
        .meta { color: #6b7280; font-size: 0.875rem; margin-bottom: 1rem; }
        .excerpt { margin-bottom: 1rem; }
        .tags { margin-top: 1rem; }
        .tag { background: #e5e7eb; color: #374151; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.75rem; margin-right: 0.5rem; }
        .content { line-height: 1.8; }
        .content h1, .content h2, .content h3 { margin: 1.5rem 0 1rem 0; color: #1f2937; }
        .content p { margin-bottom: 1rem; }
        .content code { background: #f3f4f6; padding: 0.125rem 0.25rem; border-radius: 3px; font-size: 0.875rem; }
        .content pre { background: #f3f4f6; padding: 1rem; border-radius: 6px; overflow-x: auto; margin: 1rem 0; }
        .back-link { color: #2563eb; text-decoration: none; margin-bottom: 1rem; display: inline-block; }
        .back-link:hover { text-decoration: underline; }
        .sidebar { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; padding: 1.5rem; margin-top: 2rem; }
        .sidebar h3 { margin-bottom: 1rem; color: #1f2937; }
        .sidebar ul { list-style: none; }
        .sidebar li { margin-bottom: 0.5rem; }
        .sidebar a { color: #2563eb; text-decoration: none; }
        .sidebar a:hover { text-decoration: underline; }
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 1rem; margin-bottom: 2rem; }
        .stat { background: white; border: 1px solid #e5e7eb; border-radius: 8px; padding: 1rem; text-align: center; }
        .stat-number { font-size: 2rem; font-weight: bold; color: #2563eb; }
        .stat-label { color: #6b7280; font-size: 0.875rem; }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <h1>🚀 HDM Boot Blog</h1>
            <nav>
                <a href="/blog">Home</a>
                <a href="/blog/categories">Categories</a>
                <a href="/blog/tags">Tags</a>
                <a href="/blog/about">About</a>
                <a href="/admin">Admin</a>
            </nav>
        </div>
    </header>

    <div class="container">
        <?= is_string($content ?? null) ? $content : '' ?>
    </div>
</body>
</html>
