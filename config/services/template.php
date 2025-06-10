<?php

declare(strict_types=1);

use DI\Container;
use MvaBootstrap\Shared\Services\TemplateRenderer;
use MvaBootstrap\Modules\Core\Security\Services\CsrfService;
use Odan\Session\SessionInterface;
use ResponsiveSk\Slim4Paths\Paths;
use Slim\Views\PhpRenderer;

/**
 * Template Services Configuration.
 */
return [
    // Template Renderer
    TemplateRenderer::class => function (Container $container): TemplateRenderer {
        $paths = $container->get(Paths::class);
        $templatePath = $paths->base() . '/templates';

        return new TemplateRenderer(
            $templatePath,
            $container->get(CsrfService::class),
            $container->get(SessionInterface::class)
        );
    },

    // Slim PHP Renderer
    PhpRenderer::class => function (Container $container): PhpRenderer {
        $paths = $container->get(Paths::class);
        $templatePath = $paths->base() . '/templates';

        return new PhpRenderer($templatePath);
    },
];
