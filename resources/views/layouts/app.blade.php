<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name', 'Task Management System') }}</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Sora:wght@400;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Custom Style Sheet -->
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>
<body>
    @auth
    <div class="dashboard-wrapper">
        <!-- Background Ambient Glows -->
        <div class="glow-blob blob-1"></div>
        <div class="glow-blob blob-2"></div>

        <!-- Sidebar Navigation -->
        <aside class="sidebar glass-panel">
            <div class="sidebar-brand">ALE-TM System</div>
            
            <!-- Profile Info -->
            <div class="sidebar-profile">
                <div class="profile-avatar">
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                </div>
                <div style="overflow: hidden;">
                    <div class="profile-name">{{ auth()->user()->name }}</div>
                    <div class="profile-role">{{ str_replace('_', ' ', auth()->user()->role) }}</div>
                </div>
            </div>
            
            <!-- Navigation Menu -->
            <ul class="sidebar-menu">
                <li class="menu-item {{ Request::is('/') || Request::is('dashboard') ? 'active' : '' }}">
                    <a href="{{ route('dashboard') }}">
                        <span>📊</span>
                        <span class="menu-text">Dashboard</span>
                    </a>
                </li>
                <li class="menu-item {{ Request::is('tasks*') ? 'active' : '' }}">
                    <a href="{{ route('tasks.index') }}">
                        <span>📋</span>
                        <span class="menu-text">Task Board</span>
                    </a>
                </li>
                <li class="menu-item {{ Request::is('tokens*') ? 'active' : '' }}">
                    <a href="{{ route('tokens.index') }}">
                        <span>🔑</span>
                        <span class="menu-text">API Credentials</span>
                    </a>
                </li>
                @if(auth()->user()->role === 'super_admin')
                <li class="menu-item {{ Request::is('logs*') ? 'active' : '' }}">
                    <a href="{{ route('logs.index') }}">
                        <span>📁</span>
                        <span class="menu-text">Activity Logs</span>
                    </a>
                </li>
                @endif
            </ul>
            
            <!-- Sidebar Footer -->
            <div class="sidebar-footer">
                <form action="{{ route('logout') }}" method="POST" id="logout-form">
                    @csrf
                    <button type="submit" class="btn btn-secondary" style="width: 100%; border: 1px solid rgba(255, 68, 68, 0.2); color: #ef4444;">
                        <span>🚪</span>
                        <span class="menu-text">Logout</span>
                    </button>
                </form>
            </div>
        </aside>

        <!-- Main Workspace Area -->
        <main class="main-content">
            <!-- Navbar / Top Header -->
            <header class="navbar">
                <div class="page-title">
                    @yield('title', 'Overview')
                </div>
                
                <div class="navbar-actions">
                    <!-- Theme Toggle -->
                    <button onclick="toggleTheme()" class="notification-bell" title="Toggle Theme" style="border: none;">
                        <span id="theme-icon">☀️</span>
                    </button>
                    
                    <!-- Notifications Dropdown -->
                    @php
                        $notifications = \Illuminate\Support\Facades\DB::table('notifications')
                            ->where('notifiable_id', auth()->id())
                            ->orderBy('created_at', 'desc')
                            ->take(5)
                            ->get();
                        $unreadCount = \Illuminate\Support\Facades\DB::table('notifications')
                            ->where('notifiable_id', auth()->id())
                            ->whereNull('read_at')
                            ->count();
                    @endphp
                    <div style="position: relative;">
                        <button onclick="toggleNotifications()" class="notification-bell" title="Notifications">
                            <span>🔔</span>
                            @if($unreadCount > 0)
                                <span class="notification-count">{{ $unreadCount }}</span>
                            @endif
                        </button>
                        
                        <div id="notification-drawer" class="notification-drawer glass-panel">
                            <h4 style="margin-bottom: 10px; font-weight: 700; border-bottom: 1px solid var(--panel-border); padding-bottom: 8px;">Notifications</h4>
                            @if($notifications->isEmpty())
                                <p style="font-size: 0.8rem; color: var(--text-secondary); text-align: center; padding: 10px;">No new alerts.</p>
                            @else
                                <div class="notification-list">
                                    @foreach($notifications as $notif)
                                        @php $data = json_decode($notif->data, true); @endphp
                                        <div class="notification-item @if(is_null($notif->read_at)) unread @endif">
                                            <strong>{{ $data['title'] ?? 'Alert' }}</strong>
                                            <p style="margin-top: 4px; color: var(--text-secondary);">{{ $data['message'] ?? '' }}</p>
                                            <span style="font-size: 0.7rem; color: var(--text-secondary); float: right;">{{ \Carbon\Carbon::parse($notif->created_at)->diffForHumans() }}</span>
                                            <div style="clear: both;"></div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </header>

            <!-- Alerts / Messages -->
            @if(session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif
            
            @if(session('error'))
                <div class="alert alert-danger">
                    {{ session('error') }}
                </div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger">
                    <ul style="margin-left: 20px;">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Dashboard / Page Content -->
            @yield('content')
        </main>
    </div>
    @else
        <!-- Guest Auth Layout -->
        <div class="auth-container">
            @yield('auth_content')
        </div>
    @endauth

    <!-- JavaScript Actions -->
    <script>
        function toggleNotifications() {
            const drawer = document.getElementById('notification-drawer');
            if(drawer.style.display === 'block') {
                drawer.style.display = 'none';
            } else {
                drawer.style.display = 'block';
            }
        }

        // Close notifications when clicking outside
        window.addEventListener('click', function(e) {
            const bell = document.querySelector('.notification-bell');
            const drawer = document.getElementById('notification-drawer');
            if (drawer && bell && !bell.contains(e.target) && !drawer.contains(e.target)) {
                drawer.style.display = 'none';
            }
        });

        // Theme Toggle Functionality
        function toggleTheme() {
            const html = document.documentElement;
            const icon = document.getElementById('theme-icon');
            if(html.getAttribute('data-theme') === 'dark') {
                html.setAttribute('data-theme', 'light');
                icon.textContent = '🌙';
                localStorage.setItem('theme', 'light');
            } else {
                html.setAttribute('data-theme', 'dark');
                icon.textContent = '☀️';
                localStorage.setItem('theme', 'dark');
            }
        }

        // Set Saved Theme on Load
        document.addEventListener('DOMContentLoaded', () => {
            const savedTheme = localStorage.getItem('theme');
            const html = document.documentElement;
            const icon = document.getElementById('theme-icon');
            if(savedTheme === 'light') {
                html.setAttribute('data-theme', 'light');
                if(icon) icon.textContent = '🌙';
            } else {
                html.setAttribute('data-theme', 'dark');
                if(icon) icon.textContent = '☀️';
            }
        });
    </script>
</body>
</html>
