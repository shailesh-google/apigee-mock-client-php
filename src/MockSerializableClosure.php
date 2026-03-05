<?php namespace Apigee\MockClient;

use Closure;
use function Opis\Closure\serialize as opis_serialize;
use function Opis\Closure\unserialize as opis_unserialize;

/**
 * This class acts as a wrapper for a closure, and allows it to be serialized.
 * It has been updated to use opis/closure v4.x helper functions.
 */
class MockSerializableClosure implements \Serializable
{
    /**
     * The closure being wrapped for serialization.
     *
     * @var Closure
     */
    private $closure;

    /**
     * Create a new serializable closure instance.
     *
     * @param Closure $closure
     * @param mixed   $serializer Kept for backwards compatibility with the signature.
     */
    public function __construct(Closure $closure, $serializer = null)
    {
        $this->closure = $closure;
    }

    /**
     * Return the original closure object.
     *
     * @return Closure
     */
    public function getClosure()
    {
        return $this->closure;
    }

    /**
     * Delegates the closure invocation to the actual closure object.
     *
     * @return mixed
     */
    public function __invoke()
    {
        return call_user_func_array($this->closure, func_get_args());
    }

    /**
     * Clones the SerializableClosure with a new bound object and class scope.
     *
     * @param mixed $newthis  The object to which the closure should be bound.
     * @param mixed $newscope The class scope to which the closure is to be associated.
     *
     * @return MockSerializableClosure
     */
    public function bindTo($newthis, $newscope = 'static')
    {
        return new self(
            $this->closure->bindTo($newthis, $newscope)
        );
    }

    /**
     * Magic method for PHP 7.4+ serialization.
     *
     * @return array
     */
    public function __serialize(): array
    {
        return [
            'closure_string' => opis_serialize($this->closure)
        ];
    }

    /**
     * Magic method for PHP 7.4+ unserialization.
     *
     * @param array $data
     */
    public function __unserialize(array $data): void
    {
        $this->closure = opis_unserialize($data['closure_string']);
    }

    /**
     * Serializes the closure wrapper.
     *
     * @return string|null
     */
    public function serialize()
    {
        try {
            return opis_serialize($this->closure);
        } catch (\Exception $e) {
            trigger_error(
                'Serialization of closure failed: ' . $e->getMessage(),
                E_USER_NOTICE
            );
            return null;
        }
    }

    /**
     * Unserializes the closure wrapper.
     *
     * @param string $serialized
     */
    public function unserialize($serialized)
    {
        $this->closure = opis_unserialize($serialized);
    }

    /**
     * Returns closure data for `var_dump()`.
     *
     * @return array
     */
    public function __debugInfo()
    {
        return [
            'closure' => $this->closure
        ];
    }
}
