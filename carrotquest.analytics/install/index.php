<?
	
global $MESS;

$modulePath = str_replace("\\", "/", __FILE__);
$modulePath = substr($modulePath, 0, strlen($modulePath) - strlen("/install/index.php")); 
require_once($modulePath."/constants.php");

include_once(CARROTQUEST_MODULE_PATH."include.php");

require(GetLangFileName(CARROTQUEST_MODULE_PATH."lang/", "/install/index.php"));



CModule::IncludeModule('sale');

Class carrotquest_analytics extends CModule
{
	var $MODULE_ID = "carrotquest.analytics";
	var $MODULE_VERSION;
	var $MODULE_VERSION_DATE;
	var $MODULE_NAME;
	var $MODULE_DESCRIPTION;
	var $MODULE_GROUP_RIGHTS = "N";
	var $PARTNER_NAME;
	var $PARTNER_URI;
	
	function __construct()
	{
		//$this->MODULE_ID = CARROTQUEST_MODULE_ID;
		
		$arModuleVersion = array();

		global $modulePath;
		include_once($modulePath."/version.php");
		
		if (is_array($arModuleVersion) && array_key_exists("VERSION", $arModuleVersion))
		{
			$this->MODULE_VERSION = $arModuleVersion["VERSION"];
			$this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];
		}
		else
		{
			$this->MODULE_VERSION = '1.0.12';
			$this->MODULE_VERSION_DATE = '04.06.2014';
		}
		
		$this->PARTNER_NAME = "Carrot quest";
		$this->PARTNER_URI = "http://carrotquest.ru";
		$this->MODULE_NAME = GetMessage("CARROTQUEST_MODULE_NAME");
		$this->MODULE_DESCRIPTION = GetMessage("CARROTQUEST_MODULE_DESCRIPTION");
	}

	function DoInstall()
	{
		global $DOCUMENT_ROOT, $APPLICATION, $step, $obModule, $errors;
		
		$errors = array();
		// Необходим интернет магазин и каталог
		if (!IsModuleInstalled('sale') || !IsModuleInstalled('catalog'))
		{
			array_push($errors, "CARROTQUEST_DEPENDENCES_ERROR");
			$APPLICATION->IncludeAdminFile(GetMessage("CARROTQUEST_INSTALL_TITLE"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$this->MODULE_ID."/install/step2.php");
			return;
		};
		
		$step = IntVal($step);
		$obModule = $this;
		// 1 шаг установки
		if($step<2)
		{
			$APPLICATION->IncludeAdminFile(GetMessage("CARROTQUEST_INSTALL_TITLE"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$this->MODULE_ID."/install/step1.php");
		}
		elseif($step == 2) // Второй шаг установки
		{
			if($this->InstallFiles())
			{
				$this->InstallDB();
				$this->InstallEvents();	
				
				if (!empty($errors))
					$GLOBALS["errors"] = $this->errors;
					
				$APPLICATION->IncludeAdminFile(GetMessage("CARROTQUEST_INSTALL_TITLE"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$this->MODULE_ID."/install/step2.php");
					
			}
		}
	}
	
	function DoUninstall()
	{
		global $DOCUMENT_ROOT, $APPLICATION, $CQ;

        COption::RemoveOption("cqApiKey");
		COption::RemoveOption("cqApiSecret");
		
		$this->UnInstallFiles();
		$this->UnInstallDB();
		$this->UnInstallEvents();
		
		UnRegisterModule($this->MODULE_ID);
		$APPLICATION->IncludeAdminFile(GetMessage("CARROTQUEST_UNINSTALL_TITLE"), $DOCUMENT_ROOT."/bitrix/modules/".$this->MODULE_ID."/install/unstep.php");
		
	}
	
	function InstallFiles()
	{
		CopyDirFiles(
			$_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$this->MODULE_ID."/install/js",
			$_SERVER["DOCUMENT_ROOT"]."/bitrix/js/".$this->MODULE_ID,
			true, true);
		CopyDirFiles(
			$_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$this->MODULE_ID."/install/images/", 
			$_SERVER["DOCUMENT_ROOT"]."/bitrix/images/".$this->MODULE_ID."/", 
			true, true);
		
		// Модифицируем шаблоны "на лету" при инсталляции компонента, чтобы он трекал нужную нам информацию и при этом версия шаблонов была актуальной.
		// Также установлен обработчик, который выполняет данную операцию при обновлении модулей.
		CarrotQuestEventHandlers::LoadCatalogModuleTemplates();
		CarrotQuestEventHandlers::LoadSaleModuleTemplates();
		CopyDirFiles(
			$_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$this->MODULE_ID."/install/templates/", 
			$_SERVER["DOCUMENT_ROOT"]."/bitrix/templates/.default/components/bitrix/", 
			true, true);
		return true;
	}
	
	function UnInstallFiles()
	{
		//! TODO Здесь в продакшене надо добавить в пути "$_SERVER["DOCUMENT_ROOT"].". На локалке с ним не удаляется.
		DeleteDirFilesEx($_SERVER["DOCUMENT_ROOT"]."/bitrix/js/".$this->MODULE_ID);
		DeleteDirFilesEx($_SERVER["DOCUMENT_ROOT"]."/bitrix/images/".$this->MODULE_ID);
		
		// Переопределенные темплейты компонентов
		DeleteDirFilesEx($_SERVER["DOCUMENT_ROOT"]."/bitrix/templates/.default/components/bitrix/sale.order.ajax");
		DeleteDirFilesEx($_SERVER["DOCUMENT_ROOT"]."/bitrix/templates/.default/components/bitrix/catalog");
		DeleteDirFilesEx($_SERVER["DOCUMENT_ROOT"]."/bitrix/templates/.default/components/bitrix/sale.basket.basket");
		return true;
	}	
	
	function InstallDB()
	{
		
		return true;
	}
	
	function UnInstallDB()
	{
		return true;
	}
	

	function InstallEvents()
	{
		// По идее, js надо включать в include.php. Без идеи оно там работает через раз почему-то. Поэтому включаем при загрузке любой страницы
		RegisterModuleDependences("main", "OnPageStart", $this->MODULE_ID, "CarrotQuestEventHandlers", "IncludeHandler");
		
		// Событие коннекта к системе CarrotQuest и идентификации пользователя
		RegisterModuleDependences("main", "OnAfterEpilog", $this->MODULE_ID, "CarrotQuestEventHandlers", "ConnectHandler");
		
		// Событие подмены исходной стоимости заказа на цену со скидкой Carrot Quest
		RegisterModuleDependences("sale", "OnBeforeOrderAdd", $this->MODULE_ID, "CarrotQuestEventHandlers", "OnBeforeOrderAddHandler");
		
		// Событие оформления заказа для трекинга Carrot Quest
		RegisterModuleDependences("sale", "OnOrderAdd", $this->MODULE_ID, "CarrotQuestEventHandlers", "OnOrderAddHandler");
		
		// Событие трекинга различных событий
		RegisterModuleDependences("sale", "OnBasketAdd", $this->MODULE_ID, "CarrotQuestEventHandlers", "OnBasketAdd");
		
		// Событие обновления модулей, нам нужно обновить template-ы
		RegisterModuleDependences("main", "OnUpdateInstalled", $this->MODULE_ID, "CarrotQuestEventHandlers", "OnUpdateInstalled");
		
		return true;
	}
	
	function UnInstallEvents()
	{
		UnRegisterModuleDependences("main", "OnPageStart", $this->MODULE_ID, "CarrotQuestEventHandlers", "IncludeHandler");
		UnRegisterModuleDependences("main", "OnAfterEpilog", $this->MODULE_ID, "CarrotQuestEventHandlers", "ConnectHandler");
		UnRegisterModuleDependences("sale", "OnBeforeOrderAdd", $this->MODULE_ID, "CarrotQuestEventHandlers", "OnBeforeOrderAddHandler");
		UnRegisterModuleDependences("sale", "OnOrderAdd", $this->MODULE_ID, "CarrotQuestEventHandlers", "OnOrderAddHandler");
		UnRegisterModuleDependences("main", "OnBasketAdd", $this->MODULE_ID, "CarrotQuestEventHandlers", "OnBasketAdd");
		UnRegisterModuleDependences("main", "OnUpdateInstalled", $this->MODULE_ID, "CarrotQuestEventHandlers", "OnUpdateInstalled");
		return true;
	}
	
}
?>
