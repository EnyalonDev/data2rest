<script>
    // Theme Engine - Immediate execution to prevent flash
    (function() {
        const theme = localStorage.getItem('theme') || 'dark';
        if (theme === 'light') {
            document.documentElement.classList.remove('dark');
            document.documentElement.classList.add('light');
        } else {
            document.documentElement.classList.add('dark');
            document.documentElement.classList.remove('light');
        }
    })();

    function toggleTheme() {
        const isDark = document.documentElement.classList.contains('dark');
        if (isDark) {
            document.documentElement.classList.remove('dark');
            document.documentElement.classList.add('light');
            localStorage.setItem('theme', 'light');
        } else {
            document.documentElement.classList.add('dark');
            document.documentElement.classList.remove('light');
            localStorage.setItem('theme', 'dark');
        }
    }
</script>

<style type="text/tailwindcss">
    :root {
        --bg-main: #f1f5f9;
        --bg-card: #ffffff;
        --text-main: #0f172a;
        --text-muted: #64748b;
        --border-glass: #e2e8f0;
        --nav-bg: rgba(255, 255, 255, 0.8);
        --card-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05), 0 4px 6px -2px rgba(0, 0, 0, 0.02);
        --icon-bg: #f8fafc;
    }
    .dark {
        --bg-main: #0b1120;
        --bg-card: rgba(30, 41, 59, 0.5);
        --text-main: #e2e8f0;
        --text-muted: #94a3b8;
        --border-glass: rgba(255, 255, 255, 0.1);
        --nav-bg: rgba(11, 17, 32, 0.8);
        --card-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        --icon-bg: rgba(255,255,255,0.05);
    }
    @layer base {
        body { 
            background-color: var(--bg-main) !important;
            color: var(--text-main) !important;
            transition: background-color 0.3s ease, color 0.3s ease;
        }
        
        /* Light Theme Overrides */
        .light .bg-dark { background-color: var(--bg-main) !important; }
        .light .bg-dark\/80 { background-color: var(--nav-bg) !important; }
        .light .bg-glass, .light .glass-card { 
            background-color: var(--bg-card) !important; 
            border-color: var(--border-glass) !important;
            box-shadow: var(--card-shadow) !important;
        }
        .light .border-glass-border { border-color: var(--border-glass) !important; }
        
        .light .text-slate-200, .light .text-white, .light .text-slate-300 { color: var(--text-main) !important; }
        .light .text-slate-400, .light .text-slate-500 { color: var(--text-muted) !important; }
        
        /* Box & Structure Enhancements for Light Mode */
        .light section, .light .container > div { 
            /* Subtle shadows to lift main containers */
        }

        /* Icon & Button shadows in Light Mode */
        .light [class*="bg-"][class*="/10"], .light .bg-white\/5 {
            background-color: #f1f5f9 !important;
            box-shadow: inset 0 2px 4px 0 rgba(0, 0, 0, 0.06) !important;
            border: 1px solid #e2e8f0 !important;
        }
        
        .light .w-16.h-16, .light .w-12.h-12, .light .w-10.h-10 {
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06) !important;
            background-color: #ffffff !important;
        }

        /* Inputs Light Mode */
        .light .input-dark, .light .input-field, .light .input-dark { 
            background-color: #ffffff !important; 
            border: 2px solid #e2e8f0 !important; 
            color: #0f172a !important; 
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05) !important;
        }
        
        .light .input-dark:focus, .light .input-field:focus {
            border-color: #38bdf8 !important;
            box-shadow: 0 0 0 4px rgba(56, 189, 248, 0.1) !important;
        }

        /* Hover states for Light Mode */
        .light .hover\:bg-white\/\[0\.02\]:hover { 
            background-color: #f8fafc !important; 
        }
        
        /* Tables in Light Mode */
        .light table { background-color: #ffffff; }
        .light thead { background-color: #f8fafc; }
        .light td, .light th { border-color: #f1f5f9 !important; }
        .light .divide-white\/\[0\.03\] { border-color: #f1f5f9 !important; }
        
        /* Navigation Links */
        .light .nav-link { color: #64748b !important; }
        .light .nav-link:hover { color: #38bdf8 !important; }
        
        /* Specialized UI Icons */
        .light svg {
            filter: drop-shadow(0 1px 1px rgba(0,0,0,0.05));
        }
    }
</style>
