<?php
/**
 * Template Name: Gallery+ Map
 * Template Type: widget
 */
$this->addHead('<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">');
$this->addHead('<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>');
$this->addHead('<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css">');
$this->addHead('<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css">');
$this->addHead('<script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>');
$this->addTplCSSName('galleryplus');
?>
<div class="galleryplus-map-widget" style="width:100%;height:<?php echo (int)$map_height; ?>px;flex:0 0 100%;" id="galleryplus-map-<?php echo (int)$widget->id; ?>"></div>
<script>
(function() {
    var mapId = 'galleryplus-map-<?php echo (int)$widget->id; ?>';
    var mapEl = document.getElementById(mapId);
    if (!mapEl || typeof L === 'undefined') return;

    var map = L.map(mapId).setView([<?php echo (float)$map_center_lat; ?>, <?php echo (float)$map_center_lng; ?>], <?php echo (int)$default_zoom; ?>);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap'
    }).addTo(map);

    var markers = L.markerClusterGroup({
        maxClusterRadius: 60,
        iconCreateFunction: function(cluster) {
            var count = cluster.getChildCount();
            return L.divIcon({
                html: '<div style="background:#4a6fa5;color:#fff;width:40px;height:40px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:14px;font-weight:700;border:2px solid #fff;box-shadow:0 1px 5px rgba(0,0,0,.3)">' + count + '</div>',
                className: '',
                iconSize: [40, 40]
            });
        }
    });

    var photos = <?php echo json_encode($photos, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?: '[]'; ?>;

    photos.forEach(function(p) {
        var popupContent = '<a href="' + p.url + '"><img src="' + p.thumb + '" alt="' + p.title.replace(/"/g, '&quot;') + '" class="galleryplus-map-popup-thumb"></a>';
        var marker = L.marker([p.lat, p.lon], {
            icon: L.divIcon({
                html: '<div style="width:40px;height:40px;border-radius:50%;overflow:hidden;border:2px solid #fff;box-shadow:0 1px 5px rgba(0,0,0,.3)"><img src="' + p.thumb + '" style="width:100%;height:100%;object-fit:cover;display:block"></div>',
                className: '',
                iconSize: [40, 40],
                iconAnchor: [20, 20]
            })
        }).bindPopup(popupContent, {className: 'leaflet-popup leaflet-zoom-animated galleryplus-map-popup'});
        markers.addLayer(marker);
    });

    map.addLayer(markers);
})();
</script>
