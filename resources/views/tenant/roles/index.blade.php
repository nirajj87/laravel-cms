@extends('layouts.app')

@section('title', 'Roles')
@section('kicker', 'Administration')
@section('heading', 'Roles')

@section('content')
    <div class="mb-4 flex items-center justify-between gap-3">
        <p class="text-sm text-slate-600">System roles ship with each workspace. Custom roles can be added for future teams.</p>
        @permission('roles.create')
            <a class="btn btn-primary" href="{{ route('tenant.roles.create') }}">Add role</a>
        @endpermission
    </div>
    <div class="card overflow-hidden">
        <table class="min-w-full text-left text-sm">
            <thead class="bg-stone-50 text-slate-500">
                <tr>
                    <th class="px-4 py-3 font-medium">Role</th>
                    <th class="px-4 py-3 font-medium">People</th>
                    <th class="px-4 py-3 font-medium">Permissions</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-stone-100">
                @foreach ($roles as $role)
                    <tr>
                        <td class="px-4 py-3">
                            <p class="font-medium">{{ $role->name }}</p>
                            <p class="text-xs text-slate-500">{{ $role->description }}</p>
                        </td>
                        <td class="px-4 py-3">{{ $role->users_count }}</td>
                        <td class="px-4 py-3">{{ $role->permissions_count }}</td>
                        <td class="px-4 py-3 text-right">
                            @permission('roles.edit')
                                <a class="font-medium text-teal-800" href="{{ route('tenant.roles.edit', $role) }}">Edit</a>
                            @endpermission
                            @if (! $role->is_system)
                                @permission('roles.delete')
                                    <form class="inline" method="POST" action="{{ route('tenant.roles.destroy', $role) }}" onsubmit="return confirm('Delete this role?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="ml-3 text-red-700" type="submit">Delete</button>
                                    </form>
                                @endpermission
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
