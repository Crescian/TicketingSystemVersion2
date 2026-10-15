@section('nav-role-badge')
    <span class="role-badge"><i class="bi bi-person me-1"></i>{{ Auth::user()->role?->role_name }}</span>
@endsection
@section('avatar-initials',
    strtoupper(substr(Auth::user()->name, 0, 1)) .
    strtoupper(substr(Auth::user()->name, strpos(Auth::user()->name, ' ') + 1, 1))
)
@section('nav-username', Auth::user()->name)
