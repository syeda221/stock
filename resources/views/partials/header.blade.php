<nav class="navbar navbar-light bg-light border-bottom px-4">
    <span class="navbar-brand mb-0 h6">
        Warehouse Management System
    </span>

    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button class="btn btn-sm btn-outline-danger">Logout</button>
    </form>
</nav>
