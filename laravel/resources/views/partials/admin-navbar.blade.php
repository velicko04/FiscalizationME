<style>
    .top-navbar {
        background: white;
        border-bottom: 1px solid #e5e7eb;
        padding: 16px 32px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        position: sticky;
        top: 0;
        z-index: 100;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
    }
    
    .navbar-left {
        display: flex;
        align-items: center;
        gap: 48px;
    }
    
    .logo {
        font-size: 20px;
        font-weight: 700;
        color: #6366f1;
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .logo-icon {
        width: 32px;
        height: 32px;
        background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 700;
        font-size: 16px;
    }
    
    .main-nav {
        display: flex;
        gap: 32px;
        align-items: center;
    }
    
    .main-nav a {
        color: #6b7280;
        text-decoration: none;
        font-size: 14px;
        font-weight: 500;
        padding: 8px 0;
        border-bottom: 2px solid transparent;
        transition: all 0.2s;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .main-nav a:hover { color: #111827; }
    
    .main-nav a.active {
        color: #111827;
        border-bottom-color: #6366f1;
    }
    
    .navbar-right {
        display: flex;
        align-items: center;
        gap: 16px;
    }

    /* SEARCH */
    .search-bar { position: relative; }
    
    .search-bar input {
        width: 280px;
        height: 40px;
        padding: 0 16px 0 40px;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        font-size: 14px;
        background: #f9fafb;
        transition: all 0.2s;
    }
    
    .search-bar input:focus {
        outline: none;
        border-color: #6366f1;
        background: white;
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
    }
    
    .search-icon {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: #9ca3af;
        font-size: 16px;
        pointer-events: none;
    }

    .search-results {
        position: absolute;
        top: calc(100% + 8px);
        left: 0;
        right: 0;
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        z-index: 300;
        display: none;
        overflow: hidden;
        max-height: 360px;
        overflow-y: auto;
    }

    .search-results.show { display: block; }

    .search-section-title {
        padding: 10px 16px 6px;
        font-size: 11px;
        font-weight: 600;
        color: #9ca3af;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        border-bottom: 1px solid #f3f4f6;
    }

    .search-result-item {
        padding: 10px 16px;
        display: flex;
        align-items: center;
        gap: 10px;
        cursor: pointer;
        transition: background 0.2s;
        text-decoration: none;
        color: #111827;
        border-bottom: 1px solid #f9fafb;
    }

    .search-result-item:hover { background: #f9fafb; }

    .search-result-icon {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        background: #f3f4f6;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
        flex-shrink: 0;
    }

    .search-result-title { font-size: 13px; font-weight: 600; }
    .search-result-sub { font-size: 12px; color: #6b7280; }

    .search-empty {
        padding: 24px 16px;
        text-align: center;
        color: #9ca3af;
        font-size: 13px;
    }

    /* ICON BUTTON */
    .icon-button {
        width: 40px;
        height: 40px;
        border-radius: 8px;
        border: none;
        background: #f9fafb;
        color: #6b7280;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s;
        position: relative;
    }
    
    .icon-button:hover { background: #e5e7eb; color: #111827; }

    .notif-badge {
        position: absolute;
        top: 4px;
        right: 4px;
        width: 16px;
        height: 16px;
        background: #ef4444;
        border-radius: 999px;
        font-size: 10px;
        font-weight: 700;
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        display: none;
    }
    
    /* DROPDOWN */
    .dropdown { position: relative; }
    
    .dropdown-menu {
        position: absolute;
        right: 0;
        top: calc(100% + 8px);
        min-width: 320px;
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        display: none;
        flex-direction: column;
        overflow: hidden;
        z-index: 200;
        animation: dropdownFadeIn 0.2s ease-out;
    }
    
    @keyframes dropdownFadeIn {
        from { opacity: 0; transform: translateY(-8px); }
        to { opacity: 1; transform: translateY(0); }
    }
    
    .dropdown-menu.show { display: flex; }
    
    .dropdown-header {
        padding: 16px 20px;
        font-weight: 600;
        font-size: 14px;
        color: #111827;
        border-bottom: 1px solid #e5e7eb;
        background: white;
    }
    
    .dropdown-content { max-height: 360px; overflow-y: auto; }
    
    .dropdown-item {
        padding: 12px 20px;
        font-size: 13px;
        display: flex;
        gap: 12px;
        align-items: start;
        border-bottom: 1px solid #f3f4f6;
        transition: background 0.2s;
    }
    .dropdown-item:last-child { border-bottom: none; }
    .dropdown-item:hover { background: #f9fafb; }
    
    .notification-icon {
        width: 36px;
        height: 36px;
        border-radius: 8px;
        background: #f3f4f6;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
        flex-shrink: 0;
    }
    
    .notification-content { flex: 1; }
    .notification-title { font-size: 13px; font-weight: 600; color: #111827; margin-bottom: 4px; }
    .notification-text { font-size: 12px; color: #6b7280; line-height: 1.4; }
    .notification-time { font-size: 11px; color: #9ca3af; margin-top: 4px; }
    
    .notif-warning .notification-icon { background: #fef3c7; }
    .notif-error .notification-icon { background: #fee2e2; }
    .notif-success .notification-icon { background: #d1fae5; }

    .notif-empty {
        padding: 32px 20px;
        text-align: center;
        color: #9ca3af;
        font-size: 13px;
    }
    
    .dropdown-link {
        padding: 12px 20px;
        text-decoration: none;
        color: #374151;
        font-size: 14px;
        display: flex;
        align-items: center;
        gap: 12px;
        border-bottom: 1px solid #f3f4f6;
        transition: background 0.2s;
    }
    .dropdown-link:last-child { border-bottom: none; }
    .dropdown-link:hover { background: #f9fafb; }
    
    .logout { color: #ef4444; }
    .logout:hover { background: #fef2f2; }
    
    @media (max-width: 1024px) {
        .top-navbar { padding: 12px 16px; }
        .navbar-left { gap: 24px; }
        .main-nav { gap: 16px; }
        .search-bar input { width: 180px; }
    }
    
    @media (max-width: 768px) {
        .top-navbar { flex-direction: column; gap: 16px; }
        .navbar-left, .navbar-right { width: 100%; }
        .main-nav { width: 100%; justify-content: space-around; }
        .search-bar input { width: 100%; }
    }
</style>

<nav class="top-navbar">
    <div class="navbar-left">
        <a href="{{ route('contracts.index') }}" class="logo">
            <div class="logo-icon">F</div>
            <span>FiscalizationME</span>
        </a>
        
        <div class="main-nav">
            <a href="{{ route('contracts.index') }}" class="{{ request()->routeIs('contracts.*') ? 'active' : '' }}">Contracts</a>
            <a href="{{ route('invoices.index') }}" class="{{ request()->routeIs('invoices.index') ? 'active' : '' }}">Invoices</a>
            <a href="{{ route('invoices.logs') }}" class="{{ request()->routeIs('invoices.logs') ? 'active' : '' }}">Fiscal Logs</a>
            <a href="{{ route('companies.index') }}" class="{{ request()->routeIs('companies.*') ? 'active' : '' }}">Companies</a>
        </div>
    </div>
    
    <div class="navbar-right">

        {{-- GLOBAL SEARCH --}}
        <div class="search-bar" id="global-search-wrap">
            <span class="search-icon">🔍</span>
            <input type="text" id="global-search" placeholder="Search contracts, invoices..." autocomplete="off">
            <div class="search-results" id="search-results"></div>
        </div>

        {{-- NOTIFIKACIJE --}}
        <div class="dropdown">
            <button class="icon-button dropdown-toggle" data-dropdown="notifications" title="Notifications">
                🔔
                <span class="notif-badge" id="notif-badge"></span>
            </button>
            
            <div class="dropdown-menu" id="notifications">
                <div class="dropdown-header">Notifications</div>
                <div class="dropdown-content" id="notif-content">
                    <div class="notif-empty">Učitavanje...</div>
                </div>
            </div>
        </div>
        
        {{-- SETTINGS --}}
        <div class="dropdown">
            <button class="icon-button dropdown-toggle" data-dropdown="settings" title="Settings">⚙️</button>
            
            <div class="dropdown-menu" id="settings">
                <div class="dropdown-header">Settings</div>
                <a class="dropdown-link" href="{{ route('settings.index') }}">
                    <div>Profile & Password</div>
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="dropdown-link logout" style="width:100%; text-align:left; background:none; border:none; cursor:pointer;">
                        <div>Logout</div>
                    </button>
                </form>
            </div>
        </div>
    </div>
</nav>

<script>
document.addEventListener('DOMContentLoaded', function() {

    // ==================
    // DROPDOWNS
    // ==================
    function closeAllDropdowns() {
        document.querySelectorAll('.dropdown-menu').forEach(m => m.classList.remove('show'));
    }

    document.querySelectorAll('.dropdown-toggle').forEach(function(btn) {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            var id = this.dataset.dropdown;
            var menu = document.getElementById(id);
            var wasOpen = menu.classList.contains('show');
            closeAllDropdowns();
            if (!wasOpen) {
                menu.classList.add('show');
                
                if (id === 'notifications') {
                loadNotifications();
                document.getElementById('notif-badge').style.display = 'none'; // 👈 OVDE
            }
            }
        });
    });

    document.addEventListener('click', function(e) {
        if (!e.target.closest('.dropdown') && !e.target.closest('#global-search-wrap')) {
            closeAllDropdowns();
            document.getElementById('search-results').classList.remove('show');
        }
    });

    document.querySelectorAll('.dropdown-menu').forEach(function(menu) {
        menu.addEventListener('click', function(e) { e.stopPropagation(); });
    });

    // ==================
    // NOTIFIKACIJE
    // ==================
    function loadNotifications() {
        fetch('/notifications', { headers: { 'Accept': 'application/json' } })
        .then(function(res) { return res.json(); })
        .then(function(data) {
            var content = document.getElementById('notif-content');
            var badge = document.getElementById('notif-badge');

            if (!data.length) {
                content.innerHTML = '<div class="notif-empty">Nema novih notifikacija.</div>';
                badge.style.display = 'none';
                return;
            }

            badge.style.display = 'flex';
            badge.textContent = data.length > 9 ? '9+' : data.length;

            var html = '';
            data.forEach(function(n) {
                var typeClass = n.type === 'error' ? 'notif-error' : n.type === 'warning' ? 'notif-warning' : 'notif-success';
                html += '<div class="dropdown-item ' + typeClass + '">';
                html += '<div class="notification-icon">' + n.icon + '</div>';
                html += '<div class="notification-content">';
                html += '<div class="notification-title">' + n.title + '</div>';
                html += '<div class="notification-text">' + n.text + '</div>';
                html += '</div></div>';
            });

            content.innerHTML = html;
        })
        .catch(function() {
            document.getElementById('notif-content').innerHTML = '<div class="notif-empty">Greška pri učitavanju.</div>';
        });
    }

    // Učitaj badge odmah
    fetch('/notifications', { headers: { 'Accept': 'application/json' } })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        var badge = document.getElementById('notif-badge');
        if (data.length > 0) {
            badge.style.display = 'flex';
            badge.textContent = data.length > 9 ? '9+' : data.length;
        }
    });

    // ==================
    // GLOBAL SEARCH
    // ==================
    var searchInput = document.getElementById('global-search');
    var searchResults = document.getElementById('search-results');
    var searchTimeout;

    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        var query = this.value.trim();

        if (query.length < 2) {
            searchResults.classList.remove('show');
            return;
        }

        searchTimeout = setTimeout(function() {
            fetch('/search?q=' + encodeURIComponent(query), { headers: { 'Accept': 'application/json' } })
            .then(function(res) { return res.json(); })
            .then(function(data) {
                var html = '';

                if (data.contracts && data.contracts.length) {
                    html += '<div class="search-section-title">Contracts</div>';
                    data.contracts.forEach(function(c) {
                        html += '<a href="/contracts/' + c.id + '/invoices" class="search-result-item">';
                        html += '<div class="search-result-icon">📋</div>';
                        html += '<div><div class="search-result-title">' + c.contract_number + '</div>';
                        html += '<div class="search-result-sub">' + c.buyer + ' · ' + c.status + '</div></div>';
                        html += '</a>';
                    });
                }

                if (data.invoices && data.invoices.length) {
                    html += '<div class="search-section-title">Invoices</div>';
                    data.invoices.forEach(function(i) {
                        html += '<a href="/contracts/' + i.contract_id + '/invoices" class="search-result-item">';
                        html += '<div class="search-result-icon">🧾</div>';
                        html += '<div><div class="search-result-title">' + i.invoice_number + '</div>';
                        html += '<div class="search-result-sub">' + i.buyer + ' · ' + i.total + ' €</div></div>';
                        html += '</a>';
                    });
                }

                if (!html) {
                    html = '<div class="search-empty">No results found for "' + query + '"</div>';
                }

                searchResults.innerHTML = html;
                searchResults.classList.add('show');
            });
        }, 300);
    });

    searchInput.addEventListener('focus', function() {
        if (this.value.trim().length >= 2) searchResults.classList.add('show');
    });
});
</script>