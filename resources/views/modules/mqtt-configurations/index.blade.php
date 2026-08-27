@extends('layouts.master')

@section('title') MQTT Configuration @endsection

@section('content')
@component('components.breadcrumb')
@slot('li_1') Configuration @endslot
@slot('title') MQTT Configuration @endslot
@endcomponent

@if (session('message'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('message') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if ($errors->any())
    <div class="alert alert-danger" role="alert">
        <strong>Periksa kembali konfigurasi MQTT berikut:</strong>
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

@include('modules.mqtt-configurations._guide')

<div class="card mb-4">
    <div class="card-body">
        <h4 class="card-title mb-1">Broker & Project Binding</h4>
        <p class="text-muted">Credential disimpan terenkripsi dan tidak pernah ditampilkan kembali.</p>
        @include('modules.mqtt-configurations._form', [
            'mqttProjects' => $projects,
            'mqttCanonicalParameters' => $canonicalParameters,
        ])
    </div>
</div>

<div class="card">
    <div class="card-body">
        @include('modules.mqtt-configurations._monitor', [
            'mqttConfigurations' => $configurations,
        ])
    </div>
</div>
@endsection

@section('script')
@include('modules.mqtt-configurations._scripts')
@endsection
