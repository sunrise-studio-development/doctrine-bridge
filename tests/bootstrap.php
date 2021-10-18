<?php

declare(strict_types=1);

use Doctrine\Common\Annotations\AnnotationRegistry;

if (class_exists(AnnotationRegistry::class)) {
    /** @scrutinizer ignore-deprecated */ AnnotationRegistry::registerLoader('class_exists');
}
