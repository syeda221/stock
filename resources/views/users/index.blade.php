@extends('layouts.app')

@section('content')
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
.user-page { font-family: 'Inter', sans-serif; background: #f8fafc; min-height: 100vh; }
.card-custom { background: #fff; border-radius: 16px; border: 1px solid #e2e8f0; box-shadow: 0 4px 20px -4px rgba(15,23,42,0.05); }
.badge-active { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; font-weight: 600; padding: 4px 10px; border-radius: 20px; font-size: 11.5px; }
.badge-suspended { background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; font-weight: 600; padding: 4px 10px; border-radius: 20px; font-size: 11.5px; }
.badge-role { background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; font-weight: 600; padding: 3px 9px; border-radius: 6px; font-size: 11px; margin-right: 3px; }
.badge-shift { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; font-weight: 600; padding: 3px 9px; border-radius: 6px; font-size: 11px; }
.btn-action { padding: 5px 12px; font-size: 12px; font-weight: 600; border-radius: 8px; transition: all 0.2s; }
</style>

<div class="user-page p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold text-dark mb-1"><i class="bi bi-people-fill text-primary me-2"></i>User Management & Security</h4>
            <p class="text-secondary small mb-0">Control user accounts, assigned shifts, access status & security roles</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('shifts.index') }}" class="btn btn-outline-secondary btn-sm rounded-3 px-3 fw-semibold">
                <i class="bi bi-clock-history me-1"></i> Manage Shifts
            </a>
            <a href="{{ route('login-logs.index') }}" class="btn btn-outline-dark btn-sm rounded-3 px-3 fw-semibold">
                <i class="bi bi-shield-check me-1"></i> Login Audit Log
            </a>
            <a href="{{ route('users.create') }}" class="btn btn-primary btn-sm rounded-3 px-3 fw-semibold">
                <i class="bi bi-person-plus-fill me-1"></i> Create User
            </a>
        </div>
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

    <div class="card-custom">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4 py-3 text-secondary text-uppercase fs-7 fw-bold" style="font-size:11px;">User Name</th>
                        <th class="py-3 text-secondary text-uppercase fs-7 fw-bold" style="font-size:11px;">Email</th>
                        <th class="py-3 text-secondary text-uppercase fs-7 fw-bold" style="font-size:11px;">Role(s)</th>
                        <th class="py-3 text-secondary text-uppercase fs-7 fw-bold" style="font-size:11px;">Assigned Shift</th>
                        <th class="py-3 text-secondary text-uppercase fs-7 fw-bold" style="font-size:11px;">Status</th>
                        <th class="py-3 text-secondary text-uppercase fs-7 fw-bold" style="font-size:11px;">Last Activity</th>
                        <th class="pe-4 py-3 text-end text-secondary text-uppercase fs-7 fw-bold" style="font-size:11px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($users as $u)
                    <tr>
                        <td class="ps-4">
                            <div class="d-flex align-items-center gap-2">
                                <div class="rounded-circle bg-primary bg-opacity-10 text-primary fw-bold d-flex align-items-center justify-content-center" style="width:36px;height:36px;font-size:14px;">
                                    {{ strtoupper(substr($u->name, 0, 1)) }}
                                </div>
                                <div>
                                    <div class="fw-bold text-dark" style="font-size:13.5px;">{{ $u->name }}</div>
                                    @if($u->session_id)
                                        <span class="badge bg-success bg-opacity-10 text-success p-0" style="font-size:10px;"><i class="bi bi-record-fill me-1"></i>Active Session</span>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td class="text-secondary small">{{ $u->email }}</td>
                        <td>
                            @forelse($u->roles as $role)
                                <span class="badge-role">{{ $role->name }}</span>
                            @empty
                                <span class="text-muted small">No Role</span>
                            @endforelse
                        </td>
                        <td>
                            @if($u->shift)
                                <span class="badge-shift"><i class="bi bi-clock me-1"></i>{{ $u->shift->formatted_shift }}</span>
                            @else
                                <span class="badge bg-secondary bg-opacity-10 text-secondary" style="font-size:11px;">No Shift Limit (24/7)</span>
                            @endif
                        </td>
                        <td>
                            @if($u->is_active)
                                <span class="badge-active"><i class="bi bi-check-circle-fill me-1"></i>Active</span>
                            @else
                                <span class="badge-suspended"><i class="bi bi-slash-circle-fill me-1"></i>Suspended</span>
                            @endif
                        </td>
                        <td class="small text-muted">
                            {{ $u->last_activity ? \Carbon\Carbon::parse($u->last_activity)->diffForHumans() : 'Never' }}
                        </td>
                        <td class="pe-4 text-end">
                            <div class="d-inline-flex gap-1 align-items-center">
                                <form action="{{ route('users.toggle-status', $u->id) }}" method="POST" class="d-inline">
                                    @csrf
                                    @if($u->is_active)
                                        <button type="submit" class="btn btn-outline-danger btn-action" title="Suspend User Access" {{ $u->hasRole('Super Admin') ? 'disabled' : '' }}>
                                            <i class="bi bi-pause-circle me-1"></i> Suspend
                                        </button>
                                    @else
                                        <button type="submit" class="btn btn-outline-success btn-action" title="Activate User Access">
                                            <i class="bi bi-play-circle me-1"></i> Activate
                                        </button>
                                    @endif
                                </form>
                                <a href="{{ route('users.edit', $u->id) }}" class="btn btn-outline-primary btn-action">
                                    <i class="bi bi-pencil me-1"></i> Edit
                                </a>
                                @if(!$u->hasRole('Super Admin'))
                                    <form action="{{ route('users.destroy', $u->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete user {{ $u->name }}?');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-outline-secondary btn-action text-danger" title="Delete User">
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
