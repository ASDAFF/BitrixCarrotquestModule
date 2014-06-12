<? 
IncludeModuleLangFile( __FILE__ );
CModule::IncludeModule('sale');

/**
* Класс содержит обработчики событий различных модулей, используемых в Carrot Quest.
*/
class CarrotQuestUpdater
{	
	/**
	* ID модуля Carrot Quest
	*/
	public $MODULE_ID;
	
	/**
	* Список шаблонов, которые модифицированы модулем.
	* Индексом служит имя шаблона сайта.
	* Каждый шаблон - это объект следующей структуры:
	*	"NAME" - имя шаблона сайта.
	*	"MODIFICATIONS" - массив-список изменений, произведенных с файлами шаблона. Структура элемента:
	*		"COMPONENT_TEMPLATE_PATH" - путь к шаблону компонента
	*		"COPIED_BY_CARROTQUEST" - был ли шаблон компонента целиком скопирован carrotquest-ом
	*		"FILES_CREATED" - список относительных путей файлов внутри шаблона, созданных (скопированных) carrotquest-ом. Не учитывает копирование шаблона целиком ("COPIED_BY_CARROTQUEST" = true)
	*		"FILES_MODIFIED" - список относительных путей файлов, модифицированных carrotquest-ом.	
	*/
	private $TEMPLATE_LIST;
	
	/**
	* Список модификаций, которые должны быть проведены над шаблоном для корректной работы с Carrot quest
	* Формат каждого изменения:
	*	"Имя модификации" (для удобства - имя компонента) - содержит объект следующего содержания:
	*		"name" - имя шаблона компонента для модификации
	*		"path" - абсолютный путь к источнику файлов шаблона
	*		"data" - массив модификаций файлов внутри шаблона. Каждый элемент имеет следующую структуру:
	*			"file" - относительный путь к файлу шаблона внутри "path"
	*			"after" - RegExp, после каждого вхождения которого надо вставить поле "data"
	*			"data" - Информация, которую необходимо вставить
	*/
	private $MODIFICATIONS;
	
	function __construct ()
	{
		$this->MODULE_ID = CARROTQUEST_MODULE_ID;
		$this->UpdateTemplateList();
		$this->MODIFICATIONS  = array(
			// Детальное описание товара
			"catalog" => array(
				"name" => "catalog",
				"path" => $_SERVER["DOCUMENT_ROOT"]."/bitrix/components/bitrix/catalog/templates/.default/", 
				"data" => array(
					array (
						"file" => "bitrix/catalog.element/.default/template.php",
						"after" => "#END#",
						"data" =>	"<? //Detailed Product Info Event\n".
									"	if (COption::GetOptionString('".CARROTQUEST_MODULE_ID."', 'cqTrackProductDetails')) { ?>\n".
									"		<script>\n".
									"			carrotquest.track('".'$product_view'."', {\n".
									"				objectId: '<?= ".'$arResult["ID"]'."; ?>',\n".
									"				objectName: '<?= ".'$arResult["NAME"]'."; ?>',\n".
									"				objectUrl: window.location.protocol + '//' + window.location.host + '".'<?= $arResult["DETAIL_PAGE_URL"]; ?>'."',\n".
									"				objectType: '".'$product'."',\n".
									"				fullObject: ".'<?= json_encode($arResult); ?>'."\n".
									"			});\n".
									"		</script>\n".
									"	<?} ?>\n",
					),
				),
			),
			// Корзина
			"sale.basket.basket" => array(
				"name" => "sale.basket.basket",
				"path" => $_SERVER["DOCUMENT_ROOT"]."/bitrix/components/bitrix/sale.basket.basket/templates/.default/", 
				"data" => array(
					array (
						"file" => "template.php",
						"after" => "#END#",
						"data" =>	"<?  //Basket Visit Event\n".
									"	 if (COption::GetOptionString('".CARROTQUEST_MODULE_ID."', 'cqTrackCartVisit')) { ?>\n".
									"		<script>\n".
									"			carrotquest.track('Cart')\n".
									"		</script>\n".
									"	<?} ?>\n",
					),
				),
			),
			// Страницы заказа
			"sale.order.ajax" => array(
				"name" => "sale.order.ajax",
				"path" => $_SERVER["DOCUMENT_ROOT"]."/bitrix/components/bitrix/sale.order.ajax/templates/.default/", 
				"data" => array(
					// Пред-заказ
					array (
						"file" => "summary.php",
						"after" => "#END#",
						"data" =>	'<script>'."\n".
									'<? // Pre-order event'."\n".
									'	if (COption::GetOptionString("'.CARROTQUEST_MODULE_ID.'","cqTrackPreOrder"))'."\n".
									'	{ ?>'."\n".
									'		carrotquest.track("Pre Order");'."\n".
									'	<? } '."\n".
									'	/* Carrot quest discount field */'."\n".
									'	if (COption::GetOptionString("'.CARROTQUEST_MODULE_ID.'","cqActivateBonus") && doubleval($arResult["CARROTQUEST_DISCOUNT_PRICE"]) > 0)'."\n".
									'	{'."\n".
									'		?>'."\n".
									'			var itogo = $(".bx_ordercart_order_sum").find("tr").last();'."\n".
									'			var tr = $("<tr>");'."\n".
									'			var td1 = $("<td>", {class: "custom_t1 itog", colspan: <?=$colspan?>}).html("<?=GetMessage("SOA_TEMPL_SUM_CQ_DISCOUNT")?>");'."\n".
									'			var td2 = $("<td>", {class: "custom_t2 price"}).html("<?echo $arResult["CARROTQUEST_DISCOUNT_PRICE_FORMATED"]?>");'."\n".
									'			tr.append(td1);'."\n".
									'			tr.append(td2);'."\n".
									'			itogo.prepend(tr);'."\n".
									'		<?'."\n".
									'	}?>'."\n".
									'</script>',
					),
					// Подсчет скидки Carrot Quest
					array (
						"file" => "result_modifier.php",
						"after" => "#END#",
						"data" =>	'<?'."\n".
									'	// Count discount'."\n".
									'	global $carrotquest_API;'."\n".
									'	if (COption::GetOptionString("'.CARROTQUEST_MODULE_ID.'","cqActivateBonus"))'."\n".
									'		$arResult = $carrotquest_API->CalcDiscount($arResult);'."\n".
									'	// When order is done, items are not present. So we write it to cookie'."\n".
									'	if (COption::GetOptionString("'.CARROTQUEST_MODULE_ID.'","cqTrackOrderConfirm") != "")'."\n".
									'		CarrotQuestEventHandlers::SetBasketItemsCookie($arResult["BASKET_ITEMS"])'."\n".
									'?>',
					),
					// Подтверждение заказа
					array (
						"file" => "confirm.php",
						"after" => "#END#",
						"data" =>	'<? // Track Order Event'."\n".
									'	if (COption::GetOptionString("'.CARROTQUEST_MODULE_ID.'", "cqTrackOrderConfirm")) { ?>'."\n".
									'	<script>'."\n".
									'		var items = carrotquest_cookie.get("carrotquest_basket_items");'."\n".
									'		var orderID = carrotquest_cookie.get("carrotquest_order_id");'."\n".
									'		if (items && orderID) // than track order'."\n".
									'		{'."\n".
									'			carrotquest.trackOrder(JSON.parse(items), orderID);'."\n".
									'			carrotquest_cookie.delete("carrotquest_basket_items");'."\n".
									'			carrotquest_cookie.delete("carrotquest_order_id");'."\n".
									'		}'."\n".
									'	</script>'."\n".
									'<? } ?>',
					),
					// Английские сообщения для summary.php
					array (
						"file" => "lang/en/template.php",
						"after" => "#END#",
						"data" =>	'<? $MESS["SOA_TEMPL_SUM_CQ_DISCOUNT"] = "Carrot Quest discount"; ?>',
					),
					// Русские сообщения для summary.php
					array (
						"file" => "lang/ru/template.php",
						"after" => "#END#",
						"data" =>	iconv('utf-8','windows-1251','<? $MESS["SOA_TEMPL_SUM_CQ_DISCOUNT"] = "Скидка Carrot Quest"; ?>'),
					),
				),
			),
		);
	}
	
	/**
	*	Создает backup всего шаблона.
	*	<var>$object</var> - принимает значения:
	*	<b>Возвращаемое значение:</b> 
	*	true - в случае успеха, false - неудачи
	*/
	private function CreateBackup ($tpl)
	{
		$tpl = (array)$tpl;
		$backupPath = CARROTQUEST_BACKUP_PATH.$tpl["NAME"].date("_Y.m.d_H.i.s");
		$tplPath = $_SERVER['DOCUMENT_ROOT']."/bitrix/templates/".$tpl["NAME"];
		CheckDirPath($backupPath);
		return CopyDirFiles($tplPath, $backupPath, true, true);
	}
	
	/**
	*	Очищает папку с бэкапами шаблонов сайтов
	*	<var>$all</var> - если этот параметр равен true, то будут удалены все бэкапы. Иначе - все кроме последних.
	*	<b>Возвращаемое значение:</b> нет
	*/
	public function CleanBackups ($all = false)
	{
		if ($all)
			DeleteDirFilesEx(CARROTQUEST_RELATIVE_BACKUP_PATH);
		else
		{
			$backupList = scandir(CARROTQUEST_BACKUP_PATH);
			for ($i = 1; $i < count($backupList); $i++)
			{
				if ($backuList[$i][0] != '.' && $backupList[$i-1][0] != '.')
				{
					$prefixPrev = substr($backupList[$i-1],0,-18); 
					$prefixThis = substr($backupList[$i],0,-18); 
					if ($prefixPrev == $prefixThis)
						DeleteDirFilesEx(CARROTQUEST_BACKUP_RELATIVE_PATH.$backupList[$i-1]);
				}
			}
		}
	}
	
	/**
	*	Обновляет $this->TEMPLATE_LIST и соответствующие поля в опциях модуля (COption), в зависимости от $object.
	*	<b>Параметры:</b>
	*	<var>$object</var> - принимает значения:
	*		false (по умолчанию) - считать список из параметров модуля
	*		строка - преобразовать в объект (json) и записать в список
	*		объект или массив - записать в список
	*	<b>Возвращаемое значение:</b>
	*	true в случае успеха, false - если $object некорректен
	*/
	public function UpdateTemplateList ($object = false)
	{
		// Формируем массив
		$type = gettype($object);
		if ($type=='boolean' && !$object)
		{
			$nameList = json_decode(COption::GetOptionString($this->MODULE_ID, "cqReplacedTemplates"));
			
			foreach ($nameList as $name)
				$this->TEMPLATE_LIST[$name] = json_decode(COption::GetOptionString($this->MODULE_ID, "cq_template_".$name));	
		}
		elseif ($type == 'string')
			$this->TEMPLATE_LIST = json_decode($object);
		elseif ($type != 'array' && $type != 'object')
			return false;
		else
			$this->TEMPLATE_LIST = $object;
		
		// Производим запись
		$nameList = array();
		foreach ($this->TEMPLATE_LIST as $name => $template)
		{
			$nameList[] = $name;
			COption::SetOptionString(CARROTQUEST_MODULE_ID,"cq_template_".$name,json_encode($template));
		}
		COption::SetOptionString(CARROTQUEST_MODULE_ID,"cqReplacedTemplates",json_encode($nameList));
		
		return true;
	}
	
	/**
	*	Получает $this->TEMPLATE_LIST из значений массива $_REQUEST[], заполняемого файлом /include/templateOptions.php
	*	<b>Параметры:</b> нет
	*	<b>Возвращаемое значение:</b>
	*	true
	*/
	public function GetListFromRequest ()
	{
		$this->TEMPLATE_LIST = array();

		foreach ($_REQUEST as $key => $value)
			if (preg_match('/carrotquest_template_([\s\S]+)/', $key, $matches))
				$this->TEMPLATE_LIST[$matches[1]] = array(
					"NAME" => $matches[1],
				);
		$this->UpdateTemplateList($this->TEMPLATE_LIST);

		return true;
	}
	
	/**
	*	Проверяет, закрыты ли теги php (<? ?>) в заданном файле.
	*	<b>Параметры:</b>
	*	<var>$fileName</var> - Путь к проверяемому файлу
	*	<b>Возвращаемое значение:</b>
	*	true, если теги закрыты, false - если нет.
	*/
	private function phpIsClosed ($fileName)
	{
		/*
		$status
		0 - все закрыто
		1 - предыдущий символ <
		2 - действует открывающая последовательность <?
		3 - после открывающей последоватлеьности 2 был символ ? (предыдущий). Если текущий >, сбрасываем в 0.
		
		$commentStatus
		0 - нет комментария
		1 - был слеш / вне комментария
		2 - в текущий момент действует комментарий //
		3 - в текущий момент комментарий /*
		4 - внутри комментария 3 была * предыдущим символом
		*/
		$length = filesize($fileName);
		$content = file_get_contents($fileName);
		$status = 0;
		$commentStatus = 0;
		$show = false;
		for ($i = 0; $i < $length; $i++)
		{
			switch ($commentStatus)
			{
				case 0: {
					if ($content[$i] == '/' && $status == 2)
						$commentStatus = 1;
					break;
				}
				case 1: {
					if ($content[$i] == '/')
						$commentStatus = 2;
					elseif ($content[$i] == '*')
						$commentStatus = 3;
					else
						$commentStatus = 0;
					break;
				}
				case 2: {
					if ($content[$i] == '\n')
					{
						$commentStatus = 0;
					}
					break;
				}
				case 3: {
					if ($content[$i] == '*')
						$commentStatus = 4;
					break;
				}
				case 4: {
					if ($content[$i] == '/')
						$commentStatus = 0;
					else
						$commentStatus = 3;
					break;
				}
			}
			
			switch ($status)
			{
				case 0: {
					if ($content[$i] == '<' && $commentStatus != 3)
						$status = 1;
					break;
				}
				case 1: {
					$status = ($content[$i] == '?' ? 2 : 0);
					break;
				}
				case 2: {
					if ($content[$i] == '?' && $commentStatus != 3)
					{
						$status = 3;
					}
					break;
				}
				case 3: {
					if ($content[$i] == '>')
					{
						if ($commentStatus == 2)
							$commentStatus = 0;
						$status = 0;
					}
					else
						$status = 2;
					break;
				}
			}
		}
		return ($status != 2);
	}
	
	/**
	*	Обновляет текущие шаблоны из массива $_REQUEST.
	*	Уже существующие шаблоны не обновляются. Те, которых нет в $_REQUEST - откатываются. Новые - создаются.
	*	<b>Параметры:</b> нет
	*	<b>Возвращаемое значение:</b> нет
	*/
	public function UpdateFromRequest ()
	{
		// Формируем новый список
		$newTemplateList = array();
		foreach ($_REQUEST as $key => $value)
			if (preg_match('/carrotquest_template_([\s\S]+)/', $key, $matches))
				$newTemplateList[$matches[1]] = array(
					"NAME" => $matches[1],
				);
		if (!$this->TEMPLATE_LIST)
			$this->TEMPLATE_LIST = array();

		// Очищаем шаблоны, которых нет в новом списке
		$deleteArray = array_diff_key($this->TEMPLATE_LIST, $newTemplateList);
		foreach ($deleteArray as &$template)
		{
			$this->CreateBackup($template);
			$this->RestoreTemplate($this->TEMPLATE_LIST[$template -> NAME]);
			unset($this->TEMPLATE_LIST[$template -> NAME]);
		};
		
		// Добавляем новые шаблоны
		$insertArray = array_diff_key($newTemplateList, $this->TEMPLATE_LIST);
		foreach ($insertArray as &$template)
		{
			$template= (array)$template;
					$this->CreateBackup($template);
			// Модифицируем
			$template['MODIFICATIONS'] = array();
			foreach ($this->MODIFICATIONS as $value)
				$this->ModifyTemplate($value["name"], $value["path"], $value["data"], $template);
		};
		// Обновляем список
		$this->TEMPLATE_LIST = array_merge($this->TEMPLATE_LIST, $insertArray);
		$this->UpdateTemplateList($this->TEMPLATE_LIST);	
	}
	
	/**
	*	Обновляет код шаблонов по $this->TEMPLATE_LIST и $this->MODIFICATIONS
	*	<b>Параметры:</b> нет
	*	<b>Возвращаемое значение:</b> нет
	*/
	public function UpdateAllTemplates ()
	{
		foreach($this->TEMPLATE_LIST as $tplName => & $tpl)
		{ 
			$tpl = (array)$tpl;
			$this->CreateBackup($tpl);
			
			// Модифицируем шаблон по нашим правилам
			if (!is_array($tpl['MODIFICATIONS']))
				$tpl['MODIFICATIONS'] = array();
			foreach ($this->MODIFICATIONS as $value)
			{
				$this->RestoreTemplate($tpl);
				$this->ModifyTemplate($value["name"], $value["path"], $value["data"], $tpl);
			}
		}
		// Обновляем COption
		$this->UpdateTemplateList($this->TEMPLATE_LIST);
	}
	
	/**
	*	Вырезает вставки Carrot quest по $this->TEMPLATE_LIST и $this->MODIFICATIONS. Скоированные шаблоны не удаляются!
	*	<b>Параметры:</b> нет
	*	<b>Возвращаемое значение:</b> нет
	*/
	public function RestoreAllTemplates ()
	{
		foreach($this->TEMPLATE_LIST as &$tpl)
		{
			$this->CreateBackup($tpl);
			$this->RestoreTemplate($tpl);
		}
		// Обновляем COption
		$this->UpdateTemplateList($this->TEMPLATE_LIST);
	}
	
	/**
	* Изменяет шаблон $componentTemplateName для работы с Carrot Quest.
	* При необходимости копирует его из $path в папки /bitrix/templates/имя_шаблона_сайта/components/bitrix/$componentTEmplateName
	* <b>Параметры:</b>
	* <var>$componentTemlateName</var> - имя компонента, который необходимо подменить.
	* <var>$path</var> - путь к шаблону, который необходимо подменить.
	* <var>$data</var> - массив данных, которые надо вписать в шаблон. В формате $this->MODIFICATIONS
	* <var>$templateToModify</var> - шаблон, по которому производится модификация из $this->TEMPLATE_LIST
	* <b>Возвращаемое значение:</b>
	* true - в случае успеха обновления, false - в случае неудачи.
	*/
	public function ModifyTemplate ($componentTemplateName, $path, $data, & $templateToModify)
	{
		$result = true;
		// Путь к шаблонам компонентов для данного шаблона сайта
		$componentTemplatePath = $_SERVER["DOCUMENT_ROOT"]."/bitrix/templates/".$templateToModify["NAME"].'/components/bitrix/'.$componentTemplateName."/.default/";
		// Собираем инфо о модификации
		$componentTemplateInfo = array(
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
					if (file_exists($path.$change["file"]))
						$result = copy($file, $path.$change["file"]); // Копируем из исходного шаблона
					$componentTemplateInfo['FILES_CREATED'][] = $change["file"];
				};
				
				if ($result)
				{
					$componentTemplateInfo['FILES_MODIFIED'][] = $change["file"];
					$insert = "\n<!-- Carrot Quest Insert Start -->\n".$change["data"]."\n<!-- Carrot Quest Insert End -->\n";	
					
					// Вставляем текст
					if ($change["after"] == "#END#")
					{
						if (!$this->phpIsClosed($file)) // Необходимо закрыть php если он был не закрыт...
							$insert = "\n?>".$insert;
						file_put_contents($file, $insert, FILE_APPEND);
					}
					else
					{
						$content = file_get_contents ($file);
						$content = preg_replace("(".$change["after"].")", '$0'.$insert, $content);
						$result = RewriteFile($file, $content);
					};
				};
			};
		};
			
		// Если успех - сохраняем изменения. Иначе откат к изначальному состоянию.
		$templateToModify['MODIFICATIONS'][] = $componentTemplateInfo;
		if (!$result)
			$this->RestoreTemplate($templateToModify);

		return $result;
	}
	
	/**
	* Восстанавливает шаблон, удаляя из него вставки Carrot quest.
	* <b>Параметры:</b>
	* <var>$componentTemlateName</var> - имя компонента, который необходимо подменить.
	* <var>$tpl</var> - шаблон модификации из $this->TEMPLATE_LIST
	* <var>$delete_created</var> - удалять ли созданные Carrot quest-ом файлы и папки
	* <b>Возвращаемое значение:</b> нет
	*/
	public function RestoreTemplate (&$tpl, $delete_created = false)
	{
		$tpl = (array)$tpl;
		// Нейтрализуем изменения
		foreach ($tpl["MODIFICATIONS"] as $mod)
		{
			foreach ($mod -> FILES_MODIFIED as $file)
			{
				$path = ($mod -> COMPONENT_TEMPLATE_PATH).$file;
				$content = file_get_contents ($path);
				$pattern = "~\n<!-- Carrot Quest Insert Start -->([\s\S]*?)<!-- Carrot Quest Insert End -->\n~is";
				$content = preg_replace($pattern, "", $content);
				RewriteFile($path, $content);
			}
			
			if ($delete_created)
			{
				if ($mod -> CREATED_BY_CARROTQUEST)
					DeleteDirFileEx($mod -> COMPONENT_TEMPLATE_PATH);
				else
					foreach($mod -> FILES_CREATED as $file)
						DeleteDirFileEx(($mod -> COMPONENT_TEMPLATE_PATH).$file);
				$tpl["MODIFICATIONS"] -> FILES_CREATED = array();
			}
		}
	}
	
}
