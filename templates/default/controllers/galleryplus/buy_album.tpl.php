<?php
    $this->addBreadcrumb(defined('LANG_GALLERYPLUS_BILLING_BUY_CONFIRM') ? LANG_GALLERYPLUS_BILLING_BUY_CONFIRM : 'Подтверждение покупки');
    $this->setPageTitle(defined('LANG_GALLERYPLUS_BILLING_BUY_CONFIRM') ? LANG_GALLERYPLUS_BILLING_BUY_CONFIRM : 'Подтверждение покупки');
?>

<h1><?php echo defined('LANG_GALLERYPLUS_BILLING_BUY_CONFIRM') ? LANG_GALLERYPLUS_BILLING_BUY_CONFIRM : 'Подтверждение покупки'; ?></h1>

<div class="billing-order">

	<div class="billing-order-form">
		<form action="" method="post">
            <?php echo html_csrf_token(); ?>
			<table>
				<tbody>
					<tr>
						<td class="title">
							<strong><?php echo defined('LANG_GALLERYPLUS_BILLING_BUY_ALBUM_ITEM') ? LANG_GALLERYPLUS_BILLING_BUY_ALBUM_ITEM : 'Альбом:'; ?></strong>
						</td>
						<td>
							<?php html($album['title']); ?>
						</td>
					</tr>
					<tr>
						<td class="title">
							<strong><?php echo defined('LANG_GALLERYPLUS_BILLING_BUY_CONFIRM_PRICE') ? LANG_GALLERYPLUS_BILLING_BUY_CONFIRM_PRICE : 'Цена:'; ?></strong>
						</td>
						<td>
							<?php echo $price_spell; ?>
						</td>
					</tr>
					<tr>
						<td class="title">
							<strong><?php echo defined('LANG_GALLERYPLUS_BILLING_BUY_CONFIRM_BALANCE') ? LANG_GALLERYPLUS_BILLING_BUY_CONFIRM_BALANCE : 'Баланс:'; ?></strong>
						</td>
						<td>
							<?php echo $balance_spell; ?>
						</td>
					</tr>
				</tbody>
			</table>
			<input type="submit" name="submit" class="button-submit" value="<?php echo defined('LANG_GALLERYPLUS_BILLING_BUY') ? LANG_GALLERYPLUS_BILLING_BUY : 'Купить'; ?>">
			<a class="back-btn" href="<?php echo $album_url; ?>"><?php echo LANG_CANCEL; ?></a>
		</form>
	</div>

</div>