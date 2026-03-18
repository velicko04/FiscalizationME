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
    
    .main-nav a:hover {
        color: #111827;
    }
    
    .main-nav a.active {
        color: #111827;
        border-bottom-color: #6366f1;
    }
    
    .navbar-right {
        display: flex;
        align-items: center;
        gap: 16px;
    }
    
    .search-bar {
        position: relative;
    }
    
    .search-bar input {
        width: 320px;
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
    }
    
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
    }
    
    .icon-button:hover {
        background: #e5e7eb;
        color: #111827;
    }
    
    .dropdown {
        position: relative;
    }
    
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
        from {
            opacity: 0;
            transform: translateY(-8px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
    
    .dropdown-menu.show {
        display: flex;
    }
    
    .dropdown-header {
        padding: 16px 20px;
        font-weight: 600;
        font-size: 14px;
        color: #111827;
        border-bottom: 1px solid #e5e7eb;
        background: white;
    }
    
    .dropdown-content {
        max-height: 400px;
        overflow-y: auto;
    }
    
    .dropdown-item {
        padding: 12px 20px;
        font-size: 13px;
        display: flex;
        gap: 12px;
        align-items: start;
        cursor: pointer;
        border-bottom: 1px solid #f3f4f6;
        transition: background 0.2s;
    }
    
    .dropdown-item:last-child {
        border-bottom: none;
    }
    
    .dropdown-item:hover {
        background: #f9fafb;
    }
    
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
    
    .notification-content {
        flex: 1;
    }
    
    .notification-title {
        font-size: 13px;
        font-weight: 600;
        color: #111827;
        margin-bottom: 4px;
    }
    
    .notification-text {
        font-size: 12px;
        color: #6b7280;
        line-height: 1.4;
    }
    
    .notification-time {
        font-size: 11px;
        color: #9ca3af;
        margin-top: 4px;
    }
    
    .notification-unread {
        background: #eff6ff;
    }
    
    .notification-unread .notification-icon {
        background: #dbeafe;
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
    
    .dropdown-link:last-child {
        border-bottom: none;
    }
    
    .dropdown-link:hover {
        background: #f9fafb;
    }
    
    .settings-icon {
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
    
    .settings-text {
        flex: 1;
    }
    
    
    .logout {
        color: #ef4444;
    }
    
    .logout:hover {
        background: #fef2f2;
    }
    
    .dropdown-footer {
        padding: 12px 20px;
        border-top: 1px solid #e5e7eb;
        text-align: center;
        background: #f9fafb;
    }
    
    .dropdown-footer a {
        color: #6366f1;
        text-decoration: none;
        font-size: 13px;
        font-weight: 500;
    }
    
    .dropdown-footer a:hover {
        color: #4f46e5;
    }
    
    @media (max-width: 1024px) {
        .top-navbar {
            padding: 12px 16px;
        }
        
        .navbar-left {
            gap: 24px;
        }
        
        .main-nav {
            gap: 16px;
        }
        
        .search-bar input {
            width: 200px;
        }
    }
    
    @media (max-width: 768px) {
        .top-navbar {
            flex-direction: column;
            gap: 16px;
        }
        
        .navbar-left,
        .navbar-right {
            width: 100%;
        }
        
        .main-nav {
            width: 100%;
            justify-content: space-around;
        }
        
        .search-bar input {
            width: 100%;
        }
    }
</style>

<nav class="top-navbar">
    <div class="navbar-left">
        <a href="{{ route('contracts.index') }}" class="logo">
            <div class="logo-icon">F</div>
            <span>FiscalizationME</span>
        </a>
        
        <div class="main-nav">
            <a href="{{ route('contracts.index') }}" class="{{ request()->routeIs('contracts.*') ? 'active' : '' }}">
               Contracts
            </a>
            <a href="{{ route('invoices.index') }}" class="{{ request()->routeIs('invoices.index') ? 'active' : '' }}">
               Invoices
            </a>
            <a href="{{ route('invoices.logs') }}" class="{{ request()->routeIs('invoices.logs') ? 'active' : '' }}">
              Fiscal Logs
            </a>
        </div>
    </div>
    
    <div class="navbar-right">
        <div class="search-bar">
            <span class="search-icon">🔍</span>
            <input type="text" placeholder="Search...">
        </div>
        
        <div class="dropdown">
            <button class="icon-button dropdown-toggle" data-dropdown="notifications" title="Notifications">
                🔔
            </button>
            
            <div class="dropdown-menu" id="notifications">
                <div class="dropdown-header">Notifications</div>
                
                <div class="dropdown-content">
                    <div class="dropdown-item notification-item notification-unread">
                        <div class="notification-icon">📄</div>
                        <div class="notification-content">
                            <div class="notification-title">New invoice created</div>
                            <div class="notification-text">Invoice #INV-2026-001 has been generated</div>
                            <div class="notification-time">5 minutes ago</div>
                        </div>
                    </div>
                    
                    <div class="dropdown-item notification-item notification-unread">
                        <div class="notification-icon">✅</div>
                        <div class="notification-content">
                            <div class="notification-title">Fiscalization successful</div>
                            <div class="notification-text">Invoice #INV-2026-002 fiscalized successfully</div>
                            <div class="notification-time">1 hour ago</div>
                        </div>
                    </div>
                    
                    <div class="dropdown-item notification-item">
                        <div class="notification-icon">⚠️</div>
                        <div class="notification-content">
                            <div class="notification-title">Contract expiring soon</div>
                            <div class="notification-text">Contract #CT-2026-005 expires in 7 days</div>
                            <div class="notification-time">3 hours ago</div>
                        </div>
                    </div>
                    
                    <div class="dropdown-item notification-item">
                        <div class="notification-icon">📋</div>
                        <div class="notification-content">
                            <div class="notification-title">Contract updated</div>
                            <div class="notification-text">Contract #CT-2026-003 status changed to Active</div>
                            <div class="notification-time">Yesterday</div>
                        </div>
                    </div>
                </div>
                
                <div class="dropdown-footer">
                    <a href="#">View all notifications</a>
                </div>
            </div>
        </div>
        
        <div class="dropdown">
            <button class="icon-button dropdown-toggle" data-dropdown="settings" title="Settings">
                ⚙️
            </button>
            
            <div class="dropdown-menu" id="settings">
                <div class="dropdown-header">Settings</div>
                
                <a class="dropdown-link" href="#">
                    
                    <div class="settings-text">Profile</div>
                </a>
                
                <a class="dropdown-link" href="#">
                    
                    <div class="settings-text">Company Settings</div>
                </a>
                
                <div class="dropdown-divider"></div>
                
                <a class="dropdown-link logout" href="#">
                    
                    <div class="settings-text">Logout</div>
                </a>
            </div>
        </div>
    </div>
</nav>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Get all dropdown toggles
    const dropdownToggles = document.querySelectorAll('.dropdown-toggle');
    
    // Close all dropdowns
    function closeAllDropdowns() {
        document.querySelectorAll('.dropdown-menu').forEach(menu => {
            menu.classList.remove('show');
        });
    }
    
    // Toggle dropdown on button click
    dropdownToggles.forEach(button => {
        button.addEventListener('click', function(e) {
            e.stopPropagation();
            
            const dropdownId = this.dataset.dropdown;
            const menu = document.getElementById(dropdownId);
            const isCurrentlyOpen = menu.classList.contains('show');
            
            // Close all dropdowns first
            closeAllDropdowns();
            
            // Toggle the clicked dropdown (only open if it was closed)
            if (!isCurrentlyOpen) {
                menu.classList.add('show');
            }
        });
    });
    
    // Close dropdowns when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.dropdown')) {
            closeAllDropdowns();
        }
    });
    
    // Prevent dropdown from closing when clicking inside it
    document.querySelectorAll('.dropdown-menu').forEach(menu => {
        menu.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    });
});
</script>
