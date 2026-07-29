@extends('layouts.app')

@section('content')
<div class="container-fluid p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold text-dark mb-1">Edit Role: {{ $role->name }}</h4>
            <p class="text-secondary small mb-0">Modify role title and permission access per module</p>
        </div>
        <a href="{{ route('roles.index') }}" class="btn btn-outline-secondary btn-sm rounded-3 px-3 fw-semibold">
            <i class="bi bi-arrow-left me-1"></i> Back to Roles
        </a>
    </div>

    <form action="{{ route('roles.update', $role->id) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-body p-4">
                <div class="mb-3 col-12 col-md-6">
                    <label class="form-label fw-semibold small">Role Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control rounded-3" value="{{ old('name', $role->name) }}" required {{ $role->name === 'Super Admin' ? 'readonly' : '' }}>
                </div>
            </div>
        </div>

        <h5 class="fw-bold text-dark mb-3">Module Permissions</h5>

        <div class="row g-3 mb-4">
            @foreach($groupedPermissions as $moduleName => $perms)
            <div class="col-12 col-md-6 col-lg-4">
                <div class="card border-0 shadow-sm rounded-4 h-100">
                    <div class="card-header bg-light border-0 rounded-top-4 py-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <h6 class="fw-bold text-dark mb-0" style="font-size:13.5px;">{{ $moduleName }}</h6>
                            <button type="button" class="btn btn-link btn-sm p-0 text-decoration-none text-primary fw-semibold" onclick="toggleModuleCheckboxes(this)">Toggle All</button>
                        </div>
                    </div>
                    <div class="card-body p-3">
                        @foreach($perms as $permission)
                        <div class="form-check mb-2">
                            <input class="form-check-input module-checkbox" type="checkbox" name="permissions[]" value="{{ $permission->name }}" id="perm_{{ $permission->id }}"
                                {{ in_array($permission->name, $rolePermissions) ? 'checked' : '' }}>
                            <label class="form-check-label small cursor-pointer text-dark fw-medium" for="perm_{{ $permission->id }}">
                                {{ str_replace('-', ' ', ucfirst($permission->name)) }}
                            </label>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        <div class="d-flex justify-content-end gap-2 mb-5">
            <a href="{{ route('roles.index') }}" class="btn btn-light rounded-3 px-4">Cancel</a>
            <button type="submit" class="btn btn-primary rounded-3 px-4"><i class="bi bi-save me-1"></i> Update Role</button>
        </div>
    </form>
</div>

<script>
function toggleModuleCheckboxes(btn) {
    const card = btn.closest('.card');
    const checkboxes = card.querySelectorAll('.module-checkbox');
    const allChecked = Array.from(checkboxes).every(cb => cb.checked);
    checkboxes.forEach(cb => cb.checked = !allChecked);
}
</script>
@endsection
