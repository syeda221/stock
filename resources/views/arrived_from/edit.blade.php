@extends('layouts.app')

@section('content')

<div class="card shadow-sm">
    <div class="card-header">
        <h6 class="mb-0">Edit Arrived From</h6>
    </div>

    <div class="card-body">
        <form method="POST" action="{{ route('arrived-from.update', $arrivedFrom->id) }}">
            @csrf
            @method('PUT')

            <div class="mb-3">
                <label class="form-label">Arrived From Name</label>
                <input type="text"
                       name="name"
                       class="form-control"
                       value="{{ $arrivedFrom->name }}"
                       required>
            </div>

            <div class="form-check mb-3">
                <input type="checkbox"
                       name="status"
                       value="1"
                       class="form-check-input"
                       {{ $vendor->status ? 'checked' : '' }}>
                <label class="form-check-label">
                    Active
                </label>
            </div>

            <button class="btn btn-success">Update</button>
            <a href="{{ route('arrived-from.index') }}" class="btn btn-secondary">
                Back
            </a>
        </form>
    </div>
</div>

@endsection
