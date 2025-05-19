$host = 'localhost:3307';
$dbname = 'phpdb';
$user = 'root';
$password = '';
$charset = 'utf8mb4';


$dsn = "mysql:host=$host;dbname=$dbname:charset=$charset";

$options = [
        PDO::ATTR_ERMODE         => PDO:: ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => fasle,
    ];

$pdo = null;

try{
    $pdo = new PDO($dsn,$user,$password,$options);

} catch (\PDOException $e){
    die("Error de conexión a la base de datos:" . $e->getMssage());
}

?>

