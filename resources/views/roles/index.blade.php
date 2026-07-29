@extends('layouts.app')

@section('content')
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
.role-page { font-family: 'Inter', sans-serif; background: #f8fafc; min-height: 100vh; }
.card-custom { background: #fff; border-radius: 16px; border: 1px solid #e2e8f0; box-shadow: 0 4px 20px -4px rgba(15,23,42,0.05); }
.badge-perm { background: #f1f5f9; color: #334155; border: 1px solid #cbd5e1; font-weight: 600; padding: 3px 8px; border-radius: 6px; font-size: 11px; margin-bottom: 2px; display: inline-block; }
</style>

<div class="role-page p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold text-dark mb-1"><i class="bi bi-person-badge-fill text-primary me-2"></i>Roles & Permissions Management</h4>
            <p class="text-secondary small mb-0">Define security roles and assign granular module permissions</p>
        </div>
        <a href="{{ route('roles.create') }}" class="btn btn-primary btn-sm rounded-3 px-3 fw-semibold">
            <i class="bi bi-shield-plus me-1"></i> Create New Role
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show rounded-3 small" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show rounded-3 small" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card-custom p-0 overflow-hidden">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4 py-3 text-secondary text-uppercase fs-7 fw-bold" style="font-size:11px;width:60px;">ID</th>
                        <th class="py-3 text-secondary text-uppercase fs-7 fw-bold" style="font-size:11px;width:200px;">Role Name</th>
                        <th class="py-3 text-secondary text-uppercase fs-7 fw-bold" style="font-size:11px;">Assigned Permissions</th>
                        <th class="pe-4 py-3 text-end text-secondary text-uppercase fs-7 fw-bold" style="font-size:11px;width:150px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($roles as $role)
                    <tr>
                        <td class="ps-4 fw-bold text-muted" style="font-size:13px;">#{{ $role->id }}</td>
                        <td>
                            <div class="fw-bold text-dark" style="font-size:14px;">{{ $role->name }}</div>
                            <div class="small text-muted">{{ $role->permissions->count() }} permissions</div>
                        </td>
                        <td>
                            <div class="d-flex flex-wrap gap-1 py-1">
                                @if($role->name === 'Super Admin')
                                    <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 px-3 py-1 fw-bold rounded-pill" style="font-size:12px;">
                                        <i class="bi bi-shield-lock-fill me-1"></i> Full System Access (All Permissions)
                                    </span>
                                @else
                                    @forelse($role->permissions as $permission)
                                        <span class="badge-perm">{{ $permission->name }}</span>
                                    @empty
                                        <span class="text-muted small">No permissions assigned</span>
                                    @endforelse
                                @endif
                            </div>
                        </td>
                        <td class="pe-4 text-end">
                            <div class="d-inline-flex gap-1">
                                <a href="{{ route('roles.edit', $role->id) }}" class="btn btn-outline-primary btn-sm rounded-3 px-3 fw-semibold">
                                    <i class="bi bi-pencil me-1"></i> Edit
                                </a>
                                @if($role->name !== 'Super Admin')
                                    <form action="{{ route('roles.destroy', $role->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete role {{ $role->name }}?');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-outline-danger btn-sm rounded-3 px-2">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
