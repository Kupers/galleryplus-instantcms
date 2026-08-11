<?php
    $this->addTplCSSName('galleryplus');
    $this->setPageTitle(
        (defined('LANG_GALLERYPLUS_EDIT') ? LANG_GALLERYPLUS_EDIT : 'Редактировать') . ': ' . $photo['title']
    );

    $exif = is_array($photo['exif'] ?? null) ? $photo['exif'] : [];
    $exif_labels = [
        'Camera'               => LANG_GALLERYPLUS_EXIF_CAMERA ?? 'Камера',
        'ISOSpeedRatings'      => LANG_GALLERYPLUS_EXIF_ISO ?? 'ISO',
        'FNumber'              => LANG_GALLERYPLUS_EXIF_APERTURE ?? 'Диафрагма',
        'ExposureTime'         => LANG_GALLERYPLUS_EXIF_EXPOSURE ?? 'Выдержка',
        'FocalLengthIn35mmFilm'=> LANG_GALLERYPLUS_EXIF_FOCAL ?? 'Фокусное расстояние',
        'DateTimeOriginal'     => LANG_GALLERYPLUS_EXIF_DATE ?? 'Дата съёмки',
        'Orientation'          => LANG_GALLERYPLUS_EXIF_ORIENTATION ?? 'Ориентация',
        'Software'             => 'ПО',
        'Flash'                => 'Вспышка',
        'ExposureBiasValue'    => 'Коррекция экспозиции',
        'Make'                 => LANG_GALLERYPLUS_EXIF_CAMERA ?? 'Камера',
        'Model'                => LANG_GALLERYPLUS_EXIF_CAMERA ?? 'Камера',
    ];

    $gps_lat = null;
    $gps_lon = null;
    if (!empty($exif['GPSLatitude']) && !empty($exif['GPSLongitude'])) {
        $gps_lat = (float) $exif['GPSLatitude'];
        $gps_lon = (float) $exif['GPSLongitude'];
    } elseif (!empty($exif['gps_lat']) && !empty($exif['gps_lon'])) {
        $gps_lat = (float) $exif['gps_lat'];
        $gps_lon = (float) $exif['gps_lon'];
    }

    $this->addHead('<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">');
    $this->addHead('<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>');
?>

<div class="galleryplus-edit-page">
    <form method="post" action="<?php echo href_to('galleryplus', 'edit', [$photo['id']]); ?>" class="galleryplus-edit-form">
        <?php echo html_csrf_token(); ?>

        <?php if (!empty($photo['url_big']) || !empty($photo['url_thumb'])) { ?>
            <div class="galleryplus-edit-photo">
                <a href="<?php echo $photo['url_big'] ?? $photo['url_thumb']; ?>" class="galleryplus-edit-photo-preview" target="_blank">
                    <img src="<?php echo $photo['url_big'] ?? $photo['url_thumb']; ?>" alt="<?php html($photo['title']); ?>">
                </a>
            </div>
        <?php } ?>

        <div class="galleryplus-edit-fields">
            <div class="galleryplus-edit-field">
                <label for="galleryplus-title"><?php echo defined('LANG_TITLE') ? LANG_TITLE : 'Название'; ?></label>
                <?php echo html_input('text', 'title', $photo['title'], ['id' => 'galleryplus-title', 'class' => 'form-control']); ?>
            </div>

            <?php if (!empty($use_photo_tags)) { ?>
                <div class="galleryplus-edit-field">
                    <label for="galleryplus-tags"><?php echo defined('LANG_TAGS') ? LANG_TAGS : 'Теги'; ?></label>
                    <input type="text" id="galleryplus-tags" name="tags" class="form-control" value="<?php echo htmlspecialchars($photo_tags); ?>" placeholder="<?php echo defined('LANG_GALLERYPLUS_TAGS_HINT') ? LANG_GALLERYPLUS_TAGS_HINT : 'Ключевые слова через запятую'; ?>">
                </div>
            <?php } ?>

            <div class="galleryplus-edit-field">
                <label for="galleryplus-content"><?php echo defined('LANG_DESCRIPTION') ? LANG_DESCRIPTION : 'Описание'; ?></label>
                <?php echo html_textarea('content', $photo['content'] ?? '', ['id' => 'galleryplus-content', 'class' => 'form-control', 'rows' => 6]); ?>
            </div>
        </div>

        <?php if (!empty($exif)) { ?>
            <div class="galleryplus-edit-exif-section">
                <div class="galleryplus-edit-exif-header">
                    <h3><?php echo LANG_GALLERYPLUS_TAB_EXIF ?? 'EXIF'; ?></h3>
                    <label class="galleryplus-edit-exif-toggle">
                        <input type="checkbox" name="exif_delete" value="1" id="galleryplus-exif-delete">
                        <?php echo LANG_GALLERYPLUS_DELETE_EXIF ?? 'Удалить EXIF'; ?>
                    </label>
                </div>
                <div class="galleryplus-edit-exif-fields" id="galleryplus-exif-fields">
                    <?php foreach ($exif as $key => $val) {
                        if ($key === 'GPSLatitude' || $key === 'GPSLongitude' || $key === 'gps_lat' || $key === 'gps_lon') { continue; }
                        $label = $exif_labels[$key] ?? $key;
                        $safe_id = 'galleryplus-exif-' . htmlspecialchars($key);
                    ?>
                        <div class="galleryplus-edit-field galleryplus-edit-field--exif">
                            <label for="<?php echo $safe_id; ?>"><?php echo htmlspecialchars($label); ?></label>
                            <input type="text" id="<?php echo $safe_id; ?>" name="exif[<?php echo htmlspecialchars($key); ?>]" class="form-control" value="<?php echo htmlspecialchars($val); ?>">
                        </div>
                    <?php } ?>
                </div>
            </div>
        <?php } ?>

        <div class="galleryplus-edit-map-section">
            <h4><?php echo LANG_GALLERYPLUS_EXIF_LOCATION ?? 'Расположение'; ?></h4>
            <div class="galleryplus-edit-map-coords">
                <div class="galleryplus-edit-field galleryplus-edit-field--exif">
                    <label for="galleryplus-edit-lat"><?php echo LANG_GALLERYPLUS_EXIF_GPS_LAT ?? 'Широта'; ?></label>
                    <input type="text" id="galleryplus-edit-lat" name="exif[GPSLatitude]" class="form-control" value="<?php echo $gps_lat !== null ? $gps_lat : ''; ?>">
                </div>
                <div class="galleryplus-edit-field galleryplus-edit-field--exif">
                    <label for="galleryplus-edit-lon"><?php echo LANG_GALLERYPLUS_EXIF_GPS_LON ?? 'Долгота'; ?></label>
                    <input type="text" id="galleryplus-edit-lon" name="exif[GPSLongitude]" class="form-control" value="<?php echo $gps_lon !== null ? $gps_lon : ''; ?>">
                </div>
            </div>
            <div class="galleryplus-edit-map-wrap">
                <div id="galleryplus-edit-map" class="galleryplus-map"></div>
            </div>
        </div>

        <div class="galleryplus-edit-buttons">
            <?php echo html_submit(
                defined('LANG_SAVE') ? LANG_SAVE : 'Сохранить',
                'submit',
                ['class' => 'button-submit']
            ); ?>
            <a href="<?php echo $photo_url; ?>" class="button-cancel"><?php echo defined('LANG_CANCEL') ? LANG_CANCEL : 'Отмена'; ?></a>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var cb = document.getElementById('galleryplus-exif-delete');
    var fields = document.getElementById('galleryplus-exif-fields');
    if (cb && fields) {
        cb.addEventListener('change', function() {
            fields.style.display = this.checked ? 'none' : '';
        });
    }

    if (typeof L !== 'undefined') {
        var latInput = document.getElementById('galleryplus-edit-lat');
        var lonInput = document.getElementById('galleryplus-edit-lon');
        var lat = latInput && latInput.value ? parseFloat(latInput.value) : <?php echo (float)($map_center_lat ?? '59.938933'); ?>;
        var lon = lonInput && lonInput.value ? parseFloat(lonInput.value) : <?php echo (float)($map_center_lng ?? '30.315721'); ?>;
        var map = L.map('galleryplus-edit-map').setView([lat, lon], 15);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap'
        }).addTo(map);
        var marker = L.marker([lat, lon], {draggable: true}).addTo(map);
        marker.on('dragend', function() {
            var pos = marker.getLatLng();
            latInput.value = pos.lat.toFixed(6);
            lonInput.value = pos.lng.toFixed(6);
        });
        map.on('click', function(e) {
            marker.setLatLng(e.latlng);
            latInput.value = e.latlng.lat.toFixed(6);
            lonInput.value = e.latlng.lng.toFixed(6);
        });
        var updateFromInputs = function() {
            var newLat = parseFloat(latInput.value);
            var newLon = parseFloat(lonInput.value);
            if (!isNaN(newLat) && !isNaN(newLon)) {
                marker.setLatLng([newLat, newLon]);
                map.setView([newLat, newLon]);
            }
        };
        latInput.addEventListener('change', updateFromInputs);
        lonInput.addEventListener('change', updateFromInputs);
    }
});
</script>

<?php if (!empty($use_photo_tags)) { ?>
<?php $this->addTplJSName('jquery-ui'); $this->addTplCSSName('jquery-ui'); $this->addTplJSName('fields/string_input'); ?>
<script>
initAutocomplete('galleryplus-tags', true, '/tags/autocomplete', false, ', ');
</script>
<?php } ?>
