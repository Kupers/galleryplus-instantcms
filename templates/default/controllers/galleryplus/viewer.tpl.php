<div id="galleryplus-viewer" class="galleryplus-viewer galleryplus-viewer--hide" data-cover="1" data-show-desc="<?php echo !empty($show_lightbox_desc) ? '1' : '0'; ?>" data-truncate-desc="<?php echo !empty($truncate_lightbox_desc) ? '1' : '0'; ?>" data-desc-limit="<?php echo (int)$lightbox_desc_limit; ?>" data-current-user="<?php echo $user->id; ?>">
    <div class="galleryplus-viewer-bg"></div>

    <div class="galleryplus-viewer-top">
        <div class="galleryplus-viewer-top-left"></div>
        <div class="galleryplus-viewer-top-right">
            <button class="galleryplus-viewer-close" title="<?php echo defined('LANG_CLOSE') ? LANG_CLOSE : 'Close'; ?>">&times;</button>
        </div>
    </div>

    <div class="galleryplus-viewer-content">
        <img src="" alt="" class="galleryplus-viewer-img">
    </div>

    <a href="" class="galleryplus-viewer-title" target="_blank">
        <span class="galleryplus-viewer-title-icon"><?php echo string_replace_svg_icons('{regular%share-square}'); ?></span>
        <span class="galleryplus-viewer-title-text"></span>
    </a>

    <div class="galleryplus-viewer-desc">
        <span class="galleryplus-viewer-desc-text"></span>
        <button class="galleryplus-viewer-desc-toggle" style="display:none" data-full-label="<?php echo LANG_GALLERYPLUS_SHOW_FULL_DESC ?? 'Show full'; ?>" data-collapse-label="<?php echo LANG_GALLERYPLUS_COLLAPSE_DESC ?? 'Collapse'; ?>"><?php echo LANG_GALLERYPLUS_SHOW_FULL_DESC ?? 'Show full'; ?></button>
    </div>

    <button class="galleryplus-viewer-nav galleryplus-viewer-prev" title="<?php echo LANG_GALLERYPLUS_PREV ?? 'Previous'; ?>"><svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg></button>
    <button class="galleryplus-viewer-nav galleryplus-viewer-next" title="<?php echo LANG_GALLERYPLUS_NEXT ?? 'Next'; ?>"><svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg></button>

    <div class="galleryplus-viewer-bottom">
        <div class="galleryplus-viewer-bottom-left">
            <img src="" class="galleryplus-viewer-avatar" alt="">
            <span class="galleryplus-viewer-author"></span>
        </div>
        <div class="galleryplus-viewer-bottom-right">
            <button class="galleryplus-viewer-like" data-target-id="" data-target-type="photo" title="<?php echo LANG_GALLERYPLUS_LIKE ?? 'Like'; ?>"><span class="galleryplus-viewer-like-icon"><?php echo string_replace_svg_icons('{solid%heart}'); ?></span> <span class="galleryplus-viewer-like-count">0</span></button>
            <button class="galleryplus-viewer-fav" title="<?php echo defined('LANG_GALLERYPLUS_FAVORITE') ? LANG_GALLERYPLUS_FAVORITE : 'В избранное'; ?>"><span class="galleryplus-viewer-fav-icon"><?php echo string_replace_svg_icons('{regular%star}'); ?></span></button>
            <button class="galleryplus-viewer-comments" title="<?php echo LANG_GALLERYPLUS_COMMENTS ?? 'Comments'; ?>"><span class="galleryplus-viewer-comments-icon"><?php echo string_replace_svg_icons('{regular%comment-dots}'); ?></span> <span class="galleryplus-viewer-comments-count">0</span></button>
            <button class="galleryplus-viewer-share" title="<?php echo LANG_GALLERYPLUS_SHARE ?? 'Поделиться'; ?>"><?php echo string_replace_svg_icons('{solid%share-alt}'); ?></button>
        </div>
    </div>

    <div class="galleryplus-viewer-share-popup">
        <div class="galleryplus-viewer-share-popup-arrow"></div>
        <a class="galleryplus-viewer-share-option" href="#" target="_blank" data-share="pinterest"><svg viewBox="0 0 24 24" width="20" height="20" fill="#e60023"><path d="M12 0C5.373 0 0 5.373 0 12c0 5.084 3.163 9.426 7.627 11.174-.105-.949-.2-2.405.042-3.441.218-.936 1.407-5.965 1.407-5.965s-.359-.719-.359-1.782c0-1.668.967-2.914 2.171-2.914 1.023 0 1.518.769 1.518 1.69 0 1.029-.655 2.568-.994 3.995-.283 1.194.599 2.169 1.777 2.169 2.133 0 3.772-2.249 3.772-5.495 0-2.873-2.064-4.882-5.012-4.882-3.414 0-5.418 2.561-5.418 5.207 0 1.031.397 2.138.893 2.738.098.119.112.224.083.345l-.333 1.36c-.053.22-.174.267-.402.161-1.499-.698-2.436-2.889-2.436-4.649 0-3.785 2.75-7.262 7.929-7.262 4.163 0 7.398 2.967 7.398 6.931 0 4.136-2.607 7.464-6.227 7.464-1.216 0-2.359-.632-2.75-1.378l-.748 2.853c-.271 1.043-1.002 2.35-1.492 3.146C9.57 23.812 10.763 24 12 24c6.627 0 12-5.373 12-12S18.627 0 12 0z"/></svg> Pinterest</a>
        <a class="galleryplus-viewer-share-option" href="#" target="_blank" data-share="vk"><svg viewBox="0 0 24 24" width="20" height="20" fill="#4a76a8"><path d="M15.684 0H8.316C2.755 0 0 2.755 0 8.316v7.368C0 21.245 2.755 24 8.316 24h7.368C21.245 24 24 21.245 24 15.684V8.316C24 2.755 21.245 0 15.684 0zm3.279 16.482h-1.967c-.606 0-.788-.454-.788-.454-1.333-1.667-3.636-3.242-3.636-3.242.06-.03.364-.5.364-.5s1.818-2.879 2.424-4.242c.303-.697.03-.97-.788-.97h-1.97c-.424 0-.666.212-.818.515-.152.364-1.151 2.545-1.151 2.545s-.091.152-.242.152c-.03 0-.152-.061-.152-.061s-1.03-1.243-1.697-2.03c-.212-.272-.545-.485-1.03-.485H7.393c-.424 0-.666.273-.666.515 0 .091.03.181.121.303 1.151 1.818 3.03 3.636 3.03 3.636s.03 0 .03.03c.03 0 .03.03 0 .061-.03 0-.03 0-.03.03-1.03.606-2.06 1.212-2.06 1.212-.212.152-.394.364-.394.788 0 .424.303.788.788.788h1.97c.424 0 .666-.212.666-.212s1.394-.97 1.394-1.09c.03-.03.121-.03.182 0 .03 0 .121.091.121.151 0 .03-.03.061-.03.061s-1.03 1.03-1.515 1.515c-.212.212-.121.364.091.364h.03c.818-.03 1.878-.03 1.878-.03s.576-.03.848.303c.182.212.182.576.182.576s.03.364.121.545c.091.212.333.242.515.242h1.212c.364 0 .606-.182.788-.424.182-.242.182-.606.182-.606s-.03-.97.515-1.151c.545-.182 1.333.97 2.121 1.394.606.333.97.242.97.242l1.636-.03c.333 0 .515-.182.454-.545-.061-.364-.666-1.03-1.151-1.515-.212-.212-.424-.454-.424-.545 0-.03.03-.121.03-.121s1.03-1.454 1.272-2.06c.03-.091.091-.212.091-.394.03-.212-.121-.394-.394-.394z"/></svg> VK</a>
        <a class="galleryplus-viewer-share-option" href="#" target="_blank" data-share="telegram"><svg viewBox="0 0 24 24" width="20" height="20" fill="#0088cc"><path d="M11.944 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0a12 12 0 0 0-.056 0zm4.962 7.224c.1-.002.321.023.465.14a.506.506 0 0 1 .171.325c.016.127.087.496.087.496l-1.597 7.53s-.107.44-.607.44a.87.87 0 0 1-.473-.176l-3.148-2.386-1.174 1.09c-.257.227-.436.036-.436.036l.665-3.07s3.365-3.04 3.49-3.15c.125-.11.083-.177.083-.177.005-.112-.173 0-.173 0l-6.46 4.16-1.36-.454s-.497-.176-.548-.48c-.05-.306.46-.47.46-.47l12.232-4.747s.412-.148.412-.14z"/></svg> Telegram</a>
        <button type="button" class="galleryplus-viewer-share-option galleryplus-viewer-copy-link" data-share="copy"><svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg> <span><?php echo LANG_GALLERYPLUS_COPY_LINK ?? 'Copy link'; ?></span></button>
    </div>

    <div class="galleryplus-viewer-comments-overlay">
        <div class="galleryplus-viewer-comments-panel">
            <div class="galleryplus-viewer-comments-header">
                <span><?php echo LANG_GALLERYPLUS_COMMENTS ?? 'Comments'; ?></span>
                <button class="galleryplus-viewer-comments-close">&times;</button>
            </div>
            <div class="galleryplus-viewer-comments-body">
                <div class="galleryplus-viewer-comments-loading"><?php echo LANG_LOADING ?? 'Loading...'; ?></div>
            </div>
        </div>
    </div>
</div>