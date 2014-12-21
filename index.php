<?php
require "vendor/autoload.php";

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\HttpKernelInterface;


class MyApplication implements HttpKernelInterface {
    public function handle(Request $request, $type = HttpKernelInterface::MASTER_REQUEST, $catch = true)
    {
        $token = $request->attributes->get('oauth.token');
        if (!$token) {
            return new RedirectResponse('/auth');
        }

        $params = $token->getExtraParams();
        $mustache = $request->attributes->get('mustache');
        return $mustache->render('index.html', ['name' => $params['screen_name']]);
    }
}

class Mustache implements HttpKernelInterface {
    private $app;
    private $engine;
    public function __construct(HttpKernelInterface $app, array $options = []) {
        $this->app = $app;
        $this->engine = new Mustache_Engine($options);
    }

    public function handle(Request $request, $type = HttpKernelInterface::MASTER_REQUEST, $catch = true)
    {
        $request->attributes->set('mustache', $this);
        $request->attributes->set('mustache.engine', $this->engine);
        return $this->app->handle($request, $type, $catch);
    }

    public function render($template, $params)
    {
        return new Response($this->engine->render($template, $params));
    }
}

$app = new MyApplication;

$stack = (new Stack\Builder())
    ->push('Stack\\Session')
    ->push('Igorw\\Stack\\OAuth', [
        'key' => getenv('OAUTH_KEY'),
        'secret' => getenv('OAUTH_SECRET'),
        'callback_url' => 'http://localhost:9000/auth/verify',
        'success_url' => '/',
        'failure_url' => '/auth',
    ])
    ->push('Mustache', [
        'loader' => new Mustache_Loader_FilesystemLoader(__DIR__ . '/views'),
    ]);

Stack\run($stack->resolve($app));
