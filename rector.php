<?php

declare(strict_types=1);

use Rector\CodeQuality\Rector\Class_\InlineConstructorDefaultToPropertyRector;
use Rector\Config\RectorConfig;
use Rector\Php74\Rector\LNumber\AddLiteralSeparatorToNumberRector;
use Rector\Php82\Rector\Class_\ReadOnlyClassRector;
use Rector\PHPUnit\AnnotationsToAttributes\Rector\ClassMethod\DataProviderAnnotationToAttributeRector;
use Rector\PHPUnit\PHPUnit100\Rector\Class_\StaticDataProviderClassMethodRector;
use Rector\PHPUnit\PHPUnit60\Rector\ClassMethod\ExceptionAnnotationRector;
use Rector\Set\ValueObject\LevelSetList;
use Rector\TypeDeclaration\Rector\ClassMethod\AddMethodCallBasedStrictParamTypeRector;
use Rector\TypeDeclaration\Rector\ClassMethod\ReturnTypeFromStrictNativeCallRector;
use Rector\TypeDeclaration\Rector\ClassMethod\ReturnTypeFromStrictScalarReturnExprRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ]);

    // register single rules
    $rectorConfig->rules([
        DataProviderAnnotationToAttributeRector::class,
        ExceptionAnnotationRector::class,
        StaticDataProviderClassMethodRector::class,
        ReturnTypeFromStrictNativeCallRector::class,
        ReturnTypeFromStrictScalarReturnExprRector::class,
        AddMethodCallBasedStrictParamTypeRector::class,
    ]);
    $rectorConfig->skip([
        AddLiteralSeparatorToNumberRector::class,
        ReadOnlyClassRector::class,
    ]);
    $rectorConfig->importNames();

   // $rectorConfig->cacheDirectory(__DIR__."/cache");


    // define sets of rules
        $rectorConfig->sets([
            LevelSetList::UP_TO_PHP_82
        ]);
};
