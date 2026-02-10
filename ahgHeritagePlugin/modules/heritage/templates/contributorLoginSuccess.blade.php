@extends('layouts.page')

@section('title')
<h1 class="h3">
    <i class="fas fa-user-circle me-2"></i>Contributor Login
</h1>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <div class="text-center mb-4">
                    <i class="fas fa-users-fill display-4 text-primary"></i>
                    <h2 class="h4 mt-3">Welcome Back</h2>
                    <p class="text-muted">Sign in to contribute to our heritage collection</p>
                </div>

                @if (!empty($error))
                <div class="alert alert-danger" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>{{ $error }}
                </div>
                @endif

                <form method="post" action="{{ url_for(['module' => 'heritage', 'action' => 'contributorLogin']) }}">
                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                            <input type="email" class="form-control" id="email" name="email"
                                   required autofocus
                                   placeholder="your@email.com">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" class="form-control" id="password" name="password"
                                   required
                                   placeholder="Your password">
                        </div>
                    </div>

                    <div class="d-grid gap-2 mb-3">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-box-arrow-in-right me-2"></i>Sign In
                        </button>
                    </div>
                </form>

                <hr class="my-4">

                <div class="text-center">
                    <p class="mb-2">Don't have an account?</p>
                    <a href="{{ url_for(['module' => 'heritage', 'action' => 'contributorRegister']) }}" class="btn btn-outline-primary">
                        <i class="fas fa-user-plus me-2"></i>Create Account
                    </a>
                </div>
            </div>
        </div>

        <div class="text-center mt-4">
            <a href="{{ url_for(['module' => 'heritage', 'action' => 'landing']) }}" class="text-muted text-decoration-none">
                <i class="fas fa-arrow-left me-1"></i>Back to Heritage Portal
            </a>
        </div>
    </div>
</div>
@endsection
