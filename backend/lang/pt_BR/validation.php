<?php

return [
    'array' => 'O campo :attribute deve ser uma lista.',
    'distinct' => 'O campo :attribute possui um valor duplicado.',
    'email' => 'O campo :attribute deve ser um endereço de e-mail válido.',
    'exists' => 'O :attribute selecionado é inválido.',
    'max' => [
        'numeric' => 'O campo :attribute não pode ser maior que :max.',
        'string' => 'O campo :attribute não pode ter mais de :max caracteres.',
    ],
    'min' => [
        'numeric' => 'O campo :attribute deve ser pelo menos :min.',
        'string' => 'O campo :attribute deve ter pelo menos :min caracteres.',
    ],
    'integer' => 'O campo :attribute deve ser um número inteiro.',
    'list' => 'O campo :attribute deve ser uma lista sem lacunas.',
    'password' => 'A senha deve ter pelo menos :min caracteres.',
    'regex' => 'O formato do campo :attribute é inválido.',
    'required' => 'O campo :attribute é obrigatório.',
    'string' => 'O campo :attribute deve ser um texto.',
    'unique' => 'O campo :attribute já está em uso.',
    'attributes' => [
        'deviceName' => 'nome do dispositivo',
        'description' => 'descrição',
        'email' => 'e-mail',
        'name' => 'nome',
        'password' => 'senha',
        'productId' => 'produto',
        'q' => 'busca',
        'limit' => 'limite',
        'wishIds' => 'ordem dos desejos',
        'wishIds.*' => 'item da ordem dos desejos',
    ],
];
