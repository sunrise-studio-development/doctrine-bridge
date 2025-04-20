<?php

/**
 * It's free open-source software released under the MIT License.
 *
 * @author Anatoly Nekhay <afenric@gmail.com>
 * @copyright Copyright (c) 2025, Anatoly Nekhay
 * @license https://github.com/sunrise-studio-development/doctrine-bridge/blob/master/LICENSE
 * @link https://github.com/sunrise-studio-development/doctrine-bridge
 */

declare(strict_types=1);

namespace Sunrise\Bridge\Doctrine\Integration\Router\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Sunrise\Bridge\Doctrine\Dictionary\ErrorMessage;
use Sunrise\Bridge\Doctrine\Dictionary\TranslationDomain;
use Sunrise\Bridge\Doctrine\EntityManagerNameInterface;
use Sunrise\Bridge\Doctrine\EntityManagerRegistryInterface;
use Sunrise\Bridge\Doctrine\Exception\EntityValidationFailedException;
use Sunrise\Bridge\Doctrine\Integration\Validator\Constraint\UniqueEntity;
use Sunrise\Http\Router\Exception\HttpException;
use Sunrise\Http\Router\Validation\ConstraintViolation\ValidatorConstraintViolationAdapter;
use Sunrise\Http\Router\Validation\ConstraintViolationInterface as RouterConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationInterface as ValidatorConstraintViolationInterface;

use function array_map;

final readonly class RequestTerminationMiddleware implements MiddlewareInterface
{
    public const DEFAULT_VALIDATION_FAILED_ERROR_STATUS_CODE = 400;
    public const DEFAULT_VALIDATION_FAILED_ERROR_MESSAGE = ErrorMessage::VALIDATION_FAILED;

    public function __construct(
        private EntityManagerRegistryInterface $entityManagerRegistry,
        /** @var array<array-key, EntityManagerNameInterface> */
        private array $flushableEntityManagerNames,
        /** @var array<array-key, EntityManagerNameInterface> */
        private array $clearableEntityManagerNames,
        private ?int $validationFailedErrorStatusCode = null,
        private ?string $validationFailedErrorMessage = null,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            $response = $handler->handle($request);
            $this->flushEntityManagers();
            return $response;
        } catch (EntityValidationFailedException $e) {
            throw $this->adaptEntityValidationFailedException($e);
        } finally {
            $this->clearEntityManagers();
        }
    }

    private function flushEntityManagers(): void
    {
        foreach ($this->flushableEntityManagerNames as $entityManagerName) {
            if ($this->entityManagerRegistry->hasEntityManager($entityManagerName)) {
                $this->entityManagerRegistry->getEntityManager($entityManagerName)->flush();
            }
        }
    }

    private function clearEntityManagers(): void
    {
        foreach ($this->clearableEntityManagerNames as $entityManagerName) {
            if ($this->entityManagerRegistry->hasEntityManager($entityManagerName)) {
                $this->entityManagerRegistry->getEntityManager($entityManagerName)->clear();
            }
        }
    }

    /**
     * @since 3.3.0
     */
    private function adaptEntityValidationFailedException(EntityValidationFailedException $e): HttpException
    {
        $errorStatusCode = $this->validationFailedErrorStatusCode ?? self::DEFAULT_VALIDATION_FAILED_ERROR_STATUS_CODE;
        $errorMessage = $this->validationFailedErrorMessage ?? self::DEFAULT_VALIDATION_FAILED_ERROR_MESSAGE;

        return (new HttpException($errorMessage, $errorStatusCode, previous: $e))
            ->setTranslationDomain(TranslationDomain::DOCTRINE_BRIDGE)
            ->addConstraintViolation(...array_map(
                static fn(ValidatorConstraintViolationInterface $violation): RouterConstraintViolationInterface =>
                    new ValidatorConstraintViolationAdapter($violation, match ($violation->getCode()) {
                        UniqueEntity::ERROR_CODE => TranslationDomain::DOCTRINE_BRIDGE,
                        default => null,
                    }),
                [...$e->getConstraintViolations()],
            ));
    }
}
