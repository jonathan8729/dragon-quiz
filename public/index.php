<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/vendor/autoload.php';

use DI\ContainerBuilder;
use function DI\create;
use function DI\get;
use DragonQuiz\Controller\HelloWorld;
use FastRoute\RouteCollector;
use function FastRoute\simpleDispatcher;
use Middlewares\FastRoute;
use Middlewares\RequestHandler;
use Narrowspark\HttpEmitter\SapiEmitter;
use Relay\Relay;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Zend\Diactoros\ServerRequestFactory;
use Zend\Diactoros\Response;

require_once dirname(__DIR__) . '/bootstrap.php';

$containerBuilder = new ContainerBuilder();
$containerBuilder->useAutowiring(false);
$containerBuilder->useAnnotations(false);

$containerBuilder->addDefinitions([
    HelloWorld::class => create(HelloWorld::class)->constructor(get('Response'), get('Twig'), get('EntityManager')),
    'Response' => function() {
        return new Response();
    },
    'Twig' => function() {
        $loader = new FilesystemLoader(dirname(__DIR__) . '/src/view/');

        $twig = new Environment($loader, [
            'cache' => dirname(__DIR__) . '/src/view/cache/',
        ]);

        return $twig;
    },
    'EntityManager' => function() use($entityManager) {
        return $entityManager;
    }
]);

$container = $containerBuilder->build();

$routes = simpleDispatcher(function (RouteCollector $r) {
    $r->get('/', HelloWorld::class);
});

$middlewareQueue[] = new FastRoute($routes);
$middlewareQueue[] = new RequestHandler($container);

$requestHandler = new Relay($middlewareQueue);
$response = $requestHandler->handle(ServerRequestFactory::fromGlobals());

$emitter = new SapiEmitter();
return $emitter->emit($response);
