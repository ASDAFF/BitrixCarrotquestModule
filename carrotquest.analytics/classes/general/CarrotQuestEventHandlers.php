<? 
IncludeModuleLangFile( __FILE__ );
CModule::IncludeModule('sale');

class CarrotQuestEventHandlers
{
	static function IncludeHandler($arFields)
	{
		/* ��� ������� ����� ������ �����, ��� �� �����-�� ������� � ������� OnAfterEpilog (ConnectHandler) �� ��������� include.php. �������, �� ��������. */
		/*?>
			<script src="/bitrix/js/<?= CARROTQUEST_MODULE_ID; ?>/jquery.js"></script>
			<script src="/bitrix/js/<?= CARROTQUEST_MODULE_ID; ?>/carrotquest_init.js"></script>
			<script src="/bitrix/js/<?= CARROTQUEST_MODULE_ID; ?>/cookie.js"></script>
		<?*/
		
	}
	
    static function ConnectHandler($arFields)
	{
		global $CQ;

		// ����������� � CarrotQuest
		if ($CQ->Connect())
		{
			// �������� ������� ���������� � �������
			// ���������� � Cookie ���������� ������� php OnBasketAdd (������� ��� JS ������).
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
								
								// �������� ����������� ����� � CarrotQuest
								carrotquest.trackBasketAdd({
									objectId: product['PRODUCT_ID'],
									objectName: product['NAME'].replace(/\+/gim,' '),
									objectUrl: window.location.protocol + '//' + window.location.host + product['DETAIL_PAGE_URL'],
									fullObject: product // ���� ������ ������ �� ������ ������ =)
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
		// � ������ ��� ������� ������� �����. �� �� �������� ����� ������ ��������� js. � ��� ������ ������.
		// ������� ������� �� ��, ��� ������� � ���������� ����������� ���� ���������� � ������������� ��� ������������ ������.
		// ����� ����������� JS �������, �� ������� ����� ���������� � ���������� ���� ���
		$arFields['ADDED_LIST_ID'] = $ID;
		$res = CIBlockElement::GetByID($ID); 
		if ($el_arr= $res->GetNext()) 
			$arFields['xxx'] = $el_arr['NAME'];
		setcookie("cqAddBasketProduct",json_encode($arFields));
		return true;
    }
	
	static function OnBeforeOrderAddHandler($ID, $arFields)
	{
		/*  ��� ���������� �������������� ��������� � ������ ������ Carrotquest. 
			������� ��� ���������� � result_modifier.php, ������������ ���� ������� sale.order.ajax.
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
		// ����� ���������� ������� ������� ���������� ������ � carrotquest
		if (COption::GetOptionString(CARROTQUEST_MODULE_ID,'cqTrackOrderConfirm') != '')
		{
			global $CQ;
			$CQ->OrderConfirm($ID, $arFields);
		}
		return true;
	}
	
	/* ���� ���������� ��������� ������ template-� ������� ���������� carrotquest,
	*  ���� ����� �������� ����������� ����� template-� � ���������� � ��� carrotquest.
	*/
	static function OnUpdateInstalled ($array)
	{
		if (in_array("sale", $array["arSuccessModules"]))
			CarrotQuestEventHandlers::LoadSaleModuleTemplates();
		if (in_array("catalog", $array["arSuccessModules"]))
			CarrotQuestEventHandlers::LoadCatalogModuleTemplates();
	}
	
	// ���������� ������� OnUpdateInstalled
	static function LoadSaleModuleTemplates ()
	{
		// �������
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
	
	// ���������� ������� OnUpdateInstalled
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
