<!DOCTYPE html>
<html lang="en">
<?php 
include '../includes/header.php'; 
?>
<!-- Load standard icon sets -->
<link href="../assets/icons/flaticon/flaticon.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/line-awesome/1.3.0/line-awesome/css/line-awesome.min.css">

<style>
    .icon-section-title {
        background: #f8f9ff;
        padding: 15px 25px;
        border-radius: 12px;
        margin-top: 40px;
        margin-bottom: 20px;
        border-left: 5px solid #1A237E;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .icon-section-title h3 { margin: 0; font-weight: 800; color: #1A237E; font-size: 18px; }
    
    .icon-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
        gap: 12px;
    }
    
    .icon-box {
        background: #fff;
        border: 1px solid #eee;
        border-radius: 10px;
        padding: 15px 5px;
        text-align: center;
        transition: 0.2s;
        cursor: pointer;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
    }
    .icon-box:hover {
        border-color: #1A237E;
        transform: translateY(-3px);
        box-shadow: 0 5px 15px rgba(26, 35, 126, 0.1);
        background: #f8f9ff;
    }
    .icon-box i {
        font-size: 28px;
        color: #1A237E;
        margin-bottom: 10px;
    }
    .icon-name {
        font-size: 10px;
        color: #888;
        font-weight: 600;
        word-break: break-all;
        padding: 0 5px;
    }
    .search-container {
        position: sticky;
        top: 100px;
        z-index: 100;
        background: rgba(255,255,255,0.9);
        backdrop-filter: blur(10px);
        padding: 15px 0;
        margin-bottom: 20px;
    }
    .main-search {
        border: 2px solid #1A237E;
        border-radius: 50px;
        padding: 12px 30px;
        font-size: 16px;
        width: 100%;
        box-shadow: 0 4px 15px rgba(0,0,0,0.05);
    }
</style>

<body>
    <div id="preloader"><div class="sk-three-bounce"><div class="sk-child sk-bounce1"></div><div class="sk-child sk-bounce2"></div><div class="sk-child sk-bounce3"></div></div></div>
    
    <div id="main-wrapper">
        <?php include '../includes/navbar.php' ?>
        <?php include '../includes/sidebar.php' ?>
        
        <div class="content-body">
            <div class="container-fluid">
                
                <!-- HEADER -->
                <div class="text-center mb-5 mt-3">
                    <h1 class="font-w900 text-black">Galeri Ikon AFTECH</h1>
                    <p class="text-muted">Klik ikon apa pun untuk menyalin kode class-nya ke clipboard.</p>
                </div>

                <!-- SEARCH -->
                <div class="search-container">
                    <input type="text" id="masterSearch" class="main-search" placeholder="Cari ikon di semua koleksi... (misal: user, box, setting, print)">
                </div>

                <!-- SECTION: FLATICON 381 (SIDEBAR STYLE) -->
                <div class="icon-section-title">
                    <h3><i class="flaticon-381-layer-1 me-2"></i>Koleksi Flaticon-381 (Sidebar Standard)</h3>
                    <span class="badge badge-primary light" id="count-flaticon">0 Ikon</span>
                </div>
                <div class="icon-grid" id="grid-flaticon"></div>

                <!-- SECTION: FONTAWESOME -->
                <div class="icon-section-title">
                    <h3><i class="fab fa-font-awesome me-2"></i>Font Awesome 5 (Common Actions)</h3>
                    <span class="badge badge-info light" id="count-fa">0 Ikon</span>
                </div>
                <div class="icon-grid" id="grid-fa"></div>

                <!-- SECTION: LINE AWESOME -->
                <div class="icon-section-title">
                    <h3><i class="la la-icons me-2"></i>Line Awesome (Elegant Outline)</h3>
                    <span class="badge badge-warning light" id="count-la">0 Ikon</span>
                </div>
                <div class="icon-grid" id="grid-la"></div>

            </div>
        </div>
    </div>

    <?php include '../includes/footer.php' ?>

    <script>
        const flaticonList = [
            'flaticon-381-add', 'flaticon-381-add-1', 'flaticon-381-add-2', 'flaticon-381-add-3', 'flaticon-381-alarm-clock', 'flaticon-381-alarm-clock-1', 'flaticon-381-album', 'flaticon-381-album-1', 'flaticon-381-album-2', 'flaticon-381-album-3', 'flaticon-381-app', 'flaticon-381-archive', 'flaticon-381-back', 'flaticon-381-battery', 'flaticon-381-binoculars', 'flaticon-381-blueprint', 'flaticon-381-bluetooth', 'flaticon-381-book', 'flaticon-381-bookmark', 'flaticon-381-box', 'flaticon-381-box-1', 'flaticon-381-box-2', 'flaticon-381-briefcase', 'flaticon-381-broken-heart', 'flaticon-381-broken-link', 'flaticon-381-calculator', 'flaticon-381-calendar', 'flaticon-381-calendar-1', 'flaticon-381-calendar-2', 'flaticon-381-calendar-3', 'flaticon-381-calendar-4', 'flaticon-381-calendar-5', 'flaticon-381-calendar-6', 'flaticon-381-calendar-7', 'flaticon-381-clock', 'flaticon-381-clock-1', 'flaticon-381-clock-2', 'flaticon-381-close', 'flaticon-381-cloud', 'flaticon-381-cloud-computing', 'flaticon-381-command', 'flaticon-381-compact-disc', 'flaticon-381-compact-disc-1', 'flaticon-381-compact-disc-2', 'flaticon-381-compass', 'flaticon-381-compass-1', 'flaticon-381-compass-2', 'flaticon-381-controls', 'flaticon-381-controls-1', 'flaticon-381-controls-2', 'flaticon-381-controls-3', 'flaticon-381-controls-4', 'flaticon-381-controls-5', 'flaticon-381-controls-6', 'flaticon-381-controls-7', 'flaticon-381-controls-8', 'flaticon-381-controls-9', 'flaticon-381-database', 'flaticon-381-database-1', 'flaticon-381-database-2', 'flaticon-381-diamond', 'flaticon-381-diploma', 'flaticon-381-dislike', 'flaticon-381-divide', 'flaticon-381-division', 'flaticon-381-division-1', 'flaticon-381-download', 'flaticon-381-earth-globe', 'flaticon-381-earth-globe-1', 'flaticon-381-edit', 'flaticon-381-edit-1', 'flaticon-381-eject', 'flaticon-381-eject-1', 'flaticon-381-enter', 'flaticon-381-equal', 'flaticon-381-error', 'flaticon-381-exit', 'flaticon-381-exit-1', 'flaticon-381-exit-2', 'flaticon-381-fast-forward', 'flaticon-381-fast-forward-1', 'flaticon-381-file', 'flaticon-381-file-1', 'flaticon-381-file-2', 'flaticon-381-film-strip', 'flaticon-381-film-strip-1', 'flaticon-381-fingerprint', 'flaticon-381-flag', 'flaticon-381-flag-1', 'flaticon-381-flag-2', 'flaticon-381-flag-3', 'flaticon-381-flag-4', 'flaticon-381-focus', 'flaticon-381-folder', 'flaticon-381-folder-1', 'flaticon-381-folder-10', 'flaticon-381-folder-11', 'flaticon-381-folder-12', 'flaticon-381-folder-13', 'flaticon-381-folder-14', 'flaticon-381-folder-15', 'flaticon-381-folder-16', 'flaticon-381-folder-17', 'flaticon-381-folder-18', 'flaticon-381-folder-19', 'flaticon-381-folder-2', 'flaticon-381-folder-3', 'flaticon-381-folder-4', 'flaticon-381-folder-5', 'flaticon-381-folder-6', 'flaticon-381-folder-7', 'flaticon-381-folder-8', 'flaticon-381-folder-9', 'flaticon-381-forbidden', 'flaticon-381-funnel', 'flaticon-381-gift', 'flaticon-381-heart', 'flaticon-381-heart-1', 'flaticon-381-help', 'flaticon-381-help-1', 'flaticon-381-hide', 'flaticon-381-high-volume', 'flaticon-381-home', 'flaticon-381-home-1', 'flaticon-381-home-2', 'flaticon-381-home-3', 'flaticon-381-hourglass', 'flaticon-381-hourglass-1', 'flaticon-381-hourglass-2', 'flaticon-381-id-card', 'flaticon-381-id-card-1', 'flaticon-381-id-card-2', 'flaticon-381-id-card-3', 'flaticon-381-id-card-4', 'flaticon-381-id-card-5', 'flaticon-381-idea', 'flaticon-381-incoming-call', 'flaticon-381-infinity', 'flaticon-381-internet', 'flaticon-381-key', 'flaticon-381-knob', 'flaticon-381-knob-1', 'flaticon-381-layer', 'flaticon-381-layer-1', 'flaticon-381-like', 'flaticon-381-link', 'flaticon-381-link-1', 'flaticon-381-list', 'flaticon-381-list-1', 'flaticon-381-location', 'flaticon-381-location-1', 'flaticon-381-location-2', 'flaticon-381-location-3', 'flaticon-381-location-4', 'flaticon-381-locations', 'flaticon-381-lock', 'flaticon-381-lock-1', 'flaticon-381-lock-2', 'flaticon-381-lock-3', 'flaticon-381-low-volume', 'flaticon-381-low-volume-1', 'flaticon-381-low-volume-2', 'flaticon-381-low-volume-3', 'flaticon-381-magic-wand', 'flaticon-381-magnet', 'flaticon-381-magnet-1', 'flaticon-381-magnet-2', 'flaticon-381-map', 'flaticon-381-map-1', 'flaticon-381-map-2', 'flaticon-381-menu', 'flaticon-381-menu-1', 'flaticon-381-menu-2', 'flaticon-381-menu-3', 'flaticon-381-microphone', 'flaticon-381-microphone-1', 'flaticon-381-more', 'flaticon-381-more-1', 'flaticon-381-more-2', 'flaticon-381-multiply', 'flaticon-381-multiply-1', 'flaticon-381-music-album', 'flaticon-381-mute', 'flaticon-381-mute-1', 'flaticon-381-mute-2', 'flaticon-381-network', 'flaticon-381-network-1', 'flaticon-381-network-2', 'flaticon-381-network-3', 'flaticon-381-networking', 'flaticon-381-networking-1', 'flaticon-381-news', 'flaticon-381-newspaper', 'flaticon-381-next', 'flaticon-381-next-1', 'flaticon-381-note', 'flaticon-381-notebook', 'flaticon-381-notebook-1', 'flaticon-381-notebook-2', 'flaticon-381-notebook-3', 'flaticon-381-notebook-4', 'flaticon-381-notebook-5', 'flaticon-381-notepad', 'flaticon-381-notepad-1', 'flaticon-381-notepad-2', 'flaticon-381-notification', 'flaticon-381-off', 'flaticon-381-on', 'flaticon-381-pad', 'flaticon-381-padlock', 'flaticon-381-padlock-1', 'flaticon-381-padlock-2', 'flaticon-381-panel', 'flaticon-381-panel-1', 'flaticon-381-panel-2', 'flaticon-381-panel-3', 'flaticon-381-paperclip', 'flaticon-381-pause', 'flaticon-381-pause-1', 'flaticon-381-pencil', 'flaticon-381-percentage', 'flaticon-381-percentage-1', 'flaticon-381-perspective', 'flaticon-381-phone-call', 'flaticon-381-photo', 'flaticon-381-photo-camera', 'flaticon-381-photo-camera-1', 'flaticon-381-picture', 'flaticon-381-picture-1', 'flaticon-381-picture-2', 'flaticon-381-pin', 'flaticon-381-play-button', 'flaticon-381-play-button-1', 'flaticon-381-plus', 'flaticon-381-presentation', 'flaticon-381-price-tag', 'flaticon-381-print', 'flaticon-381-print-1', 'flaticon-381-privacy', 'flaticon-381-promotion', 'flaticon-381-promotion-1', 'flaticon-381-push-pin', 'flaticon-381-quaver', 'flaticon-381-quaver-1', 'flaticon-381-radar', 'flaticon-381-reading', 'flaticon-381-receive', 'flaticon-381-record', 'flaticon-381-repeat', 'flaticon-381-repeat-1', 'flaticon-381-resume', 'flaticon-381-rewind', 'flaticon-381-rewind-1', 'flaticon-381-ring', 'flaticon-381-ring-1', 'flaticon-381-rotate', 'flaticon-381-rotate-1', 'flaticon-381-route', 'flaticon-381-save', 'flaticon-381-search', 'flaticon-381-search-1', 'flaticon-381-search-2', 'flaticon-381-search-3', 'flaticon-381-send', 'flaticon-381-send-1', 'flaticon-381-send-2', 'flaticon-381-settings', 'flaticon-381-settings-1', 'flaticon-381-settings-2', 'flaticon-381-settings-3', 'flaticon-381-settings-4', 'flaticon-381-settings-5', 'flaticon-381-settings-6', 'flaticon-381-settings-7', 'flaticon-381-settings-8', 'flaticon-381-settings-9', 'flaticon-381-share', 'flaticon-381-share-1', 'flaticon-381-share-2', 'flaticon-381-shuffle', 'flaticon-381-shuffle-1', 'flaticon-381-shut-down', 'flaticon-381-silence', 'flaticon-381-silent', 'flaticon-381-smartphone', 'flaticon-381-smartphone-1', 'flaticon-381-smartphone-2', 'flaticon-381-smartphone-3', 'flaticon-381-smartphone-4', 'flaticon-381-smartphone-5', 'flaticon-381-smartphone-6', 'flaticon-381-smartphone-7', 'flaticon-381-speaker', 'flaticon-381-speedometer', 'flaticon-381-spotlight', 'flaticon-381-star', 'flaticon-381-star-1', 'flaticon-381-stop', 'flaticon-381-stop-1', 'flaticon-381-stopclock', 'flaticon-381-stopwatch', 'flaticon-381-stopwatch-1', 'flaticon-381-stopwatch-2', 'flaticon-381-substract', 'flaticon-381-substract-1', 'flaticon-381-substract-2', 'flaticon-381-success', 'flaticon-381-success-1', 'flaticon-381-success-2', 'flaticon-381-sunglasses', 'flaticon-381-switch', 'flaticon-381-switch-1', 'flaticon-381-switch-2', 'flaticon-381-switch-3', 'flaticon-381-switch-4', 'flaticon-381-switch-5', 'flaticon-381-sync', 'flaticon-381-tab', 'flaticon-381-target', 'flaticon-381-television', 'flaticon-381-time', 'flaticon-381-transfer', 'flaticon-381-trash', 'flaticon-381-trash-1', 'flaticon-381-trash-2', 'flaticon-381-trash-3', 'flaticon-381-turn-off', 'flaticon-381-umbrella', 'flaticon-381-unlocked', 'flaticon-381-unlocked-1', 'flaticon-381-unlocked-2', 'flaticon-381-unlocked-3', 'flaticon-381-unlocked-4', 'flaticon-381-upload', 'flaticon-381-upload-1', 'flaticon-381-user', 'flaticon-381-user-1', 'flaticon-381-user-2', 'flaticon-381-user-3', 'flaticon-381-user-4', 'flaticon-381-user-5', 'flaticon-381-user-6', 'flaticon-381-user-7', 'flaticon-381-user-8', 'flaticon-381-user-9', 'flaticon-381-video-camera', 'flaticon-381-video-clip', 'flaticon-381-video-player', 'flaticon-381-video-player-1', 'flaticon-381-view', 'flaticon-381-view-1', 'flaticon-381-view-2', 'flaticon-381-volume', 'flaticon-381-warning', 'flaticon-381-warning-1', 'flaticon-381-wifi', 'flaticon-381-wifi-1', 'flaticon-381-wifi-2', 'flaticon-381-windows', 'flaticon-381-windows-1', 'flaticon-381-zoom-in', 'flaticon-381-zoom-out'
        ];

        const faList = [
            'fa fa-home', 'fa fa-user', 'fa fa-users', 'fa fa-cog', 'fa fa-cogs', 'fa fa-bell', 'fa fa-search', 'fa fa-print', 'fa fa-trash', 'fa fa-edit', 'fa fa-save', 'fa fa-check', 'fa fa-times', 'fa fa-plus', 'fa fa-minus', 'fa fa-info-circle', 'fa fa-exclamation-triangle', 'fa fa-question-circle', 'fa fa-eye', 'fa fa-eye-slash', 'fa fa-lock', 'fa fa-unlock', 'fa fa-key', 'fa fa-shield-alt', 'fa fa-truck', 'fa fa-box', 'fa fa-boxes', 'fa fa-barcode', 'fa fa-qrcode', 'fa fa-shopping-cart', 'fa fa-shopping-bag', 'fa fa-file', 'fa fa-file-alt', 'fa fa-file-pdf', 'fa fa-file-excel', 'fa fa-chart-bar', 'fa fa-chart-pie', 'fa fa-calendar-alt', 'fa fa-clock', 'fa fa-history'
        ];

        const laList = [
            'la la-home', 'la la-user', 'la la-users', 'la la-cog', 'la la-cogs', 'la la-bell', 'la la-search', 'la la-print', 'la la-trash', 'la la-edit', 'la la-save', 'la la-check', 'la la-times', 'la la-plus', 'la la-minus', 'la la-info-circle', 'la la-exclamation-triangle', 'la la-question-circle', 'la la-eye', 'la la-eye-slash', 'la la-lock', 'la la-unlock', 'la la-key', 'la la-shield-alt', 'la la-truck', 'la la-box', 'la la-boxes', 'la la-barcode', 'la la-qrcode', 'la la-shopping-cart', 'la la-file-invoice', 'la la-clipboard-list', 'la la-chart-bar', 'la la-balance-scale', 'la la-clock', 'la la-calendar'
        ];

        function renderGrid(containerId, list, countId) {
            const container = document.getElementById(containerId);
            document.getElementById(countId).innerText = list.length + ' Ikon';
            
            list.forEach(cls => {
                const box = document.createElement('div');
                box.className = 'icon-box';
                box.setAttribute('data-name', cls.toLowerCase());
                box.onclick = () => copyText(cls);
                box.innerHTML = `
                    <i class="${cls}"></i>
                    <div class="icon-name">${cls}</div>
                `;
                container.appendChild(box);
            });
        }

        function copyText(text) {
            navigator.clipboard.writeText(text).then(() => {
                toastr.success(`<b>${text}</b> disalin ke clipboard!`);
            });
        }

        // Search logic
        document.getElementById('masterSearch').oninput = function() {
            const query = this.value.toLowerCase();
            document.querySelectorAll('.icon-box').forEach(box => {
                const name = box.getAttribute('data-name');
                box.style.display = name.includes(query) ? 'flex' : 'none';
            });
        }

        // Initial load
        renderGrid('grid-flaticon', flaticonList, 'count-flaticon');
        renderGrid('grid-fa', faList, 'count-fa');
        renderGrid('grid-la', laList, 'count-la');
    </script>
</body>
</html>