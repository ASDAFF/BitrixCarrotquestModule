<?
global $DB, $MESS, $APPLICATION;

// Подключаю файл с константами
$modulePath = str_replace("\\", "/", __FILE__);
$modulePath = substr($modulePath, 0, strlen($modulePath) - strlen("/include.php"));
include_once($modulePath."/constants.php");

// Этот массив был в исходном модуле, я не стал убирать.
$GLOBALS["xsd_simple_type"] = array(
	"string"=>"string", "bool"=>"boolean", "boolean"=>"boolean",
	"int"=>"integer", "integer"=>"integer", "double"=>"double", "float"=>"float", "number"=>"float",
	"base64"=>"base64Binary", "base64Binary"=>"base64Binary",
	"any"=>"any"
);

// Подключаем js, который создаст объект carrotquest на каждой странице
CJSCore::RegisterExt('carrotquest', array(
	'js' => CARROTQUEST_JS_PATH.'carrotquest_init.js',
));

// Подклчюаем js для управления куками из JavaScript
CJSCore::RegisterExt('cookie', array(
	'js' => CARROTQUEST_JS_PATH.'cookie.js',
));

// По умолчанию jquery выключен. Тем не менее он встроен в битрикс. Инициализируем все три расширения js.
CJSCore::Init(array('jquery', 'cookie','carrotquest'));

// Подключаем файлы с классами carrotquest-а
CModule::AddAutoloadClasses(
	CARROTQUEST_MODULE_ID,
	array(
		"CarrotQuestEventHandlers" => "classes/general/CarrotQuestEventHandlers.php",
		"CarrotQuestApi" => "classes/general/CarrotQuestApi.php",
	)
);

/**
* Переменная содержит инициализированный, единый для всей страницы объект <var>CarrotQuestApi</var>
*/
$GLOBALS['carrotquest_API'] = new CarrotQuestApi();

// Подключаем язык
IncludeModuleLangFile(__FILE__);

?>