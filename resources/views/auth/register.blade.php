@extends('layouts.app')

@section('content')
<div class="container" style="max-width:480px;">
  <h1 class="mb-3 text-center">Create Account</h1>

  <form method="POST" action="{{ route('register') }}">
    @csrf

    <div class="mb-3">
      <label class="form-label">Username</label>
      <input type="text" name="username" class="form-control" value="{{ old('username') }}" required autofocus>
      @error('username')<div class="text-danger small">{{ $message }}</div>@enderror
    </div>

    <div class="mb-3">
      <label class="form-label">Email</label>
      <input type="email" name="email" class="form-control" value="{{ old('email') }}" required>
      @error('email')<div class="text-danger small">{{ $message }}</div>@enderror
    </div>

    <div class="mb-3">
      <label class="form-label">Password</label>
      <input type="password" name="password" class="form-control" required>
      @error('password')<div class="text-danger small">{{ $message }}</div>@enderror
    </div>

    <div class="mb-3">
      <label class="form-label">Confirm Password</label>
      <input type="password" name="password_confirmation" class="form-control" required>
    </div>

    <button type="submit" class="btn btn-success w-100">Register</button>
  </form>
</div>
@endsection
