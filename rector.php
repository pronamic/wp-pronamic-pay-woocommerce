<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Php80\Rector\Class_\ClassPropertyAssignToConstructorPromotionRector;

return RectorConfig::configure()
	->withPaths(
		[
			__DIR__ . '/src',
			__DIR__ . '/tests',
			__DIR__ . '/views',
		]
	)
	->withPhpSets()
	->withSkip(
		[
			ClassPropertyAssignToConstructorPromotionRector::class,
		]
	)
	->withTypeCoverageLevel( 0 );
