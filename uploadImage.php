<?php

class DatabaseWork
{
	var $host,$login,$password;
	var $databaseConnector;

	
	public function __construct($host,$login,$password)
    {
		$this->host = $host;
		$this->login = $login;
		$this->password = $password;
		$this->databaseConnector = @mysqli_connect($host, $login, $password);
		if (!($this->databaseConnector)) {
			addLog("Error: ".mysqli_connect_error() + " (wrong login or password)");
			exit();
		}
    }
	
	function __destruct() 
	{
       $this->databaseConnector->close();
	}
   
	public function selectDatabase($databaseName)
	{
		$selector = mysqli_select_db($this->databaseConnector, $databaseName); 
		if (!$selector) {
			addLog("Error: ".mysqli_connect_error()+" (database not found)");
			exit();
		}
	}
	
	public function executeSQLQuery($querySQL)
	{
		echo $querySQL.'\n';
		if ($this->databaseConnector->query($querySQL) === false) {
			addLog("Error: ".mysqli_connect_error()." (wrong SQL query)");
			exit();
		}
	}
	

}

class UploadManager 
{
	var $protocolType = 'http://';
	var $fileSaveDirectry = '',$host;
	function __construct($host)
	{
		$this->host = $host;
	}
	
	public function writeFileToLocalStorage($content,$fileName)
	{
		$rez = file_put_contents($fileName, $content);
	}
	function getFileByURL($path)
	{
		
		if(($file = @file_get_contents($path)) === false)
		{
			echo "Wrong link";
			exit();
		}
		return $file;
	}
	function generateRandomName($length = 10) {
		$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$charactersLength = strlen($characters);
		$randomString = '';
		for ($i = 0; $i < $length; $i++) {
			$randomString .= $characters[rand(0, $charactersLength - 1)];
		}
		return $randomString;
	}
	function getExtension($path)
	{
		return pathinfo($path, PATHINFO_EXTENSION);
	}
	
	public function saveToLocalStorage($path, $content)
	{
		$name = $this->fileSaveDirectry.$this->generateRandomName(15);
		
		if(!is_null($content))
		{
			$name .= '.'.$this->getExtension($content['name']);
			move_uploaded_file($content['tmp_name'], __DIR__.'/'.$name);
			return $this->protocolType.$this->host.$name;
		}
	
		$name .= '.'.$this->getExtension($path);
        $this->writeFileToLocalStorage($this->getFileByURL($path),$name);
		return $this->protocolType.$this->host.$name;
	}
	public function saveToYandexDisk()
	{}
	
}

function myErrorHandler($errno, $errstr, $errfile, $errline)
{
    if (!(error_reporting() & $errno)) {
        return false;
    }
	addLog("[$errno] $errstr ошибка в строке $errline файла $errfile\n");
    switch ($errno) {
    case E_USER_ERROR:
        echo "<b>My ERROR</b> [$errno] $errstr<br />\n";
        echo "  Фатальная ошибка в строке $errline файла $errfile";
        echo ", PHP " . PHP_VERSION . " (" . PHP_OS . ")<br />\n";
        echo "Завершение работы...<br />\n";
        exit(1);
        break;
    case E_USER_WARNING:
	 echo "<b>My ERROR</b> [$errno] $errstr<br />\n";
        break;
    case E_USER_NOTICE:
	 echo "<b>My ERROR</b> [$errno] $errstr<br />\n";
        break;
	case E_WARNING:
	 echo "<b>My ERROR</b> [$errno] $errstr<br />\n";
        break;
	case E_NOTICE:
	 echo "<b>My ERROR</b> [$errno] $errstr<br />\n";
        break;
	case E_ERROR:
	 echo "<b>My ERROR</b> [$errno] $errstr<br />\n";
        break;
    default:
        echo "Неизвестная ошибка: [$errno] $errstr<br />\n";
        break;
    }
    return true;
}
function addLog($log)
{
	file_put_contents('./log_'.date("j.n.Y").'.txt', $log, FILE_APPEND);
}

set_error_handler("myErrorHandler");

$uploadManager = new UploadManager('localhost/Akscorp/');
$uploadManager->fileSaveDirectry = 'Files/';
$url = @$_POST['url'];
$file = $_FILES['file'];
$is_choose_crop = isset($_POST['is_choose_crop'])?$_POST['is_choose_crop']:"0";
$is_choose_resize = isset($_POST['is_choose_resize'])?$_POST['is_choose_resize']:"0";

if($url!="")
	echo $uploadManager->saveToLocalStorage($url,null);
else
	echo $uploadManager->saveToLocalStorage(null,$file);
//move_uploaded_file($_FILES['file']['tmp_name'], __DIR__.'\kek.jpg');

/*
$db = new DatabaseWork('localhost', 'root', '');
$db->selectDatabase('images');
$db->executeSQLQuery(sprintf('INSERT INTO listimages (%s) VALUES  (\'%s\')', 'link', 'lkjuhygfdcvbnj'));
*/



?>