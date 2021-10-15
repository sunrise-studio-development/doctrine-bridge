<?php declare(strict_types=1);

namespace Sunrise\Bridge\Doctrine\Tests\Fixtures;

use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\ManagerRegistry as ManagerRegistryInterface;
use Sunrise\Bridge\Doctrine\EntityManagerRegistry;

trait EntityManagerRegistryAwareTrait
{

    /**
     * @return array
     */
    public function getDoctrineConfig() : array
    {
        return (function () : array {
            return require __DIR__ . '/config/doctrine.php';
        })->call($this);
    }

    /**
     * @var array
     */
    public function getMigrationsConfig() : array
    {
        return (function () : array {
            return require __DIR__ . '/config/migrations.php';
        })->call($this);
    }

    /**
     * @param string|null $name
     *
     * @return ManagerRegistryInterface
     */
    private function getEntityManagerRegistry(?string $name = null) : ManagerRegistryInterface
    {
        $config = $this->getDoctrineConfig();

        $registry = new EntityManagerRegistry($config, $name);

        foreach ($registry->getManagers() as $manager) {
            $schema = new SchemaTool($manager);
            $schema->dropSchema($manager->getMetadataFactory()->getAllMetadata());
            $schema->createSchema($manager->getMetadataFactory()->getAllMetadata());
        }

        return $registry;
    }
}
