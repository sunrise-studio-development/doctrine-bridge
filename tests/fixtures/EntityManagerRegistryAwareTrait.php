<?php declare(strict_types=1);

namespace Sunrise\Bridge\Doctrine\Tests\Fixtures;

use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\ManagerRegistry as ManagerRegistryInterface;
use Sunrise\Bridge\Doctrine\EntityManagerRegistry;

use function array_replace_recursive;

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
     * @param array<string, mixed>|null $parameters
     *
     * @return ManagerRegistryInterface
     */
    private function getEntityManagerRegistry(
        ?string $name = null,
        ?array $parameters = null
    ) : ManagerRegistryInterface {
        $config = $this->getDoctrineConfig();

        if (isset($parameters)) {
            $config = array_replace_recursive($config, $parameters);
        }

        $registry = new EntityManagerRegistry($config, $name);

        foreach ($registry->getManagers() as $manager) {
            $schema = new SchemaTool($manager);
            $schema->dropSchema($manager->getMetadataFactory()->getAllMetadata());
            $schema->createSchema($manager->getMetadataFactory()->getAllMetadata());
        }

        return $registry;
    }
}
