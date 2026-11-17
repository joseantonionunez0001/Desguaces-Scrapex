@extends('layouts.app')

@section('title', 'Editar vehículo')

@section('content')
    <x-ui.page-header title="Editar vehículo" :subtitle="$vehiculo->matricula.' · '.$vehiculo->marca.' '.$vehiculo->modelo"
        :breadcrumbs="[
            ['label' => 'Inicio', 'url' => route('dashboard')],
            ['label' => 'Vehículos', 'url' => route('vehiculos.index')],
            ['label' => $vehiculo->matricula, 'url' => route('vehiculos.show', $vehiculo)],
            ['label' => 'Editar'],
        ]" />

    <x-ui.card class="max-w-3xl">
        @include('vehiculos._form', [
            'action' => route('vehiculos.update', $vehiculo),
            'method' => 'PUT',
            'submit' => 'Guardar cambios',
            'cancel' => route('vehiculos.show', $vehiculo),
        ])
    </x-ui.card>
@endsection
