<? 
IncludeModuleLangFile( __FILE__ );
CModule::IncludeModule('sale');

/**
* Класс содержит обработчики событий различных модулей, используемых в Carrot Quest.
*/
class CarrotQuestEventHandlers
{
	/**
	*	Это событие нужно только затем, что по какой-то мистике в событии OnAfterEpilog (ConnectHandler) не вызвается include.php. Костыль, но работает.
	*/
	static function IncludeHandler($arFields)
	{
		$mod = array(
			array("file" => ".default/template.php", "after" => '#END#', "data" => "Test Data 1"),
			array("file" => ".default/template.php", "after" => '<div class="BX_PROLOG_INCLUDED">', "data" => "Test Data 3"),
		);
		$res = CarrotQuestEventHandlers::UpdateTemplate($_SERVER['DOCUMENT_ROOT']."/bitrix/modules/carrotquest.analytics/install/templates/sale.order.ajax",$mod);
		?><script>console.log("<?= $res; ?>");</script><?
		return true;
	}
	
	/**
	*	Событие вызывается перед загрузкой любой страницы магазина, подключая carrotquest.
	*	Кроме того, обрабатывает событие добавления товара в корзину со стороны клиента.
	*	<b>Параметры:</b>
	*	<var>$_COOKIE['carrotquest_add_basket_product']</var> - Если этот кук определен, то будет вызван трек добавления товара в корзину.
	*/
    static function ConnectHandler()
	{
		global $carrotquest_API;

		// Подключение к CarrotQuest
		if ($carrotquest_API->Connect())
		{
			// Перехват события добавления в корзину
			// Информацию в Cookie записывает событие php OnBasketAdd (вывести там JS нельзя).
			if (COption::GetOptionString(CARROTQUEST_MODULE_ID,"cqTrackCartAdd")) {
			?>	<script>
					function OnBasketAdd()
					{
						if (typeof carrotquest_cookie != 'undefined')
						{
							var info = carrotquest_cookie.get("carrotquest_add_basket_product");
							if (info)
							{
								carrotquest_cookie.delete("carrotquest_add_basket_product");

								var product = JSON.parse(info);
								
								// Отсылаем добавленный товар в CarrotQuest
								carrotquest.trackBasketAdd({
									objectId: product['PRODUCT_ID'],
									objectName: product['NAME'].replace(/\+/gim,' '),
									objectUrl: window.location.protocol + '//' + window.location.host + product['DETAIL_PAGE_URL'],
									fullObject: product // Весь объект товара на всякий случай =)
								});
							}
						}
						else ; //console.log('carrotquest_cookie is undefined');
					}
					// Это на случай если js событие не сработало. Тогда при первом же обновлении  страницы мы отошлем в базу событие.
					OnBasketAdd();
					// JS событие. Незадокументировано. Поэтому страховка выше.
					BX.addCustomEvent(window, "OnBasketChange", OnBasketAdd);
				</script>
			<?	};
			
			return true;
		}
		else
		{
			//echo '<script>console.log("Connect fail");</script>';
		}

        return true;
    }
	
	/**
	*	Серверное событие добавления в корзину. В идеале всё событие должно быть обработано здесь.
	*	Но на практике здесь нельзя выполнять JavaScript. Поэтому рассчет на то, что сначала в компоненте срабатывает этот обработчик
	*	и устанавливает cookie добавленного товара, а затем срабатывает JS событие, на котором висит обработчик и использует этот cookie
	*	<b>Параметры:</b>
	*	<var>$ID</var> - номер, под которым товар был добавлен в список (не путать с ID товара и заказа)
	*	<var>$arFields</var> - параметры товара в формате Bitrix.
	*	<b>Возвращаемое значение:</b>
	*	true
	*/
	static function OnBasketAdd($ID, $arFields)
	{ 
		
		$arFields['ADDED_LIST_ID'] = $ID;
		
		// Кодировка Windows-1251 распознается некорректно...
		$lang = CLanguage::GetList($by="active", $order="desc", Array("NAME" => "russian"));
		$lang = $lang->Fetch();
		if ($lang['CHARSET'] == 'windows-1251')
			CarrotQuestEventHandlers::ToUTF($arFields);
			
		setcookie("carrotquest_add_basket_product",json_encode($arFields), 0, "/");
		return true;
    }
	
	/**
	*	Нам необходимо переопределить стоимость с учетом скидки Carrotquest. 
	*	Рассчет был произведен в result_modifier.php, подмененного нами шаблона sale.order.ajax.
	*	<b>Параметры:</b>
	*	<var>$ID</var> - ID заказа
	*	<var>$arFields</var> - параметры заказа в формате Bitrix.
	*	<var>$_COOKIE['carrotquest_price']</var> - стоимость товара с учетом скидки Carrot Quest
	*	<b>Возвращаемое значение:</b>
	*	true
	*/
	static function OnBeforeOrderAddHandler($ID, $arFields)
	{
		if (COption::GetOptionString(CARROTQUEST_MODULE_ID,'cqActivateBonus') != '')
		{
			$arFields['PRICE'] = $_COOKIE['carrotquest_price'];
			CSaleOrder::Update($ID, $arFields);
		}
		
        return true;
    }
	
	/**
	*	Перекодирует все поля объекта, заданного параметром из windows-1251 в UTF-8
	*	<b>Параметры:</b>
	*	<var>$object</var> - объект для перекодирования
	*	<b>Возвращаемое значение:</b>
	*	нет
	*/
	static function ToUTF (&$object)
	{
		foreach($object as $key => $value)
			if (gettype($value) == 'array' || gettype($value) == 'object')
				CarrotQuestEventHandlers::ToUTF($value);
			else
				$object[$key] = iconv('windows-1251', 'UTF-8', $value);
	}
	
	/**
	*	Событие оформления заказа - трекинг в Carrot Quest
	*	<b>Параметры:</b>
	*	<var>$ID</var> - ID заказа
	*	<var>$arFields</var> - параметры заказа в формате Bitrix.
	*	<b>Возвращаемое значение:</b>
	*	true
	*/
	static function OnOrderAddHandler ($ID, $arFields)
	{
		if (COption::GetOptionString(CARROTQUEST_MODULE_ID,'cqTrackOrderConfirm') != '')
		{
			global $carrotquest_API;
			$carrotquest_API->OrderConfirm($ID, $arFields);
		}
		return true;
	}
	
	/** Если обновились сторонние модули template-ы которых использует Carrot Quest,
	*  этот метод копирует необходимые новые template-ы и встраивает в них код Carrot Quest.
	*	<b>Параметры:</b>
	*	<var>$array</var> - параметры обновленных модулей
	*	<b>Возвращаемое значение:</b> отсутствует.
	*/
	static function OnUpdateInstalled ($array)
	{
		if (in_array("sale", $array["arSuccessModules"]))
			CarrotQuestEventHandlers::LoadSaleModuleTemplates();
		if (in_array("catalog", $array["arSuccessModules"]))
			CarrotQuestEventHandlers::LoadCatalogModuleTemplates();
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
	static function UpdateTemplate ($path, $data)
	{
		$result = true;
		// Получаем из $path имя копируемого шаблона
		$ar = split('/', $path);
		$templateName = array_pop($ar);
		
		// Сохраним в настройках модуля, чтобы потом корректно все деинсталлировать
		$filesCreated = array();
		$filesModified = array();
		
		// Для каждого шаблона сайта в папке /bitrix/templates пытаемся вставить свой шаблон
		$sites = CSite::GetList();
		while ($site = $sites->Fetch())
		{
			$rsTemplates = CSite::GetTemplateList($site["LID"]);
			while ($template = $rsTemplates->Fetch())
			{
				// Путь к шаблонам компонентов для данного шаблона сайта
				$templatePath = $_SERVER['DOCUMENT_ROOT'].'/bitrix/templates/'.$template['TEMPLATE'].'/components/bitrix/'.$templateName."/";
				if (!file_exists($templatePath))
				{
					// Копируем шаблон
					CheckDirPath($templatePath);
					$result = CopyDirFiles($path, $templatePath, true, true);
					$filesCreated[] = $templatePath;
				}
				else ; // Будем модифицировать шаблон пользователя
				
				if ($result)
					foreach ($data as $change)
					{
						// Путь к файлу, который будем изменять
						$file = $templatePath.$change["file"];
						if (!file_exists($file))
						{
							// Копируем из исходного шаблона
							$result = copy($file, $path.$change["file"]);
							$filesCreated[] = $file;
						};
						
						if ($result)
						{
							// Обновляем содержимое файла
							$insert = "\n<!-- Carrot Quest Insert Start -->\n".$change["data"]."\n<!-- Carrot Quest Insert End -->\n";
							
							if ($change["after"] == "#END#")
							{
								$result = (file_put_contents($file, $insert, FILE_APPEND) == strlen($insert));
							}
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
							$filesModified[] = $file;
						};
					};
			};
		};
		
		// Если успех - сохраняем изменения. Иначе откат к изначальному состоянию.
		if ($result)
		{
			$filesCreated = array_merge($filesCreated, json_decode(COption::GetOptionString(CARROTQUEST_MODULE_ID, "CQFilesCreated")));
			$filesModified = array_merge($filesModified, json_decode(COption::SetOptionString(CARROTQUEST_MODULE_ID, "CQFilesModified")));
			COption::SetOptionString(CARROTQUEST_MODULE_ID, "CQFoldersCreated", json_encode($foldersCreated));
			COption::SetOptionString(CARROTQUEST_MODULE_ID, "CQFilesModified", json_encode($filesModified));
		}
		else
			CarrotQuestEventHandlers::RestoreTemplates($filesCreated, $filesModified);
		
		return $result;
	}
	
	static function RestoreTemplates ($filesCreated, $filesModified)
	{
		if (!$foldersCreated && !$filesModified)
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
		}
		
		COption::SetOptionString(CARROTQUEST_MODULE_ID, "CQFoldersCreated", json_encode(array()));
		COption::SetOptionString(CARROTQUEST_MODULE_ID, "CQFilesModified", json_encode(array()));
	}
	
	/**
	*	Обновляет шаблоны модуля sale.
	*	<b>Параметры:</b> отсутствуют
	*	<b>Возвращаемое значение:</b> отсутствует.
	*/
	static function LoadSaleModuleTemplates ()
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
	static function LoadCatalogModuleTemplates ()
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
