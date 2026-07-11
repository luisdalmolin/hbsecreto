<?php

use Illuminate\Database\Eloquent\Relations\Relation;

test('requires explicit aliases for polymorphic relations', function (): void {
    expect(Relation::requiresMorphMap())->toBeTrue();
});
