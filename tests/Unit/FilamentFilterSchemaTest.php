<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class FilamentFilterSchemaTest extends TestCase
{
    public function test_filament_filters_do_not_use_schema_on_filter_instances(): void
    {
        $filamentPath = dirname(__DIR__, 2).DIRECTORY_SEPARATOR.'app'.DIRECTORY_SEPARATOR.'Filament';

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($filamentPath, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $contents = file_get_contents($file->getPathname());

            if ($contents === false) {
                $this->fail('Unable to read '.$file->getPathname());
            }

            $this->assertDoesNotMatchRegularExpression(
                '/Filter::make\([\s\S]{0,500}?->schema\(/',
                $contents,
                'Unexpected ->schema() call on a Filament filter in '.$file->getPathname()
            );

            $this->assertDoesNotMatchRegularExpression(
                '/function\s+form\(Form \$form\): Form\s*\{\s*return \$schema/s',
                $contents,
                'Unexpected return $schema in a Filament form() method in '.$file->getPathname()
            );
        }

        $this->assertTrue(true);
    }
}
