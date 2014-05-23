<?
	/**
	* Этот файл содержит описание настроек модулей в админке сайта (вкладка "Настройки" -> "Настройки модуля")
	*/
	
	// Идентификатор модуля
	$ModuleId  = 'carrotquest';
	 
	// Подключаем модуль (выполняем код в файле include.php)
	CModule::IncludeModule( $ModuleId );
	 
	// Языковые константы (файлы в папке lang заполняют этот массив в зависимости от языка. Пока ru или en)
	global $MESS;
	IncludeModuleLangFile( __FILE__ );
	
	// Если форма была сохранена, устанавливаем значение опции модуля
	if( $REQUEST_METHOD == 'POST' && $_POST['Update'] == 'Y' )
	{
		COption::SetOptionString("carrotquest","cqApiKey",$_POST['ApiKey']);
		COption::SetOptionString("carrotquest","cqApiSecret",$_POST['ApiSecret']);
		COption::SetOptionString("carrotquest","cqTrackCartAdd", $_POST['TrackCartAdd'] ? "checked" : "");
		COption::SetOptionString("carrotquest","cqTrackCartVisit",$_POST['TrackCartVisit'] ? "checked" : "");
		COption::SetOptionString("carrotquest","cqTrackPreOrder",$_POST['TrackPreOrder'] ? "checked" : "");
		COption::SetOptionString("carrotquest","cqTrackProductDetails",$_POST['TrackProductDetails'] ? "checked" : "");
		COption::SetOptionString("carrotquest","cqTrackOrderConfirm",$_POST['TrackOrderConfirm'] ? "checked" : "");
		COption::SetOptionString("carrotquest","cqActivateBonus",$_POST['ActivateBonus'] ? "checked" : "");
	}
	 
	/**
	 * Описываем табы административной панели битрикса
	 * Пока один таб, где можно поменять настройки ключей, событий, включить бонусы
	 */
	$aTabs = array(
		array(
			'DIV'   => 'edit1',
			'TAB'   => GetMessage('MAIN_TAB_SET'),
			'ICON'  => 'fileman_settings',
			'TITLE' => GetMessage('MAIN_TAB_TITLE_SET' )
		),
	);
	 
	// Инициализируем табы
	$oTabControl = new CAdmintabControl( 'tabControl', $aTabs );
	$oTabControl->Begin();
?>

<!-- Ниже пошла форма страницы с настройками модуля -->
<form method="POST" enctype="multipart/form-data" action="<?= $APPLICATION->GetCurPage() ?>?mid=<?=htmlspecialchars( $ModuleId )?>&lang=<?= LANG?>">
    <?=bitrix_sessid_post()?> <!-- Жизненно необходимая вставка, без нее ничего не работает. -->
    <?$oTabControl->BeginNextTab();?>
	<!-- Настройки ключей -->
	<tr class="heading">
		<td colspan="2"><? echo GetMessage("OPTIONS_KEYS"); ?></td>
	</tr>
    <tr>
        <td width="50%" valign="center"><label for="ApiKey"><?= GetMessage('OPTIONS_API_KEY_DESCRIPTION'); ?>:</td>
        <td  valign="top">
			<input type="text" style="width: 500px;" name="ApiKey" id="ApiKey" placeholder="API-KEY" value="<?= COption::GetOptionString("carrotquest","cqApiKey")?>" />
        </td>
    </tr>
	<tr>
        <td width="50%" valign="center"><label for="SecretKey"><?= GetMessage('OPTIONS_SECRET_KEY_DESCRIPTION'); ?>:</td>
        <td  valign="top">
			<input type="text" style="width: 500px;" name="ApiSecret" id="SecretKey" placeholder="API-SECRET" value="<?= COption::GetOptionString("carrotquest","cqApiSecret")?>" />
        </td>
    </tr>
	<!-- Далее отслеживание событий -->
	<tr class="heading">
		<td colspan="2"><? echo GetMessage("OPTIONS_EVENTS"); ?></td>
	</tr>
	<tr>
        <td width="50%" valign="center"><label for="TrackCartAddBox"><?= GetMessage('OPTIONS_TRACK_CART_ADD'); ?>:</td>
        <td  valign="top">
			<input type="checkbox" name="TrackCartAdd" id="TrackCartAddBox" <?= COption::GetOptionString("carrotquest","cqTrackCartAdd") ?> />
        </td>
    </tr>
	<tr>
        <td width="50%" valign="center"><label for="TrackCartVisitBox"><?= GetMessage('OPTIONS_TRACK_CART_VISIT'); ?>:</td>
        <td  valign="top">
			<input type="checkbox" name="TrackCartVisit" id="TrackCartVisitBox" <?= COption::GetOptionString("carrotquest","cqTrackCartVisit") ?> />
        </td>
    </tr>
	<tr>
        <td width="50%" valign="center"><label for="TrackPreOrderBox"><?= GetMessage('OPTIONS_TRACK_CART_PRE_ORDER'); ?>:</td>
        <td  valign="top">
			<input type="checkbox" name="TrackPreOrder" id="TrackPreOrderBox" <?= COption::GetOptionString("carrotquest","cqTrackPreOrder")?> />
        </td>
    </tr>
	<tr>
        <td width="50%" valign="center"><label for="TrackProductDetailesBox"><?= GetMessage('OPTIONS_TRACK_PRODUCT_DETAILES'); ?>:</td>
        <td  valign="top">
			<input type="checkbox" name="TrackProductDetails" id="TrackProductDetailesBox" <?= COption::GetOptionString("carrotquest","cqTrackProductDetails") ?> />
        </td>
    </tr>
	<tr>
        <td width="50%" valign="center"><label for="TrackOrderConfirmBox"><?= GetMessage('OPTIONS_TRACK_ORDER_CONFIRM'); ?>:</td>
        <td  valign="top">
			<input type="checkbox" name="TrackOrderConfirm" id="TrackOrderConfirmBox" <?= COption::GetOptionString("carrotquest","cqTrackOrderConfirm") ?> />
        </td>
    </tr>
	<!-- Далее настройки бонусов -->
	<tr class="heading">
		<td colspan="2"><? echo GetMessage("OPTIONS_BONUS"); ?></td>
	</tr>
	<tr>
        <td width="50%" valign="center"><label for="ActivateBonusBox"><?= GetMessage('OPTIONS_ACTIVATE_BONUS'); ?>:</td>
        <td  valign="top">
			<input type="checkbox" name="ActivateBonus" id="ActivateBonusBox" <?= COption::GetOptionString("carrotquest","cqActivateBonus") ?> />
        </td>
    </tr>
	<tr>
		<td width="50%" valign="center"><label for="AdminLink"><?= GetMessage('OPTIONS_ADMIN_LINK'); ?>:</td>
        <td  valign="top">
			<a id="AdminLink" href="http://carrotquest.io/panel/#/login" target="_blank"><?= GetMessage('OPTIONS_ADMIN_LINK_NAME') ?></a>
        </td>
	</tr>
	<?$oTabControl->BeginNextTab();?>
	
    <?$oTabControl->Buttons();?>
    <input type="submit" name="Update" value="<?= GetMessage('OPTIONS_BUTTON_SAVE') ?>" />
    <input type="reset" name="reset" value="<?= GetMessage('OPTIONS_BUTTON_RESET') ?>" />
    <input type="hidden" name="Update" value="Y" />
    <?$oTabControl->End();?>
</form>