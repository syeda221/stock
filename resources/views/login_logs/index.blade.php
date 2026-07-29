@extends('layouts.app')

@section('content')
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
.log-page { font-family: 'Inter', sans-serif; background: #f8fafc; min-height: 100vh; }
.card-custom { background: #fff; border-radius: 16px; border: 1px solid #e2e8f0; box-shadow: 0 4px 20px -4px rgba(15,23,42,0.05); }
.status-badge-success { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; font-weight: 700; padding: 4px 10px; border-radius: 20px; font-size: 11.5px; }
.status-badge-suspended { background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; font-weight: 700; padding: 4px 10px; border-radius: 20px; font-size: 11.5px; }
.status-badge-shift { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; font-weight: 700; padding: 4px 10px; border-radius: 20px; font-size: 11.5px; }
.status-badge-session { background: #ffedd5; color: #c2410c; border: 1px solid #fed7aa; font-weight: 700; padding: 4px 10px; border-radius: 20px; font-size: 11.5px; }
.status-badge-logout { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; font-weight: 600; padding: 4px 10px; border-radius: 20px; font-size: 11.5px; }
</style>

<div class="log-page p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold text-dark mb-1"><i class="bi bi-shield-check text-primary me-2"></i>Login Security Audit Log</h4>
            <p class="text-secondary small mb-0">Complete history of user logins, logout timestamps, IP addresses & blocked security attempts</p>
        </div>
        <a href="{{ route('users.index') }}" class="btn btn-outline-secondary btn-sm rounded-3 px-3 fw-semibold">
            <i class="bi bi-arrow-left me-1"></i> Back to Users
        </a>
    </div>

    {{-- Filter Bar --}}
    <div class="card-custom mb-4 p-3">
        <form method="GET" action="{{ route('login-logs.index') }}" class="row g-2 align-items-center">
            <div class="col-12 col-md-5">
                <input type="text" name="search" class="form-control form-control-sm rounded-3" 
                       placeholder="Search user name, email, IP address..." value="{{ request('search') }}">
            </div>
            <div class="col-12 col-md-4">
                <select name="status" class="form-select form-select-sm rounded-3">
                    <option value="">-- All Security Events --</option>
                    <option value="success" {{ request('status') == 'success' ? 'selected' : '' }}>Successful Login</option>
                    <option value="blocked_shift" {{ request('status') == 'blocked_shift' ? 'selected' : '' }}>Blocked (Outside Shift)</option>
                    <option value="blocked_suspended" {{ request('status') == 'blocked_suspended' ? 'selected' : '' }}>Blocked (Suspended User)</option>
                    <option value="single_session_terminated" {{ request('status') == 'single_session_terminated' ? 'selected' : '' }}>Terminated (New Login Elsewhere)</option>
                    <option value="logout" {{ request('status') == 'logout' ? 'selected' : '' }}>User Logout</option>
                </select>
            </div>
            <div class="col-12 col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm rounded-3 px-3 w-100"><i class="bi bi-search me-1"></i> Filter Logs</button>
                <a href="{{ route('login-logs.index') }}" class="btn btn-light btn-sm rounded-3 px-2" title="Reset Filters"><i class="bi bi-x-circle"></i></a>
            </div>
        </form>
    </div>

    <div class="card-custom p-0 overflow-hidden">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4 py-3 text-secondary text-uppercase fs-7 fw-bold" style="font-size:11px;">Date & Time</th>
                        <th class="py-3 text-secondary text-uppercase fs-7 fw-bold" style="font-size:11px;">User Name</th>
                        <th class="py-3 text-secondary text-uppercase fs-7 fw-bold" style="font-size:11px;">Email</th>
                        <th class="py-3 text-secondary text-uppercase fs-7 fw-bold" style="font-size:11px;">IP Address</th>
                        <th class="py-3 text-secondary text-uppercase fs-7 fw-bold" style="font-size:11px;">Security Status</th>
                        <th class="pe-4 py-3 text-end text-secondary text-uppercase fs-7 fw-bold" style="font-size:11px;">Browser/Device</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                    <tr>
                        <td class="ps-4">
                            <div class="fw-bold text-dark" style="font-size:13px;">
                                {{ $log->created_at ? $log->created_at->format('d M Y, h:i:s A') : '-' }}
                            </div>
                            <div class="small text-muted">{{ $log->created_at ? $log->created_at->diffForHumans() : '' }}</div>
                        </td>
                        <td>
                            <span class="fw-bold text-dark" style="font-size:13.5px;">{{ $log->user_name ?? 'Guest' }}</span>
                        </td>
                        <td class="text-secondary small">{{ $log->email }}</td>
                        <td>
                            <code class="bg-light border px-2 py-1 rounded text-dark small" style="font-size:11.5px;">{{ $log->ip_address ?? '127.0.0.1' }}</code>
                        </td>
                        <td>
                            @if($log->status === 'success')
                                <span class="status-badge-success"><i class="bi bi-check-circle-fill me-1"></i>Logged In</span>
                            @elseif($log->status === 'blocked_shift')
                                <span class="status-badge-shift"><i class="bi bi-clock-history me-1"></i>Blocked (Outside Shift)</span>
                            @elseif($log->status === 'blocked_suspended')
                                <span class="status-badge-suspended"><i class="bi bi-slash-circle-fill me-1"></i>Blocked (Suspended)</span>
                            @elseif($log->status === 'single_session_terminated')
                                <span class="status-badge-session"><i class="bi bi-exclamation-triangle-fill me-1"></i>Logged Out (Device Changed)</span>
                            @elseif($log->status === 'logout')
                                <span class="status-badge-logout"><i class="bi bi-door-closed me-1"></i>Logged Out</span>
                            @else
                                <span class="badge bg-secondary" style="font-size:11px;">{{ ucfirst($log->status) }}</span>
                            @endif
                        </td>
                        <td class="pe-4 text-end text-muted small text-truncate" style="max-width:200px;" title="{{ $log->user_agent }}">
                            {{ $log->user_agent ? substr($log->user_agent, 0, 35) . '...' : '-' }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">
                            <i class="bi bi-shield-check fs-1 d-block mb-2 opacity-30"></i>
                            No login audit records found.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($logs->hasPages())
            <div class="p-3 border-top">
                {{ $logs->appends(request()->query())->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
