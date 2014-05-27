<?
	
	if(!check_bitrix_sessid())
		return;
	IncludeModuleLangFile(__FILE__);
	if(!check_bitrix_sessid()) 
		return;
		
	// Проверка ключа на валидность. Можно устроить более серьезную проверку, но пока по внешним признакам.
	if (!array_key_exists('ApiKey', $_REQUEST) || strlen($_REQUEST['ApiKey'])!=32)
	{
		// Если ключ неверен, то уничтожаем уже существующий в настройках ключ.
		if (COption::GetOptionString(CARROTQUEST_MODULE_ID,"cqApiKey"))
			COption::RemoveOption(CARROTQUEST_MODULE_ID, "cqApiKey");
		if (COption::GetOptionString(CARROTQUEST_MODULE_ID,"cqApiSecret"))
			COption::RemoveOption(CARROTQUEST_MODULE_ID, "cqApiSecret");
		
		// Показываем ошибку
		echo CAdminMessage::ShowMessage(Array("TYPE"=>"ERROR", "MESSAGE" =>GetMessage("MOD_INST_ERR"), "DETAILS"=>GetMessage("CARROTQUEST_KEY_ERROR"), "HTML"=>true));
	}
	else
	{
		// Пишем ключ в параметры модуля
		COption::SetOptionString(CARROTQUEST_MODULE_ID,"cqApiKey",$_REQUEST['ApiKey']);
		COption::SetOptionString(CARROTQUEST_MODULE_ID,"cqApiSecret",$_REQUEST['ApiSecret']);
		
		// Трекаем успешную установку модуля
		global $CQ;
		$CQ->Connect();
		?>
			<script>carrotquest.track('SuccessfullInstallBitrixModule');</script>
		<?
		
		// Модуль регистрирую тут, поскольку так и не понял, как в index.php отловить некорректное завершение step2.
		RegisterModule(CARROTQUEST_MODULE_ID);
		
		// Сообщение пользователю
		echo CAdminMessage::ShowNote(GetMessage("MOD_INST_OK"));
	}
?>
<form action="<?echo $APPLICATION->GetCurPage()?>">
	<input type="hidden" name="lang" value="<?echo LANG?>">
	<input type="submit" name="" value="<?echo GetMessage("MOD_BACK")?>">
<form>
