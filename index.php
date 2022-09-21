<?php
header("Access-Control-Allow-Origin: http://localhost:3000");

$_ENV["current"] = "dev";
$config = file_get_contents("configs/" . $_ENV["current"] . ".config.json");
$_ENV['config'] = json_decode($config);

require_once 'services/database.service.php';
require_once 'controllers/database.controller.php';


$route = trim($_SERVER["REQUEST_URI"], '/');
$route = filter_var($route, FILTER_SANITIZE_URL);
$route = explode('/', $route);

$controllerName = array_shift($route);

// permet de lister toutes les table de notre base de données blog
// Puis pour chaque nom de table contenu dans $rows, nous créons un fichier avec son contenu.
if ($_ENV["current"] == "dev" && $controllerName == 'init') {
       $dbs = new DatabaseService(null);
       $query_resp = $dbs->query("SELECT table_name FROM information_schema.tables WHERE table_schema = ?", ['blog']);
       $rows = $query_resp->statement->fetchAll(PDO::FETCH_COLUMN);
       foreach ($rows as $tableName) {
              $controllerFile = "controllers/$tableName.controller.php";
              if (!file_exists($controllerFile)) {
                     $fileContent = "<?php class " . ucfirst($tableName) . "Controller extends DatabaseController {\r\n\r\n}?>";
                     file_put_contents($controllerFile, $fileContent);
                     echo ucfirst($tableName) . "Controller created\r\n";
              }
       }

       echo 'api initialized';
}

$controllerFilePath = "controllers/$controllerName.controller.php";
if (!file_exists($controllerFilePath)) {
       // header('HTTP/1.0 404 Not Found');
       die;
}

require_once $controllerFilePath;
$controllerClassName = ucfirst($controllerName) . "Controller";
$controller = new $controllerClassName($route);

$response = $controller->action;
if (!isset($response)) {
       header('HTTP/1.0 404 Not Found');
       die;
}

echo json_encode($response);
?>