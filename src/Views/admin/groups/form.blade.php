@extends('layouts.main')

@section('title', ($id ? \App\Core\Lang::get('groups.title_edit') : \App\Core\Lang::get('groups.title_new')))

@section('content')
    <header class="mb-12 text-center">
        <h1 class="text-4xl font-black text-p-title italic tracking-tighter mb-2">
            {{ $id ? \App\Core\Lang::get('groups.title_edit') : \App\Core\Lang::get('groups.title_new') }} <span
                class="text-primary italic">{{ \App\Core\Lang::get('groups.title_suffix') }}</span>
        </h1>
        <p class="text-p-muted font-medium">{{ \App\Core\Lang::get('groups.desc') }}</p>
    </header>

    <section class="max-w-2xl mx-auto">
        <form action="{{ $baseUrl }}admin/groups/save" method="POST" class="glass-card space-y-8">
            {!! $csrf_field !!}
            <input type="hidden" name="id" value="{{ $group['id'] ?? '' }}">

            <div>
                <label class="form-label">{{ \App\Core\Lang::get('groups.name') }}</label>
                <input type="text" name="name" value="{{ $group['name'] ?? '' }}" required
                    placeholder="{{ \App\Core\Lang::get('groups.name_placeholder') }}" class="form-input">
            </div>

            <div>
                <label class="form-label">{{ \App\Core\Lang::get('groups.description') }}</label>
                <textarea name="description" rows="4" placeholder="{{ \App\Core\Lang::get('groups.desc_placeholder') }}"
                    class="form-input">{{ $group['description'] ?? '' }}</textarea>
            </div>

            <div class="pt-6">
                <h3 class="text-sm font-black text-p-title uppercase mb-4">{{ \App\Core\Lang::get('groups.permissions') }}
                </h3>
                @php
                    $permissions = $group['permissions'] ?? [];
                @endphp
                @include('partials.policy_architect', ['permissions' => $permissions])
            </div>

            <div class="pt-8 border-t border-glass-border flex justify-end gap-6">
                <a href="{{ $baseUrl }}admin/groups" class="btn-outline">
                    {{ \App\Core\Lang::get('common.cancel') }}
                </a>
                <button type="submit" class="btn-primary">
                    {{ \App\Core\Lang::get('common.save') }}
                </button>
            </div>
        </form>
    </section>
@endsection