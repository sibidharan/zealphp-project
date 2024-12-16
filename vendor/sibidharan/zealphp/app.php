<?php

require_once __DIR__ . '/vendor/autoload.php';
use OpenSwoole\Coroutine as co;
use OpenSwoole\Coroutine\Channel;
use ZealPHP\App;
use ZealPHP\G;

use function ZealPHP\elog;
use function ZealPHP\zlog;
App::superglobals(true);

$app = App::init('0.0.0.0', 8181);
// $app->route('/', function() {
//     zlog("App started", "system");
//     echo "<h1>This is index override</h1>";
// });

$app->route('/sessleak', function() {
    $channel = new Channel(1);
    go(function() use ($channel){
        $g = G::getInstance();
        $g->session['test'] = 'test';
        elog("Session leak started, inside coroutine, waiting for 10 seconds to check if _SESSION gets overwritten. Now bombard the server with requests...", "test");
        co::sleep(2);
        $g->session['test'];
        co::sleep(2);
        $g->session['test'];
        co::sleep(2);
        $g->session['test'];
        co::sleep(2);
        $g->session['test'];
        co::sleep(2);
        $g->session['test'];
        $channel->push($g->session);
    });
        // elog("Session leak started, inside coroutine, waiting for 10 seconds to check if _SESSION gets overwritten. Now bombard the server with requests...", "test");
    $data = $channel->pop();
    echo "<pre>";
    print_r($data ?? "Session leak detected");
    echo "</pre>";
});

$app->route('/co', function() {
    $channel = new Channel(5);
    go(function() use ($channel) {
        co::sleep(3);
        $channel->push('Hello, Coroutine 1!');
    });
    go(function() use ($channel) {
        co::sleep(3);
        $channel->push('Hello, Coroutine! 2');
    });
    go(function() use ($channel) {
        co::sleep(1);
        $channel->push('Hello, Coroutine! 3');
    });
    go(function() use ($channel) {
        co::sleep(2);
        $channel->push('Hello, Coroutine! 4');
    });
    go(function() use ($channel) {
        co::sleep(3);
        $channel->push('Hello, Coroutine 5!');
    });
    $results = [];
    for ($i = 0; $i < 5; $i++) {
        $results[] = $channel->pop();
    }
    echo "<pre>";
    print_r($results);
    echo "</pre>";
});

// $app->route('/home', function() {
//     echo "<h1>This is home override</h1>";
// });

$app->route('/quiz/{page}', function($page) {
    echo "<h1>This is quiz: $page</h1>";
});

$app->route('/quiz/{page}/{tab}/{nwe}', function($nwe, $tab, $page) {
    echo "<h1>This is quiz: $page tab=$tab</h1>";
});

// $app->route('/quiz/{page}/{tab}/{id}', function($page, $tab, $id) {
//     echo "<h1>This is quiz: $page tab=$tab id=$id</h1>";
// });

// $app->route('/hello/{name}', function($name, $self) {
//     echo "<h1>Hello, $self->get $name!</h1>";
// });

$app->route("/global/{name}", [
    'methods' => ['GET', 'POST']
],function($name) {
    // $g = G::getInstance();
    if (isset($GLOBALS[$name])) {
        print_r($GLOBALS[$name]);
    } else{
        echo "Unknown superglobal";
    }
});

$app->route("/coglobal/set/session", [
    'methods' => ['GET', 'POST']
],function($name) {
    G::set('session', ['name' => 'John Doe']);
});

$app->route("/coglobal/get/session", [
    'methods' => ['GET', 'POST']
],function($name) {
    echo G::get('session')['name'];
});

$app->route('/user/{id}/post/{postId}',[
    'methods' => ['GET', 'POST']
], function($id, $postId) {
    echo "<h1>User $id, Post $postId</h1>";
});

$app->nsRoute('watch', '/get/{key}', function($key){
    echo $_GET[$key] ?? null;
});

// patternRoute
// Matches any URL starting with /raw/
$app->patternRoute('/raw/(?P<rest>.*)', ['methods' => ['GET']], function($rest) {
    echo "You requested: $rest";
});

# Override Implicit Rules
// $app->nsRoute('api', '{name}', function($name) {
//     echo "<h1>Namespace Route Override, $name!</h1>";
// });


$app->run();