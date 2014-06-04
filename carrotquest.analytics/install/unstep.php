<?if(!check_bitrix_sessid()) return;?>
<?
	// Чистим кэш сайта, иначе js объект carrotqust "потеряется" после удаления модуля
	$phpCache = new CPHPCache();
	$phpCache->CleanDir();
echo CAdminMessage::ShowNote(GetMessage("MOD_UNINST_OK"));
?>
<form action="<?echo $APPLICATION->GetCurPage()?>">
	<input type="hidden" name="lang" value="<?echo LANG?>">
	<input type="submit" name="" value="<?echo GetMessage("MOD_BACK")?>">	
<form>
