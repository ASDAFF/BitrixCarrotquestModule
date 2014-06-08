<? 
	IncludeModuleLangFile(__FILE__); 
	if(!check_bitrix_sessid()) return;
?>

<style type="text/css">
	input
	{
		width: 500px;
		
	}
	input[name="inst"]
	{
		width: 200px;
	}
	#carrotquest_install_keys
	{
		margin-left: 20px;
	}
</style>

<form action="<?echo $APPLICATION->GetCurPage()?>" name="form1">
	<?=bitrix_sessid_post()?>
	<input type="hidden" name="lang" value="<?echo LANG?>">
	<input type="hidden" name="install" value="Y">
	<input type="hidden" name="id" value="<?=CARROTQUEST_MODULE_ID?>">
	<input type="hidden" name="step" value="2">
	<input type="hidden" name="clear_cache_session" value="Y">
	<table id="carrotquest_install_keys">
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
	<? include(CARROTQUEST_INCLUDE_PATH."installHelp.php"); ?>
<form>
