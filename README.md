<p align="center"><img width="255" height="256" alt="icon" src="https://github.com/user-attachments/assets/29689156-3552-4be9-b3b0-cc1dda231f82" /></p>


Gallery+ — full-featured gallery with masonry grid, infinite scroll, lightbox, 6 album privacy levels (password, 18+ included), categories, tags, likes, EXIF/GPS, Leaflet maps, embed codes, and 5 widgets
AJAX likes, collaborative albums, bcrypt password protection, adult content with karma gate, XMP description extraction, no-crop WebP preset instead of storing originals, 3 view modes (albums, infinite scroll, paged), custom lightbox (swipe/likes/comments/shares), 5 widgets (Random, Photos, Albums, Categories, Map), Leaflet geo-maps, admin cleanup tool

<img width="1124" height="776" alt="2026-08-10_16-01-13" src="https://github.com/user-attachments/assets/13670c7d-1c25-46c6-a97a-4575de73109c" />
<img width="1242" height="940" alt="2026-08-10_16-04-30" src="https://github.com/user-attachments/assets/4d309308-a50d-4cbb-8baf-df60947049b0" />
<img width="1132" height="938" alt="2026-08-10_16-03-57" src="https://github.com/user-attachments/assets/3bc54afb-f66e-4dc7-ba12-04f940b85a29" />

## Location detection (Map widget)

The visitor's location is resolved by IP on the server (PHP); the browser does not request anything.

How it works (`widgets/map/widget.php`):
- `cmsUser::getIp()` — visitor IP from the InstantCMS core (`detect_ip_key` config option, usually `REMOTE_ADDR`).
- Cache check: `cache/galleryplus_map/<md5(ip)>.json` for 24 hours — to avoid calling an external API on every render.
- If there is no cache → external request via `httpGetJson()`:
  - ip-api.com — `http://ip-api.com/json/{ip}?fields=status,lat,lon,query` (free, no API key);
  - on failure — ipwho.is — `https://ipwho.is/{ip}` (free, no API key, HTTPS).
- If coordinates are obtained, the map center is set to that point; on failure (local/private IP, service unavailable) — the configured center is used.

Required PHP modules: none specific.
- Either `allow_url_fopen=On` (then `file_get_contents` with a stream context works) or the curl extension — the code tries both.
- `json` is built-in (always available in PHP 8.x). geoip/GeoIP2 are not required and not used.

Accuracy — this is IP geolocation from provider databases, not GPS:
- Usually resolves to a city/locality; coordinates are the city center, error from ~1–2 km (city) to tens of km (small towns, regions).
- Worse on mobile networks (3G/4G) — may show the operator's city/region.
- Private/local IPs (127.x, 10.x, 192.168.x, 172.16–31.x) and corporate VPN/proxies are not resolved → fallback to the configured center.
- Good enough for a "where is the visitor" widget; meter-accurate positioning would require browser geolocation, but it asks for permission, which contradicts the task.

Rate limits: the ip-api.com free tier allows ~45 requests/minute from one server IP — hence the 24-hour per-visitor-IP cache.

## Определение местоположения (виджет карты)

Определяем местоположение по IP посетителя на сервере (PHP), браузер ничего не запрашивает.

Как это работает (`widgets/map/widget.php`):
- `cmsUser::getIp()` — IP посетителя из ядра InstantCMS (`detect_ip_key` из конфига, обычно `REMOTE_ADDR`).
- Проверка кеша: `cache/galleryplus_map/<md5(ip)>.json` на сутки — чтобы не дёргать внешний API при каждом рендере.
- Нет кеша → внешний запрос к `httpGetJson()`:
  - ip-api.com — `http://ip-api.com/json/{ip}?fields=status,lat,lon,query` (бесплатно, без ключа);
  - при неудаче ipwho.is — `https://ipwho.is/{ip}` (бесплатно, без ключа, https).
- Если получили координаты — центр карты ставится в эту точку; если fail (локальный/приватный IP, сервис недоступен) — используется заданный в настройках центр.

Необходимые модули PHP: специальных нет.
- Нужен либо `allow_url_fopen=On` (тогда работает `file_get_contents` с stream context), либо расширение curl — код пробует оба варианта.
- `json` (встроенный, в PHP 8.x всегда есть). geoip/GeoIP2 не требуются и не используются.

Точность — это IP-геолокация по базам провайдера, не GPS:
- Обычно определяет до города/населённого пункта; координаты — центр города, погрешность от ~1–2 км (город) до десятков км (мелкие населённые пункты, регионы).
- Для мобильного интернета (3G/4G) хуже — может показывать город/регион оператора.
- Приватные/локальные IP (127.x, 10.x, 192.168.x, 172.16–31.x) и корпоративные VPN/прокси — не определяются → fallback на заданный центр.
- Для виджета «где сейчас посетитель» этого достаточно; для точного позиционирования (метры) нужна была бы браузерная геолокация, но она требует запроса разрешения у пользователя, что противоречит задаче.

Ограничения по лимитам: ip-api.com бесплатно даёт ~45 запросов/мин с одного IP сервера — поэтому и стоит суточный кеш по IP посетителя.


