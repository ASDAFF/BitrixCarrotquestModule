<?
global $DB, $MESS, $APPLICATION;

// Этот массив был в исходном модуле, я не стал убирать.
$GLOBALS["xsd_simple_type"] = array(
	"string"=>"string", "bool"=>"boolean", "boolean"=>"boolean",
	"int"=>"integer", "integer"=>"integer", "double"=>"double", "float"=>"float", "number"=>"float",
	"base64"=>"base64Binary", "base64Binary"=>"base64Binary",
	"any"=>"any"
);

// Подключаем js, который создаст объект carrotquest на каждой странице
CJSCore::RegisterExt('carrotquest', array(
	'js' => '/bitrix/js/carrotquest/carrotquest_init.js',
));
// Подклчюаем js для управления куками из JavaScript
CJSCore::RegisterExt('cookie', array(
	'js' => '/bitrix/js/carrotquest/cookie.js',
));

// По умолчанию jquery выключен. Тем не менее он встроен в битрикс. Инициализируем все три расширения js.
CJSCore::Init(array("jquery", "carrotquest", "cookie"));

// Подключаем файлы с классами carrotquest-а
CModule::AddAutoloadClasses(
	"carrotquest",
	array(
		"CarrotQuestEventHandlers" => "classes/general/CarrotQuestEventHandlers.php",
		"CarrotQuestApi" => "classes/general/CarrotQuestApi.php",
	)
);

// Нам нужно, чтобы во всем приложении действовал один объект
$GLOBALS['CQ'] = new CarrotQuestApi();

// Подключаем язык
IncludeModuleLangFile(__FILE__);

?>