<?
	
global $MESS;

$modulePath = str_replace("\\", "/", __FILE__);
$modulePath = substr($modulePath, 0, strlen($modulePath) - strlen("/install/index.php")); 
require_once($modulePath."/include/constants.php");

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
			$this->MODULE_VERSION = '1.1.7';
			$this->MODULE_VERSION_DATE = '12.06.2014';
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
		if($step < 2)
		{
			$APPLICATION->IncludeAdminFile(GetMessage("CARROTQUEST_INSTALL_TITLE"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$this->MODULE_ID."/install/step1.php");
		}
		elseif($step == 2) // Второй шаг установки
		{
			// Проверяем ключи на валидность
			if (empty($errors))
			{
				if (!array_key_exists('ApiKey', $_REQUEST) || strlen($_REQUEST['ApiKey']) != 32)
					array_push($errors, "CARROTQUEST_KEY_ERROR");
				elseif (!array_key_exists('ApiSecret', $_REQUEST) || strlen($_REQUEST['ApiSecret']) != 64)
					array_push($errors, "CARROTQUEST_KEY_ERROR");
			};
			
			// Почему-то при uninstall параметры не удаляются целиком...
			COption::RemoveOption($this->MODULE_ID);
			if (empty($errors))
			{
				// Пишем параметры модуля
				COption::SetOptionString($this->MODULE_ID, "cqApiKey", $_REQUEST['ApiKey']);
				COption::SetOptionString($this->MODULE_ID, "cqApiSecret", $_REQUEST['ApiSecret']);
				$APPLICATION->IncludeAdminFile(GetMessage("CARROTQUEST_INSTALL_TITLE"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$this->MODULE_ID."/install/step2.php");
			}
			else
				$APPLICATION->IncludeAdminFile(GetMessage("CARROTQUEST_INSTALL_TITLE"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$this->MODULE_ID."/install/step3.php");
		}
		elseif($step == 3) // Третий шаг установки
		{
			if(empty($errors) && $this->InstallFiles())
			{
				$this->InstallDB();
				$this->InstallEvents();	
			};
			
			if (!empty($errors) || $_REQUEST['SendEmail'])
			{
				$GLOBALS["carrotquest_errors"] = $errors;	
				
				// Стереть ключи
				RemoveOption($this->MODULE_ID);
			}
			else
			{						
				// Чистим кэш сайта, иначе js объект carrotqust появится не сразу
				$phpCache = new CPHPCache();
				$phpCache->CleanDir();
				
				RegisterModule($this->MODULE_ID);
			};
			
			$APPLICATION->IncludeAdminFile(GetMessage("CARROTQUEST_INSTALL_TITLE"), $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$this->MODULE_ID."/install/step3.php");
		}
	}
	
	function DoUninstall()
	{
		global $DOCUMENT_ROOT, $APPLICATION;
		
		COption::RemoveOption($this->MODULE_ID);
		
		$this->UnInstallFiles();
		$this->UnInstallDB();
		$this->UnInstallEvents();
		
		UnRegisterModule($this->MODULE_ID);
		$APPLICATION->IncludeAdminFile(GetMessage("CARROTQUEST_UNINSTALL_TITLE"), $DOCUMENT_ROOT."/bitrix/modules/".$this->MODULE_ID."/install/unstep.php");
		
	}
	
	function InstallFiles()
	{
		// Копируем JavaScript скрипты
		CheckDirPath($_SERVER["DOCUMENT_ROOT"]."/bitrix/js/".$this->MODULE_ID."/");	
		CopyDirFiles(
			$_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$this->MODULE_ID."/install/js/",
			$_SERVER["DOCUMENT_ROOT"]."/bitrix/js/".$this->MODULE_ID,
			true, true);
		
		// Копируем кортинки
		CheckDirPath($_SERVER["DOCUMENT_ROOT"]."/bitrix/images/".$this->MODULE_ID."/");	
		CopyDirFiles(
			$_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$this->MODULE_ID."/install/images/", 
			$_SERVER["DOCUMENT_ROOT"]."/bitrix/images/".$this->MODULE_ID."/", 
			true, true);
				
		// Модифицируем шаблоны "на лету" при инсталляции компонента, чтобы он трекал нужную нам информацию и при этом версия шаблонов была актуальной.
		// Также установлен обработчик, который выполняет данную операцию при обновлении модулей.
		global $carrotquest_UPDATER;

		$carrotquest_UPDATER->GetListFromRequest();
		$carrotquest_UPDATER->UpdateAllTemplates();
		
		return true;
	}
	
	function UnInstallFiles()
	{
		global $APPLICATION, $carrotquest_UPDATER;

		$carrotquest_UPDATER->RestoreAllTemplates();
		
		DeleteDirFilesEx("/bitrix/js/".$this->MODULE_ID."/");
		DeleteDirFilesEx("/bitrix/images/".$this->MODULE_ID."/");
		
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
		// Включаем include.php при загрузке каждой страницы
		RegisterModuleDependences("main", "OnPageStart", $this->MODULE_ID);
		
		// Событие коннекта к системе CarrotQuest и идентификации пользователя
		RegisterModuleDependences("main", "OnAfterEpilog", $this->MODULE_ID, "CarrotQuestEventHandlers", "ConnectHandler");
		
		// Событие подмены исходной стоимости заказа на цену со скидкой Carrot Quest
		RegisterModuleDependences("sale", "OnBeforeOrderAdd", $this->MODULE_ID, "CarrotQuestEventHandlers", "OnBeforeOrderAddHandler");
		
		// Событие оформления заказа для трекинга Carrot Quest
		RegisterModuleDependences("sale", "OnOrderAdd", $this->MODULE_ID, "CarrotQuestEventHandlers", "OnOrderAddHandler");
		
		// Событие трекинга различных событий
		RegisterModuleDependences("sale", "OnBasketAdd", $this->MODULE_ID, "CarrotQuestEventHandlers", "OnBasketAdd");
		
		return true;
	}
	
	function UnInstallEvents()
	{
		UnRegisterModuleDependences("main", "OnPageStart", $this->MODULE_ID, "CarrotQuestEventHandlers", "IncludeHandler");
		UnRegisterModuleDependences("main", "OnAfterEpilog", $this->MODULE_ID, "CarrotQuestEventHandlers", "ConnectHandler");
		UnRegisterModuleDependences("sale", "OnBeforeOrderAdd", $this->MODULE_ID, "CarrotQuestEventHandlers", "OnBeforeOrderAddHandler");
		UnRegisterModuleDependences("sale", "OnOrderAdd", $this->MODULE_ID, "CarrotQuestEventHandlers", "OnOrderAddHandler");
		UnRegisterModuleDependences("main", "OnBasketAdd", $this->MODULE_ID, "CarrotQuestEventHandlers", "OnBasketAdd");
		return true;
	}
	
}
?>
