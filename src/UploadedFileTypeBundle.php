<?php

namespace Tiloweb\UploadedFileTypeBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class UploadedFileTypeBundle extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}