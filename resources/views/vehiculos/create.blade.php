@extends('layouts.app')

@section('title', 'Registrar vehículo')

@section('content')
    <x-ui.page-header title="Registrar vehículo" subtitle="Da de alta un vehículo recibido en el desguace."
        :breadcrumbs="[
            ['label' => 'Inicio', 'url' => route('dashboard')],
            ['label' => 'Vehículos', 'url' => route('vehiculos.index')],
            ['label' => 'Registrar'],
        ]" />

    <x-ui.card class="max-w-3xl">
        @include('vehiculos._form', [
            'action' => route('vehiculos.store'),
            'method' => 'POST',
            'submit' => 'Registrar vehículo',
            'cancel' => route('vehiculos.index'),
        ])
    </x-ui.card>
@endsection
