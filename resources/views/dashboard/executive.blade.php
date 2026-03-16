<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Executive Dashboard</title>
</head>
<body>

    <h1>Welcome, {{ Auth::user()->name }}! 👋</h1>
    <p>Role: {{ Auth::user()->role->role_name }}</p>
    <p>Position: {{ Auth::user()->position }}</p>

    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit">Logout</button>
    </form>

</body>
</html>