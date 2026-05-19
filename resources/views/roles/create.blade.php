@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4>Create Role</h4>
        <a href="{{ route('roles.index') }}" class="btn btn-secondary">Back</a>
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
            <form action="{{ route('roles.store') }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label>Role Name</label>
                    <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
                </div>
                
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <label class="mb-0">Permissions</label>
                        <input type="text" id="permissionSearch" class="form-control form-control-sm w-25" placeholder="Search permissions...">
                    </div>
                    
                    <div class="row" id="permissionsList">
                        @foreach($permissions as $permission)
                            <div class="col-md-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="permissions[]" value="{{ $permission->name }}" id="perm_{{ $permission->id }}">
                                    <label class="form-check-label" for="perm_{{ $permission->id }}">
                                        {{ $permission->name }}
                                    </label>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="mb-3">
                    <button type="button" id="selectAll" class="btn btn-sm btn-outline-secondary">Select All</button>
                    <button type="button" id="deselectAll" class="btn btn-sm btn-outline-secondary">Deselect All</button>
                </div>

                <button type="submit" class="btn btn-primary">Save</button>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('permissionSearch');
        const permissionDivs = document.querySelectorAll('#permissionsList .col-md-3');

        // Search functionality
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            
            permissionDivs.forEach(div => {
                const label = div.querySelector('label').textContent.toLowerCase();
                if (label.includes(searchTerm)) {
                    div.style.display = '';
                } else {
                    div.style.display = 'none';
                }
            });
        });

        // Select All
        document.getElementById('selectAll').addEventListener('click', function() {
            permissionDivs.forEach(div => {
                if(div.style.display !== 'none') {
                    div.querySelector('input[type="checkbox"]').checked = true;
                }
            });
        });

        // Deselect All
        document.getElementById('deselectAll').addEventListener('click', function() {
            permissionDivs.forEach(div => {
                if(div.style.display !== 'none') {
                    div.querySelector('input[type="checkbox"]').checked = false;
                }
            });
        });
    });
</script>
@endpush

@endsection
