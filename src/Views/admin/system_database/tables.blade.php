@extends('layouts.main')

@section('title', $title)

@section('content')
    <!-- Header Section -->
    <header class="text-center mb-16 relative">
        <div class="absolute -top-20 left-1/2 -translate-x-1/2 w-96 h-96 bg-blue-500/10 blur-[120px] rounded-full -z-10">
        </div>
        <div
            class="inline-block bg-blue-500 text-white px-4 py-1 rounded-full text-[10px] font-black uppercase tracking-[0.2em] mb-6">
            {{ \App\Core\Lang::get('system_database.tables') }}
        </div>
        <h1 class="text-5xl md:text-7xl font-black text-p-title mb-6 tracking-tighter uppercase italic">
            Tablas del Sistema
        </h1>
        <p class="text-p-muted font-medium max-w-2xl mx-auto">
            Estructura completa de la base de datos del sistema
        </p>
    </header>

    <!-- Tables Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($tables as $table)
            <a href="<?= $baseUrl ?>admin/system-database/table-details?table=<?= urlencode($table['name']) ?>"
                class="glass-card hover:border-blue-500/50 transition-all group">
                <div class="flex items-center gap-4 mb-4">
                    <div
                        class="w-12 h-12 bg-blue-500/10 rounded-xl flex items-center justify-center text-blue-500 group-hover:scale-110 transition-transform">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                            <polyline points="9 22 9 12 15 12 15 22"></polyline>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-sm font-black text-p-title mb-1">{{ $table['name'] }}</h3>
                        <p class="text-[10px] text-p-muted font-medium">{{ $table['records'] }} registros</p>
                    </div>
                </div>
                <div class="flex items-center justify-between pt-4 border-t border-white/5">
                    <span class="text-[9px] font-black text-p-muted uppercase tracking-widest">Tamaño</span>
                    <span class="text-xs font-black text-p-title">{{ $table['size'] }}</span>
                </div>
            </a>
        @endforeach
    </div>

    @if(empty($tables))
        <div class="glass-card !p-20 text-center">
            <p class="text-[10px] font-black text-p-muted uppercase tracking-[0.2em]">
                No se encontraron tablas en el sistema
            </p>
        </div>
    @endif
@endsection