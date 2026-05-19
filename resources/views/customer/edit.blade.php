@extends('layouts.app')

@section('content')

<div class="card shadow-sm">
    <div class="card-header">
        <h6 class="mb-0">Edit Customer</h6>
    </div>

    <div class="card-body">
        <form method="POST" action="{{ route('customer.update',$customer->id) }}">
            @csrf
            @method('PUT')

            <div class="mb-3">
                <label class="form-label">Customer Name</label>
                <input type="text"
                       name="name"
                       class="form-control"
                       value="{{ $customer->name }}"
                       required>
            </div>

            <div class="form-check mb-3">
                <input type="checkbox"
                       name="status"
                       value="1"
                       class="form-check-input"
                       {{ $customer->status ? 'checked' : '' }}>
                <label class="form-check-label">Active</label>
            </div>

            <button class="btn btn-success">Update</button>
            <a href="{{ route('customer.index') }}" class="btn btn-secondary">Back</a>
        </form>
    </div>
</div>

@endsection
