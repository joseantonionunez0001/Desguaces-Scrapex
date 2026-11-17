@extends('layouts.app')

@section('title', 'Editar pieza')

@section('content')
    <x-ui.page-header title="Editar pieza" :subtitle="$pieza->nombre"
        :breadcrumbs="[
            ['label' => 'Inicio', 'url' => route('dashboard')],
            ['label' => 'Piezas', 'url' => route('piezas.index')],
            ['label' => $pieza->nombre, 'url' => route('piezas.show', $pieza)],
            ['label' => 'Editar'],
        ]" />

    <x-ui.card class="max-w-3xl">
        @include('piezas._form', [
            'action' => route('piezas.update', $pieza),
            'method' => 'PUT',
            'submit' => 'Guardar cambios',
            'cancel' => route('piezas.show', $pieza),
        ])
    </x-ui.card>
@endsection
