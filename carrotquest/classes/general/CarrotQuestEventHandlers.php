<? 
IncludeModuleLangFile( __FILE__ );
CModule::IncludeModule('sale');

class CarrotQuestEventHandlers
{
	
    static $MODULE_ID="carrotquest";
	
    static function ConnectHandler($arFields)
	{
		global $CQ;
		
		// ����������� � CarrotQuest
		if ($CQ->Connect())
		{
			// �������� JS �������
			
			// ������� ���������� � �������
			if (COption::GetOptionString("carrotquest","cqTrackCartAdd")) {
			?>	<script>
					BX.addCustomEvent(window, "OnBasketChange", function () {
						if (typeof Cookie != 'undefined')
						{
							var info = Cookie.get("cqAddBasketProduct");
							if (info)
							{
								var product = JSON.parse(info);

								// �������� ����������� ����� � CarrotQuest
								carrotquest.trackBasketAdd({
									objectId: product['PRODUCT_ID'],
									objectName: product['NAME'].replace(/\+/gim,' '),
									objectUrl: window.location.protocol + '//' + window.location.host + product['DETAIL_PAGE_URL'],
									fullObject: product // ���� ������ ������ �� ������ ������ =)
								});
							}
						}
						else
							console.log('Cookie is undefined');
					});
				</script>
			<?	};
			
			return true;
		}
		else
		{
			echo '<script>console.log("'.$CQ->Error.'");</script>';
		}
		
		
        // ���������
        return true;
    }
	
	static function OnBasketAdd($ID, $arFields)
	{ 
		// � ������ ��� ������� ������� �����. �� �� �������� ����� ������ ��������� js. � ��� ������ ������.
		// ������� ������� �� ���, ��� ������� � ���������� ����������� ���� ���������� � ������������� ��� ������������ ������.
		// ����� ����������� JS �������, �� ������� ����� ����������, ������� ���������� ���� ���
		$arFields['ADDED_LIST_ID'] = $ID;
		setcookie("cqAddBasketProduct",json_encode($arFields));
		return true;
    }
	

	static function OnBeforeOrderAddHandler($ID, $arFields)
	{
		/*  ��� ���������� �������������� ��������� � ������ ������ Carrotquest. 
			������� ��� ���������� � result_modifier.php, ������������ ���� ������� sale.order.ajax.
		*/
		if (COption::GetOptionString('carrotquest','cqActivateBonus') != '')
		{
			$arFields['PRICE'] = $_COOKIE['CQPrice'];
			CSaleOrder::Update($ID, $arFields);
		}
		
		
		// ���������
        return true;
    }
	
	static function OnOrderAddHandler ($ID, $arFields)
	{
		// ����� ���������� ������� ������� ���������� ������ � carrotquest
		if (COption::GetOptionString('carrotquest','cqTrackOrderConfirm') != '')
		{
			global $CQ;
			$CQ->OrderConfirm($ID, $arFields);
		}
		return true;
	}
	
	static function OnUpdateInstalled ($array)
	{
		if (in_array("sale", $array["arSuccessModules"]))
			CarrotQuestEventHandlers::LoadSaleModuleTemplates();
		if (in_array("catalog", $array["arSuccessModules"]))
			CarrotQuestEventHandlers::LoadCatalogModuleTemplates();
	}
	
	static function LoadSaleModuleTemplates ()
	{
		// �������
		CopyDirFiles(
			$_SERVER["DOCUMENT_ROOT"]."/bitrix/components/bitrix/sale.basket.basket/templates/.default/", 
			$_SERVER["DOCUMENT_ROOT"]."/bitrix/templates/.default/components/bitrix/sale.basket.basket/.default/", 
			true, true);
		file_put_contents($_SERVER["DOCUMENT_ROOT"]."/bitrix/templates/.default/components/bitrix/sale.basket.basket/.default/template.php",
		"\n<!-- CarrotQuest Basket Visit Event Start -->\n".
		"<? if (COption::GetOptionString('carrotquest', 'cqTrackCartVisit')) { ?>\n".
		"	<script>\n".
		"		carrotquest.track('Cart')\n".
		"	</script>\n".
		"<?} ?>".
		"<!-- CarrotQuest Basket Visit Event End -->",FILE_APPEND);
		
		//!! TODO ���� �� ��������. ���� ���� ������ - �� ���� �������� put_contents � ����� Discount
		// ���������
		/*CopyDirFiles(
			$_SERVER["DOCUMENT_ROOT"]."/bitrix/components/bitrix/sale.order.ajax/templates/.default/", 
			$_SERVER["DOCUMENT_ROOT"]."/bitrix/templates/.default/components/bitrix/sale.order.ajax/.default/", 
			true, true);
		
		file_put_contents($_SERVER["DOCUMENT_ROOT"]."/bitrix/templates/.default/components/bitrix/sale.order.ajax/.default/bitrix/catalog.element/.default/summary.php",
		"<!-- CarrotQuest Pre Order Event Start -->\n".
		"<? if (COption::GetOptionString('carrotquest','cqTrackPreOrder')) { ?>\n".
		"	<script>\n".
		"		carrotquest.track('Pre Order');\n".
		"	</script>\n".
		"<? } ?>\n".
		"<!-- CarrotQuest Pre Order Event End -->\n".
		
		"<!-- CarrotQuest Discount Start -->\n".
		"<? if (doubleval(".'$arResult[\'CARROTQUEST_DISCOUNT_PRICE\']'.") > 0)\n".
		"{ ?>\n".
		"<script>
			$('.bx_ordercart_order_sum tbody').append</script>".
		"	<tr>\n".
		"		<td class='custom_t1' colspan='<?=".'$colspan'."?>' class='itog'>\n".
		"			<?=GetMessage('SOA_TEMPL_SUM_CQ_DISCOUNT')?>\n".
		"		</td>\n".
		"		<td class='custom_t2' class='price'><?echo ".'$arResult[\'CARROTQUEST_DISCOUNT_PRICE_FORMATED\']'."?></td>\n".
		"	</tr>\n".
		"	<? } ?>\n".
		"<!-- CarrotQuest Discount End -->\n"
		,FILE_APPEND);*/
	}
	
	static function LoadCatalogModuleTemplates ()
	{
		CopyDirFiles(
			$_SERVER["DOCUMENT_ROOT"]."/bitrix/components/bitrix/catalog/templates/.default/", 
			$_SERVER["DOCUMENT_ROOT"]."/bitrix/templates/.default/components/bitrix/catalog/.default/", 
			true, true);
		file_put_contents($_SERVER["DOCUMENT_ROOT"]."/bitrix/templates/.default/components/bitrix/catalog/.default/bitrix/catalog.element/.default/template.php",
		"\n<!-- CarrotQuest Detailed Product Info Event Start -->\n".
		"<? if (COption::GetOptionString('carrotquest', 'cqTrackProductDetails')) { ?>".
		"	<script>\n".
		"		carrotquest.track('".'$product_view'."', {\n".
		"			objectId: '<?= ".'$arResult["ID"]'."; ?>',\n".
		"			objectName: '<?= ".'$arResult["NAME"]'."; ?>',\n".
		"			objectUrl: window.location.protocol + '//' + window.location.host + '".'<?= $arResult["DETAIL_PAGE_URL"]; ?>'."',\n".
		"			objectType: '".'$product'."',\n".
		"			fullObject: ".'<?= json_encode($arResult); ?>'."\n".
		"		});\n".
		"	</script>\n".
		"<?} ?>\n".
		"<!-- CarrotQuest Detailed Product Info Event End -->",FILE_APPEND);
	}
}