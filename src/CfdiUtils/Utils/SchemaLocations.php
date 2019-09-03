<?php
namespace CfdiUtils\Utils;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use Traversable;

class SchemaLocations implements Countable, IteratorAggregate
{
    /** @var array<string, string> */
    private $pairs = [];

    /**
     * SchemaLocations constructor.
     * @param array<string, string> $pairs
     */
    public function __construct(array $pairs = [])
    {
        foreach ($pairs as $namespace => $location) {
            $this->append($namespace, $location);
        }
    }

    /**
     * Create a collection of namespaces (key) and location (value)
     *
     * @param string $schemaLocationValue
     * @param bool $includeLastUnpairedItem
     * @return self
     */
    public static function fromString(string $schemaLocationValue, bool $includeLastUnpairedItem): self
    {
        $schemaLocations = new self();
        $components = array_values(array_filter(explode(' ', $schemaLocationValue)));
        $length = count($components);
        for ($c = 0; $c < $length; $c = $c + 2) {
            $location = $components[$c + 1] ?? '';
            if ($location != '' || $includeLastUnpairedItem) {
                $schemaLocations->append($components[$c], $location);
            }
        }
        return $schemaLocations;
    }

    public static function fromStingStrictXsd(string $schemaLocationValue): self
    {
        $schemaLocations = new self();
        $components = array_values(array_filter(explode(' ', $schemaLocationValue)));
        $length = count($components);
        for ($c = 0; $c < $length; $c = $c + 1) {
            $namespace = $components[$c];
            $location = $components[$c + 1] ?? '';
            if ('.xsd' === (substr($location, -4) ?: '')) {
                $schemaLocations->append($namespace, $location);
                $c = $c + 1; // skip ns declaration
                continue;
            }
            $schemaLocations->append($namespace, '');
        }
        return $schemaLocations;
    }

    public function isEmpty(): bool
    {
        return (0 === count($this->pairs));
    }

    public function pairs(): array
    {
        return $this->pairs;
    }

    public function has(string $namespace): bool
    {
        return array_key_exists($namespace, $this->pairs);
    }

    /**
     * @return string[]
     */
    public function getNamespacesWithoutLocation(): array
    {
        return array_keys(array_filter($this->pairs, function (string $location): bool {
            return ('' === $location);
        }));
    }

    public function hasAnyNamespaceWithoutLocation(): bool
    {
        return count($this->getNamespacesWithoutLocation()) > 0;
    }

    public function append(string $namespace, string $location)
    {
        $this->pairs[$namespace] = $location;
    }

    public function remove(string $namespace)
    {
        unset($this->pairs[$namespace]);
    }

    public function asString(): string
    {
        return implode(' ', array_filter(array_map(
            function (string $namespace, string $location): string {
                if ('' === $location) {
                    return '';
                }
                return $namespace . ' ' . $location;
            },
            array_keys($this->pairs),
            $this->pairs
        )));
    }

    /**
     * @return Traversable<string, string>
     */
    public function getIterator()
    {
        /** @var Traversable<string, string> $traversable */
        $traversable = new ArrayIterator($this->pairs);
        return $traversable;
    }

    public function count(): int
    {
        return count($this->pairs);
    }
}
