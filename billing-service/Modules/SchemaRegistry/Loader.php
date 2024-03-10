<?php

namespace Modules\SchemaRegistry;

use Illuminate\Support\Facades\File;
use Junges\Kafka\Message\KafkaAvroSchema;

class Loader
{
    private readonly string $schemasRootPath;

    public function __construct()
    {
        $this->schemasRootPath = '/var/www/html/Modules/SchemaRegistry/Schemas';
    }

    public function getSchema(string $name, int $version)
    {
        return File::get(sprintf('%s/%s/%s.json', $this->schemasRootPath, $name, $version));
    }
}
