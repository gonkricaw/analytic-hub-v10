@extends('layouts.admin')

@section('title', 'Menu Role Assignment - Error')

@section('content')
<div class="container-fluid">
    <div class="alert alert-danger">
        <h4>Menu not found or not accessible</h4>
        <p>The requested menu could not be loaded. Please check if the menu exists and you have permission to access it.</p>
        <a href="{{ route('admin.menus.index') }}" class="btn btn-primary">Back to Menu List</a>
    </div>
</div>
@endsection