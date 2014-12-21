<?php
require "vendor/autoload.php";

use Stack\CallableHttpKernel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\HttpKernelInterface;

$app = new CallableHttpKernel(function (Request $request) {
    $token = $request->attributes->get('oauth.token');
    if (!$token) {
        return new RedirectResponse('/auth');
    }

    $params = $token->getExtraParams();
    return new Response($request->attributes->get('mustache')->render('Hello, {{ name }}', ['name' => $params['screen_name']]));
});

class Mustache implements HttpKernelInterface {
    private $app;
    private $engine;
    public function __construct(HttpKernelInterface $app, array $options = []) {
        $this->app = $app;
        $this->engine = new Mustache_Engine($options);
    }

    public function handle(Request $request, $type = HttpKernelInterface::MASTER_REQUEST, $catch = true)
    {
        $request->attributes->set('mustache', $this->engine);
        return $this->app->handle($request, $type, $catch);
    }
}


$stack = (new Stack\Builder())
    ->push('Stack\\Session')
    ->push('Igorw\\Stack\\OAuth', [
        'key' => getenv('OAUTH_KEY'),
        'secret' => getenv('OAUTH_SECRET'),
        'callback_url' => 'http://localhost:9000/auth/verify',
        'success_url' => '/',
        'failure_url' => '/auth',
    ])
    ->push('Mustache');

Stack\run($stack->resolve($app));
