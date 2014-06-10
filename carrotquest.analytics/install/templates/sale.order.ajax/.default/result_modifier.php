<?
	// Вычисляем скидку
	global $carrotquest_API;
	if (COption::GetOptionString(CARROTQUEST_MODULE_ID,"cqActivateBonus"))
		$arResult = $carrotquest_API->CalcDiscount($arResult);
	
	// При оформлении заказа недоступны item-ы этого заказа. Запишем их в куки.
	if (COption::GetOptionString(CARROTQUEST_MODULE_ID,'cqTrackOrderConfirm') != '')
		CarrotQuestEventHandlers::SetBasketItemsCookie($arResult["BASKET_ITEMS"])
?>