<?php
class Router
{
  private $request;
  private $supportedHttpMethods = array(
    "GET",
    "POST"
  );
  private $paramRoutes = array();
  private $currentRequestUri;
  function __construct(IRequest $request)
  {
   $this->request = $request;
    $this->currentRequestUri = $this->request->requestUri;
  }
  private function addParamRoute($route,$method){    
    $pattern = "/:\w+/";
    $matches = array();
    $paramRoute = array();
    $delimiter = "/";
    $explodedRoute = explode($delimiter,trim($route,$delimiter));
    if(preg_match_all($pattern, $route,$matches)){
      $paramRoute["method"] = $method;
      $paramRoute["fullRoute"] = $route;    
      $paramRoute["routePattern"] = str_replace("/","\/",$route);
      $paramRoute["rawParams"] = array();
      $paramRoute["routeChunks"] = array();
      $allMatches =  $matches[0];
      $paramRoute["rawParams"] = array();
      foreach($explodedRoute as $routeChunk){
          $routeChunkPattern = "/^:\w+$/";
          $routeChunkItem = array();
          $routeChunkItem["raw"] = $routeChunk;
          if(preg_match($routeChunkPattern,$routeChunk)){
            $routeChunkItem["isParam"] = true;
          }else{
            $routeChunkItem["isParam"] = false;
          }
          array_push($paramRoute["routeChunks"],$routeChunkItem);
      }
      foreach($allMatches as $match){
        $rawParam = array();
        $paramRoute["routePattern"] = str_replace($match,"\w+",$paramRoute["routePattern"]);
        $rawParam["rawParam"] = $match;                
        $rawParam["paramIndex"] = array_search($match,$explodedRoute);
        array_push($paramRoute["rawParams"],$rawParam);
      }
      $paramRoute["routePattern"] = "/^".$paramRoute["routePattern"]."$/";
      array_push($this->paramRoutes,$paramRoute);
    }    
  }
  private function isRouteHaveParams($route){
    $pattern = "/\/:\w+/";
    if(preg_match($pattern,$route)){
      return true;
    }
    return false;
  }

  function __call($name, $args)
  {
    /*
    echo "$name args : <pre>";
    print_r($args);
    echo "</pre>";
    */
    list($route) = $args;
    $method = end($args);
    $middlewares = null;
    $argCount = count($args);
    if( $argCount > 2){
      $middlewares = array_slice($args,1,$argCount - 2);
    }
    if(!in_array(strtoupper($name), $this->supportedHttpMethods))
    {
      $this->invalidMethodHandler();
      return;
    }
    if($this->isRouteHaveParams($this->formatRoute($route))){
      $this->addParamRoute($this->formatRoute($route),$name);
    }

    $this->{strtolower($name)}[$this->formatRoute($route)] = $method;
    $this->{strtolower($name)."@".$this->formatRoute($route)} = $middlewares;
  }

  /**
   * Removes trailing forward slashes from the right of the route.
   * @param route (string)
   */
  private function formatRoute($route)
  {
    $result = rtrim($route, '/');
    if ($result === '')
    {
      return '/';
    }
    return $result;
  }

  private function invalidMethodHandler()
  {
    header("{$this->request->serverProtocol} 405 Method Not Allowed");
  }
  private function defaultRequestHandler()
  {
    header("{$this->request->serverProtocol} 404 Not Found");
  }
  
  private function isRouteExist($route){
    // get all current method routes handlers
    $methodDictionary = $this->{strtolower($this->request->requestMethod)};
    // get formated route
    $formatedRoute = $this->formatRoute($route);
    // get handler for the current route
    if(!$methodDictionary[$formatedRoute] || $this->isRouteHaveParams($route) ){
      return false;
    }
    return true;
  }

  private function matchRouteParam($route){
    $delimiter = "/";
    foreach($this->paramRoutes as $paramRoute){      
      $pattern = $paramRoute["routePattern"];
      $matches = array();
      if($paramRoute["method"] == strtolower($this->request->requestMethod) && preg_match_all($pattern,$route,$matches)){
        echo "<br> ------------------------ !  macth routes : ------------------------------- <br>";
        echo "<br> $pattern , $route , <br>";
        print_r($matches);
        echo "<br>";
        print_r($paramRoute);
        echo "<br>";
        $explodedRoute = explode($delimiter,trim($route,$delimiter));
        /*foreach($paramRoute["routeChunks"] as $index => $routeChunk){
          echo "<br>". $routeChunk["isParam"]  ." - " . $explodedRoute[$index]. " - ". $routeChunk["raw"] . "<br>";
          if($routeChunk["isParam"] == false && $routeChunk["raw"] != $explodedRoute[$index]) return false;
        }*/
        foreach( $paramRoute["rawParams"] as $param){
          $this->request->{trim($param["rawParam"],":")} = $explodedRoute[$param["paramIndex"]];
        }
        $this->currentRequestUri = $paramRoute["fullRoute"];
        echo "<br> <pre>";
        print_r($this->request);
        echo "<pre> <br>";
        return true;
      }
    }
    return false;
  }

  /**
   * Resolves a route
   */
  function resolve()
  {
    // get all current method routes handlers
    $methodDictionary = $this->{strtolower($this->request->requestMethod)};
    // get formated route
    $formatedRoute = $this->formatRoute($this->currentRequestUri);
    // get handler for the current route
    $method = $methodDictionary[$formatedRoute];
    // check if ther's no handler fire defaultRequestHandler
    if(is_null($method))
    {
      $this->defaultRequestHandler();
      return;
    }
    // run the current route handler with request as parameter
    echo call_user_func_array($method, array($this->request));
  }
  function nextMiddleware($index){    
    // get request method [get,post]    
    $methodName = strtolower($this->request->requestMethod);
    // get formated route (/route)
    $formatedRoute = $this->formatRoute($this->currentRequestUri);
    // get middlewares of this route
    $middlewares = $this->{strtolower($methodName)."@".$this->formatRoute($formatedRoute)};
    // if there's no midleware resolve
    if(empty($middlewares)) return $this->resolve();
    // if the current midleware doesn't existe resolve
    if(empty($middlewares[$index])) return  $this->resolve();
    // get current middleware 
    $currentMiddleware = $middlewares[$index];
    $nextIndex = $index + 1;
    /*
    $nextMiddleware = $this->resolve;
    if(!empty($middlewares[$nextIndex])){
      $nextMiddleware = $middlewares[$nextIndex];
    }
    */
    $next = function () use ($nextIndex){$this->nextMiddleware($nextIndex);};
    $currentMiddleware($this->request,$next);
  }  
  function __destruct()
  {
    /**/
    echo " router : <br>";
    echo "<pre> <h1>";
    var_dump($this);
    echo "</h1></pre>";
    echo " Params : <br>";
    echo "<pre> <h1>";
    var_dump($this->paramRoutes);
    echo "</h1></pre>";
    echo "<br>";
    $pattern = $this->paramRoutes[0]["routePattern"];
    echo "pattern |   $pattern   | test :";
    var_dump(preg_match_all($pattern,$this->formatRoute("/oppp/lol/")));
    echo "<br>";
    echo "Formated Route" . $this->formatRoute($this->currentRequestUri);
    echo "<br>";
    echo "Incoming Route" . $this->currentRequestUri . "<br>";
    // ----------------------------------------------------------
    if(!$this->isRouteExist($this->currentRequestUri)){
      if(!$this->matchRouteParam($this->currentRequestUri)){
        return $this->defaultRequestHandler();
      }
    }
    echo "<br> current route *{$this->currentRequestUri}*  <br>";
    $this->nextMiddleware(0);
  }
}
