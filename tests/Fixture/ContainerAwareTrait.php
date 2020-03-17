<?php declare(strict_types=1);

namespace Arus\Doctrine\Bridge\Tests\Fixture;

/**
 * Import classes
 */
use Arus\Doctrine\Bridge\ManagerRegistry;
use DI\Container;
use DI\ContainerBuilder;
use Symfony\Component\Validator\ContainerConstraintValidatorFactory;
use Symfony\Component\Validator\Validation;

/**
 * Import functions
 */
use function DI\autowire;
use function DI\factory;

/**
 * ContainerAwareTrait
 */
trait ContainerAwareTrait
{

    /**
     * Creates and returns the container
     *
     * @return Container
     */
    private function getContainer() : Container
    {
        $builder = new ContainerBuilder();
        $builder->useAnnotations(true);
        $builder->useAutowiring(false);

        $container = $builder->build();

        $container->set('doctrine', autowire(ManagerRegistry::class));

        $container->set('doctrine.configuration', [
            'foo' => [
                'connection' => [
                    'url' => 'sqlite:///' . __DIR__ . '/../db/foo.sqlite',
                ],
                'metadata_sources' => [
                    __DIR__ . '/Entity',
                ],
                'proxy_auto_generate' => false,
                'types' => [
                    Example1DbalType::NAME => Example1DbalType::class,
                ],
            ],
            'bar' => [
                'connection' => [
                    'url' => 'sqlite:///' . __DIR__ . '/../db/bar.sqlite',
                ],
                'metadata_sources' => [
                    __DIR__ . '/Entity',
                ],
                'proxy_auto_generate' => false,
                'types' => [
                    Example2DbalType::NAME => Example2DbalType::class,
                ],
            ],
        ]);

        $container->set('validator', factory(function ($container) {
            $builder = Validation::createValidatorBuilder();

            $builder->enableAnnotationMapping();

            $builder->setConstraintValidatorFactory(
                new ContainerConstraintValidatorFactory($container)
            );

            return $builder->getValidator();
        }));

        return $container;
    }
}
