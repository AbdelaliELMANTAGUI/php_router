<?php

include_once 'Request.php';
include_once 'Router.php';
$router = new Router(new Request);

$router->get('/', function ($request,$next){
  echo "<br>First middleware<br>";
  $next();
},function ($request,$next){ 
  echo "<br>Second Middleware<br>";
  $next();
},function() {
  return <<<HTML
  <br>
  <h1>Hello world</h1>
HTML;
});

$router->get('/my', function($request) {
  return <<<HTML
  <button >Hi bro how are you</button>
HTML;
});
$router->get("/route/:idd/yy/:id",function($request,$next){$next();},function($request){ echo "<h1> /route/{$request->idd}  -  {$request->id};) </h1>";});
$router->get('/:company/:profile', function($request) {
return <<<HTML
  <h1>Profile</h1>
HTML;
});
$router->post("/route/:param",function(){});
$router->post('/data', function($request) {

  return json_encode($request->getBody());
});
/*
echo "<br> midlewares empty <br>**";
print_r($router["post@/data"]);
echo "**<br>";

*/

/*

<?php
$str = "/:first/:second";
$pattern = "/:\w+/";
$matches = array();
var_dump(preg_match_all($pattern, $str,$matches));
echo "<br>";
var_dump($matches);

*/
?>
