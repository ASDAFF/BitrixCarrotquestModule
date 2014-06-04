<? 
IncludeModuleLangFile( __FILE__ );
CModule::IncludeModule('sale');

class CarrotQuestEventHandlers
{
	static function IncludeHandler($arFields)
	{
		/* Это событие нужно только затем, что по какой-то мистике в событии OnAfterEpilog (ConnectHandler) не вызвается include.php. Костыль, но работает. */
		/*?>
			<script src="/bitrix/js/<?= CARROTQUEST_MODULE_ID; ?>/jquery.js"></script>
			<script src="/bitrix/js/<?= CARROTQUEST_MODULE_ID; ?>/carrotquest_init.js"></script>
			<script src="/bitrix/js/<?= CARROTQUEST_MODULE_ID; ?>/cookie.js"></script>
		<?*/
		
	}
	
    static function ConnectHandler($arFields)
	{
		global $CQ;

		// Подключение к CarrotQuest
		if ($CQ->Connect())
		{
			// Перехват события добавления в корзину
			// Информацию в Cookie записывает событие php OnBasketAdd (вывести там JS нельзя).
			if (COption::GetOptionString(CARROTQUEST_MODULE_ID,"cqTrackCartAdd")) {
			?>	<script>
					BX.addCustomEvent(window, "OnBasketChange", function () {
						if (typeof Cookie != 'undefined')
						{
							var info = Cookie.get("cqAddBasketProduct");
							if (info)
							{
								var product = JSON.parse(info);
								console.log('beforeTrack', product);
								
								// Отсылаем добавленный товар в CarrotQuest
								carrotquest.trackBasketAdd({
									objectId: product['PRODUCT_ID'],
									objectName: product['NAME'].replace(/\+/gim,' '),
									objectUrl: window.location.protocol + '//' + window.location.host + product['DETAIL_PAGE_URL'],
									fullObject: product // Весь объект товара на всякий случай =)
								});
							}
						}
						else ;
							//console.log('Cookie is undefined');
					});
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
	
	static function OnBasketAdd($ID, $arFields)
	{ 
		// В идеале все события весятся здесь. Но на практике здесь нельзя выполнить js. И все грусть печаль.
		// Поэтому рассчет на то, что сначала в компоненте срабатывает этот обработчик и устанавливает кук добавленного товара.
		// Затем срабатывает JS событие, на котором висит обработчик и использует этот кук
		$arFields['ADDED_LIST_ID'] = $ID;
		$res = CIBlockElement::GetByID($ID); 
		if ($el_arr= $res->GetNext()) 
			$arFields['xxx'] = $el_arr['NAME'];
		setcookie("cqAddBasketProduct",json_encode($arFields));
		return true;
    }
	
	static function OnBeforeOrderAddHandler($ID, $arFields)
	{
		/*  Нам необходимо переопределить стоимость с учетом скидки Carrotquest. 
			Рассчет был произведен в result_modifier.php, подмененного нами шаблона sale.order.ajax.
		*/
		if (COption::GetOptionString(CARROTQUEST_MODULE_ID,'cqActivateBonus') != '')
		{
			$arFields['PRICE'] = $_COOKIE['CQPrice'];
			CSaleOrder::Update($ID, $arFields);
		}
		
        return true;
    }
	
	static function OnOrderAddHandler ($ID, $arFields)
	{
		// Также необхоидмо вызвать событие оформления заказа в carrotquest
		if (COption::GetOptionString(CARROTQUEST_MODULE_ID,'cqTrackOrderConfirm') != '')
		{
			global $CQ;
			$CQ->OrderConfirm($ID, $arFields);
		}
		return true;
	}
	
	/* Если обновились сторонние модули template-ы которых использует carrotquest,
	*  этот метод копирует необходимые новые template-ы и встраивает в них carrotquest.
	*/
	static function OnUpdateInstalled ($array)
	{
		if (in_array("sale", $array["arSuccessModules"]))
			CarrotQuestEventHandlers::LoadSaleModuleTemplates();
		if (in_array("catalog", $array["arSuccessModules"]))
			CarrotQuestEventHandlers::LoadCatalogModuleTemplates();
	}
	
	// Вызывается методом OnUpdateInstalled
	static function LoadSaleModuleTemplates ()
	{
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
	
	// Вызывается методом OnUpdateInstalled
	static function LoadCatalogModuleTemplates ()
	{
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
