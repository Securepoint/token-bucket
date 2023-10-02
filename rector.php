<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector\Class_\InlineConstructorDefaultToPropertyRector;
use Rector\Config\RectorConfig;
use Rector\PHPUnit\AnnotationsToAttributes\Rector\ClassMethod\DataProviderAnnotationToAttributeRector;
use Rector\PHPUnit\PHPUnit100\Rector\Class_\StaticDataProviderClassMethodRector;
use Rector\PHPUnit\PHPUnit60\Rector\ClassMethod\ExceptionAnnotationRector;
use Rector\Set\ValueObject\LevelSetList;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ]);

    // register a single rule
    $rectorConfig->rule(DataProviderAnnotationToAttributeRector::class);
    $rectorConfig->rule(ExceptionAnnotationRector::class);
    $rectorConfig->rule(StaticDataProviderClassMethodRector::class);
    $rectorConfig->importNames();

   // $rectorConfig->cacheDirectory(__DIR__."/cache");


    // define sets of rules
        $rectorConfig->sets([
            LevelSetList::UP_TO_PHP_82
        ]);
};
