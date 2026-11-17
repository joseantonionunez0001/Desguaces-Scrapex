<?php

namespace App\Http\Requests\Web\Concerns;

/**
 * Mensajes de validación en español para las reglas que usan las pantallas web.
 * (La aplicación no incluye archivos de traducción de validación.)
 */
trait MensajesDeValidacion
{
    /**
     * @param  list<string>  $campos
     * @return array<string, string>
     */
    protected function mensajesEnEspanol(array $campos): array
    {
        $plantillas = [
            'required' => 'El campo :attribute es obligatorio.',
            'string' => 'El campo :attribute debe ser un texto.',
            'max' => 'El campo :attribute no puede superar los :max caracteres.',
            'integer' => 'El campo :attribute debe ser un número entero.',
            'min' => 'El campo :attribute debe ser como mínimo :min.',
            'exists' => 'El valor elegido en :attribute no es válido.',
            'date' => 'El campo :attribute debe ser una fecha válida.',
            'after_or_equal' => 'El campo :attribute no puede ser anterior a :date.',
            'array' => 'El campo :attribute no es válido.',
        ];

        $mensajes = [];
        foreach ($campos as $campo) {
            foreach ($plantillas as $regla => $texto) {
                $mensajes["{$campo}.{$regla}"] = $texto;
            }
        }

        return $mensajes;
    }
}
