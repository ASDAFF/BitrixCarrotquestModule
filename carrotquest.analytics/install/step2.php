<?
	
	if(!check_bitrix_sessid())
		return;
	IncludeModuleLangFile(__FILE__);
	if(!check_bitrix_sessid()) 
		return;
		
	// Проверка ключа на валидность. Можно устроить более серьезную проверку, но пока по внешним признакам.
	
	if (empty($errors) && (!array_key_exists('ApiKey', $_REQUEST) || !array_key_exists('ApiSecret', $_REQUEST) || strlen($_REQUEST['ApiKey'])!=32 || strlen($_REQUEST['ApiSecret'])!=64))
		array_push($errors, "CARROTQUEST_KEY_ERROR");

	if (!empty($errors))
	{
		// Стереть ключи
		if (COption::GetOptionString(CARROTQUEST_MODULE_ID,"cqApiKey"))
			COption::RemoveOption(CARROTQUEST_MODULE_ID, "cqApiKey");
		if (COption::GetOptionString(CARROTQUEST_MODULE_ID,"cqApiSecret"))
			COption::RemoveOption(CARROTQUEST_MODULE_ID, "cqApiSecret");
			
		// Показываем ошибку
		$message = '';
		foreach($errors as $value)
			$message .= GetMessage($value)."<br>";

		echo CAdminMessage::ShowMessage(Array("TYPE"=>"ERROR", "MESSAGE" =>GetMessage("MOD_INST_ERR"), "DETAILS"=>$message, "HTML"=>true));
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