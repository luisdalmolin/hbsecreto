<?php

namespace App\Draws;

interface DrawAlgorithm
{
    public function draw(DrawInput $input): DrawResult;
}
