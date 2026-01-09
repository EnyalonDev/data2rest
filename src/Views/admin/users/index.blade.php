@extends('layouts.main')

@section('title', \App\Core\Lang::get('users_list.title'))

@section('content')
    <header class="mb-12 flex flex-col md:flex-row justify-between items-end gap-6">
        <div>
            <h1 class="text-5xl font-black text-p-title italic tracking-tighter mb-2">
                {{ \App\Core\Lang::get('users_list.title') }}
            </h1>
            <p class="text-p-muted font-medium">{{ \App\Core\Lang::get('users_list.subtitle') }}</p>
        </div>
        <div class="flex gap-4">
            @if(\App\Core\Auth::hasPermission('module:users.manage_roles'))
                <a href="{{ $baseUrl }}admin/roles"
                    class="btn-primary !bg-slate-800 !text-slate-300 !py-2">{{ \App\Core\Lang::get('users_list.access_policies') }}</a>
            @else
                <button onclick="showAccessDenied('{{ \App\Core\Lang::get('users_list.access_policies') }}')"
                    class="btn-primary !bg-slate-800 !text-slate-300 !py-2 opacity-50 cursor-pointer">{{ \App\Core\Lang::get('users_list.access_policies') }}</button>
            @endif

            @if(\App\Core\Auth::hasPermission('module:users.manage_groups'))
                <a href="{{ $baseUrl }}admin/groups"
                    class="btn-primary !bg-slate-800 !text-slate-300 !py-2">{{ \App\Core\Lang::get('common.groups') }}</a>
            @endif

            @if(\App\Core\Auth::hasPermission('module:users.invite_users'))
                <a href="{{ $baseUrl }}admin/users/new"
                    class="btn-primary !py-2">{{ \App\Core\Lang::get('users_list.create') }}</a>
            @endif
        </div>
    </header>

    <section class="glass-card overflow-hidden !p-0 shadow-2xl">
        <table class="w-full text-left">
            <thead>
                <tr
                    class="bg-black/5 dark:bg-black/40 text-[10px] font-black text-p-muted uppercase tracking-widest border-b border-p-border">
                    <th class="px-8 py-5">{{ \App\Core\Lang::get('users_list.identity') }}</th>
                    <th class="px-8 py-5">{{ \App\Core\Lang::get('users_list.role') }}</th>
                    <th class="px-8 py-5">{{ \App\Core\Lang::get('common.groups') }}</th>
                    <th class="px-8 py-5">{{ \App\Core\Lang::get('users_list.status') }}</th>
                    <th class="px-8 py-5 text-right">{{ \App\Core\Lang::get('common.actions') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-white/[0.03]">
                @foreach ($users as $u)
                    <tr class="hover:bg-white/[0.02] transition-colors group">
                        <td class="px-8 py-6">
                            <div class="flex items-center gap-4">
                                <div
                                    class="w-10 h-10 rounded-full bg-gradient-to-tr from-primary to-blue-600 flex items-center justify-center text-dark font-black">
                                    {{ strtoupper(substr($u['username'], 0, 1)) }}
                                </div>
                                <div>
                                    <p class="font-bold text-p-title">{{ $u['username'] }}</p>
                                    <p class="text-[10px] text-p-muted uppercase font-black">
                                        {{ \App\Core\Lang::get('users_list.node_id') }}: #{{ $u['id'] }}
                                    </p>
                                </div>
                            </div>
                        </td>
                        <td class="px-8 py-6">
                            <div class="flex flex-col">
                                <span
                                    class="text-xs font-bold text-p-muted dark:text-slate-300">{{ $u['role_name'] ?? \App\Core\Lang::get('common.none') }}</span>
                                <span
                                    class="text-[9px] text-p-muted uppercase font-black tracking-widest">{{ \App\Core\Lang::get('users_list.policy_level') }}</span>
                            </div>
                        </td>
                        <td class="px-8 py-6">
                            <div class="flex flex-col">
                                <span
                                    class="text-xs font-bold text-p-muted dark:text-slate-300">{{ $u['group_name'] ?? \App\Core\Lang::get('common.none') }}</span>
                            </div>
                        </td>
                        <td class="px-8 py-6">
                            @if ($u['status'])
                                <span class="text-emerald-500 flex items-center gap-2 text-[10px] font-black uppercase">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                    {{ \App\Core\Lang::get('users_list.authorized') }}
                                </span>
                            @else
                                <span class="text-red-500 flex items-center gap-2 text-[10px] font-black uppercase">
                                    <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>
                                    {{ \App\Core\Lang::get('users_list.revoked') }}
                                </span>
                            @endif
                        </td>
                        <td class="px-8 py-6 text-right">
                            <div class="flex justify-end gap-3 opacity-60 group-hover:opacity-100 transition-opacity">
                                @if(\App\Core\Auth::hasPermission('module:users.edit_users'))
                                    <a href="{{ $baseUrl }}admin/users/edit?id={{ $u['id'] }}"
                                        class="text-p-muted hover:text-primary p-2">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z">
                                            </path>
                                        </svg>
                                    </a>
                                @endif
                                @if ($u['id'] != $_SESSION['user_id'] && \App\Core\Auth::hasPermission('module:users.delete_users'))
                                    <button onclick="confirmDeleteUser({{ $u['id'] }}, '{{ addslashes($u['username']) }}')"
                                        class="text-p-muted hover:text-red-500 p-2">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                            </path>
                                        </svg>
                                    </button>
                                @endif
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
        function confirmDeleteUser(id, name) {
            showModal({
                title: '{!! addslashes(\App\Core\Lang::get('users_list.delete_confirm_title')) !!}',
                message: `{!! addslashes(\App\Core\Lang::get('users_list.delete_confirm_msg')) !!}`.replace(':name', name),
                type: 'confirm',
                typeLabel: '{!! addslashes(\App\Core\Lang::get('users_list.delete_confirm_btn')) !!}',
                onConfirm: () => {
                    window.location.href = `{{ $baseUrl }}admin/users/delete?id=${id}`;
                }
            });
        }

        function showAccessDenied(action) {
            showModal({
                title: '{!! addslashes(\App\Core\Lang::get('system_modal.access_restricted')) !!}',
                message: '{!! addslashes(\App\Core\Lang::get('system_modal.access_restricted_msg')) !!}'.replace(':action', action),
                type: 'alert',
                typeLabel: 'OK'
            });
        }
    </script>
@endsection