<?
	/**
	* Этот файл содержит описание настроек модулей в админке сайта (вкладка "Настройки" -> "Настройки модуля")
	*/
	 
	// Подключаем модуль (выполняем код в файле include.php)
	CModule::IncludeModule(CARROTQUEST_MODULE_ID);
	 
	// Языковые константы (файлы в папке lang заполняют этот массив в зависимости от языка. Пока ru или en)
	global $MESS;
	IncludeModuleLangFile( __FILE__ );
	
	// Если форма была сохранена, устанавливаем значение опции модуля
	if( $REQUEST_METHOD == 'POST' && $_POST['Update'] == 'Y' )
	{
		COption::SetOptionString(CARROTQUEST_MODULE_ID,"cqApiKey",$_POST['ApiKey']);
		COption::SetOptionString(CARROTQUEST_MODULE_ID,"cqApiSecret",$_POST['ApiSecret']);
		COption::SetOptionString(CARROTQUEST_MODULE_ID,"cqTrackCartAdd", $_POST['TrackCartAdd'] ? "checked" : "");
		COption::SetOptionString(CARROTQUEST_MODULE_ID,"cqTrackCartVisit",$_POST['TrackCartVisit'] ? "checked" : "");
		COption::SetOptionString(CARROTQUEST_MODULE_ID,"cqTrackPreOrder",$_POST['TrackPreOrder'] ? "checked" : "");
		COption::SetOptionString(CARROTQUEST_MODULE_ID,"cqTrackProductDetails",$_POST['TrackProductDetails'] ? "checked" : "");
		COption::SetOptionString(CARROTQUEST_MODULE_ID,"cqTrackOrderConfirm",$_POST['TrackOrderConfirm'] ? "checked" : "");
		COption::SetOptionString(CARROTQUEST_MODULE_ID,"cqActivateBonus",$_POST['ActivateBonus'] ? "checked" : "");
		
		global $carrotquest_UPDATER;
		$carrotquest_UPDATER->GetListFromRequest();
		$carrotquest_UPDATER->UpdateAllTemplates();
	}
	 
	 // Описываем табы административной панели битрикса
	$aTabs = array(
		array(
			'DIV'   => 'edit1',
			'TAB'   => GetMessage('MAIN_TAB_SET'),
			'ICON'  => 'fileman_settings',
			'TITLE' => GetMessage('MAIN_TAB_TITLE_SET' )
		),
		array(
			'DIV'   => 'edit2',
			'TAB'   => GetMessage('CARROTQUEST_OPTIONS_TEMPLATE_TAB_TITLE'),
			'ICON'  => 'fileman_settings',
			'TITLE' => GetMessage('CARROTQUEST_OPTIONS_TEMPLATE_TAB_TITLE' )
		),
	);
	 
	// Инициализируем табы
	$oTabControl = new CAdmintabControl( 'tabControl', $aTabs );
	$oTabControl->Begin();
?>

<!-- Ниже пошла форма страницы с настройками модуля -->
<form method="POST" enctype="multipart/form-data" action="<?= $APPLICATION->GetCurPage() ?>?mid=<?=htmlspecialchars(CARROTQUEST_MODULE_ID)?>&lang=<?= LANG?>">
    <?=bitrix_sessid_post()?> <!-- Жизненно необходимая вставка, без нее ничего не работает. -->
    <?$oTabControl->BeginNextTab();?>
	<!-- Настройки ключей -->
	<tr class="heading">
		<td colspan="2"><? echo GetMessage('CARROTQUEST_OPTIONS_KEYS'); ?></td>
	</tr>
    <tr>
        <td width="50%" valign="center"><label for="ApiKey"><?= GetMessage('CARROTQUEST_OPTIONS_API_KEY_DESCRIPTION'); ?>:</td>
        <td  valign="top">
			<input type="text" style="width: 500px;" name="ApiKey" id="ApiKey" placeholder="API-KEY" value="<?= COption::GetOptionString(CARROTQUEST_MODULE_ID,"cqApiKey")?>" />
        </td>
    </tr>
	<tr>
        <td width="50%" valign="center"><label for="SecretKey"><?= GetMessage('CARROTQUEST_OPTIONS_SECRET_KEY_DESCRIPTION'); ?>:</td>
        <td  valign="top">
			<input type="text" style="width: 500px;" name="ApiSecret" id="SecretKey" placeholder="API-SECRET" value="<?= COption::GetOptionString(CARROTQUEST_MODULE_ID,"cqApiSecret")?>" />
        </td>
    </tr>
	<!-- Далее отслеживание событий -->
	<tr class="heading">
		<td colspan="2"><? echo GetMessage('CARROTQUEST_OPTIONS_EVENTS'); ?></td>
	</tr>
	<tr>
        <td width="50%" valign="center"><label for="TrackCartAddBox"><?= GetMessage('CARROTQUEST_OPTIONS_TRACK_CART_ADD'); ?>:</td>
        <td  valign="top">
			<input type="checkbox" name="TrackCartAdd" id="TrackCartAddBox" <?= COption::GetOptionString(CARROTQUEST_MODULE_ID,"cqTrackCartAdd") ?> />
        </td>
    </tr>
	<tr>
        <td width="50%" valign="center"><label for="TrackCartVisitBox"><?= GetMessage('CARROTQUEST_OPTIONS_TRACK_CART_VISIT'); ?>:</td>
        <td  valign="top">
			<input type="checkbox" name="TrackCartVisit" id="TrackCartVisitBox" <?= COption::GetOptionString(CARROTQUEST_MODULE_ID,"cqTrackCartVisit") ?> />
        </td>
    </tr>
	<tr>
        <td width="50%" valign="center"><label for="TrackPreOrderBox"><?= GetMessage('CARROTQUEST_OPTIONS_TRACK_CART_PRE_ORDER'); ?>:</td>
        <td  valign="top">
			<input type="checkbox" name="TrackPreOrder" id="TrackPreOrderBox" <?= COption::GetOptionString(CARROTQUEST_MODULE_ID,"cqTrackPreOrder")?> />
        </td>
    </tr>
	<tr>
        <td width="50%" valign="center"><label for="TrackProductDetailesBox"><?= GetMessage('CARROTQUEST_OPTIONS_TRACK_PRODUCT_DETAILES'); ?>:</td>
        <td  valign="top">
			<input type="checkbox" name="TrackProductDetails" id="TrackProductDetailesBox" <?= COption::GetOptionString(CARROTQUEST_MODULE_ID,"cqTrackProductDetails") ?> />
        </td>
    </tr>
	<tr>
        <td width="50%" valign="center"><label for="TrackOrderConfirmBox"><?= GetMessage('CARROTQUEST_OPTIONS_TRACK_ORDER_CONFIRM'); ?>:</td>
        <td  valign="top">
			<input type="checkbox" name="TrackOrderConfirm" id="TrackOrderConfirmBox" <?= COption::GetOptionString(CARROTQUEST_MODULE_ID,"cqTrackOrderConfirm") ?> />
        </td>
    </tr>
	<!-- Далее настройки бонусов -->
	<tr class="heading">
		<td colspan="2"><? echo GetMessage('CARROTQUEST_OPTIONS_BONUS'); ?></td>
	</tr>
	<tr>
        <td width="50%" valign="center"><label for="ActivateBonusBox"><?= GetMessage('CARROTQUEST_OPTIONS_ACTIVATE_BONUS'); ?>:</td>
        <td  valign="top">
			<input type="checkbox" name="ActivateBonus" id="ActivateBonusBox" <?= COption::GetOptionString(CARROTQUEST_MODULE_ID,"cqActivateBonus") ?> />
        </td>
    </tr>
	<!-- Ссылка на админку Carrot quest -->
	<tr class="heading">
		<td colspan="2"><? echo GetMessage('CARROTQUEST_OPTIONS_CARROTQUEST_ADMIN'); ?></td>
	</tr>
	<tr>
		<td width="50%" valign="center"><label for="AdminLink"><?= GetMessage('CARROTQUEST_OPTIONS_ADMIN_LINK'); ?>:</td>
        <td  valign="top">
			<a id="AdminLink" href="http://carrotquest.io/panel/#/login" target="_blank"><?= GetMessage('CARROTQUEST_OPTIONS_ADMIN_LINK_NAME') ?></a>
        </td>
	</tr>
	<?$oTabControl->BeginNextTab();?>
	<? include(CARROTQUEST_INCLUDE_PATH."templateOptions.php"); ?>
    <?$oTabControl->Buttons();?>
    <input type="submit" name="Update" value="<?= GetMessage('CARROTQUEST_OPTIONS_BUTTON_SAVE') ?>" />
    <input type="reset" name="reset" value="<?= GetMessage('CARROTQUEST_OPTIONS_BUTTON_RESET') ?>" />
    <input type="hidden" name="Update" value="Y" />
    <?$oTabControl->End();?>
</form>