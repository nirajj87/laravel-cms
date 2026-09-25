<form method="POST" action="{{ $action }}" class="card max-w-xl p-5">
    @csrf
    @if ($member->exists)
        @method('PUT')
    @endif
    <x-field label="Name" name="name">
        <input class="field" name="name" value="{{ old('name', $member->name) }}" required>
    </x-field>
    <x-field label="Email" name="email">
        <input class="field" type="email" name="email" value="{{ old('email', $member->email) }}" required>
    </x-field>
    <x-field label="Phone" name="phone">
        <input class="field" name="phone" value="{{ old('phone', $member->phone) }}">
    </x-field>
    <x-field label="Role" name="role_id">
        <select class="field" name="role_id" required>
            @foreach ($roles as $role)
                <option value="{{ $role->id }}" @selected((string) old('role_id', $member->roles->first()?->id) === (string) $role->id)>{{ $role->name }}</option>
            @endforeach
        </select>
    </x-field>
    <x-field label="Status" name="status">
        <select class="field" name="status">
            @foreach (\App\Enums\UserStatus::cases() as $status)
                <option value="{{ $status->value }}" @selected(old('status', $member->status?->value ?? 'active') === $status->value)>{{ $status->label() }}</option>
            @endforeach
        </select>
    </x-field>
    <x-field label="{{ $member->exists ? 'New password' : 'Password' }}" name="password">
        <input class="field" type="password" name="password" {{ $member->exists ? '' : 'required' }}>
    </x-field>
    <x-field label="Confirm password" name="password_confirmation">
        <input class="field" type="password" name="password_confirmation" {{ $member->exists ? '' : 'required' }}>
    </x-field>
    <button class="btn btn-primary" type="submit">{{ $member->exists ? 'Save user' : 'Add user' }}</button>
</form>
