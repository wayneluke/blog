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

/***
* @implements ArrayAccess<mixed,mixed>
***/

final class ImmutableConfig implements ArrayAccess
{
    private array $data;

    /***
    * @param $data
    ***/
    
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function offsetExists($offset): bool
    {
        return isset($this->data[$offset]);
    }

    public function offsetGet($offset): mixed
    {
        return $this->data[$offset] ?? null;
    }

    public function offsetSet($offset, $value): void
    {
        throw new LogicException("Cannot modify immutable config.");
    }

    public function offsetUnset($offset): void
    {
        throw new LogicException("Cannot unset immutable config.");
    }

    public function toArray(): array
    {
        return $this->data;
    }
}

$config = new ImmutableConfig(Yaml::parseFile('../config/config.yaml'));

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


// Build Home Page.
$app->map(['GET'], '/', function (Request $request, Response $response, array $args) use ($app, $config) {
    $container = $app->getContainer();
    $view = $container->get('view');
    $contentAggregator = $container->get(ContentAggregatorInterface::class);
    $sorter = new \MarkdownBlog\Sorter\SortByReverseDateOrder();
    $items = $contentAggregator->getItems();
    usort($items, $sorter);
    $iterator = new \MarkdownBlog\Iterator\PublishedItemFilterIterator(
        new ArrayIterator($items)
    );
    return $view->render(
        $response,
        'index.html.twig',
        ['site' => $config['site'],'links' => $config['links'],'items' => $iterator]
    );
});

// Show individual blog entry.
$app->map(['GET'], '/post/{slug}', function (Request $request, Response $response, array $args) use ($app, $config) {
    $container = $app->getContainer();
    $view = $container->get('view');
    $contentAggregator = $container->get(ContentAggregatorInterface::class);
    return $view->render(
        $response,
        'view.html.twig',
        ['site' => $config['site'],'links' => $config['links'],'item' => $contentAggregator->findItemBySlug($args['slug'])]
    );
});


// Register routes based on $config['links'].
if (isset($config['links']) && is_array($config['links'])) {
    foreach ($config['links'] as $link) {
        $title = $link['title'] ?? 'Untitled';
        $url = $link['url'] ?? '';
        $template = $link['template'] ?? null;

        // Register only internal routes with a valid Twig template
        if (!preg_match('/^https?:\/\//i', $url) && !empty($template)) {
            $app->map(['GET'], $url, function (Request $request, Response $response) use ($app, $template, $title, $config) {
                $container = $app->getContainer();
                $view = $container->get('view');
                return $view->render($response, $template, [
                    'title' => $title,'site' => $config['site'],'links' => $config['links']
                ]);
            });
        }
    }
}

// Add the ErrorMiddleware
if ($config['site']['environment'] == 'debug') {
    $errorMiddleware = $app->addErrorMiddleware(true, false, false);
} else {
    $errorMiddleware = $app->addErrorMiddleware(false, false, false);
}


$app->run();