<style>
    .admin-navbar {
        background: rgba(255, 255, 255, 0.15);
        backdrop-filter: blur(10px);
        border-radius: 16px;
        padding: 16px 32px;
        margin-bottom: 40px;
        display: flex;
        justify-content: center;
        gap: 48px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
    }
    
    .admin-navbar a {
        color: rgba(255, 255, 255, 0.8);
        text-decoration: none;
        font-size: 16px;
        font-weight: 600;
        padding: 8px 16px;
        border-radius: 8px;
        transition: all 0.3s ease;
        position: relative;
    }
    
    .admin-navbar a:hover {
        color: white;
        background: rgba(255, 255, 255, 0.1);
    }
    
    .admin-navbar a.active {
        color: white;
        background: rgba(255, 255, 255, 0.2);
    }
    
    .admin-navbar a.active::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 16px;
        right: 16px;
        height: 2px;
        background: white;
        border-radius: 2px;
    }
    
    @media (max-width: 768px) {
        .admin-navbar {
            flex-direction: column;
            gap: 8px;
            padding: 16px;
        }
        
        .admin-navbar a {
            text-align: center;
        }
    }
</style>

<nav class="admin-navbar">
    <a href="{{ route('contracts.index') }}" class="{{ request()->routeIs('contracts.*') ? 'active' : '' }}">
        Ugovori
    </a>
    <a href="{{ route('invoices.index') }}" class="{{ request()->routeIs('invoices.index') ? 'active' : '' }}">
        Računi
    </a>
    <a href="{{ route('invoices.logs') }}" class="{{ request()->routeIs('invoices.logs') ? 'active' : '' }}">
        Logovi
    </a>
</nav>
