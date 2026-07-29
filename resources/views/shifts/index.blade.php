@extends('layouts.app')

@section('content')
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
.shift-page { font-family: 'Inter', sans-serif; background: #f8fafc; min-height: 100vh; }
.card-custom { background: #fff; border-radius: 16px; border: 1px solid #e2e8f0; box-shadow: 0 4px 20px -4px rgba(15,23,42,0.05); }
.shift-badge { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; font-weight: 700; padding: 6px 14px; border-radius: 8px; font-size: 12.5px; }
</style>

<div class="shift-page p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold text-dark mb-1"><i class="bi bi-clock-history text-primary me-2"></i>Work Shifts Setup</h4>
            <p class="text-secondary small mb-0">Define working hours and shifts to restrict staff access outside shift timings</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('users.index') }}" class="btn btn-outline-secondary btn-sm rounded-3 px-3 fw-semibold">
                <i class="bi bi-people me-1"></i> Back to Users
            </a>
            <button class="btn btn-primary btn-sm rounded-3 px-3 fw-semibold" data-bs-toggle="modal" data-bs-target="#createShiftModal">
                <i class="bi bi-plus-circle me-1"></i> Add New Shift
            </button>
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

    <div class="card-custom p-0 overflow-hidden">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-4 py-3 text-secondary text-uppercase fs-7 fw-bold" style="font-size:11px;">#</th>
                        <th class="py-3 text-secondary text-uppercase fs-7 fw-bold" style="font-size:11px;">Shift Name</th>
                        <th class="py-3 text-secondary text-uppercase fs-7 fw-bold" style="font-size:11px;">Start Time</th>
                        <th class="py-3 text-secondary text-uppercase fs-7 fw-bold" style="font-size:11px;">End Time</th>
                        <th class="py-3 text-secondary text-uppercase fs-7 fw-bold" style="font-size:11px;">Assigned Users</th>
                        <th class="pe-4 py-3 text-end text-secondary text-uppercase fs-7 fw-bold" style="font-size:11px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($shifts as $index => $shift)
                    <tr>
                        <td class="ps-4 fw-bold text-muted" style="font-size:12px;">{{ $index + 1 }}</td>
                        <td>
                            <span class="fw-bold text-dark" style="font-size:14px;">{{ $shift->name }}</span>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border px-3 py-2 fw-semibold" style="font-size:12.5px;">
                                <i class="bi bi-brightness-high text-warning me-1"></i>
                                {{ \Carbon\Carbon::createFromTimeString($shift->start_time)->format('h:i A') }}
                            </span>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border px-3 py-2 fw-semibold" style="font-size:12.5px;">
                                <i class="bi bi-moon-stars text-indigo me-1"></i>
                                {{ \Carbon\Carbon::createFromTimeString($shift->end_time)->format('h:i A') }}
                            </span>
                        </td>
                        <td>
                            <span class="badge bg-primary bg-opacity-10 text-primary px-3 py-1 fw-bold rounded-pill" style="font-size:12px;">
                                <i class="bi bi-people-fill me-1"></i> {{ $shift->users_count }} Staff Members
                            </span>
                        </td>
                        <td class="pe-4 text-end">
                            <div class="d-inline-flex gap-1">
                                <button class="btn btn-outline-primary btn-sm rounded-3 px-3 fw-semibold" 
                                        data-bs-toggle="modal" data-bs-target="#editShiftModal{{ $shift->id }}">
                                    <i class="bi bi-pencil me-1"></i> Edit
                                </button>
                                <form action="{{ route('shifts.destroy', $shift->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete shift {{ $shift->name }}?');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-outline-danger btn-sm rounded-3 px-2" {{ $shift->users_count > 0 ? 'disabled' : '' }}>
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>

                    {{-- Edit Modal --}}
                    <div class="modal fade" id="editShiftModal{{ $shift->id }}" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content rounded-4 border-0 shadow">
                                <div class="modal-header border-bottom-0 pb-0">
                                    <h5 class="modal-title fw-bold text-dark">Edit Shift</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <form action="{{ route('shifts.update', $shift->id) }}" method="POST">
                                    @csrf
                                    @method('PUT')
                                    <div class="modal-body p-4">
                                        <div class="mb-3">
                                            <label class="form-label small fw-semibold">Shift Name</label>
                                            <input type="text" name="name" class="form-control rounded-3" value="{{ $shift->name }}" required>
                                        </div>
                                        <div class="row g-2">
                                            <div class="col-6 mb-3">
                                                <label class="form-label small fw-semibold">Start Time</label>
                                                <input type="time" name="start_time" class="form-control rounded-3" value="{{ $shift->start_time }}" required>
                                            </div>
                                            <div class="col-6 mb-3">
                                                <label class="form-label small fw-semibold">End Time</label>
                                                <input type="time" name="end_time" class="form-control rounded-3" value="{{ $shift->end_time }}" required>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer border-top-0 pt-0">
                                        <button type="button" class="btn btn-light rounded-3" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-primary rounded-3 px-4">Update Shift</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">
                            <i class="bi bi-clock-history fs-1 d-block mb-2 opacity-30"></i>
                            No shifts configured yet.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Create Modal --}}
<div class="modal fade" id="createShiftModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold text-dark">Add New Shift</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('shifts.store') }}" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Shift Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control rounded-3" placeholder="e.g. Morning Shift (9 AM to 5 PM)" required>
                    </div>
                    <div class="row g-2">
                        <div class="col-6 mb-3">
                            <label class="form-label small fw-semibold">Start Time <span class="text-danger">*</span></label>
                            <input type="time" name="start_time" class="form-control rounded-3" value="09:00" required>
                        </div>
                        <div class="col-6 mb-3">
                            <label class="form-label small fw-semibold">End Time <span class="text-danger">*</span></label>
                            <input type="time" name="end_time" class="form-control rounded-3" value="17:00" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top-0 pt-0">
                    <button type="button" class="btn btn-light rounded-3" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-3 px-4">Create Shift</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
