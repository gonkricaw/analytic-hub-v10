@extends('layouts.admin')

@section('title', 'Admin Dashboard')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <h4 class="page-title">Admin Dashboard</h4>
                <div class="page-title-right">
                    <ol class="breadcrumb m-0">
                        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Admin</a></li>
                        <li class="breadcrumb-item active">Dashboard</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-3 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-6">
                            <h5 class="text-muted fw-normal mt-0 text-truncate" title="Total Users">Total Users</h5>
                            <h3 class="my-2 py-1">{{ $totalUsers ?? 0 }}</h3>
                        </div>
                        <div class="col-6">
                            <div class="text-end">
                                <div id="users-chart" data-colors="#727cf5"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-6">
                            <h5 class="text-muted fw-normal mt-0 text-truncate" title="Total Roles">Total Roles</h5>
                            <h3 class="my-2 py-1">{{ $totalRoles ?? 0 }}</h3>
                        </div>
                        <div class="col-6">
                            <div class="text-end">
                                <div id="roles-chart" data-colors="#0acf97"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-6">
                            <h5 class="text-muted fw-normal mt-0 text-truncate" title="Total Menus">Total Menus</h5>
                            <h3 class="my-2 py-1">{{ $totalMenus ?? 0 }}</h3>
                        </div>
                        <div class="col-6">
                            <div class="text-end">
                                <div id="menus-chart" data-colors="#fa5c7c"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-6">
                            <h5 class="text-muted fw-normal mt-0 text-truncate" title="Total Permissions">Total Permissions</h5>
                            <h3 class="my-2 py-1">{{ $totalPermissions ?? 0 }}</h3>
                        </div>
                        <div class="col-6">
                            <div class="text-end">
                                <div id="permissions-chart" data-colors="#ffbc00"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="header-title">Quick Actions</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <a href="{{ route('admin.users.index') }}" class="btn btn-primary btn-block">
                                <i class="fas fa-users me-1"></i> Manage Users
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="{{ route('admin.roles.index') }}" class="btn btn-success btn-block">
                                <i class="fas fa-user-tag me-1"></i> Manage Roles
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="{{ route('admin.permissions.index') }}" class="btn btn-warning btn-block">
                                <i class="fas fa-key me-1"></i> Manage Permissions
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="{{ route('admin.menus.index') }}" class="btn btn-info btn-block">
                                <i class="fas fa-bars me-1"></i> Manage Menus
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Dashboard initialization
    $(document).ready(function() {
        console.log('Admin Dashboard loaded');
    });
</script>
@endpush