<?php

declare(strict_types=1);

use DI\Container;
use MarkdownBlog\ContentAggregator\ContentAggregatorFactory;
use MarkdownBlog\ContentAggregator\ContentAggregatorInterface;
use Mni\FrontYAML\Parser;
use Psr\Http\Message\{
    ResponseInterface as Response,
    ServerRequestInterface as Request
};
use Slim\Factory\AppFactory;
use Slim\Views\{Twig,TwigMiddleware};
use Symfony\Component\Yaml\Yaml;
use Twig\Extra\Intl\IntlExtension;

require __DIR__ . '/../vendor/autoload.php';

$config = Yaml::parseFile('../config/config.yaml');

$container = new Container();
$container->set('view', function($c) {
        $twig = Twig::create(__DIR__ . '/../resources/templates');
        $twig->addExtension(new IntlExtension());
        return $twig;
});
$container->set(
    ContentAggregatorInterface::class,
    fn() => (new ContentAggregatorFactory())->__invoke([
        'path' => __DIR__ . '/../data/posts',
        'parser' => new Parser(),
    ])
);

AppFactory::setContainer($container);
$app = AppFactory::create();
$app->add(TwigMiddleware::createFromContainer($app));

$app->map(['GET'], '/', function (Request $request, Response $response, array $args) {
    global $config;
    $view = $this->get('view');
    /** @var ContentAggregatorInterface $contentAggregator */
    $contentAggregator = $this->get(ContentAggregatorInterface::class);
    $sorter = new \MarkdownBlog\Sorter\SortByReverseDateOrder();
    $items = $contentAggregator->getItems();
    usort($items, $sorter);
    $iterator = new \MarkdownBlog\Iterator\PublishedItemFilterIterator(
        new ArrayIterator($items)
    );
    return $view->render(
        $response,
        'index.html.twig',
        ['site' => $config['site'],
        'links' => $config['links'],
        'items' => $iterator]
    );
});

$app->map(['GET'], '/item/{slug}', function (Request $request, Response $response, array $args) {
    $view = $this->get('view');
    /** @var ContentAggregatorInterface $contentAggregator */
    $contentAggregator = $this->get(ContentAggregatorInterface::class);
    return $view->render(
        $response,
        'view.html.twig',
        ['site' => $config['site'],'item' => $contentAggregator->findItemBySlug($args['slug'])]
    );
});

/*
$app->map(['GET'], '/privacy-policy', function (Request $request, Response $response, array $args) {
    global $config;
    $view = $this->get('view');

    return $view->render(
        $response,
        'privacy.html.twig',
        ['site' => $config['site'],
        'links' => $config['links'],]
    );
});
*/

// Register routes based on config
if (isset($config['links']) && is_array($config['links'])) {
    foreach ($config['links'] as $link) {
        $title = $link['title'] ?? 'Untitled';
        $url = $link['url'] ?? '';
        $template = $link['template'] ?? null;

        // Register only internal routes with a valid Twig template
        if (!preg_match('/^https?:\/\//i', $url) && !empty($template)) {
            $app->map(['GET'], $url, function (Request $request, Response $response) use ($template, $title) {
                $view = $this->get('view');
                return $view->render($response, $template, [
                    'title' => $title,
                ]);
            });
        }
    }
}


$app->run();