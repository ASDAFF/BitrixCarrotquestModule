<? 
IncludeModuleLangFile( __FILE__ );
CModule::IncludeModule('sale');

/**
* ����� �������� ����������� ������� ��������� �������, ������������ � Carrot Quest.
*/
class CarrotQuestEventHandlers
{
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
	
	/**
	* ������������� Cookie ��� ������� ���������� ������
	* <b>���������:</b>
	* <var>$items</var> - ���������� ������� � ������� Bitrix
	* <b>������������ ��������:</b> ���
	*/
	static function SetBasketItemsCookie ($items)
	{
		if (!array_key_exists('carrotquest_basket_items', $_COOKIE) || !$_COOKIE['carrotquest_basket_items'] || $_COOKIE['carrotquest_basket_items'] == '[]')
		{
			$cookie = array();
			
			// ��������� Windows-1251 ������������ �����������...
			$lang = CLanguage::GetList($by="active", $order="desc", Array("NAME" => "russian"));
			$lang = $lang->Fetch();
				
			foreach ($items as $value)
			{
				$item = array(
					"objectId"		=> $value['ID'],
					"objectName"	=> $value['NAME'],
					"objectUrl"		=> $_SERVER['HTTP_HOST'].$value['DETAIL_PAGE_URL'],
					"quantity"		=> $value['QUANTITY'],
					"price"			=> $value['PRICE'],
				);
				
				if ($lang['CHARSET'] == 'windows-1251')
					CarrotQuestEventHandlers::ToUTF($item);
					
				array_push($cookie,$item);
			};
			setcookie('carrotquest_basket_items', json_encode($cookie), 0, "/");
		};
	}
}
