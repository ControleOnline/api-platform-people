<?php

$autoloadPaths = [
    __DIR__ . '/../vendor/autoload.php',
    __DIR__ . '/../../../../vendor/autoload.php',
];

foreach ($autoloadPaths as $autoloadPath) {
    if (is_file($autoloadPath)) {
        require_once $autoloadPath;
        break;
    }
}

if (!class_exists('Doctrine\\ORM\\EntityRepository')) {
    eval(<<<'PHP'
namespace Doctrine\ORM {
    class EntityRepository {}

    interface EntityManagerInterface
    {
        public function getRepository(string $className);
    }

    class QueryBuilder
    {
        public function getRootAliases(): array { return ['peopleLink']; }
        public function getAllAliases(): array { return ['peopleLink']; }
        public function leftJoin($join, $alias, $conditionType = null, $condition = null): self { return $this; }
        public function andWhere($where): self { return $this; }
        public function setParameter($key, $value): self { return $this; }
        public function expr(): object {
            return new class {
                public function orX(...$parts): array { return $parts; }
            };
        }
    }
}
PHP);
}

if (!class_exists('Symfony\\Component\\HttpFoundation\\Request')) {
    eval(<<<'PHP'
namespace Symfony\Component\HttpFoundation {
    class ParameterBag
    {
        public function __construct(private array $parameters = []) {}

        public function get(string $key, mixed $default = null): mixed
        {
            return $this->parameters[$key] ?? $default;
        }

        public function has(string $key): bool
        {
            return array_key_exists($key, $this->parameters);
        }

        public function all(?string $key = null): array
        {
            if ($key === null) {
                return $this->parameters;
            }

            $value = $this->parameters[$key] ?? [];

            return is_array($value) ? $value : [];
        }
    }

    class Request
    {
        public ParameterBag $query;

        public function __construct(array $query = [])
        {
            $this->query = new ParameterBag($query);
        }
    }

    class RequestStack
    {
        private array $requests = [];

        public function push(Request $request): void
        {
            $this->requests[] = $request;
        }

        public function getCurrentRequest(): ?Request
        {
            return $this->requests === [] ? null : $this->requests[array_key_last($this->requests)];
        }
    }
}
PHP);
}

if (!interface_exists('Symfony\\Component\\Security\\Core\\Authentication\\Token\\TokenInterface')) {
    eval(<<<'PHP'
namespace Symfony\Component\Security\Core\Authentication\Token {
    interface TokenInterface
    {
        public function getUser();
    }
}

namespace Symfony\Component\Security\Core\Authentication\Token\Storage {
    use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

    interface TokenStorageInterface
    {
        public function getToken(): ?TokenInterface;
    }
}
PHP);
}

if (!class_exists('Symfony\\Component\\Security\\Core\\Exception\\AccessDeniedException')) {
    eval(<<<'PHP'
namespace Symfony\Component\Security\Core\Exception {
    class AccessDeniedException extends \RuntimeException {}
}
PHP);
}

if (!interface_exists('Symfony\\Component\\Serializer\\Normalizer\\NormalizerInterface')) {
    eval(<<<'PHP'
namespace Symfony\Component\Serializer\Normalizer {
    interface NormalizerInterface
    {
        public function normalize(mixed $object, ?string $format = null, array $context = []);
        public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool;
    }

    interface NormalizerAwareInterface
    {
        public function setNormalizer(NormalizerInterface $normalizer): void;
    }

    trait NormalizerAwareTrait
    {
        protected NormalizerInterface $normalizer;

        public function setNormalizer(NormalizerInterface $normalizer): void
        {
            $this->normalizer = $normalizer;
        }
    }
}
PHP);
}
