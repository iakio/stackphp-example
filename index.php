<?php
require "vendor/autoload.php";

use Stack\CallableHttpKernel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
$app = new CallableHttpKernel(function (Request $request) {
    $token = $request->attributes->get('oauth.token');
    if (!$token) {
        return new RedirectResponse('/auth');
    }

    $params = $token->getExtraParams();
    return new Response("Hello, " . $params['screen_name']);
});

$stack = (new Stack\Builder())
    ->push('Stack\\Session')
    ->push('Igorw\\Stack\\OAuth', [
        'key' => getenv('OAUTH_KEY'),
        'secret' => getenv('OAUTH_SECRET'),
        'callback_url' => 'http://localhost:9000/auth/verify',
        'success_url' => '/',
        'failure_url' => '/auth',
    ]);

Stack\run($stack->resolve($app));