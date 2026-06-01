<?php

namespace App\Modules\AI\Contracts;

interface EmbeddingClientContract
{
    /** @return float[] */
    public function embed(string $text): array;

    public function getDimensions(): int;

    public function getModel(): string;
}
