@extends('layouts.app')

@section('title', 'Almacén exterior')

@section('content')
    <x-ui.page-header
        title="Almacén exterior"
        subtitle="Estructura de los patios: desguace, patios, sectores, filas, posiciones y niveles."
        :breadcrumbs="[['label' => 'Inicio', 'url' => route('dashboard')], ['label' => 'Almacén exterior']]"
    />

    <x-ui.alert type="info" class="mb-4">
        La ocupación se calcula con las ubicaciones vigentes de los vehículos (sin fecha de fin):
        un nivel está ocupado si tiene una, y cada posición muestra «ocupados / capacidad máxima».
    </x-ui.alert>

    @include('almacen.partials.jerarquia', ['zona' => 'exterior'])
@endsection
