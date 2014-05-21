<?
global $DB, $MESS, $APPLICATION;

$GLOBALS["xsd_simple_type"] = array(
	"string"=>"string", "bool"=>"boolean", "boolean"=>"boolean",
	"int"=>"integer", "integer"=>"integer", "double"=>"double", "float"=>"float", "number"=>"float",
	"base64"=>"base64Binary", "base64Binary"=>"base64Binary",
	"any"=>"any"
);

CJSCore::RegisterExt('carrotquest', array(
	'js' => '/bitrix/js/carrotquest/carrotquest_init.js',
));
CJSCore::RegisterExt('cookie', array(
	'js' => '/bitrix/js/carrotquest/cookie.js',
));

CJSCore::Init(array("jquery", "carrotquest", "cookie"));

CModule::AddAutoloadClasses(
	"carrotquest",
	array(
		"CarrotQuestEventHandlers" => "classes/general/CarrotQuestEventHandlers.php",
		"CarrotQuestApi" => "classes/general/CarrotQuestApi.php",
	)
);
$GLOBALS['CQ'] = new CarrotQuestApi();


IncludeModuleLangFile(__FILE__);

?>