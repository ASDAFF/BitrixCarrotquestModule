<?
global $MESS;
$strPath2Lang = str_replace("\\", "/", __FILE__);
$strPath2Lang = substr($strPath2Lang, 0, strlen($strPath2Lang)-18);
include(GetLangFileName($strPath2Lang."/lang/", "/install/index.php"));
CModule::IncludeModule('sale');
include($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/carrotquest/classes/general/CarrotQuestEventHandlers.php");

class carrotquest extends CModule
{
	var $MODULE_ID = "carrotquest";
	var $MODULE_VERSION;
	var $MODULE_VERSION_DATE;
	var $MODULE_NAME;
	var $MODULE_DESCRIPTION;
	var $MODULE_GROUP_RIGHTS = "N";

	function carrotquest()
	{
		$arModuleVersion = array();

		$path = str_replace("\\", "/", __FILE__);
		$path = substr($path, 0, strlen($path) - strlen("/index.php"));
		include($path."/version.php");

		if (is_array($arModuleVersion) && array_key_exists("VERSION", $arModuleVersion))
		{
			$this->MODULE_VERSION = $arModuleVersion["VERSION"];
			$this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];
		}
		else
		{
			$this->MODULE_VERSION = $carrotquest_VERSION;
			$this->MODULE_VERSION_DATE = $carrotquest_VERSION_DATE;
		}

		$this->MODULE_NAME = GetMessage("CARROTQUEST_MODULE_NAME");
		$this->MODULE_DESCRIPTION = GetMessage("CARROTQUEST_MODULE_DESCRIPTION");
	}

	function DoInstall()
	{
		global $DOCUMENT_ROOT, $APPLICATION, $step, $obModule;
		
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
		global $DOCUMENT_ROOT, $APPLICATION;
                
			
		$this->UnInstallFiles();
		$this->UnInstallDB();
		$this->UnInstallEvents();
		
		UnRegisterModule("carrotquest");
		$APPLICATION->IncludeAdminFile(GetMessage("CARROTQUEST_UNINSTALL_TITLE"), $DOCUMENT_ROOT."/bitrix/modules/carrotquest/install/unstep.php");
		
	}
	
	function InstallFiles()
	{
		CopyDirFiles(
			$_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/carrotquest/install/js",
			$_SERVER["DOCUMENT_ROOT"]."/bitrix/js/carrotquest",
			true, true);
		CopyDirFiles(
			$_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/carrotquest/install/images/", 
			$_SERVER["DOCUMENT_ROOT"]."/bitrix/images/carrotquest/", 
			true, true);
		
		// Модифицируем шаблоны "на лету" при инсталляции компонента, чтобы он трекал нужную нам информацию и при этом версия шаблонов была актуальной.
		// Также установлен обработчик, который выполняет данную операцию при обновлении модулей.
		CarrotQuestEventHandlers::LoadCatalogModuleTemplates();
		CarrotQuestEventHandlers::LoadSaleModuleTemplates();
		CopyDirFiles(
			$_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/carrotquest/install/templates/", 
			$_SERVER["DOCUMENT_ROOT"]."/bitrix/templates/.default/components/bitrix/", 
			true, true);
		return true;
	}
	
	function UnInstallFiles()
	{
		//! TODO Здесь в продакшене надо добавить в пути "$_SERVER["DOCUMENT_ROOT"].". На локалке с ним не удаляется.
		DeleteDirFilesEx("/bitrix/js/carrotquest");
		DeleteDirFilesEx("/bitrix/images/carrotquest");
		
		// Переопределенные темплейты компонентов
		DeleteDirFilesEx("/bitrix/templates/.default/components/bitrix/sale.order.ajax");
		DeleteDirFilesEx("/bitrix/templates/.default/components/bitrix/catalog");
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
		// Событие коннекта к системе CarrotQuest и идентификации пользователя
		RegisterModuleDependences("main", "OnAfterEpilog", "carrotquest", "CarrotQuestEventHandlers", "ConnectHandler");
		
		// Событие подмены исходной стоимости заказа на цену со скидкой Carrot Quest
		RegisterModuleDependences("sale", "OnBeforeOrderAdd", "carrotquest", "CarrotQuestEventHandlers", "OnBeforeOrderAddHandler");
		
		// Событие оформления заказа для трекинга Carrot Quest
		RegisterModuleDependences("sale", "OnOrderAdd", "carrotquest", "CarrotQuestEventHandlers", "OnOrderAddHandler");
		
		// Событие трекинга различных событий
		RegisterModuleDependences("sale", "OnBasketAdd", "carrotquest", "CarrotQuestEventHandlers", "OnBasketAdd");
		
		// Событие обновления модулей, нам нужно обновить template-ы
		RegisterModuleDependences("main", "OnUpdateInstalled", "carrotquest", "CarrotQuestEventHandlers", "OnUpdateInstalled");
		
		return true;
	}
	
	function UnInstallEvents()
	{
		
		UnRegisterModuleDependences("main", "OnAfterEpilog", "carrotquest", "CarrotQuestEventHandlers", "ConnectHandler");
		UnRegisterModuleDependences("sale", "OnBeforeOrderAdd", "carrotquest", "CarrotQuestEventHandlers", "OnBeforeOrderAddHandler");
		UnRegisterModuleDependences("sale", "OnOrderAdd", "carrotquest", "CarrotQuestEventHandlers", "OnOrderAddHandler");
		UnRegisterModuleDependences("main", "OnBasketAdd", "carrotquest", "CarrotQuestEventHandlers", "OnBasketAdd");
		UnRegisterModuleDependences("main", "OnUpdateInstalled", "carrotquest", "CarrotQuestEventHandlers", "OnUpdateInstalled");
		return true;
	}
	
}
