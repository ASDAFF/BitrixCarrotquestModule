<? 
IncludeModuleLangFile( __FILE__ );
CModule::IncludeModule('sale');

/**
* Класс содержит обработчики событий различных модулей, используемых в Carrot Quest.
*/
class CarrotQuestEventHandlers
{
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
	
	/**
	* Устанавливает Cookie для события оформления заказа
	* <b>Параметры:</b>
	* <var>$items</var> - содержимое корзины в формате Bitrix
	* <b>Возвращаемое значение:</b> нет
	*/
	static function SetBasketItemsCookie ($items)
	{
		if (!array_key_exists('carrotquest_basket_items', $_COOKIE) || !$_COOKIE['carrotquest_basket_items'] || $_COOKIE['carrotquest_basket_items'] == '[]')
		{
			$cookie = array();
			
			// Кодировка Windows-1251 распознается некорректно...
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
