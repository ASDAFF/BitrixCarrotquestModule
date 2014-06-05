<?
	function askServer($arOrder)
	{
		// Получаем морковки
		global $carrotquest_API;
		return $carrotquest_API->GetSelectedCarrots();
	}
	function formatPrice ($price)
	{
		return number_format($price,0,'',' ').' '.GetMessage("CARROTQUEST_CURRENCY");
	};
	
	function CalcDiscount($arResult)
	{
		// Вычисляем скидку
		if (COption::GetOptionString(CARROTQUEST_MODULE_ID,"cqActivateBonus"))
		{
			$total = $arResult['ORDER_PRICE'];
		
			$CarrotInfo = askServer($arOrder);
			// Максимальное количество морковок, которым можно расплатиться за заказ
			$max_carrots = floor($total * $CarrotInfo['max_discount']);

			// Валидация скидки
			if ( $CarrotInfo['carrots_selected'] > $max_carrots || $CarrotInfo['carrots_selected'] < 0)
				$discount_percent = 0;
			else
				// Доля скидки
				$discount_percent = round($CarrotInfo['carrots_selected'] * $CarrotInfo['max_discount'] / $max_carrots, 4);
			
			// Стоимость скидки в рублях
			$discount_value = floor($total * $discount_percent);
			
			// Перопределяем итоговую стоимость
			$arResult["CARROTQUEST_DISCOUNT_PRICE"] = $discount_value;
			$arResult["CARROTQUEST_DISCOUNT_PRICE_FORMATED"] = formatPrice($discount_value);
			$priceFormat = $arResult["ORDER_TOTAL_PRICE_FORMATED"];
			$price = 0;
			
			for ($i = 0, $out = false; !$out && $i < strlen($priceFormat); $i++)
			{
				if ($priceFormat[$i] == ' ');
				elseif (ord($priceFormat[$i]) >= 48 && ord($priceFormat[$i]) <= 57)
					$price = $price * 10 + $priceFormat[$i];
				else
					$out = true;
			}
			$result_price = $price - $discount_value;
			
			// Устанавливаем кук для обработчика оформления заказа
			setcookie('carrotquest_price',$result_price,0, "/");
			$arResult["ORDER_TOTAL_PRICE_FORMATED"] = formatPrice($result_price);
		}
		else
		{
			$arResult["CARROTQUEST_DISCOUNT_PRICE"] = 0;
			$arResult["CARROTQUEST_DISCOUNT_PRICE_FORMATED"] = formatPrice(0);
		};
		
		return $arResult;
	}
	
	$arResult = CalcDiscount($arResult);
	
	// При оформлении заказ недоступны item-ы этого заказа. Запишем их в куки.
	if (COption::GetOptionString(CARROTQUEST_MODULE_ID,'cqTrackOrderConfirm') != '')
	{
		$cookie = array();
		
		// Кодировка Windows-1251 распознается некорректно...
		$lang = CLanguage::GetList($by="active", $order="desc", Array("NAME" => "russian"));
		$lang = $lang->Fetch();
		
		foreach ($arResult['BASKET_ITEMS'] as $value)
		{
			$item = array(
				"objectId"		=> $value['ID'],
				"objectName"	=> $value['NAME'],
				"objectUrl"		=> $_SERVER['HTTP_HOST'].$value['DETAIL_PAGE_URL'],
				"quantity"		=> $value['QUANTITY'],
				"price"			=> $value['PRICE'],
				//"fullObject"	=> $value
			);
			
			if ($lang['CHARSET'] == 'windows-1251')
				CarrotQuestEventHandlers::ToUTF($item);
			array_push($cookie,$item);
		};
		setcookie('carrotquest_basket_items', json_encode($cookie), 0, "/");
	};

?>
<?/*
	// Выводит массив в консоль. Просто для удобства отладки.
	function outArray ($array, $name = '$array', $recursive = true)
	{
		if (!$array)
		{
			?> <script>console.log("<?=$name;?> is undefined!");</script> <?
		}
		foreach ($array as $key => $val)
		{
			if ((gettype($val) == "array" || gettype($val) == "object") && $recursive)
				outArray($val, $name."['".$key."']");
			else { ?>
				<script>console.log("<?=$name;?>['<?= $key;?>'] = '<?= $val;?>'");</script>
			<? };
		};
	};
	outArray($arResult['BASKET_ITEMS'][0], '$arResult[\'BASKET_ITEMS\']', false);*/
?>