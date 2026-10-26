<?php

use App\Support\DocumentCalculator;

it('calcule une facture simple à 20 %', function () {
    $totals = DocumentCalculator::calculate([
        ['quantity' => '1', 'unit_price' => 250000],
        ['quantity' => '3', 'unit_price' => 120050],
    ], 20);

    expect($totals->lineAmounts)->toBe([250000, 360150])
        ->and($totals->subtotal)->toBe(610150)
        ->and($totals->discount)->toBe(0)
        ->and($totals->totalHt)->toBe(610150)
        ->and($totals->vat)->toBe(122030)
        ->and($totals->totalTtc)->toBe(732180);
});

it('arrondit chaque ligne au centime avant de faire la somme', function () {
    // 0,333 × 10,00 = 3,33 ; trois lignes => 9,99 (et non 10,00).
    $totals = DocumentCalculator::calculate(array_fill(0, 3, ['quantity' => '0,333', 'unit_price' => 1000]), 0);

    expect($totals->lineAmounts)->toBe([333, 333, 333])
        ->and($totals->totalTtc)->toBe(999);
});

it('arrondit 0,5 centime vers le haut', function () {
    // 1,5 × 0,33 = 0,495 => 0,50
    expect(DocumentCalculator::lineAmount('1,5', 33))->toBe(50);

    // TVA : 0,25 × 20 % = 0,05 ; 0,03 × 14 % = 0,0042 => 0,00
    expect(DocumentCalculator::calculate([['quantity' => 1, 'unit_price' => 25]], 20)->vat)->toBe(5)
        ->and(DocumentCalculator::calculate([['quantity' => 1, 'unit_price' => 3]], 14)->vat)->toBe(0);
});

it('applique une remise globale en pourcentage avant la TVA', function () {
    $totals = DocumentCalculator::calculate([
        ['quantity' => '2', 'unit_price' => 500000],
    ], 20, '7,5');

    expect($totals->subtotal)->toBe(1000000)
        ->and($totals->discountPercent)->toBe('7.50')
        ->and($totals->discount)->toBe(75000)
        ->and($totals->totalHt)->toBe(925000)
        ->and($totals->vat)->toBe(185000)
        ->and($totals->totalTtc)->toBe(1110000)
        ->and($totals->hasDiscount())->toBeTrue();
});

it('gère tous les taux de TVA marocains', function (int $rate, int $expectedVat) {
    expect(DocumentCalculator::calculate([['quantity' => 1, 'unit_price' => 100000]], $rate)->vat)
        ->toBe($expectedVat);
})->with([
    [20, 20000],
    [14, 14000],
    [10, 10000],
    [7, 7000],
    [0, 0],
]);

it('ignore les lignes vides ou incomplètes', function () {
    $totals = DocumentCalculator::calculate([
        ['quantity' => '', 'unit_price' => 1000],
        ['quantity' => '2', 'unit_price' => null],
        ['quantity' => 'abc', 'unit_price' => 1000],
    ], 20);

    expect($totals->lineAmounts)->toBe([0, 0, 0])
        ->and($totals->totalTtc)->toBe(0);
});

it('donne zéro pour un document sans ligne', function () {
    $totals = DocumentCalculator::calculate([], 20);

    expect($totals->subtotal)->toBe(0)->and($totals->totalTtc)->toBe(0);
});
