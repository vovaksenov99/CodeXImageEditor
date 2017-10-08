<?php

/*
описание каждой операции над изображением по параметрам

*/
class operationDescriptions
{
	static function cropImageDescription($x,$y,$cropWidth,$cropHeight)
	{		
		return 'cropWidth='.$cropWidth.'&cropHeight='.$cropHeight;
	}
	static function resizeImageDescription($width,$height)
	{		
		return 'resizeWidth='.$width.'&resizeHeight='.$height;
	}
	/*
	получение строки описаний кадой операции которая лежит в eventSystem
	*/
	static function getDescritionString(array $eventSystemArray)
	{
		$rezult = array();
		for($i=0;$i<count($eventSystemArray);$i++)
		{
			array_push($rezult,call_user_func_array(array('operationDescriptions',$eventSystemArray[$i][1].'Description'),array_slice($eventSystemArray[$i],2)));
		}	
		return 	'_'.implode('&',$rezult);
	}
}
/*
Класс отвечающий за хранение и обработку изображения 
*/
class Image
{
	var $imageResource = null,$imageExtension;
	var $height,$width;
	var $imageDirectory;
	var $imagePath = null;
	var $url;
	function __construct($url)
	{
		$this->url = $url;
		$this->imageExtension = strtolower($this->getExtension($url));
		$extensions = ["png","jpg","gif"];
		$isValidExtension = false;
		for ($i = 0; $i < 3; $i++) {
			if($this->imageExtension == $extensions[$i])
			{
				$isValidExtension = true;
				break;
			}
		}
		if(!$isValidExtension)
			throw new Exception('Invalid format. Supported format is '.implode(", ",$extensions).'. ');
		$image = imagecreatefromstring($this->getImageByURL($url));
		$this->imageResource = $image;
		$this->height = imagesy($image);
		$this->width = imagesx($image);
	}
	//Функции обработки
	function cropImage($x,$y,$cropWidth,$cropHeight)
	{
		 $this->imageResource = imagecrop( $this->imageResource, ['x' => $x, 'y' => $y, 'width' => min($cropWidth, $this->width), 'height' => min($cropHeight, $this->height)]);
		 return operationDescriptions::cropImageDescription($x,$y,$cropWidth,$cropHeight);
	}
	
	function resizeImage($width,$height)
	{
		$image_p = imagecreatetruecolor($width, $height);
		imagecopyresampled($image_p, $this->imageResource, 0, 0, 0, 0, $width, $height, $this->width, $this->height);
		$this->imageResource = $image_p;
		return operationDescriptions::resizeImageDescription($width,$height);
	}
	
	//Служебные функции
	function getImageByURL($path)
	{
		
		if(($file = @file_get_contents($path)) === false)
		{
			echo "Wrong link";
			exit();
		}
		return $file;
	}
	function getExtension($path)
	{
		return pathinfo($path, PATHINFO_EXTENSION);
	}
	function getName($path)
	{
		return pathinfo($path)['filename'];
	}
	function saveToMemory($description)
	{
		$name = $this->imageDirectory.$this->getName($this->url).$description;
		switch($this->imageExtension)
		{
			case "png":
				$name .= '.png';
				$this->imagePath = $name;
				if(!file_exists($name))
					imagepng($this->imageResource,$name);
				break;
			case "jpg":
				$name .= '.jpg';
				$this->imagePath = $name;
				
				if(!file_exists($name))
					imagejpeg($this->imageResource,$name);
				break;
			case "gif":
				$name .= '.gif';
				$this->imagePath = $name;
				if(!file_exists($name))
					imagegif($this->imageResource,$name);
				break;
		}
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
	
}
/*
Содержит список фильтров которые необходимо применить к изображению
*/
class EventSystem
{
	var $callFunctionArray = array();
	var $invokeDescription = array();
	function addEvent(array $args)
	{
		array_push($this->callFunctionArray,$args);
		

	}
	function invoke()
	{
		for ($i = 0; $i < count($this->callFunctionArray); $i++) {
			$obj = $this->callFunctionArray[$i][0];
			array_push($this->invokeDescription,call_user_func_array(array($obj,$this->callFunctionArray[$i][1]), array_slice($this->callFunctionArray[$i],2)));
		}
		return '_'.implode("&",$this->invokeDescription);
	}
	
}

$url = $_POST['url'];
$isChooseCrop = isset($_POST['is_choose_crop'])?$_POST['is_choose_crop']:"0";
$isChooseResize = isset($_POST['is_choose_resize'])?$_POST['is_choose_resize']:"0";

$eventSystem = new EventSystem();

$image = new Image($url);
$image->imageDirectory='Temp-files/';

//Добавляем в список событий фильтры которые нужно наложить
if($isChooseResize == "1")
{
	if(isset($_POST['x_resize']) || isset($_POST['y_resize']))
	{	
		$x_resize = (int)$_POST['x_resize'];
		$y_resize = (int)$_POST['y_resize'];
		if(is_int($x_resize) && is_int($y_resize) && $x_resize>0 && $y_resize>0)
			$eventSystem->addEvent(array($image,'resizeImage',$x_resize,$y_resize));
	}
}
if($isChooseCrop == "1")
{
	if(isset($_POST['x_crop']) || isset($_POST['y_crop']))
	{
		$x_crop = (int)$_POST['x_crop'];
		$y_crop = (int)$_POST['y_crop'];
		if(is_int($x_crop) && is_int($y_crop) && $x_crop>0 && $y_crop>0)
		{$eventSystem->addEvent(array($image,'cropImage',0,0,$x_crop,$y_crop));}
	}
}

$eventsDescription = operationDescriptions::getDescritionString($eventSystem->callFunctionArray);

$imgPath = $image->imageDirectory.pathinfo($url)['filename'].$eventsDescription.'.'.pathinfo($url)['extension'];
if(file_exists($imgPath))
{
	echo "<img src='" . $imgPath . "' alt='Очень жаль. Картинки нет'>";
	exit();
}
$eventSystem->invoke();
$image->saveToMemory($eventsDescription);
echo "<img src='" . $image->imagePath . "' alt='Очень жаль. Картинки нет'>";
?>