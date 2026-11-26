<!doctype html>
<html lang="id">
<head>
    @include('admin.layouts.head')
    <title>Masuk Admin — Timore Soccer School</title>
</head>
<body class="login-page">
    <div class="login-theme"><x-admin.theme-control /></div>
    <form class="login-card" method="POST" action="{{ route('admin.login.submit') }}">
        @csrf
        <img class="brand-mark" src="{{ asset('assets/admin/images/timore-crest.png') }}" alt="Logo Timore Soccer School" width="76" height="76">
        <div><h1>Masuk Admin</h1><p>Pusat pengelolaan Timore Soccer School.</p></div>
        @if($errors->any())<div class="alert error" role="alert">{{ $errors->first() }}</div>@endif
        <div class="field"><label for="email">Email</label><input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username"></div>
        <div class="field"><label for="password">Kata sandi</label><input id="password" type="password" name="password" required autocomplete="current-password"></div>
        <button class="btn primary full" type="submit">Masuk ke dashboard</button>
    </form>
</body>
</html>
