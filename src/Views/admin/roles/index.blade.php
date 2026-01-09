@extends('layouts.main')

@section('title', \App\Core\Lang::get('roles_list.title'))

@section('content')
    <header class="mb-12 flex flex-col md:flex-row justify-between items-end gap-6">
        <div>
            <h1 class="text-5xl font-black text-p-title italic tracking-tighter mb-2">
                {{ \App\Core\Lang::get('roles_list.title') }}
            </h1>
            <p class="text-p-muted font-medium">{{ \App\Core\Lang::get('roles_list.subtitle') }}</p>
        </div>
        <div class="flex gap-4">
            <a href="{{ $baseUrl }}admin/users"
                class="btn-primary !bg-slate-800 !text-slate-300 !py-2">{{ \App\Core\Lang::get('roles_list.back') }}</a>
            <a href="{{ $baseUrl }}admin/roles/new"
                class="btn-primary !py-2">{{ \App\Core\Lang::get('roles_list.new') }}</a>
        </div>
    </header>

    <section class="glass-card overflow-hidden !p-0 shadow-2xl">
        <table class="w-full text-left">
            <thead>
                <tr
                    class="bg-black/5 dark:bg-black/40 text-[10px] font-black text-p-muted uppercase tracking-widest border-b border-p-border">
                    <th class="px-8 py-5">{{ \App\Core\Lang::get('roles_list.name') }}</th>
                    <th class="px-8 py-5">{{ \App\Core\Lang::get('common.status') }}</th>
                    <th class="px-8 py-5 text-right">{{ \App\Core\Lang::get('common.actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/[0.03]">
                @foreach ($roles as $r)
                    @php
                        $perms = json_decode($r['permissions'] ?? '[]', true);
                        $isAdmin = isset($perms['all']) && $perms['all'] === true;
                    @endphp
                    <tr class="hover:bg-white/[0.02] transition-colors group">
                        <td class="px-8 py-6">
                            <div class="flex items-center gap-4">
                                <div
                                    class="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center text-primary border border-primary/20">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z">
                                        </path>
                                    </svg>
                                </div>
                                <div>
                                    <p class="font-bold text-p-title">{{ $r['name'] }}</p>
                                    <p class="text-[9px] text-p-muted font-black uppercase">
                                        {{ $isAdmin ? \App\Core\Lang::get('roles_list.full_access') : \App\Core\Lang::get('roles_list.custom_policy') }}
                                    </p>
                                </div>
                            </div>
                        </td>
                        <td class="px-8 py-6">
                            @if ($isAdmin)
                                <span
                                    class="bg-red-500/10 text-red-500 border border-red-500/20 px-2 py-0.5 rounded text-[9px] font-black uppercase tracking-widest">{{ \App\Core\Lang::get('roles_list.system_master') }}</span>
                            @else
                                <span
                                    class="bg-emerald-500/10 text-emerald-500 border border-emerald-500/20 px-2 py-0.5 rounded text-[9px] font-black uppercase tracking-widest">{{ \App\Core\Lang::get('roles_list.active_policy') }}</span>
                            @endif
                        </td>
                        <td class="px-8 py-6 text-right">
                            <div class="flex justify-end gap-3 opacity-60 group-hover:opacity-100 transition-opacity">
                                <a href="{{ $baseUrl }}admin/roles/edit?id={{ $r['id'] }}"
                                    class="p-2 bg-p-bg dark:bg-white/5 rounded-lg text-p-muted hover:text-primary hover:bg-primary/10 transition-all shadow-sm hover:shadow-md">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                                        </path>
                                    </svg>
                                </a>
                                <button onclick="confirmDeleteRole({{ $r['id'] }}, '{{ addslashes($r['name']) }}')"
                                    class="p-2 bg-p-bg dark:bg-white/5 rounded-lg text-p-muted hover:text-red-500 hover:bg-red-500/10 transition-all shadow-sm hover:shadow-md">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                        </path>
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </section>
@endsection

@section('scripts')
    <script>
        function confirmDeleteRole(id, name) {
            showModal({
                title: '{!! addslashes(\App\Core\Lang::get('roles_list.delete_confirm_title')) !!}',
                message: `{!! addslashes(\App\Core\Lang::get('roles_list.delete_confirm_msg')) !!}`.replace(':name', name),
                type: 'confirm',
                typeLabel: '{!! addslashes(\App\Core\Lang::get('roles_list.delete_confirm_btn')) !!}',
                onConfirm: () => {
                    window.location.href = `{{ $baseUrl }}admin/roles/delete?id=${id}`;
                }
            });
        }
    </script>
@endsection