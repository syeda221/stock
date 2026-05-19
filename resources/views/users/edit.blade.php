@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4>Edit User</h4>
        <a href="{{ route('users.index') }}" class="btn btn-secondary">Back</a>
    </div>

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card">
        <div class="card-body">
            <form action="{{ route('users.update', $user->id) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="mb-3">
                    <label>Name</label>
                    <input type="text" name="name" class="form-control" value="{{ old('name', $user->name) }}" required>
                </div>

                <div class="mb-3">
                    <label>Email</label>
                    <input type="email" name="email" class="form-control" value="{{ old('email', $user->email) }}" required>
                </div>

                <div class="mb-3">
                    <label>Password <small class="text-muted">(Leave blank to keep current password)</small></label>
                    <input type="password" name="password" class="form-control">
                </div>
                
                <div class="mb-3">
                    <label>Roles</label>
                    <select name="roles[]" class="form-control" multiple {{ $user->hasRole('Super Admin') && $user->id == auth()->id() ? 'disabled' : '' }}>
                        @foreach($roles as $role)
                            <option value="{{ $role->name }}" {{ in_array($role->name, $userRoles) ? 'selected' : '' }}>
                                {{ $role->name }}
                            </option>
                        @endforeach
                    </select>
                    @if($user->hasRole('Super Admin') && $user->id == auth()->id())
                        <input type="hidden" name="roles[]" value="Super Admin">
                    @endif
                    <small class="text-muted">Hold Ctrl (Windows) or Command (Mac) to select multiple roles.</small>
                </div>

                <button type="submit" class="btn btn-primary">Update</button>
            </form>
        </div>
    </div>
</div>
@endsection
