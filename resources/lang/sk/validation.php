<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines contain the default error messages used by
    | the validator class. Some of these rules have multiple versions such
    | as the size rules. Feel free to tweak each of these messages here.
    |
    */

    'accepted' => ':attribute musí byť akceptovaný.',
    'active_url' => ':attribute nie je platná URL adresa.',
    'after' => ':attribute musí byť dátum po :date.',
    'after_or_equal' => ':attribute musí byť dátum po alebo rovný :date.',
    'alpha' => ':attribute môže obsahovať iba písmená.',
    'alpha_dash' => ':attribute môže obsahovať iba písmená, čísla, čiarky a podčiarkovníky.',
    'alpha_num' => ':attribute môže obsahovať iba písmená a čísla.',
    'array' => ':attribute musí byť pole.',
    'before' => ':attribute musí byť dátum pred :date.',
    'before_or_equal' => ':attribute musí byť dátum pred alebo rovný :date.',
    'between' => [
        'numeric' => ':attribute musí byť medzi :min a :max.',
        'file' => ':attribute musí byť medzi :min a :max kilobajtov.',
        'string' => ':attribute musí mať medzi :min a :max písmen.',
        'array' => ':attribute musí mať medzi :min a :max položiek.',
    ],
    'boolean' => ':attribute musí byť pravda alebo nepravda.',
    'confirmed' => ':attribute nebolo potvrdené správne.',
    'date' => ':attribute nie je validný dátum.',
    'date_equals' => ':attribute musí byť tento dátum :date.',
    'date_format' => ':attribute nema správny formát - :format.',
    'different' => ':attribute a :other musia byť rozdielne.',
    'digits' => ':attribute musí byť :digits číslic.',
    'digits_between' => ':attribute musí mať medzi :min a :max číslic.',
    'dimensions' => ':attribute má zlé rozmery.',
    'distinct' => ':attribute už existuje v databáze.',
    'email' => ':attribute musí byť validná mailová adresa.',
    'ends_with' => ':attribute musí končiť na: :values.',
    'exists' => 'Vybratý :attribute nie je správny.',
    'file' => ':attribute musí byť súbor.',
    'filled' => ':attribute musí mať hodnotu.',
    'gt' => [
        'numeric' => ':attribute musí byť väčší ako :value.',
        'file' => ':attribute musí mať viac ako :value kilobajtov.',
        'string' => ':attribute musí mať viac ako :value písmen.',
        'array' => ':attribute musí mať viac ako :value položiek.',
    ],
    'gte' => [
        'numeric' => ':attribute musí byť väčší alebo rovnaký ako :value.',
        'file' => ':attribute musí mať viac alebo musí byť rovný :value kilobajtom.',
        'string' => ':attribute musí mať viac alebo musí mať počet písmen rovný :value.',
        'array' => ':attribute musí mať :value alebo viac položiek.',
    ],
    'image' => ':attribute musí byť obrázok.',
    'in' => 'Vybraná hodnota :attribute nie je validná.',
    'in_array' => ':attribute neexistuje v :other.',
    'integer' => ':attribute musí byť celé číslo.',
    'ip' => ':attribute musí byť validná IP adresa.',
    'ipv4' => ':attribute musí byť validná IPv4 adresa.',
    'ipv6' => ':attribute musí byť validná IPv6 adresa.',
    'json' => ':attribute musí byť validný JSON text.',
    'lt' => [
        'numeric' => ':attribute musí byť menej ako :value.',
        'file' => ':attribute musí mať menej ako :value kilobajtov.',
        'string' => ':attribute musí mať menej ako :value písmen.',
        'array' => ':attribute musí mať menej ako :value položiek.',
    ],
    'lte' => [
        'numeric' => ':attribute musí byť menej alebo musí byť rovný :value.',
        'file' => ':attribute musí mať menej ako alebo musí mať presne :value kilobajtov.',
        'string' => ':attribute musí mať menej ako alebo musí mať presne :value písmen.',
        'array' => ':attribute musí mať menej ako alebo musí mať presne :value položiek.',
    ],
    'max' => [
        'numeric' => ':attribute nesmie byť väčší ako :max.',
        'file' => ':attribute nesmie mať viac ako :max kilobajtov.',
        'string' => ':attribute nesmie mať viac ako :max písmen.',
        'array' => ':attribute nesmie mať viac ako :max položiek.',
    ],
    'mimes' => ':attribute musí byť súbor typu: :values.',
    'mimetypes' => ':attribute musí byť súbor typu: :values.',
    'min' => [
        'numeric' => ':attribute musí byť aspoň :min.',
        'file' => ':attribute musí mať aspoň :min kilobajtov.',
        'string' => ':attribute musí mať aspoň :min písmen.',
        'array' => ':attribute musí mať aspoň :min položiek.',
    ],
    'not_in' => 'Vybraná hodnota :attribute nie je validná.',
    'not_regex' => ':attribute nie je v správnom tvare.',
    'numeric' => ':attribute musí byť číslo.',
    'password' => 'Heslo nie je správne.',
    'present' => ':attribute musí byť zadaný.',
    'regex' => ':attribute nemá správny formát.',
    'required' => ':attribute je povinný údaj.',
    'required_if' => ':attribute je povinný údaj ak :other je :value.',
    'required_unless' => ':attribute je povinný údaj pokiaľ :other je v :values.',
    'required_with' => ':attribute je povinný údaj ak :values je zadaný.',
    'required_with_all' => ':attribute je povinný údaj ak :values sú zadané.',
    'required_without' => ':attribute je povinný údaj ak :values nie je zadaný.',
    'required_without_all' => ':attribute je povinný údaj ak :values nie sú zadané.',
    'same' => ':attribute a :other sa musia zhodovať.',
    'size' => [
        'numeric' => ':attribute musí byť :size.',
        'file' => ':attribute musí byť :size kilobajtov.',
        'string' => ':attribute musí mať :size písmen.',
        'array' => ':attribute musí mať :size položiek.',
    ],
    'starts_with' => ':attribute musí začať na: :values.',
    'string' => ':attribute musí byť text.',
    'timezone' => ':attribute musí byť validné časové pásmo.',
    'unique' => ':attribute už bol použitý.',
    'uploaded' => ':attribute sa nepodarilo nahrať.',
    'url' => ':attribute nie je validná URL adresa.',
    'uuid' => ':attribute musí byť validné UUID.',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Here you may specify custom validation messages for attributes using the
    | convention "attribute.rule" to name the lines. This makes it quick to
    | specify a specific custom language line for a given attribute rule.
    |
    */

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'custom-message',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | The following language lines are used to swap our attribute placeholder
    | with something more reader friendly such as "E-Mail Address" instead
    | of "email". This simply helps us make our message more expressive.
    |
    */

    'attributes' => [
        'password' => 'Heslo',
        'username' => 'Prihlasovacie meno',
        'bill' => 'Prijatá faktúra',
        'docs' => 'Prepravné dokumenty',
        'email' => 'E-mail',
    ],

];
