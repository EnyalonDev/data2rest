<?php use App\Core\Auth;
use App\Core\Lang; ?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <!-- Header -->
    <header class="text-center mb-16 relative">
        <div class="absolute -top-20 left-1/2 -translate-x-1/2 w-96 h-96 bg-primary/10 blur-[120px] rounded-full -z-10">
        </div>
        <div
            class="inline-block bg-primary text-dark px-4 py-1 rounded-full text-[10px] font-black uppercase tracking-[0.2em] mb-6 animate-pulse">
            Portal de Acceso
        </div>
        <h1 class="text-5xl md:text-7xl font-black text-p-title mb-6 tracking-tighter uppercase italic">
            Mis Proyectos
        </h1>
        <p class="text-p-muted font-medium max-w-2xl mx-auto">Selecciona el entorno de trabajo al que deseas acceder
            para gestionar tus datos e infraestructura.</p>

        <?php if (Auth::isAdmin()): ?>
            <div class="mt-8 flex flex-wrap justify-center gap-4">
                <a href="<?php echo $baseUrl; ?>admin/projects/new"
                    class="btn-primary !py-3.5 !px-8 text-[11px] font-black uppercase tracking-widest flex items-center gap-3 shadow-xl shadow-primary/20 hover:scale-105 transition-all">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                    </svg>
                    Nuevo Proyecto
                </a>
                <a href="<?php echo $baseUrl; ?>admin/projects"
                    class="px-8 py-3.5 bg-p-card border border-glass-border rounded-xl text-[11px] font-black uppercase tracking-widest text-p-title hover:text-white hover:bg-primary/10 hover:border-primary/50 transition-all flex items-center gap-3 shadow-xl">
                    <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                            d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                        <circle cx="12" cy="12" r="3" stroke-width="2.5" />
                    </svg>
                    Gestionar Proyectos
                </a>
            </div>
        <?php endif; ?>
    </header>

    <!-- Search Section -->
    <div class="mb-16 relative max-w-xl mx-auto">
        <input type="text" id="project-search" placeholder="Buscar proyecto por nombre..."
            class="form-input !pl-16 !py-6 shadow-2xl shadow-primary/5 focus:shadow-primary/20 transition-all text-base font-bold bg-p-card/50 backdrop-blur-md rounded-2xl border-glass-border focus:border-primary/50">
        <div class="absolute left-6 top-1/2 -translate-y-1/2 text-primary opacity-60">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
        </div>
    </div>

    <!-- Projects Grid -->
    <div id="projects-container" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-10 mb-24">
        <?php foreach ($projects as $p): ?>
            <div class="project-card group" data-name="<?php echo strtolower(htmlspecialchars($p['name'])); ?>">
                <div
                    class="relative h-full flex flex-col bg-p-card border border-glass-border rounded-[2rem] overflow-hidden hover:border-primary/40 hover:shadow-2xl hover:shadow-primary/10 transition-all duration-500 hover:-translate-y-2">

                    <!-- Top Section -->
                    <div class="p-8 pb-4 flex justify-between items-start">
                        <div
                            class="w-14 h-14 bg-primary/10 rounded-2xl flex items-center justify-center text-primary group-hover:scale-110 transition-transform duration-500 text-2xl">
                            📁
                        </div>
                        <div class="flex flex-col items-end gap-2">
                            <span
                                class="text-[9px] font-black uppercase tracking-widest text-primary px-3 py-1 bg-primary/10 rounded-full border border-primary/20">
                                <?php echo $p['plan_type'] ?? 'Free'; ?>
                            </span>
                            <?php if ($p['id'] == $active_project_id): ?>
                                <div
                                    class="flex items-center gap-2 px-3 py-1 bg-emerald-500/10 rounded-full border border-emerald-500/20">
                                    <span
                                        class="text-[9px] font-black uppercase tracking-widest text-emerald-500 italic">Activo</span>
                                    <div class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Content Section -->
                    <div class="p-8 pt-4 flex-grow">
                        <h3
                            class="text-2xl font-black text-p-title mb-3 tracking-tight group-hover:text-primary transition-colors">
                            <?php echo htmlspecialchars($p['name']); ?>
                        </h3>
                        <p class="text-sm text-p-muted font-medium leading-relaxed">
                            <?php echo htmlspecialchars($p['description'] ?: 'Infraestructura de datos aislada para este proyecto.'); ?>
                        </p>
                    </div>

                    <!-- Actions Section -->
                    <div class="p-8 pt-0 flex gap-3 mt-auto">
                        <a href="<?php echo $baseUrl; ?>admin/projects/switch?id=<?php echo $p['id']; ?>"
                            class="flex-grow py-4 bg-primary text-dark rounded-xl text-center text-xs font-black uppercase tracking-widest hover:bg-white transition-all shadow-lg shadow-primary/10">
                            Entrar al Proyecto &rarr;
                        </a>
                        <?php if (Auth::isAdmin()): ?>
                            <a href="<?php echo $baseUrl; ?>admin/projects/edit?id=<?php echo $p['id']; ?>"
                                class="p-4 bg-white/5 border border-white/10 rounded-xl text-p-muted hover:text-primary hover:border-primary/50 transition-all shadow-lg"
                                title="Configurar Proyecto">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                        d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                    <circle cx="12" cy="12" r="3" stroke-width="2.5" />
                                </svg>
                            </a>
                        <?php endif; ?>
                    </div>

                    <!-- Decoration Background -->
                    <?php if ($p['id'] == $active_project_id): ?>
                        <div
                            class="absolute -bottom-10 -right-10 w-40 h-40 bg-emerald-500/10 blur-[60px] rounded-full -z-10 animate-pulse">
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Empty State -->
    <div id="no-results"
        class="hidden text-center py-24 bg-p-card border border-dashed border-glass-border rounded-[3rem]">
        <div class="text-8-xl mb-6 opacity-20">📂</div>
        <h3 class="text-3xl font-black text-p-title mb-2">Proyecto no encontrado</h3>
        <p class="text-p-muted font-medium">No hay proyectos que coincidan con tu búsqueda.</p>
        <button
            onclick="document.getElementById('project-search').value=''; document.getElementById('project-search').dispatchEvent(new Event('input'))"
            class="mt-8 text-primary font-black uppercase tracking-widest text-[10px] hover:underline">
            Limpiar Búsqueda
        </button>
    </div>
</div>

<script>
    document.getElementById('project-search').addEventListener('input', function (e) {
        const term = e.target.value.toLowerCase().trim();
        const cards = document.querySelectorAll('.project-card');
        const container = document.getElementById('projects-container');
        const noResults = document.getElementById('no-results');
        let visibleCount = 0;

        cards.forEach(card => {
            const name = card.getAttribute('data-name');
            if (name.includes(term)) {
                card.style.display = 'block';
                visibleCount++;
            } else {
                card.style.display = 'none';
            }
        });

        if (visibleCount === 0) {
            container.style.display = 'none';
            noResults.classList.remove('hidden');
        } else {
            container.style.display = 'grid';
            noResults.classList.add('hidden');
        }
    });
</script>