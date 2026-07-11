<?php

namespace Tests;

use Kirschbaum\OpenApiValidator\ValidatesOpenApiSpec;

abstract class ApiTestCase extends TestCase
{
    use ValidatesOpenApiSpec;
}
