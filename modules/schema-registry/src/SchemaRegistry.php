<?php

namespace Modules\SchemaRegistry;

use Opis\JsonSchema\Exceptions\SchemaException;

class SchemaRegistry
{
    public function __construct(
        private readonly Validator $validator
    ) {
    }

    /**
     * @param $data
     * @param string $schemaName
     * @param int $version
     * @return bool
     * @throws SchemaException
     */
    public function validateEvent($data, string $schemaName, int $version = 1): bool
    {
        return $this->validator->validate(
            $data,
            $schemaName,
            $version
        );
    }
}
