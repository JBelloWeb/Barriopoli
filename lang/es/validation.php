<?php

return [
    'required' => 'Este campo es obligatorio.',
    'email' => 'Ingresá un email válido.',
    'unique' => 'Ese valor ya está registrado.',
    'confirmed' => 'La confirmación no coincide.',
    'string' => 'El valor no es válido.',
    'date' => 'Ingresá una fecha válida.',
    'after' => 'La fecha tiene que ser posterior.',
    'exists' => 'La opción elegida no es válida.',
    'in' => 'La opción elegida no es válida.',
    'url' => 'Ingresá un enlace válido.',
    'numeric' => 'Tiene que ser un número.',
    'image' => 'El archivo tiene que ser una imagen.',
    'mimes' => 'Formato permitido: :values.',
    'regex' => 'El formato no es válido.',
    'boolean' => 'El valor no es válido.',
    'integer' => 'Tiene que ser un número entero.',
    'min' => ['string' => 'Tiene que tener al menos :min caracteres.'],
    'max' => [
        'string' => 'Puede tener hasta :max caracteres.',
        'file' => 'El archivo no puede superar los :max KB.',
    ],
    'password' => [
        'letters' => 'Tiene que incluir una letra.',
        'numbers' => 'Tiene que incluir un número.',
    ],
];
