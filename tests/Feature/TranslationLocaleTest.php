<?php

use App\Models\Translation;

test('replaceMap is locale-aware — en uses target_en, lo uses target', function () {
    Translation::create([
        'type' => 'replace', 'group' => 'custom',
        'source' => 'ລາຍການ ຢືມ', 'target' => 'ລາຍການ ຢືມ', // Lao identity (no override)
        'target_en' => 'Borrow list', 'is_active' => true,
    ]);

    // lo: target == source → identity row, omitted from the map (no-op)
    expect(Translation::replaceMap('lo'))->not->toHaveKey('ລາຍການ ຢືມ');

    // en: target_en drives the swap
    expect(Translation::replaceMap('en'))->toMatchArray(['ລາຍການ ຢືມ' => 'Borrow list']);
});

test('applyReplacements swaps Lao for English only in en mode', function () {
    Translation::create([
        'type' => 'replace', 'group' => 'custom',
        'source' => 'ບັນທຶກ', 'target' => 'ບັນທຶກ', 'target_en' => 'Save', 'is_active' => true,
    ]);

    app()->setLocale('lo');
    expect(Translation::applyReplacements('<button>ບັນທຶກ</button>'))->toContain('ບັນທຶກ');

    app()->setLocale('en');
    expect(Translation::applyReplacements('<button>ບັນທຶກ</button>'))->toContain('Save');
});

test('an empty target_en falls back to the Lao source in en mode', function () {
    Translation::create([
        'type' => 'replace', 'group' => 'custom',
        'source' => 'ພິມ ບັນຊີ', 'target' => 'ພິມ ບັນຊີ', 'target_en' => null, 'is_active' => true,
    ]);

    app()->setLocale('en');
    // no English provided → source untouched (graceful fallback)
    expect(Translation::applyReplacements('<span>ພິມ ບັນຊີ</span>'))->toContain('ພິມ ບັນຊີ');
});

test('term() is locale-aware with a Lao default fallback', function () {
    Translation::create([
        'type' => 'term', 'group' => 'custom',
        'source' => 'status.draft', 'target' => 'ຮ່າງ', 'target_en' => 'Draft', 'is_active' => true,
    ]);

    app()->setLocale('lo');
    expect(Translation::term('status.draft', 'x'))->toBe('ຮ່າງ');

    app()->setLocale('en');
    expect(Translation::term('status.draft', 'x'))->toBe('Draft');

    // a key with no en value → default (the hard-coded Lao passed by @term)
    expect(Translation::term('status.missing', 'ຄ່າ ເດີມ'))->toBe('ຄ່າ ເດີມ');
});
