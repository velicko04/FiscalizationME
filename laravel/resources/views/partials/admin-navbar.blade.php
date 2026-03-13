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
                <span>📋</span> Contracts
            </a>
            <a href="{{ route('invoices.index') }}" class="{{ request()->routeIs('invoices.index') ? 'active' : '' }}">
                <span>💰</span> Invoices
            </a>
            <a href="{{ route('invoices.logs') }}" class="{{ request()->routeIs('invoices.logs') ? 'active' : '' }}">
                <span>📊</span> Fiscal Logs
            </a>
        </div>
    </div>
    
    <div class="navbar-right">
        <div class="search-bar">
            <span class="search-icon">🔍</span>
            <input type="text" placeholder="Search...">
        </div>
        
        <button class="icon-button" title="Notifications">
            🔔
        </button>
        
        <button class="icon-button" title="Settings">
            ⚙️
        </button>
    </div>
</nav>
