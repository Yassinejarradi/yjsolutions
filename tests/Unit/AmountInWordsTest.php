<?php

use App\Support\AmountInWords;

describe('nombres', function () {
    it('écrit correctement :number', function (int $number, string $expected) {
        expect(AmountInWords::number($number))->toBe($expected);
    })->with([
        [0, 'zéro'],
        [1, 'un'],
        [16, 'seize'],
        [17, 'dix-sept'],
        [20, 'vingt'],
        [21, 'vingt et un'],
        [22, 'vingt-deux'],
        [31, 'trente et un'],
        [61, 'soixante et un'],
        [70, 'soixante-dix'],
        [71, 'soixante et onze'],
        [72, 'soixante-douze'],
        [79, 'soixante-dix-neuf'],
        [80, 'quatre-vingts'],
        [81, 'quatre-vingt-un'],
        [90, 'quatre-vingt-dix'],
        [91, 'quatre-vingt-onze'],
        [99, 'quatre-vingt-dix-neuf'],
        [100, 'cent'],
        [101, 'cent un'],
        [180, 'cent quatre-vingts'],
        [200, 'deux cents'],
        [201, 'deux cent un'],
        [280, 'deux cent quatre-vingts'],
        [1000, 'mille'],
        [1001, 'mille un'],
        [1080, 'mille quatre-vingts'],
        [2000, 'deux mille'],
        [21000, 'vingt et un mille'],
        [80000, 'quatre-vingt mille'],
        [80080, 'quatre-vingt mille quatre-vingts'],
        [100000, 'cent mille'],
        [200000, 'deux cent mille'],
        [235400, 'deux cent trente-cinq mille quatre cents'],
        [1000000, 'un million'],
        [2000000, 'deux millions'],
        [1001000, 'un million mille'],
        [200000000, 'deux cents millions'],
        [80000000, 'quatre-vingts millions'],
        [1000000000, 'un milliard'],
        [3000000000, 'trois milliards'],
    ]);
});

describe('montants en dirhams', function () {
    it('écrit l’exemple du cahier des charges', function () {
        expect(AmountInWords::fromCents(10723535))
            ->toBe('Cent sept mille deux cent trente-cinq dirhams et trente-cinq centimes TTC');
    });

    it('écrit :cents centimes', function (int $cents, string $expected) {
        expect(AmountInWords::fromCents($cents))->toBe($expected);
    })->with([
        [0, 'Zéro dirham TTC'],
        [100, 'Un dirham TTC'],
        [200, 'Deux dirhams TTC'],
        [1, 'Zéro dirham et un centime TTC'],
        [50, 'Zéro dirham et cinquante centimes TTC'],
        [2101, 'Vingt et un dirhams et un centime TTC'],
        [7100, 'Soixante et onze dirhams TTC'],
        [8000, 'Quatre-vingts dirhams TTC'],
        [8100, 'Quatre-vingt-un dirhams TTC'],
        [20000, 'Deux cents dirhams TTC'],
        [100000, 'Mille dirhams TTC'],
        [20000000, 'Deux cent mille dirhams TTC'],
        [100000000, 'Un million de dirhams TTC'],
        [300000000, 'Trois millions de dirhams TTC'],
        [100010000, 'Un million cent dirhams TTC'],
        [300000080, 'Trois millions de dirhams et quatre-vingts centimes TTC'],
    ]);

    it('peut omettre la mention TTC', function () {
        expect(AmountInWords::fromCents(150, ttc: false))->toBe('Un dirham et cinquante centimes');
    });

    it('gère un montant négatif', function () {
        expect(AmountInWords::fromCents(-200))->toBe('Moins deux dirhams TTC');
    });
});
