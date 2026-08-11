<?php

if (php_sapi_name() !== 'cli') {

    $is_post = $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete']);

    if ($is_post) {
        require_once __DIR__ . '/index.php';
        $result = @uninstall_package();
    }

?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="utf-8">
<title>Удаление Gallery+</title>
<style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #f0f2f5; color: #333; padding: 40px 20px; }
    .wrap { max-width: 600px; margin: 0 auto; }
    h1 { font-size: 24px; margin-bottom: 8px; color: #c0392b; }
    .subtitle { color: #666; margin-bottom: 24px; font-size: 14px; }
    .card { background: #fff; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,.12); padding: 24px; margin-bottom: 20px; }
    .card h2 { font-size: 16px; margin-bottom: 16px; color: #555; border-bottom: 1px solid #eee; padding-bottom: 8px; }
    .card h2 span { color: #c0392b; }
    ul { list-style: none; padding: 0; }
    li { padding: 8px 0; border-bottom: 1px solid #f5f5f5; font-size: 14px; display: flex; align-items: center; gap: 8px; }
    li:last-child { border-bottom: none; }
    .icon { width: 20px; height: 20px; border-radius: 4px; display: inline-flex; align-items: center; justify-content: center; font-size: 11px; flex-shrink: 0; }
    .icon-tbl { background: #e8f5e9; color: #2e7d32; }
    .icon-widget { background: #e3f2fd; color: #1565c0; }
    .icon-file { background: #fff3e0; color: #e65100; }
    .icon-preset { background: #f3e5f5; color: #7b1fa2; }
    .icon-other { background: #eceff1; color: #546e7a; }
    .count { background: #f5f5f5; color: #999; font-size: 12px; padding: 2px 8px; border-radius: 10px; margin-left: auto; }
    .warn { background: #fff8e1; border: 1px solid #ffe082; border-radius: 8px; padding: 16px; margin-bottom: 20px; font-size: 13px; color: #795548; line-height: 1.5; }
    .warn strong { color: #c0392b; }
    .btn { display: inline-block; padding: 12px 32px; border: none; border-radius: 6px; font-size: 15px; font-weight: 600; cursor: pointer; text-decoration: none; transition: all .15s; }
    .btn-danger { background: #c0392b; color: #fff; }
    .btn-danger:hover { background: #a93226; }
    .btn-secondary { background: #ecf0f1; color: #555; margin-left: 8px; }
    .btn-secondary:hover { background: #ddd; }
    .result { padding: 20px; border-radius: 8px; font-size: 14px; line-height: 1.8; }
    .result-ok { background: #e8f5e9; color: #2e7d32; border: 1px solid #a5d6a7; }
    .result-err { background: #fce4ec; color: #c62828; border: 1px solid #ef9a9a; }
    .actions { margin-top: 8px; }
</style>
</head>
<body>
<div class="wrap">
    <h1>Удаление Gallery+</h1>
    <p class="subtitle">Плагин галереи для InstantCMS 2</p>

<?php if (isset($result)): ?>

    <div class="card">
        <?php if ($result === true): ?>
            <div class="result result-ok">
                <strong>Gallery+ успешно удалён.</strong><br>
                Все таблицы, виджеты, пресеты и файлы удалены.<br>
                Теперь удалите файл <code>uninstall.php</code> из корня сайта.
            </div>
        <?php else: ?>
            <div class="result result-err">
                <strong>Ошибка при удалении:</strong><br>
                <?= htmlspecialchars($result instanceof \Throwable ? $result->getMessage() : 'Неизвестная ошибка') ?>
            </div>
        <?php endif; ?>
    </div>

<?php else: ?>

    <div class="warn">
        <strong>Внимание!</strong> Это действие необратимо. Будет удалён весь контент Gallery+:
        все фотографии, альбомы и настройки.
    </div>

    <form method="post">
        <div class="card">
            <h2>База данных <span>(4 таблицы)</span></h2>
            <ul>
                <li><span class="icon icon-tbl">T</span> cms_galleryplus_albums<span class="count">альбомы</span></li>
                <li><span class="icon icon-tbl">T</span> cms_galleryplus_photos<span class="count">фотографии</span></li>
                <li><span class="icon icon-tbl">T</span> cms_galleryplus_likes<span class="count">лайки</span></li>
                <li><span class="icon icon-tbl">T</span> cms_galleryplus_categories<span class="count">категории</span></li>
            </ul>
        </div>

        <div class="card">
            <h2>Виджеты и страницы <span>(5 + 3 + 3)</span></h2>
            <ul>
                <li><span class="icon icon-widget">W</span> Виджеты: Альбомы, Последние фото, Случайные фото, Категории, Карта фото</li>
                <li><span class="icon icon-widget">P</span> Страницы виджетов: all, galleryplus.albums, galleryplus.photos</li>
                <li><span class="icon icon-widget">B</span> Привязки виджетов к страницам и позициям</li>
            </ul>
        </div>

        <div class="card">
            <h2>Пресеты изображений <span>(3)</span></h2>
            <ul>
                <li><span class="icon icon-preset">I</span> galleryplus_thumb — 400px, quality 85</li>
                <li><span class="icon icon-preset">I</span> galleryplus_big — 700px height, WebP, quality 85</li>
                <li><span class="icon icon-preset">I</span> galleryplus_nocrop — WebP, quality 92</li>
            </ul>
        </div>

        <div class="card">
            <h2>Файлы и прочее</h2>
            <ul>
                <li><span class="icon icon-file">F</span> upload/galleryplus/ — все загруженные фотографии</li>
                <li><span class="icon icon-other">+</span> Вкладка профиля «Альбомы»</li>
            </ul>
        </div>

        <div class="actions">
            <button type="submit" name="confirm_delete" value="1" class="btn btn-danger"
                onclick="return confirm('Вы уверены? Все данные Gallery+ будут удалены безвозвратно!')">
                Удалить Gallery+
            </button>
            <a href="javascript:history.back()" class="btn btn-secondary">Отмена</a>
        </div>
    </form>

<?php endif; ?>

</div>
</body>
</html>
<?php

} else {
    // CLI mode
    require_once __DIR__ . '/index.php';
    $result = @uninstall_package();
    echo $result === true ? "Gallery+ удалён.\n" : "Ошибка: " . ($result instanceof \Throwable ? $result->getMessage() : 'неизвестная') . "\n";
}

function uninstall_package() {

    $db = cmsCore::getModel('galleryplus')->db;
    $prefix = cmsConfig::get('db_prefix');

    // Remove widget bindings
    $widget_ids = $db->getCol("SELECT id FROM `{$prefix}widgets` WHERE controller = 'galleryplus'");
    if ($widget_ids) {
        $ids_csv = implode(',', array_map('intval', $widget_ids));
        $db->query("DELETE FROM `{$prefix}widgets_bind_pages` WHERE bind_id IN (SELECT id FROM `{$prefix}widgets_bind` WHERE widget_id IN ({$ids_csv}))");
        $db->query("DELETE FROM `{$prefix}widgets_bind` WHERE widget_id IN ({$ids_csv})");
    }

    // Remove widgets
    $db->query("DELETE FROM `{$prefix}widgets` WHERE controller = 'galleryplus'");

    // Remove widget pages
    $db->query("DELETE FROM `{$prefix}widgets_pages` WHERE controller = 'galleryplus'");

    // Remove user profile tab
    $db->query("DELETE FROM `{$prefix}users_tabs` WHERE controller = 'galleryplus' AND name = 'albums'");

    // Drop database tables
    $tables = [
        $prefix . 'galleryplus_albums',
        $prefix . 'galleryplus_photos',
        $prefix . 'galleryplus_likes',
        $prefix . 'galleryplus_categories',
    ];
    foreach ($tables as $table) {
        $db->query("DROP TABLE IF EXISTS `{$table}`");
    }

    // Remove image presets
    $preset_names = ['galleryplus_thumb', 'galleryplus_big', 'galleryplus_nocrop'];
    $images_model = cmsCore::getModel('images');
    foreach ($preset_names as $name) {
        $preset = $images_model->getPresetByName($name);
        if ($preset) {
            $images_model->deletePreset($preset['id']);
        }
    }

    // Remove upload directory
    $upload_dir = cmsConfig::get('upload_path') . 'galleryplus';
    if (is_dir($upload_dir)) {
        files_remove_directory($upload_dir);
    }

    return true;
}
