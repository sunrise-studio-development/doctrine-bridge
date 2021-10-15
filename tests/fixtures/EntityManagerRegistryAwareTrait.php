<?php declare(strict_types=1);

namespace Sunrise\Bridge\Doctrine\Tests\Fixtures;

use Sunrise\Bridge\Doctrine\EntityManagerRegistry;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\ManagerRegistry as ManagerRegistryInterface;

trait EntityManagerRegistryAwareTrait
{

    /**
     * @return array
     */
    public function getConfiguration() : array
    {
        return (function () : array {
            return require __DIR__ . '/configuration.php';
        })->call($this);
    }

    /**
     * @param string|null $name
     *
     * @return ManagerRegistryInterface
     */
    private function getEntityManagerRegistry(?string $name = null) : ManagerRegistryInterface
    {
        $configuration = $this->getConfiguration();

        $entityManagerRegistry = new EntityManagerRegistry($configuration['doctrine'] ?? [], $name);

        foreach ($entityManagerRegistry->getManagers() as $manager) {
            $schema = new SchemaTool($manager);
            $schema->dropSchema($manager->getMetadataFactory()->getAllMetadata());
            $schema->createSchema($manager->getMetadataFactory()->getAllMetadata());
        }

        return $entityManagerRegistry;
    }
}
