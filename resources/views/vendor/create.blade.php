@extends('layouts.app')

@section('content')

<div class="card shadow-sm">
    <div class="card-header">
        <h6 class="mb-0">Add Vendor</h6>
    </div>

    <div class="card-body">
        <form method="POST" action="{{ route('vendor.store') }}">
            @csrf

            <div class="mb-3">
                <label class="form-label">Vendor Name</label>
                <input type="text" name="name" class="form-control" required>
            </div>

            <div class="form-check mb-3">
                <input type="checkbox"
                       name="status"
                       value="1"
                       class="form-check-input"
                       checked>
                <label class="form-check-label">
                    Active
                </label>
            </div>

            <button class="btn btn-success">Save</button>
            <a href="{{ route('vendor.index') }}" class="btn btn-secondary">
                Back
            </a>
        </form>
    </div>
</div>

@endsection
