<?php
include_once 'Request.php';
include_once 'Router.php';
$router = new Router(new Request);

$router->get('/', function () {

  return <<<HTML
  <br>
  <h1>Hello world</h1>
HTML;
});

$router->get('/middleware', function ($request, $next) {
  echo "From middleware  <br>";
  $next();
},function ($request, $next) {
  echo "From middleware 2 <br>";
  $next();
}, function ($request) {
  return <<<HTML
  <button >Hi bro how are you</button>
  HTML;
});

$router->get('/company/:profile', function ($request) {
  return <<<HTML
  profile : $request->profile <br>
  <h1>Profile</h1>
HTML;//http://localhost:3000/
});

$router->get("/route/:idd/yy/:id", function ($request) {
  echo "<h1> /route/{$request->idd}  -  {$request->id};) </h1>";
});

$router->post('/data', function ($request) {

  return json_encode($request->getBody());
});