<?php

namespace Src\Shared\Collection;

use ArrayAccess;
use Countable;
use IteratorAggregate;
use Traversable;
use ArrayIterator;
use InvalidArgumentException;
use OutOfBoundsException;

/**
 * @template T
 * @implements ArrayAccess<int|string, T>
 * @implements IteratorAggregate<int|string, T>
 */
class Collection implements ArrayAccess, IteratorAggregate, Countable
{
    /**
     * @var array<int|string, T>
     */
    protected array $items;

    /**
     * @param array<int|string, T> $items
     */
    public function __construct(array $items = [])
    {
        $this->items = $items;
    }

    /**
     * Crea una nueva Collection desde un array
     *
     * @template U
     * @param array<int|string, U> $items
     * @return Collection<U>
     */
    public static function make(array $items = []): self
    {
        return new self($items);
    }

    /**
     * @param callable(T): bool $callback
     * @return Collection<T>
     */
    public function filter(callable $callback): self
    {
        return new self(array_filter($this->items, $callback, ARRAY_FILTER_USE_BOTH));
    }

    /**
     * @template U
     * @param callable(T, int|string): U $callback
     * @return Collection<U>
     */
    public function map(callable $callback): self
    {
        return new self(array_map($callback, $this->items, array_keys($this->items)));
    }

    /**
     * Ejecuta una función para cada elemento
     *
     * @param callable(T, int|string): void $callback
     * @return Collection<T>
     */
    public function each(callable $callback): self
    {
        foreach ($this->items as $key => $item) {
            $callback($item, $key);
        }

        return $this;
    }

    /**
     * Reduce la colección a un solo valor
     *
     * @template U
     * @param callable(U|null, T): U $callback
     * @param U|null $initial
     * @return U|null
     */
    public function reduce(callable $callback, $initial = null)
    {
        return array_reduce($this->items, $callback, $initial);
    }

    /**
     * Devuelve el primer elemento que cumple la condición
     *
     * @param callable(T): bool|null $callback
     * @return T|null
     */
    public function first(?callable $callback = null)
    {
        if ($callback === null) {
            return reset($this->items) ?: null;
        }

        foreach ($this->items as $item) {
            if ($callback($item)) {
                return $item;
            }
        }

        return null;
    }

    /**
     * Devuelve los valores de una columna específica
     *
     * @param string|int $columnKey
     * @param string|int|null $indexKey
     * @return Collection<mixed>
     */
    public function pluck($columnKey, $indexKey = null): self
    {
        $result = [];

        foreach ($this->items as $key => $item) {
            if (is_array($item)) {
                $value = $item[$columnKey] ?? null;
                $newKey = $indexKey ? ($item[$indexKey] ?? $key) : $key;
            } elseif (is_object($item)) {
                $value = $item->{$columnKey} ?? null;
                $newKey = $indexKey ? ($item->{$indexKey} ?? $key) : $key;
            } else {
                continue;
            }

            if ($indexKey !== null) {
                $result[$newKey] = $value;
            } else {
                $result[] = $value;
            }
        }

        return new self($result);
    }

    /**
     * Agrupa elementos por una clave
     *
     * @param string|callable(T): mixed $groupBy
     * @return Collection<Collection<T>>
     */
    public function groupBy($groupBy): self
    {
        $groups = [];

        foreach ($this->items as $key => $item) {
            if (is_callable($groupBy)) {
                $groupKey = $groupBy($item, $key);
            } elseif (is_array($item)) {
                $groupKey = $item[$groupBy] ?? null;
            } elseif (is_object($item)) {
                $groupKey = $item->{$groupBy} ?? null;
            } else {
                $groupKey = $item;
            }

            if (!isset($groups[$groupKey])) {
                $groups[$groupKey] = [];
            }

            $groups[$groupKey][$key] = $item;
        }

        return new self(array_map(fn($group) => new self($group), $groups));
    }

    /**
     * Ordena la colección
     *
     * @param callable(T, T): int|null $callback
     * @return Collection<T>
     */
    public function sort(?callable $callback = null): self
    {
        $items = $this->items;

        if ($callback) {
            uasort($items, $callback);
        } else {
            asort($items);
        }

        return new self($items);
    }

    /**
     * Ordena la colección por valores descendentes
     *
     * @return Collection<T>
     */
    public function sortDesc(): self
    {
        $items = $this->items;
        arsort($items);
        return new self($items);
    }

    /**
     * Devuelve valores únicos
     *
     * @return Collection<T>
     */
    public function unique(): self
    {
        return new self(array_unique($this->items, SORT_REGULAR));
    }

    /**
     * Combina con otra colección
     *
     * @param Collection<T>|array<T> $items
     * @return Collection<T>
     */
    public function merge($items): self
    {
        $array = $items instanceof self ? $items->toArray() : $items;
        return new self(array_merge($this->items, $array));
    }

    /**
     * Añade un elemento al final
     *
     * @param T $item
     * @return Collection<T>
     */
    public function push($item): self
    {
        $this->items[] = $item;
        return $this;
    }

    /**
     * Elimina y devuelve el último elemento
     *
     * @return T|null
     */
    public function pop()
    {
        return array_pop($this->items);
    }

    /**
     * Añade un elemento al inicio
     *
     * @param T $item
     * @return Collection<T>
     */
    public function prepend($item): self
    {
        array_unshift($this->items, $item);
        return $this;
    }

    /**
     * Elimina y devuelve el primer elemento
     *
     * @return T|null
     */
    public function shift()
    {
        return array_shift($this->items);
    }

    /**
     * Obtiene un slice de la colección
     *
     * @param int $offset
     * @param int|null $length
     * @return Collection<T>
     */
    public function slice(int $offset, ?int $length = null): self
    {
        return new self(array_slice($this->items, $offset, $length, true));
    }

    /**
     * Salta los primeros N elementos
     *
     * @param int $count
     * @return Collection<T>
     */
    public function skip(int $count): self
    {
        return $this->slice($count);
    }

    /**
     * Toma los primeros N elementos
     *
     * @param int $count
     * @return Collection<T>
     */
    public function take(int $count): self
    {
        return $this->slice(0, $count);
    }

    /**
     * Verifica si la colección está vacía
     */
    public function isEmpty(): bool
    {
        return empty($this->items);
    }

    /**
     * Verifica si la colección no está vacía
     */
    public function isNotEmpty(): bool
    {
        return !$this->isEmpty();
    }

    /**
     * Convierte a array
     *
     * @return array<int|string, T>
     */
    public function toArray(): array
    {
        return $this->items;
    }

    /**
     * Convierte a JSON
     */
    public function toJson(int $options = JSON_PRETTY_PRINT): string
    {
        return json_encode($this->toArray(), $options);
    }

    /**
     * Método mágico para convertir a string
     */
    public function __toString(): string
    {
        return $this->toJson();
    }

    /**
     * Implementación de ArrayAccess: offsetExists
     *
     * @param int|string $offset
     */
    public function offsetExists(mixed $offset): bool
    {
        return isset($this->items[$offset]) || array_key_exists($offset, $this->items);
    }

    /**
     * Implementación de ArrayAccess: offsetGet
     *
     * @param int|string $offset
     * @return T
     */
    public function offsetGet(mixed $offset): mixed
    {
        if (!$this->offsetExists($offset)) {
            throw new OutOfBoundsException("Offset $offset does not exist");
        }

        return $this->items[$offset];
    }

    /**
     * Implementación de ArrayAccess: offsetSet
     *
     * @param int|string|null $offset
     * @param T $value
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        if ($offset === null) {
            $this->items[] = $value;
        } else {
            $this->items[$offset] = $value;
        }
    }

    /**
     * Implementación de ArrayAccess: offsetUnset
     *
     * @param int|string $offset
     */
    public function offsetUnset(mixed $offset): void
    {
        unset($this->items[$offset]);
    }

    /**
     * Implementación de IteratorAggregate
     *
     * @return Traversable<int|string, T>
     */
    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->items);
    }

    /**
     * Implementación de Countable
     */
    public function count(): int
    {
        return count($this->items);
    }

    /**
     * Obtiene todas las claves
     *
     * @return Collection<int|string>
     */
    public function keys(): self
    {
        return new self(array_keys($this->items));
    }

    /**
     * Obtiene todos los valores
     *
     * @return Collection<T>
     */
    public function values(): self
    {
        return new self(array_values($this->items));
    }

    /**
     * Busca un elemento
     *
     * @param callable(T): bool $callback
     * @return int|string|false
     */
    public function search(callable $callback)
    {
        foreach ($this->items as $key => $item) {
            if ($callback($item)) {
                return $key;
            }
        }

        return false;
    }

    /**
     * Verifica si algún elemento cumple la condición
     *
     * @param callable(T): bool $callback
     */
    public function contains(callable $callback): bool
    {
        return $this->search($callback) !== false;
    }

    /**
     * Aplana la colección un nivel
     *
     * @return Collection<mixed>
     */
    public function flatten(): self
    {
        $result = [];

        foreach ($this->items as $item) {
            if ($item instanceof self) {
                $item = $item->toArray();
            }

            if (is_array($item)) {
                $result = array_merge($result, $item);
            } else {
                $result[] = $item;
            }
        }

        return new self($result);
    }

    /**
     * Transforma la colección y aplana el resultado
     *
     * @template U
     * @param callable(T): array<U>|Collection<U> $callback
     * @return Collection<U>
     */
    public function flatMap(callable $callback): self
    {
        return $this->map($callback)->flatten();
    }
}
