<?
	// ��������� ������
	global $carrotquest_API;
	if (COption::GetOptionString(CARROTQUEST_MODULE_ID,"cqActivateBonus"))
		$arResult = $carrotquest_API->CalcDiscount($arResult);
	
	// ��� ���������� ������ ���������� item-� ����� ������. ������� �� � ����.
	if (COption::GetOptionString(CARROTQUEST_MODULE_ID,'cqTrackOrderConfirm') != '')
		CarrotQuestEventHandlers::SetBasketItemsCookie($arResult["BASKET_ITEMS"])
?>