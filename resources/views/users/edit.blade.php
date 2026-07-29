@extends('layouts.app')

@section('content')
<div class="container-fluid p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold text-dark mb-1">Edit User: {{ $user->name }}</h4>
            <p class="text-secondary small mb-0">Modify user profile, shift assignment, and role permissions</p>
        </div>
        <a href="{{ route('users.index') }}" class="btn btn-outline-secondary btn-sm rounded-3 px-3 fw-semibold">
            <i class="bi bi-arrow-left me-1"></i> Back to Users
        </a>
    </div>

    <div class="card border-0 shadow-sm rounded-4 col-12 col-md-8 col-lg-6 mx-auto">
        <div class="card-body p-4">
            <form action="{{ route('users.update', $user->id) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label class="form-label fw-semibold small">Full Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control rounded-3" value="{{ old('name', $user->name) }}" required>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold small">Email Address <span class="text-danger">*</span></label>
                    <input type="email" name="email" class="form-control rounded-3" value="{{ old('email', $user->email) }}" required>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold small">Password <span class="text-muted fw-normal">(Leave blank to keep unchanged)</span></label>
                    <input type="password" name="password" class="form-control rounded-3" placeholder="New password">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold small">Assigned Shift (Time Restriction)</label>
                    <select name="shift_id" class="form-select rounded-3">
                        <option value="">-- No Shift Restriction (24/7 Access) --</option>
                        @foreach($shifts as $s)
                            <option value="{{ $s->id }}" {{ old('shift_id', $user->shift_id) == $s->id ? 'selected' : '' }}>
                                {{ $s->formatted_shift }}
                            </option>
                        @endforeach
                    </select>
                    <div class="form-text small text-muted">User will ONLY be able to log in during this shift timeframe.</div>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-semibold small">Assign Role(s)</label>
                    <div class="d-flex flex-wrap gap-2">
                        @foreach($roles as $role)
                            <div class="form-check border rounded-3 px-3 py-2 bg-light">
                                <input class="form-check-input" type="checkbox" name="roles[]" value="{{ $role->name }}" id="role_{{ $role->id }}"
                                    {{ in_array($role->name, $userRoles) ? 'checked' : '' }}>
                                <label class="form-check-input-label fw-semibold small cursor-pointer" for="role_{{ $role->id }}">
                                    {{ $role->name }}
                                </label>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ route('users.index') }}" class="btn btn-light rounded-3 px-4">Cancel</a>
                    <button type="submit" class="btn btn-primary rounded-3 px-4"><i class="bi bi-save me-1"></i> Update User</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
