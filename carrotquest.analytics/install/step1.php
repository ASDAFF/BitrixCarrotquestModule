<?if(!check_bitrix_sessid()) return;?>
<style type="text/css">
	input
	{
		width: 500px;
		
	}
	input[name="inst"]
	{
		width: 200px;
	}
	#keys
	{
		margin-left: 20px;
	}
	#help
	{
	
		margin-top: 40px !important;	
		display: block !important;
		width: 500px;
	}
	#help input[type="text"]
	{
		width: 200px;
	}
</style>
<script>
	function SendMail()
	{
		$("input[name='SendEmail']").val(true);
	}
</script>
<form action="<?echo $APPLICATION->GetCurPage()?>" name="form1">
	<?=bitrix_sessid_post()?>
	<input type="hidden" name="lang" value="<?echo LANG?>">
	<input type="hidden" name="install" value="Y">
	<input type="hidden" name="id" value="<?=CARROTQUEST_MODULE_ID?>">
	<input type="hidden" name="step" value="2">
	<input type="hidden" name="clear_cache_session" value="Y">
	<table id="keys">
		<tr>
			<td>
				<?= GetMessage('CARROTQUEST_INSTALL_ENTER_API_KEY') ?>: 
			</td>
			<td>
				<input type="text" name="ApiKey" placeholder="API-KEY">
			</td>
		<tr>
			<td>
				<?= GetMessage('CARROTQUEST_INSTALL_ENTER_API_SECRET') ?>:
			</td>
			<td>
				<input type="text" name="ApiSecret" placeholder="API-SECRET">
			</td>
		</tr>
		<tr><td><input type="submit" name="inst" style="display: block;" value="<?echo GetMessage("MOD_INSTALL")?>"></td></tr>
	</table>
	<table id="help" class="adm-info-message">
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
				<input type="text" name="Email" placeholder="Email" value="<?= CUser::GetParam('EMAIL'); ?>">
				<input type="hidden" name="SendEmail" value="">
			</td>
		</tr>
		<tr><td><input type="submit" name="inst" value="<?echo GetMessage("CARROTQUEST_SEND_EMAIL") ?>" onclick="SendMail();"></td></tr>
	</table>
	
<form>
