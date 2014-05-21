<?
	function askServer($arOrder)
	{
		// Сервер пока не работает, это заглушка
		return array(	
			max_discount => 0.5,
			carrots_selected => 5,
		);
	}
	function formatPrice ($price)
	{
		return number_format($price,0,'',' ').' '.GetMessage("CARROTQUEST_CURRENCY");
	};
	
	function CalcDiscount($arResult)
	{
		// Вычисляем скидку
		if (COption::GetOptionString("carrotquest","cqActivateBonus"))
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
			setcookie('CQPrice',$result_price);
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
?>
<?/*
	function outArray ($array, $name = '$array')
	{
		if (!$array)
		{
			?> <script>console.log("<?=$name;?> is undefined!");</script> <?
		}
		foreach ($array as $key => $val)
		{
			if (gettype($val) == "array" || gettype($val) == "object" )
				outArray($val, $name."['".$key."']");
			else { ?>
				<script>console.log("<?=$name;?>['<?= $key;?>'] = '<?= $val;?>'");</script>
			<? };
		};
	};
	outArray($arResult["PAY_SYSTEM"], '$arResult[\'PAY_SYSTEM\']');*/
?>