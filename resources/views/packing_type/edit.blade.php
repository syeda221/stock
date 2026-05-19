@extends('layouts.app')

@section('content')

{{-- Breadcrumbs + Page Header --}}
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard') }}" class="text-decoration-none">Dashboard</a>
                </li>
                <li class="breadcrumb-item">
                    <a href="{{ route('packing-type.index') }}" class="text-decoration-none">Packing Types</a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">Edit</li>
            </ol>
        </nav>
        <h5 class="mb-0 fw-bold">Edit Packing Type</h5>
        <small class="text-muted">Update packing configuration details</small>
    </div>
</div>

{{-- Card --}}
<div class="card border-0 shadow-lg rounded-4">
    <div class="card-body p-4">

        <form method="POST" action="{{ route('packing-type.update', $packingType->id) }}">
            @csrf
            @method('PUT')

            {{-- Packing Type Name --}}
            <div class="mb-3">
                <label class="form-label fw-semibold">Packing Type Name</label>
                <input type="text"
                       name="name"
                       value="{{ old('name', $packingType->name) }}"
                       class="form-control rounded-pill @error('name') is-invalid @enderror"
                       placeholder="e.g. Box, Carton, Packet"
                       required>

                @error('name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            {{-- Status --}}
            <div class="form-check form-switch mb-4">
                <input class="form-check-input"
                       type="checkbox"
                       role="switch"
                       name="status"
                       value="1"
                       id="statusSwitch"
                       {{ old('status', $packingType->status) ? 'checked' : '' }}>
                <label class="form-check-label fw-semibold" for="statusSwitch">
                    Active
                </label>
            </div>

            {{-- Actions --}}
            <div class="d-flex justify-content-start gap-2">
                <a href="{{ route('packing-type.index') }}"
                   class="btn btn-outline-secondary rounded-pill px-4">
                    Cancel
                </a>

                <button type="submit"
                        class="btn btn-primary rounded-pill px-4 shadow-sm">
                    <i class="bi bi-arrow-repeat me-1"></i> Update
                </button>
            </div>

        </form>

    </div>
</div>

@endsection
