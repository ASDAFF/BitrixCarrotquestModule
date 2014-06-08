<? 
	IncludeModuleLangFile(__FILE__); 
	if(!check_bitrix_sessid()) return;
	global $carrotquest_errors;
	if (!empty($errors))
	{
		// Показываем ошибку
		$message = '';
		foreach($errors as $value)
			$message .= GetMessage($value)."<br>";

		echo CAdminMessage::ShowMessage(Array("TYPE"=>"ERROR", "MESSAGE" =>GetMessage("MOD_INST_ERR"), "DETAILS"=>$message, "HTML"=>true));
	}
	elseif ($_REQUEST['SendEmail'])
		echo CAdminMessage::ShowNote(GetMessage("CARROTQUEST_SEND_EMAIL_OK"));
	else
	{
		// Трекаем успешную установку модуля
		global $carrotquest_API;
		$carrotquest_API->Connect();
		?>
			<script>carrotquest.track('SuccessfullInstallBitrixModule');</script>
		<?
		echo CAdminMessage::ShowNote(GetMessage("MOD_INST_OK"));
	}
?>
<form action="<?echo $APPLICATION->GetCurPage()?>">
	<input type="hidden" name="lang" value="<?echo LANG?>">
	<input type="submit" name="" value="<?echo GetMessage("MOD_BACK")?>">
</form>
<? include(CARROTQUEST_INCLUDE_PATH."installHelp.php"); ?>
