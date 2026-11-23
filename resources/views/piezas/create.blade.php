@extends('layouts.app')

@section('title', 'Registrar pieza')

@section('content')
    <x-ui.page-header title="Registrar pieza" subtitle="Da de alta la pieza con las unidades recibidas y su ubicación: se registra como su Entrada inicial, a tu nombre."
        :breadcrumbs="[
            ['label' => 'Inicio', 'url' => route('dashboard')],
            ['label' => 'Piezas', 'url' => route('piezas.index')],
            ['label' => 'Registrar'],
        ]" />

    <x-ui.card class="max-w-3xl">
        @include('piezas._form', [
            'action' => route('piezas.store'),
            'method' => 'POST',
            'submit' => 'Registrar pieza',
            'cancel' => route('piezas.index'),
        ])
    </x-ui.card>
@endsection
