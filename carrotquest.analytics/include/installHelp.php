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
</style>

<script>
	function SendMail()
	{
		$("input[name='SendEmail']").val(true);
	}
</script>

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
			<input type="text" name="Email" placeholder="Email" value="<?= CUser::GetParam('EMAIL'); ?>">
			<input type="hidden" name="SendEmail" value="">
		</td>
	</tr>
	<tr><td><input type="submit" name="inst" value="<?echo GetMessage("CARROTQUEST_SEND_EMAIL") ?>" onclick="SendMail();"></td></tr>
</table>