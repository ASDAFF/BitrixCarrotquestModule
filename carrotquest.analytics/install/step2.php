<? 
	IncludeModuleLangFile(__FILE__); 
	if(!check_bitrix_sessid()) return;
?>


<style type="text/css">
	input[name="carrotquest_install"]
	{
		width: 200px;
		margin: 20px 0 0 20px;
	}
</style>

<form action="<?echo $APPLICATION->GetCurPage()?>" name="form1">
	<?=bitrix_sessid_post()?>
	<input type="hidden" name="lang" value="<?echo LANG?>">
	<input type="hidden" name="install" value="Y">
	<input type="hidden" name="id" value="<?=CARROTQUEST_MODULE_ID?>">
	<input type="hidden" name="step" value="3">
	<input type="hidden" name="clear_cache_session" value="Y">
	<? include(CARROTQUEST_INCLUDE_PATH."templateOptions.php"); ?>
	<input type="submit" name="carrotquest_install" style="display: block;" value="<?echo GetMessage("MOD_INSTALL")?>">
</form>
<? include(CARROTQUEST_INCLUDE_PATH."installHelp.php"); ?>
