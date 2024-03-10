<?php

namespace Modules\SchemaRegistry;

use Exception;
use Opis\JsonSchema\Errors\ValidationError;
use Opis\JsonSchema\Helper;
use Opis\JsonSchema\Validator as JsonSchemaValidator;
use Psr\Log\LoggerInterface;
use Opis\JsonSchema\Exceptions\SchemaException;

class Validator
{
    public function __construct(
        private readonly Loader $loader,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * @param array $data
     * @param $schemaName
     * @param $version
     * @return bool
     * @throws SchemaException
     */
    public function validate(array $data, $schemaName, $version = 1): bool
    {
        $schema = $this->loader->getSchema($schemaName, $version);

        $data = Helper::toJSON($data);

        try {
            $result = (new JsonSchemaValidator())->validate($data, $schema);
        }  catch (SchemaException $e) {
            $this->logger->error($e->getMessage());
            throw $e;
        }

        if ($result->isValid()) {
            return true;
        } else {
            $this->logger->error($result->error(), $this->parseErrors($result->error()));
            throw new Exception(
                sprintf('Failed validation json schema %s version %s: %s', $schemaName, $version, $result->error())
            );
        }
    }

    private function parseErrors(ValidationError $error): array
    {
        $errorsResult[] = ['message' => $error->message(), 'args' => $error->args()];

        foreach ($error->subErrors() as $subError) {
            $errorsResult = array_merge($errorsResult, $this->parseErrors($subError));
        }

        return $errorsResult;
    }
}
