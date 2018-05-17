<?php
namespace Haskel\ParamBag;

use Haskel\ParamBag\Restriction\KeyRestrictionInterface;
use Haskel\ParamBag\Restriction\ScalarKeyRestriction;
use Haskel\ParamBag\ValueExtractor\ValueExtractorInterface;

abstract class AbstractParamBag
{
    protected $bag = [];

    /**
     * @var KeyRestrictionInterface[]
     */
    protected $keyRestrictions = [];

    /**
     * @var ValueExtractorInterface[]
     */
    protected $valueExtractors = [];

    /**
     * @var array
     */
    protected $checkedKeys = [];

    /**
     * @var bool
     */
    protected $hasCallableValue = false;

    /**
     * @var bool
     */
    protected $caseSensitiveKey = false;

    /**
     * @var ParamBagSettings
     */
    protected $settings;

    /**
     * @param array $hash
     * @param array $keyRestrictions
     *
     * @throws ParamBagException
     */
    public function __construct(array $hash = [], array $keyRestrictions = [])
    {
        $this->settings = new ParamBagSettings();

        foreach ($keyRestrictions as $keyRestriction) {
            if ($keyRestriction instanceof KeyRestrictionInterface) {
                $this->keyRestrictions[] = $keyRestriction;
            }
        }
        if (!count($this->keyRestrictions)) {
            $this->keyRestrictions[] = new ScalarKeyRestriction();
        }

        foreach ($hash as $key => $value) {
            $this->set($key, $value);
        }
    }

    /**
     * @param mixed $key
     * @param mixed $default
     *
     * @return mixed|null
     *
     * @throws ParamBagException
     */
    public function get($key, $default = null)
    {
        $key = $this->normalizeKey($key);
        $this->checkKeyRestrictions($key);

        if (!$this->has($key)) {
            return null;
        }

        $value = $this->bag[$key];
        if (is_callable($value)) {
            $value = $value();
        }
        if (is_null($value)) {
            $value = $default;
        }

        return $value;
    }

    /**
     * @param $key
     *
     * @return mixed
     *
     * @throws ParamBagException
     */
    public function take($key)
    {
        $key = $this->normalizeKey($key);
        $this->checkKeyRestrictions($key);

        if (!$this->has($key)) {
            throw new ParamBagException("Value not defined");
        }

        $value = $this->bag[$key];
        if (is_callable($value)) {
            $value = $value();
        }

        return $value;
    }

    /**
     * @param mixed $key
     * @param mixed $value
     * @param null  $type
     *
     * @return $this
     * @throws ParamBagException
     */
    public function set($key, $value, $type = null)
    {
        if (is_callable($key)) {
            $key = $key();
        }
        $key = $this->normalizeKey($key);
        $this->checkKeyRestrictions($key);

        if (is_callable($value)) {
            $this->hasCallableValue = true;
        }

        $this->bag[$key] = $this->extractValue($key, $value, $type);

        return $this;
    }

    /**
     * @param $key
     *
     * @return string
     */
    private function normalizeKey($key)
    {
        if ($this->caseSensitiveKey && is_string($key)) {
            $key = strtolower($key);
        }

        return $key;
    }

    /**
     * @param $key
     */
    public function remove($key)
    {
        $key = $this->normalizeKey($key);
        if ($this->has($key)) {
            unset($this->bag[$key]);
        }
    }

    /**
     * @return array
     */
    public function all()
    {
        if (!$this->hasCallableValue) {
            return $this->bag;
        }

        $hash = [];
        foreach ($this->bag as $key => $value) {
            $value = is_callable($value) ? $value() : $value;
            $hash[$key] = $value;
        }

        return $hash;
    }

    /**
     * @return array
     */
    public function values()
    {
        return array_values($this->all());
    }

    /**
     * @return array
     */
    public function keys()
    {
        return array_keys($this->bag);
    }

    /**
     * @param $object
     *
     * @throws ParamBagException
     */
    public function fill($object)
    {
        if (!is_object($object)) {
            throw new ParamBagException("Supports only objects");
        }

        foreach ($this->all() as $key => $value) {
            if (!property_exists($object, $key)) {
                continue;
            }
            $object->{$key} = $value;
        }
    }

    /**
     * @param callable $func
     *
     * @return $this
     * @throws ParamBagException
     */
    public function map($func)
    {
        if (!is_callable($func)) {
            throw new ParamBagException("Argument for method map() must be callable: func(\$key, \$value) {}");
        }

        foreach ($this->all() as $key => $value) {
            $func($key, $value);
        }

        return $this;
    }

    /**
     * @param callable $func
     *
     * @return $this
     * @throws ParamBagException
     */
    public function filter($func)
    {
        if (!is_callable($func)) {
            throw new ParamBagException("Argument for method filter() must be callable: func(\$key, \$value) {}");
        }

        foreach ($this->all() as $key => $value) {
            if ($func($key, $value) === false) {
                $this->remove($key);
            }
        }

        return $this;
    }

    /**
     * @param $key
     *
     * @return bool
     */
    public function has($key)
    {
        $key = $this->normalizeKey($key);

        return (isset($this->bag[$key]));
    }

    /**
     * @return AbstractParamBag
     */
    public function copy()
    {
        return clone $this;
    }

    /**
     * @return array
     */
    public function dump()
    {
        return $this->all();
    }

    /**
     * @return string
     */
    public function json($jsonOptions = 0)
    {
        return json_encode($this->all(), $jsonOptions);
    }

    /**
     * @param $key
     *
     * @throws ParamBagException
     */
    private function checkKeyRestrictions($key)
    {
        if (!$this->settings->checkKey) {
            return;
        }

        if (isset($this->checkedKeys[$key])) {
            if ($this->checkedKeys[$key] instanceof KeyRestrictionInterface) {
                $message = $this->checkedKeys[$key]->getMessage($key) ?: $this->settings->defaultWrongKeyMessage;
                throw new ParamBagException($message);
            } else {
                return;
            }
        }

        foreach ($this->keyRestrictions as $keyRestriction) {
            if ($keyRestriction->isValid($key)) {
                continue;
            }
            if (is_scalar($key)) {
                $this->checkedKeys[$key] = $keyRestriction;
            }

            $message = $keyRestriction->getMessage($key) ?: $this->settings->defaultWrongKeyMessage;
            throw new ParamBagException($message);
        }

        $this->checkedKeys[$key] = true;
    }

    /**
     * @param $key
     * @param $value
     *
     * @return mixed
     */
    private function extractValue($key, $value, $type)
    {
        foreach ($this->valueExtractors as $valueExtractor) {
            if (!$valueExtractor->isFit($key, $value, $type)) {
                continue;
            }
            $value = $valueExtractor->extract($value);
            break;
        }

        return $value;
    }
}