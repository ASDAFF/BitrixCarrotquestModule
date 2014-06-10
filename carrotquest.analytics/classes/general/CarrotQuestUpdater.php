<? 
IncludeModuleLangFile( __FILE__ );
CModule::IncludeModule('sale');

/**
* Класс содержит обработчики событий различных модулей, используемых в Carrot Quest.
*/
class CarrotQuestUpdater
{	
	public $MODULE_ID;
	private $TEMPLATE_LIST;
	private $MODIFICATIONS;
	
	function __construct ()
	{
		$this->MODULE_ID = CARROTQUEST_MODULE_ID;
		$this->SetTemplateList();
		$this->MODIFICATIONS  = array(
			"catalog" => array(
				"name" => "catalog",
				"path" => $_SERVER["DOCUMENT_ROOT"]."/bitrix/components/bitrix/catalog/templates/.default/", 
				"data" => array (
					"file" => ".default/bitrix/catalog.element/.default/template.php",
					"after" => "#END#",
					"data" =>	"\n<!-- CarrotQuest Detailed Product Info Event Start -->\n".
								"<? //CModule::IncludeModule('".CARROTQUEST_MODULE_ID."');\n".
								"	if (COption::GetOptionString('".CARROTQUEST_MODULE_ID."', 'cqTrackProductDetails')) { ?>".
								"		<script>\n".
								"			carrotquest.track('".'$product_view'."', {\n".
								"				objectId: '<?= ".'$arResult["ID"]'."; ?>',\n".
								"				objectName: '<?= ".'$arResult["NAME"]'."; ?>',\n".
								"				objectUrl: window.location.protocol + '//' + window.location.host + '".'<?= $arResult["DETAIL_PAGE_URL"]; ?>'."',\n".
								"				objectType: '".'$product'."',\n".
								"				fullObject: ".'<?= json_encode($arResult); ?>'."\n".
								"			});\n".
								"		</script>\n".
								"	<?} ?>\n".
								"<!-- CarrotQuest Detailed Product Info Event End -->",
				),
			),
		);
	}
	
	function SetTemplateList ($object = false)
	{
		// Формируем массив		
		$type = gettype($object);
		if (!$object)
			$this->TEMPLATE_LIST = json_decode(COption::GetOptionString($this->MODULE_ID, "cqReplacedTemplates"));
		elseif ($type == 'string')
			$this->TEMPLATE_LIST = json_decode($object);
		elseif ($type != 'array' && $type != 'object')
			return false;
		else
			$this->TEMPLATE_LIST = $object;
		
		COption::SetOptionString(CARROTQUEST_MODULE_ID,"cqReplacedTemplates",json_encode($this->TEMPLATE_LIST));
		
		return true;
	}
	
	function GetListFromRequest ()
	{
		$this->TEMPLATE_LIST = array();

		foreach ($_REQUEST as $key => $value)
		{
			if (preg_match('/carrotquest_template_([\s\S]+)/', $key, $matches))
				$this->TEMPLATE_LIST[$matches[1]] = array(
					"template" => $matches[1],
				);
			/* Для выпадающего списка
			if (preg_match('/carrotquest_site_([\s\S]+)/', $key, $matches))
				$this->TEMPLATE_LIST[$key] = array(
					"site" => $matches[1],
					"template" => false,
				);
			
			if (preg_match('/carrotquest_template_([\s\S]+)\^([\s\S]+)/', $key, $matches))
				$this->TEMPLATE_LIST[$key] = array(
					"site" => $matches[1],
					"template" => $matches[2],
				);*/
		}
		COption::SetOptionString(CARROTQUEST_MODULE_ID,"cqReplacedTemplates",json_encode($this->TEMPLATE_LIST));

		return true;
	}
	
	function UpdateAllTemplates ()
	{
		foreach($this->TEMPLATE_LIST as $tplName => $tpl)
		{ 
			// Создаем backup
			$tpl = (array)$tpl;
			$tpl["SITE_BACKUP_PATH"] = CARROTQUEST_BACKUP_PATH.$tplName;
			$tpl["PATH"] = $_SERVER["DOCUMENT_ROOT"]."/bitrix/templates/".$tplName;
			CheckDirPath($tpl["SITE_BACKUP_PATH"]);	
			CopyDirFiles(
				$tpl["PATH"],
				$tpl["SITE_BACKUP_PATH"],
				true, true);
			
			// Модифицируем шаблон по нашим правилам
			$tpl['MODIFICATIONS'] = array();
			?><script>console.log(<?= json_encode($this->MODIFICATIONS); ?>)</script><?
			foreach ($this->MODIFICATIONS as $value)
				$this->ModifyTemplate($value["name"], $value["path"], $value["data"], $tpl);
		}
	}
	
	/**
	* Переопределяет текущий шаблон вывода компонента Bitrix.
	* При необходимости копирует его из стандартных шаблонов в папки /bitrix/templates/имя_шаблона_сайта/components/bitrix/
	* <b>Параметры:</b>
	* <var>$path</var> - путь к шаблону, который необходимо подменить.
	* <var>$data</var> - массив данных, которые надо вписать в шаблон. В формате <code>{file: "template.php", after: "regexp", data: ""}</code>
	* Путь к файлу - относительно $path.
	* Поле after может содержать ключевое слово #END# - тогда запись будет осуществлена в конец файла.
	* <b>Возвращаемое значение:</b>
	* true - в случае успеха обновления, false - в случае неудачи.
	*/
	function ModifyTemplate ($componentTemplateName, $path, $data, & $templateToModify)
	{
		$result = true;
		
		// Путь к шаблонам компонентов для данного шаблона сайта
		$componentTemplatePath = $tpl["SITE_TEMPLATE_PATH"].'/components/bitrix/'.$componentTemplateName."/";
		
		// Собираем инфо о модификации
		$componentTemplateInfo = array(
			"COMPONENT_TEMPLATE_NAME" => $componentTemplateName,
			"COMPONENT_TEMPLATE_PATH" => $componentTemplatePath,
		);

		if (!file_exists($componentTemplatePath))
		{
			// Копируем шаблон
			CheckDirPath($componentTemplatePath);
			$result = CopyDirFiles($path, $componentTemplatePath, true, true);
			$componentTemplateInfo["COPIED_BY_CARROTQUEST"] = true;
		}
		else 
			$componentTemplateInfo["COPIED_BY_CARROTQUEST"] = false; // Будем модифицировать шаблон пользователя
		
		if ($result)
		{
			$componentTemplateInfo['FILES_CREATED'] = array();
			$componentTemplateInfo['FILES_MODIFIED'] = array();
			foreach ($data as $change)
			{
				// Путь к файлу, который будем изменять
				$file = $componentTemplatePath.$change["file"];
				
				if (!file_exists($file))
				{
					// Копируем из исходного шаблона
					$result = copy($file, $path.$change["file"]);
					$componentTemplateInfo['FILES_CREATED'][] = $file;
				};
				
				if ($result)
				{
					$componentTemplateInfo['FILES_MODIFIED'][] = $file;
					// Обновляем содержимое файла
					$insert = "\n<!-- Carrot Quest Insert Start -->\n".$change["data"]."\n<!-- Carrot Quest Insert End -->\n";
					
					if ($change["after"] == "#END#")
						$result = (file_put_contents($file, $insert, FILE_APPEND) == strlen($insert));
					else;
					{
						$content = file_get_contents ($file);
						if ($content)
						{
							$content = preg_replace("(".$change["after"].")", '$0'.$insert, $content);
							$result = RewriteFile($file, $content);
						}
						else
							$result = false;
					};
				};
			};
		};
			
		// Если успех - сохраняем изменения. Иначе откат к изначальному состоянию.
		$templateToModify['MODIFICATIONS'][] = $componentTemplateInfo;
		if (!$result)
			$this->RestoreTemplate($templateToModify);
		?> <script>console.log(<?= $componentTemplateInfo; ?>);</script><?
		
		return $result;
	}
	
	function RestoreTemplate ($tpl)
	{
		/*if (!$foldersCreated && !$filesModified)
		{
			$foldersCreated = json_decode(COption::GetOptionString(CARROTQUEST_MODULE_ID, "CQFilesCreated"));
			$filesModified = json_decode(COption::SetOptionString(CARROTQUEST_MODULE_ID, "CQFilesModified"));
		}
		
		// Нейтрализуем все изменения
		foreach ($foldersCreated as $path)
			DeleteDirFilesEx($path);
		
		foreach ($filesModified as $path)
		{
			$content = file_get_contents ($path);
			$pattern = "~<!-- Carrot Quest Insert Start -->([\s\S]*?)<!-- Carrot Quest Insert End -->~is";
			$content = preg_replace($pattern, "", $content);
			RewriteFile($path, $content);
		}*/
	}
	
	/**
	*	Обновляет шаблоны модуля sale.
	*	<b>Параметры:</b> отсутствуют
	*	<b>Возвращаемое значение:</b> отсутствует.
	*/
	function LoadSaleModuleTemplates ()
	{
		global $APPLICATION;
		// Корзина
		CopyDirFiles(
			$_SERVER["DOCUMENT_ROOT"]."/bitrix/components/bitrix/sale.basket.basket/templates/.default/", 
			$_SERVER["DOCUMENT_ROOT"]."/bitrix/templates/.default/components/bitrix/sale.basket.basket/.default/", 
			true, true);
		file_put_contents($_SERVER["DOCUMENT_ROOT"]."/bitrix/templates/.default/components/bitrix/sale.basket.basket/.default/template.php",
		"\n<!-- CarrotQuest Basket Visit Event Start -->\n".
		"<?  //CModule::IncludeModule('".CARROTQUEST_MODULE_ID."');\n".
		"	 if (COption::GetOptionString('".CARROTQUEST_MODULE_ID."', 'cqTrackCartVisit')) { ?>\n".
		"		<script>\n".
		"			carrotquest.track('Cart')\n".
		"		</script>\n".
		"	<?} ?>".
		"<!-- CarrotQuest Basket Visit Event End -->",FILE_APPEND);
	}
	
	/**
	*	Обновляет шаблоны модуля catalog.
	*	<b>Параметры:</b> отсутствуют
	*	<b>Возвращаемое значение:</b> отсутствует.
	*/
	function LoadCatalogModuleTemplates ()
	{
		global $APPLICATION;	
		CopyDirFiles(
			$_SERVER["DOCUMENT_ROOT"]."/bitrix/components/bitrix/catalog/templates/.default/", 
			$_SERVER["DOCUMENT_ROOT"]."/bitrix/templates/.default/components/bitrix/catalog/.default/", 
			true, true);
		file_put_contents($_SERVER["DOCUMENT_ROOT"]."/bitrix/templates/.default/components/bitrix/catalog/.default/bitrix/catalog.element/.default/template.php",
		"\n<!-- CarrotQuest Detailed Product Info Event Start -->\n".
		"<? //CModule::IncludeModule('".CARROTQUEST_MODULE_ID."');\n".
		"	if (COption::GetOptionString('".CARROTQUEST_MODULE_ID."', 'cqTrackProductDetails')) { ?>".
		"		<script>\n".
		"			carrotquest.track('".'$product_view'."', {\n".
		"				objectId: '<?= ".'$arResult["ID"]'."; ?>',\n".
		"				objectName: '<?= ".'$arResult["NAME"]'."; ?>',\n".
		"				objectUrl: window.location.protocol + '//' + window.location.host + '".'<?= $arResult["DETAIL_PAGE_URL"]; ?>'."',\n".
		"				objectType: '".'$product'."',\n".
		"				fullObject: ".'<?= json_encode($arResult); ?>'."\n".
		"			});\n".
		"		</script>\n".
		"	<?} ?>\n".
		"<!-- CarrotQuest Detailed Product Info Event End -->",FILE_APPEND);
	}
}
