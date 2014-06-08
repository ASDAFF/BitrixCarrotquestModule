<? IncludeModuleLangFile(__FILE__); ?>

<style type="text/css">
	#carrotquest_install_help
	{
		margin-top: 40px !important;	
		display: block !important;
		width: 500px;
	}
	#carrotquest_install_help input[type="text"]
	{
		width: 200px;
	}
	#carrotquest_install_help input[type="submit"]
	{
		width: 200px;
	}
</style>

<?
	// Отправка письма, если форма была засабмичена
	if ($_REQUEST['SendEmail'])
	{
		$mail = $_REQUEST['Email'];
		if ($mail)
		{
			$mess = "Клиент просит помощи при установке модуля 1C Bitrix.\n".
					"Его Email: ".$mail;
			$mess = $convertedText = mb_convert_encoding($mess, 'utf-8', mb_detect_encoding($mess));
			$header = "From: CarrotQuestBitrixModule\r\n";
			$header .= "Content-type: text/plain; charset=\"utf-8\"\r\n";
			if (!mail("admin@carrotquest.ru","Новый клиент Bitrix", $mess))
				array_push($errors, "CARROTQUEST_SEND_MAIL_ERROR");
		}
		else
			array_push($errors, "CARROTQUEST_SEND_EMAIL_FAIL");
	}
	else
	{
?>
		<form action="<?echo $APPLICATION->GetCurPage()?>" name="carrotquest_help">
			<?=bitrix_sessid_post()?>
			<input type="hidden" name="lang" value="<?echo LANG?>">
			<input type="hidden" name="install" value="N">
			<input type="hidden" name="id" value="<?=CARROTQUEST_MODULE_ID?>">
			<input type="hidden" name="step" value="3">
			<input type="hidden" name="clear_cache_session" value="Y">
			<input type="hidden" name="SendEmail" value="Y">
			<table id="carrotquest_install_help" class="adm-info-message">
				<tr>
					<td>
						<?= GetMessage('CARROTQUEST_INSTALL_NO_KEY') ?>:
					</td>
					<td>
						<a href="http://carrotquest.ru" target="_blank"><?= GetMessage('CARROTQUEST_INSTALL_NO_KEY_LINK_NAME') ?></a>
					</td>
				</tr>
				<tr>
					<td>
						<?= GetMessage('CARROTQUEST_INSTALL_NO_KEY_EMAIL') ?>:
					</td>
					<td>
						<input type="text" name="Email"  form="carrotquest_help" placeholder="Email" value="<?= CUser::GetParam('EMAIL'); ?>">
						<input type="hidden" name="SendEmail" value="" form="carrotquest_help">
					</td>
				</tr>
				<tr><td><input type="submit" value="<?echo GetMessage("CARROTQUEST_SEND_EMAIL") ?>" ></td></tr>
			</table>
		</form>
<? } ?>