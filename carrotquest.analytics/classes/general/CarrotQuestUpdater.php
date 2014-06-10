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
			// Детальное описание товара
			"catalog" => array(
				"name" => "catalog",
				"path" => $_SERVER["DOCUMENT_ROOT"]."/bitrix/components/bitrix/catalog/templates/.default/", 
				"data" => array(
					array (
						"file" => "bitrix/catalog.element/.default/template.php",
						"after" => "#END#",
						"data" =>	"<? //Detailed Product Info Event\n".
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
			"sale.order.ajax" => array(
				"name" => "sale.order.ajax",
				"path" => $_SERVER["DOCUMENT_ROOT"]."/bitrix/components/bitrix/sale.order.ajax/templates/.default/", 
				"data" => array(
					array (
						"file" => "summary.php",
						"after" => "#END#",
						"data" =>	'<script>'."\n".
									'<? // Pre-order event'."\n".
									'	if (COption::GetOptionString(CARROTQUEST_MODULE_ID,"cqTrackPreOrder"))'."\n".
									'	{ ?>'."\n".
									'		carrotquest.track("Pre Order");'."\n".
									'	<? } '."\n".
									'	/* Carrot quest discount field */'."\n".
									'	if (COption::GetOptionString(CARROTQUEST_MODULE_ID,"cqActivateBonus") && doubleval($arResult["CARROTQUEST_DISCOUNT_PRICE"]) > 0)'."\n".
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
					array (
						"file" => "result_modifier.php",
						"after" => "#END#",
						"data" =>	'<?'."\n".
									'	// Count discount'."\n".
									'	global $carrotquest_API;'."\n".
									'	if (COption::GetOptionString(CARROTQUEST_MODULE_ID,"cqActivateBonus"))'."\n".
									'		$arResult = $carrotquest_API->CalcDiscount($arResult);'."\n".
									'	// When order is done, items are not present. So we write it to cookie'."\n".
									'	if (COption::GetOptionString(CARROTQUEST_MODULE_ID,"cqTrackOrderConfirm") != "")'."\n".
									'		CarrotQuestEventHandlers::SetBasketItemsCookie($arResult["BASKET_ITEMS"])'."\n".
									'?>',
					),
					array (
						"file" => "confirm.php",
						"after" => "#END#",
						"data" =>	'<? Track Order Event -->'."\n".
									'	if (COption::GetOptionString(CARROTQUEST_MODULE_ID, "cqActivateBonus")) { ?>'."\n".
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
					array (
						"file" => "lang/en/template.php",
						"after" => "#END#",
						"data" =>	'<? $MESS["SOA_TEMPL_SUM_CQ_DISCOUNT"] = "Carrot Quest discount"; ?>',
					),
					array (
						"file" => "lang/ru/template.php",
						"after" => "#END#",
						"data" =>	iconv('utf-8','windows-1251','<? $MESS["SOA_TEMPL_SUM_CQ_DISCOUNT"] = "Скидка Carrot Quest"; ?>'),
					),
				),
			),
		);
	}

	public function SetTemplateList ($object = false)
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
	
	public function GetListFromRequest ()
	{
		$this->TEMPLATE_LIST = array();

		foreach ($_REQUEST as $key => $value)
		{
			if (preg_match('/carrotquest_template_([\s\S]+)/', $key, $matches))
				$this->TEMPLATE_LIST[$matches[1]] = array(
					"NAME" => $matches[1],
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
	
	public function UpdateAllTemplates ()
	{
		/**
			Полная структура шаблона:
			"NAME" - имя шаблона
			"PATH" - путь к шаблону
			"SITE_BACKUP_PATH" - путь
			"MODIFICATIONS" - массив-список изменений, произведенных с файлами шаблона. Структура элемента:
				"COMPONENT_TEMPLATE_NAME" - имя шаблона компонента
				"COMPONENT_TEMPLATE_PATH" - путь к шаблону компонента
				"COPIED_BY_CARROTQUEST" - был ли шаблон компонента целиком скопирован carrotquest-ом
				"FILES_CREATED" - список путей файлов внутри шаблона, созданных (скопированных) carrotquest-ом. Не учитывает копирование шаблона целиком ("COPIED_BY_CARROTQUEST" = true)
				"FILES_MODIFIED" - список путей файлов, модифицированных carrotquest-ом.
		*/
		foreach($this->TEMPLATE_LIST as $tplName => & $tpl)
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
			
			foreach ($this->MODIFICATIONS as $value)
				$this->ModifyTemplate($value["name"], $value["path"], $value["data"], $tpl);
		}
		
		COption::SetOptionString(CARROTQUEST_MODULE_ID,"cqReplacedTemplates",json_encode($this->TEMPLATE_LIST));
	}
	
	public function RestoreAllTemplates ()
	{
		foreach($this->TEMPLATE_LIST as $tpl)
			$this->RestoreTemplate($tpl);
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
	public function ModifyTemplate ($componentTemplateName, $path, $data, & $templateToModify)
	{
		$result = true;
		
		// Путь к шаблонам компонентов для данного шаблона сайта
		$componentTemplatePath = $templateToModify["PATH"].'/components/bitrix/'.$componentTemplateName."/";

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
					if (file_exists($path.$change["file"]))
						// Копируем из исходного шаблона
						$result = copy($file, $path.$change["file"]);
					else
						// Создаем новый пустой файл
						$result = RewriteFile($file,'');
					$componentTemplateInfo['FILES_CREATED'][] = $file;
				};
				
				if ($result)
				{
					$componentTemplateInfo['FILES_MODIFIED'][] = $file;
					// Обновляем содержимое файла
					$insert = "\n<!-- Carrot Quest Insert Start -->\n".$change["data"]."\n<!-- Carrot Quest Insert End -->\n";	
					
					// Вставляем текст
					if ($change["after"] == "#END#")
					{
						// Необходимо закрыть php если он был не закрыт...
						if (!$this->phpIsClosed($file))
							$insert = "\n?>".$insert;
						
						file_put_contents($file, $insert, FILE_APPEND);
					}
					else
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
		
		return $result;
	}
	
	public function RestoreTemplate ($tpl)
	{
		$tpl = (array)$tpl;
		// Нейтрализуем изменения
		foreach ($tpl["MODIFICATIONS"] as $mod)
		{
			foreach ($mod -> FILES_MODIFIED as $file)
			{
				$content = file_get_contents ($file);
				$pattern = "~\n<!-- Carrot Quest Insert Start -->([\s\S]*?)<!-- Carrot Quest Insert End -->\n~is";
				$content = preg_replace($pattern, "", $content);
				RewriteFile($file, $content);
			}
		}
	}
}
