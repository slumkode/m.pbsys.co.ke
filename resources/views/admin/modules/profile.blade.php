@extends('admin.includes.body')
@section('title', 'Profile')
@section('subtitle', 'Profile')
@section('content')
    <div class="row">
        <div class="col-12 col-xl-8">
            <div class="card">
                <div class="card-body">
                    <form action="{{ route('profile.update') }}" method="POST">
                        @csrf
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="name">Full Name</label>
                                <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $authUser->name) }}" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-group col-md-6">
                                <label for="username">Username</label>
                                <input type="text" name="username" id="username" class="form-control @error('username') is-invalid @enderror" value="{{ old('username', $authUser->username) }}" required>
                                @error('username')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" name="email" id="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $authUser->email) }}" required>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="line-divider"></div>

                        <legend>Security</legend>
                        <p class="text-muted">Leave the password fields blank if you want to keep your current password.</p>

                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="password">New Password</label>
                                <input type="password" name="password" id="password" class="form-control @error('password') is-invalid @enderror" autocomplete="new-password">
                                @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-group col-md-6">
                                <label for="password_confirmation">Confirm New Password</label>
                                <input type="password" name="password_confirmation" id="password_confirmation" class="form-control" autocomplete="new-password">
                            </div>
                        </div>

                        <div class="form-group form-row mb-0">
                            <div class="ml-auto">
                                <button type="submit" class="btn btn-primary">Save Changes</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Account Summary</h5>
                </div>
                <div class="card-body">
                    <div class="text-center mb-30">
                        <img src="{{ $userimg && $userimg->access_value ? asset('uploads/'.$userimg->access_value) : asset('assets/img/avatar.png') }}" class="img-fluid rounded-circle img-thumbnail mb-3" alt="{{ $authUser->name }}" style="max-width: 110px;">
                        <h4 class="mb-1">{{ $authUser->name }}</h4>
                        <small class="text-muted">{{ $authUser->email }}</small>
                    </div>

                    <div class="form-group">
                        <label for="summary-username" class="control-label">Username</label>
                        <label for="summary-username" class="input-label">{{ $authUser->username }}</label>
                    </div>

                    <div class="form-group">
                        <label for="summary-status" class="control-label">Status</label>
                        <label for="summary-status" class="input-label">{{ $authUser->status ? 'Active' : 'Inactive' }}</label>
                    </div>

                    <div class="form-group">
                        <label for="summary-created" class="control-label">Account Created</label>
                        <label for="summary-created" class="input-label">{{ optional($authUser->created_at)->format('d M Y, H:i') ?: 'Not available' }}</label>
                    </div>

                    <div class="form-group mb-0">
                        <label for="summary-updated" class="control-label">Last Updated</label>
                        <label for="summary-updated" class="input-label">{{ optional($authUser->updated_at)->format('d M Y, H:i') }}</label>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('script')
    <script>
        $(document).ready(function(){
            @if (session('status'))
                toastr.success(@json(session('status')), 'Profile', {
                    timeOut: 2000,
                    closeButton: true,
                    progressBar: true,
                    newestOnTop: true
                });
            @endif

            @if ($errors->any())
                @foreach ($errors->all() as $message)
                    toastr.error(@json($message), 'Validation', {
                        timeOut: 2000,
                        closeButton: true,
                        progressBar: true,
                        newestOnTop: true
                    });
                @endforeach
            @endif
        });
    </script>
@endsection
