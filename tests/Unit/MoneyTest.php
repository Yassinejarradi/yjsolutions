<?php

use App\Support\DecimalInput;
use App\Support\Money;

it('formate les centimes au format marocain', function (int $cents, string $expected) {
    expect(Money::format($cents))->toBe($expected);
})->with([
    [0, '0,00'],
    [5, '0,05'],
    [100, '1,00'],
    [123456, '1 234,56'],
    [1234567, '12 345,67'],
    [123456789, '1 234 567,89'],
    [-123456, '-1 234,56'],
]);

it('ajoute la devise sur demande', function () {
    expect(Money::format(1234567, withCurrency: true))->toBe('12 345,67 MAD');
});

it('abrège les montants pour le graphique', function (int $cents, string $expected) {
    expect(Money::compact($cents))->toBe($expected);
})->with([
    [0, '0'],
    [95000, '950'],
    [100000, '1 k'],
    [1250000, '12,5 k'],
    [8350500, '83,5 k'],
    [45000000, '450 k'],
    [125000000, '1,25 M'],
]);

it('lit les saisies courantes', function (string $input, ?int $expected) {
    expect(Money::parse($input))->toBe($expected);
})->with([
    ['1234,56', 123456],
    ['1 234,56', 123456],
    ["1\u{00A0}234,56", 123456],
    ['1234.56', 123456],
    ['1.234,56', 123456],
    ['1,234.56', 123456],
    ['2500', 250000],
    ['0,5', 50],
    [',5', 50],
    ['12,', 1200],
    ['-15,25', -1525],
    ['1,005', 101],   // arrondi au plus proche
    ['1,004', 100],
    ['', null],
    ['abc', null],
    ['12a', null],
    ['1,2,3', null],
]);

it('lit les quantités avec 3 décimales', function () {
    expect((string) DecimalInput::parse('1,5', 3))->toBe('1.500')
        ->and((string) DecimalInput::parse('0.3333', 3))->toBe('0.333')
        ->and(DecimalInput::parse(null, 3))->toBeNull();
});

it('prépare une valeur pour un champ de saisie', function () {
    expect(Money::toInput(123450))->toBe('1234,50')
        ->and(Money::toInput(5))->toBe('0,05')
        ->and(Money::toInput(null))->toBe('');
});
