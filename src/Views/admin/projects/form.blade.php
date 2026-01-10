@extends('layouts.main')

@section('title', ($project ? \App\Core\Lang::get('projects.edit') : \App\Core\Lang::get('projects.new')))

@section('content')
    <div class="max-w-4xl mx-auto animate-in fade-in slide-in-from-bottom-4 duration-700">
        <div class="mb-8">
            <h1 class="text-4xl font-black text-p-title tracking-tight mb-2">
                {{ $project ? \App\Core\Lang::get('projects.edit') : \App\Core\Lang::get('projects.new') }}
            </h1>
            <p class="text-p-muted font-medium">{{ \App\Core\Lang::get('projects.form_subtitle') }}</p>
        </div>

        <form action="{{ $baseUrl }}admin/projects/save" method="POST" class="space-y-6">
            {!! $csrf_field !!}
            @if ($project)
                <input type="hidden" name="id" value="{{ $project['id'] }}">
            @endif

            <div class="glass-card">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                    <!-- Project Name -->
                    <div class="col-span-2">
                        <label class="form-label mb-2">{{ \App\Core\Lang::get('projects.name') }}</label>
                        <input type="text" name="name" value="{{ $project['name'] ?? '' }}" class="form-input"
                            placeholder="{{ \App\Core\Lang::get('projects.name_placeholder') }}" required>
                    </div>

                    <!-- Description -->
                    <div class="col-span-2">
                        <label class="form-label mb-2">{{ \App\Core\Lang::get('projects.description') }}</label>
                        <textarea name="description" rows="3" class="form-input resize-none"
                            placeholder="{{ \App\Core\Lang::get('projects.desc_placeholder') }}">{{ $project['description'] ?? '' }}</textarea>
                    </div>

                    <!-- Plan Selection -->
                    <div>
                        <label class="form-label mb-2">{{ \App\Core\Lang::get('projects.plan') }}</label>
                        <select name="plan_type" class="form-input">
                            <option value="monthly" {{ ($project['plan_type'] ?? '') == 'monthly' ? 'selected' : '' }}>
                                {{ \App\Core\Lang::get('projects.monthly_plan') }}
                            </option>
                            <option value="quarterly" {{ ($project['plan_type'] ?? '') == 'quarterly' ? 'selected' : '' }}>
                                {{ \App\Core\Lang::get('projects.quarterly_plan') }}
                            </option>
                            <option value="semiannual" {{ ($project['plan_type'] ?? '') == 'semiannual' ? 'selected' : '' }}>
                                {{ \App\Core\Lang::get('projects.semiannual_plan') }}
                            </option>
                            <option value="annual" {{ ($project['plan_type'] ?? '') == 'annual' ? 'selected' : '' }}>
                                {{ \App\Core\Lang::get('projects.annual_plan') }}
                            </option>
                        </select>
                    </div>

                    <!-- Start Date -->
                    <div>
                        <label class="form-label mb-2">{{ \App\Core\Lang::get('projects.activation_date') }}</label>
                        <input type="datetime-local" name="start_date"
                            value="{{ date('Y-m-d\TH:i', strtotime($project['start_date'] ?? 'now')) }}" class="form-input">
                    </div>
                </div>

                <!-- Team Management Refactor -->
                <div class="mb-12">
                    <div class="flex items-center justify-between mb-8 border-b border-glass-border pb-4">
                        <h3 class="text-xs font-black text-p-muted uppercase tracking-[0.3em] flex items-center gap-3">
                            <span class="w-8 h-[1px] bg-slate-800"></span> {{ \App\Core\Lang::get('common.team') }}
                        </h3>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-10">
                        <!-- Column 1: Assigned Users -->
                        <div class="space-y-6">
                            <div class="flex items-center gap-2 mb-2">
                                <span
                                    class="px-2 py-0.5 bg-emerald-500/10 text-emerald-500 text-[10px] font-black uppercase rounded-md border border-emerald-500/20">{{ \App\Core\Lang::get('projects.assigned') }}</span>
                                <h4 class="text-sm font-bold text-p-title">
                                    {{ \App\Core\Lang::get('projects.users_with_access') }}
                                </h4>
                            </div>

                            <div id="assigned-users-list"
                                class="space-y-3 min-h-[200px] p-4 bg-black/20 rounded-2xl border border-dashed border-glass-border">
                                <!-- JS will populate this -->
                            </div>
                        </div>

                        <!-- Column 2: Search & Add -->
                        <div class="space-y-6">
                            <div class="flex items-center gap-2 mb-2">
                                <span
                                    class="px-2 py-0.5 bg-primary/10 text-primary text-[10px] font-black uppercase rounded-md border border-primary/20">{{ \App\Core\Lang::get('common.search') }}</span>
                                <h4 class="text-sm font-bold text-p-title">
                                    {{ \App\Core\Lang::get('projects.search_members') }}
                                </h4>
                            </div>

                            <div class="relative">
                                <input type="text" id="user-search-input"
                                    placeholder="{{ addslashes(\App\Core\Lang::get('common.search')) }}..."
                                    class="form-input !pl-12 !py-4 text-sm font-bold bg-white/5 border-glass-border focus:border-primary/50 transition-all">
                                <div class="absolute left-4 top-1/2 -translate-y-1/2 text-p-muted opacity-50">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                </div>
                            </div>

                            <div id="search-results-list"
                                class="space-y-3 max-h-[300px] overflow-y-auto custom-scrollbar p-1">
                                <!-- JS will populate results -->
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Hidden Inputs Container for POST data -->
                <div id="user-ids-inputs"></div>

                <div class="bg-primary/5 border border-primary/20 rounded-2xl p-6 mb-8 flex items-start gap-4">
                    <div class="p-2 bg-primary/20 rounded-lg text-primary">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="text-xs leading-relaxed text-p-muted font-medium">
                        <strong
                            class="text-p-text block mb-1 uppercase tracking-widest">{{ \App\Core\Lang::get('projects.about_subscriptions') }}</strong>
                        {{ \App\Core\Lang::get('projects.subscription_info') }}
                    </div>
                </div>

                <div class="flex items-center gap-4 pt-4">
                    <button type="submit"
                        class="px-8 py-4 bg-primary text-dark rounded-xl font-black uppercase tracking-widest shadow-xl shadow-primary/20 hover:shadow-primary/40 hover:-translate-y-1 transition-all">
                        {{ $project ? \App\Core\Lang::get('common.save') : \App\Core\Lang::get('common.commit') }}
                    </button>
                    <a href="{{ $baseUrl }}admin/projects"
                        class="px-8 py-4 bg-white/5 text-p-muted border border-glass-border rounded-xl font-black uppercase tracking-widest hover:bg-red-500/10 hover:text-red-500 transition-all">
                        {{ \App\Core\Lang::get('common.cancel') }}
                    </a>
                </div>
            </div>
        </form>
    </div>
@endsection

@section('scripts')
    <script>
        // Initial data from PHP/Blade
        const allUsers = {!! json_encode($users) !!};
        let assignedUserIds = {!! json_encode($project['user_ids'] ?? []) !!};

        function renderLists() {
            const assignedContainer = document.getElementById('assigned-users-list');
            const searchResultsContainer = document.getElementById('search-results-list');
            const inputsContainer = document.getElementById('user-ids-inputs');
            const searchInput = document.getElementById('user-search-input');
            if (!assignedContainer || !searchResultsContainer || !inputsContainer || !searchInput) return;

            const searchTerm = searchInput.value.toLowerCase().trim();

            assignedContainer.innerHTML = '';
            searchResultsContainer.innerHTML = '';
            inputsContainer.innerHTML = '';

            // 1. Render Assigned
            if (assignedUserIds.length === 0) {
                assignedContainer.innerHTML = `
                        <div class="text-center py-10 opacity-30 italic text-xs">
                            {!! addslashes(\App\Core\Lang::get('projects.no_users_assigned')) !!}
                        </div>
                    `;
            }

            allUsers.forEach(user => {
                const isAssigned = assignedUserIds.includes(user.id.toString()) || assignedUserIds.includes(parseInt(user.id));

                if (isAssigned) {
                    // Render in assigned list
                    const card = document.createElement('div');
                    card.className = 'flex items-center justify-between p-4 bg-white/5 border border-glass-border rounded-xl hover:border-red-500/30 transition-all group';
                    card.innerHTML = `
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-emerald-500/10 flex items-center justify-center text-emerald-500 font-bold text-xs capitalize">
                                    ${user.username.charAt(0)}
                                </div>
                                <span class="text-sm font-bold text-p-title capitalize">${user.username}</span>
                            </div>
                            <button type="button" onclick="removeUserAccess('${user.id}', \`${user.username}\`)" 
                                    class="p-2 text-p-muted hover:text-red-500 hover:bg-red-500/10 rounded-lg transition-all opacity-0 group-hover:opacity-100">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        `;
                    assignedContainer.appendChild(card);

                    // Add hidden input for form submission
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'user_ids[]';
                    input.value = user.id;
                    inputsContainer.appendChild(input);

                } else {
                    // Check if matches search
                    if (searchTerm === '' || user.username.toLowerCase().includes(searchTerm)) {
                        const card = document.createElement('div');
                        card.className = 'flex items-center justify-between p-4 bg-white/5 border border-glass-border rounded-xl hover:border-primary/50 transition-all group cursor-pointer';
                        card.onclick = () => assignUser(user.id);
                        card.innerHTML = `
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center text-primary font-bold text-xs capitalize">
                                        ${user.username.charAt(0)}
                                    </div>
                                    <span class="text-sm font-bold text-p-title capitalize">${user.username}</span>
                                </div>
                                <span class="text-[9px] font-black uppercase tracking-widest text-primary opacity-0 group-hover:opacity-100 transition-all">{!! addslashes(\App\Core\Lang::get('common.add')) !!} +</span>
                            `;
                        searchResultsContainer.appendChild(card);
                    }
                }
            });
        }

        function assignUser(id) {
            if (!assignedUserIds.includes(id.toString()) && !assignedUserIds.includes(parseInt(id))) {
                assignedUserIds.push(id.toString());
                renderLists();
            }
        }

        function removeUserAccess(id, username) {
            showModal({
                title: '{!! addslashes(\App\Core\Lang::get('projects.remove_access_confirm_title')) !!}',
                message: `{!! addslashes(\App\Core\Lang::get('projects.remove_access_confirm_msg')) !!}`.replace(':name', username.toUpperCase()),
                type: 'confirm',
                confirmText: '{!! addslashes(\App\Core\Lang::get('projects.remove_access_btn')) !!}',
                onConfirm: () => {
                    assignedUserIds = assignedUserIds.filter(userId => userId.toString() !== id.toString() && parseInt(userId) !== parseInt(id));
                    renderLists();
                }
            });
        }

        document.addEventListener('DOMContentLoaded', () => {
            const searchInput = document.getElementById('user-search-input');
            if (searchInput) {
                searchInput.addEventListener('input', renderLists);
            }
            renderLists();
        });
    </script>
@endsection