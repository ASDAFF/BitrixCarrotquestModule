<? 
IncludeModuleLangFile( __FILE__ );
CModule::IncludeModule('sale');

/**
* ����� �������� ����������� ������� ��������� �������, ������������ � Carrot Quest.
*/
class CarrotQuestEventHandlers
{
	/**
	*	��� ������� ����� ������ �����, ��� �� �����-�� ������� � ������� OnAfterEpilog (ConnectHandler) �� ��������� include.php. �������, �� ��������.
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
	*	������� ���������� ����� ��������� ����� �������� ��������, ��������� carrotquest.
	*	����� ����, ������������ ������� ���������� ������ � ������� �� ������� �������.
	*	<b>���������:</b>
	*	<var>$_COOKIE['carrotquest_add_basket_product']</var> - ���� ���� ��� ���������, �� ����� ������ ���� ���������� ������ � �������.
	*/
    static function ConnectHandler()
	{
		global $carrotquest_API;

		// ����������� � CarrotQuest
		if ($carrotquest_API->Connect())
		{
			// �������� ������� ���������� � �������
			// ���������� � Cookie ���������� ������� php OnBasketAdd (������� ��� JS ������).
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
								
								// �������� ����������� ����� � CarrotQuest
								carrotquest.trackBasketAdd({
									objectId: product['PRODUCT_ID'],
									objectName: product['NAME'].replace(/\+/gim,' '),
									objectUrl: window.location.protocol + '//' + window.location.host + product['DETAIL_PAGE_URL'],
									fullObject: product // ���� ������ ������ �� ������ ������ =)
								});
							}
						}
						else ; //console.log('carrotquest_cookie is undefined');
					}
					// ��� �� ������ ���� js ������� �� ���������. ����� ��� ������ �� ����������  �������� �� ������� � ���� �������.
					OnBasketAdd();
					// JS �������. �������������������. ������� ��������� ����.
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
	*	��������� ������� ���������� � �������. � ������ �� ������� ������ ���� ���������� �����.
	*	�� �� �������� ����� ������ ��������� JavaScript. ������� ������� �� ��, ��� ������� � ���������� ����������� ���� ����������
	*	� ������������� cookie ������������ ������, � ����� ����������� JS �������, �� ������� ����� ���������� � ���������� ���� cookie
	*	<b>���������:</b>
	*	<var>$ID</var> - �����, ��� ������� ����� ��� �������� � ������ (�� ������ � ID ������ � ������)
	*	<var>$arFields</var> - ��������� ������ � ������� Bitrix.
	*	<b>������������ ��������:</b>
	*	true
	*/
	static function OnBasketAdd($ID, $arFields)
	{ 
		
		$arFields['ADDED_LIST_ID'] = $ID;
		
		// ��������� Windows-1251 ������������ �����������...
		$lang = CLanguage::GetList($by="active", $order="desc", Array("NAME" => "russian"));
		$lang = $lang->Fetch();
		if ($lang['CHARSET'] == 'windows-1251')
			CarrotQuestEventHandlers::ToUTF($arFields);
			
		setcookie("carrotquest_add_basket_product",json_encode($arFields), 0, "/");
		return true;
    }
	
	/**
	*	��� ���������� �������������� ��������� � ������ ������ Carrotquest. 
	*	������� ��� ���������� � result_modifier.php, ������������ ���� ������� sale.order.ajax.
	*	<b>���������:</b>
	*	<var>$ID</var> - ID ������
	*	<var>$arFields</var> - ��������� ������ � ������� Bitrix.
	*	<var>$_COOKIE['carrotquest_price']</var> - ��������� ������ � ������ ������ Carrot Quest
	*	<b>������������ ��������:</b>
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
	*	������������ ��� ���� �������, ��������� ���������� �� windows-1251 � UTF-8
	*	<b>���������:</b>
	*	<var>$object</var> - ������ ��� ���������������
	*	<b>������������ ��������:</b>
	*	���
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
	*	������� ���������� ������ - ������� � Carrot Quest
	*	<b>���������:</b>
	*	<var>$ID</var> - ID ������
	*	<var>$arFields</var> - ��������� ������ � ������� Bitrix.
	*	<b>������������ ��������:</b>
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
	
	/** ���� ���������� ��������� ������ template-� ������� ���������� Carrot Quest,
	*  ���� ����� �������� ����������� ����� template-� � ���������� � ��� ��� Carrot Quest.
	*	<b>���������:</b>
	*	<var>$array</var> - ��������� ����������� �������
	*	<b>������������ ��������:</b> �����������.
	*/
	static function OnUpdateInstalled ($array)
	{
		if (in_array("sale", $array["arSuccessModules"]))
			CarrotQuestEventHandlers::LoadSaleModuleTemplates();
		if (in_array("catalog", $array["arSuccessModules"]))
			CarrotQuestEventHandlers::LoadCatalogModuleTemplates();
	}
	
	/**
	* �������������� ������� ������ ������ ���������� Bitrix.
	* ��� ������������� �������� ��� �� ����������� �������� � ����� /bitrix/templates/���_�������_�����/components/bitrix/
	* <b>���������:</b>
	* <var>$path</var> - ���� � �������, ������� ���������� ���������.
	* <var>$data</var> - ������ ������, ������� ���� ������� � ������. � ������� <code>{file: "template.php", after: "regexp", data: ""}</code>
	* ���� � ����� - ������������ $path.
	* ���� after ����� ��������� �������� ����� #END# - ����� ������ ����� ������������ � ����� �����.
	* <b>������������ ��������:</b>
	* true - � ������ ������ ����������, false - � ������ �������.
	*/
	static function UpdateTemplate ($path, $data)
	{
		$result = true;
		// �������� �� $path ��� ����������� �������
		$ar = split('/', $path);
		$templateName = array_pop($ar);
		
		// �������� � ���������� ������, ����� ����� ��������� ��� ����������������
		$filesCreated = array();
		$filesModified = array();
		
		// ��� ������� ������� ����� � ����� /bitrix/templates �������� �������� ���� ������
		$sites = CSite::GetList();
		while ($site = $sites->Fetch())
		{
			$rsTemplates = CSite::GetTemplateList($site["LID"]);
			while ($template = $rsTemplates->Fetch())
			{
				// ���� � �������� ����������� ��� ������� ������� �����
				$templatePath = $_SERVER['DOCUMENT_ROOT'].'/bitrix/templates/'.$template['TEMPLATE'].'/components/bitrix/'.$templateName."/";
				if (!file_exists($templatePath))
				{
					// �������� ������
					CheckDirPath($templatePath);
					$result = CopyDirFiles($path, $templatePath, true, true);
					$filesCreated[] = $templatePath;
				}
				else ; // ����� �������������� ������ ������������
				
				if ($result)
					foreach ($data as $change)
					{
						// ���� � �����, ������� ����� ��������
						$file = $templatePath.$change["file"];
						if (!file_exists($file))
						{
							// �������� �� ��������� �������
							$result = copy($file, $path.$change["file"]);
							$filesCreated[] = $file;
						};
						
						if ($result)
						{
							// ��������� ���������� �����
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
		
		// ���� ����� - ��������� ���������. ����� ����� � ������������ ���������.
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
		
		// ������������ ��� ���������
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
	*	��������� ������� ������ sale.
	*	<b>���������:</b> �����������
	*	<b>������������ ��������:</b> �����������.
	*/
	static function LoadSaleModuleTemplates ()
	{
		global $APPLICATION;
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
	
	/**
	*	��������� ������� ������ catalog.
	*	<b>���������:</b> �����������
	*	<b>������������ ��������:</b> �����������.
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
