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
     * @var ParamBagSettings
     */
    protected $settings;

    /**
     * @param array $hash
     * @param array $keyRestrictions
     *
     * @throws ParamBagException
     */
    public function __construct(array $hash, array $keyRestrictions = [])
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
        $this->checkKey($key);

        if (!isset($this->bag[$key])) {
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
        $this->checkKey($key);

        if (!isset($this->bag[$key])) {
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
     * @param int   $type
     *
     * @throws ParamBagException
     */
    public function set($key, $value, $type = null)
    {
        if (is_callable($key)) {
            $this->hasCallableValue = true;
            $key = $key();
        }
        $this->checkKey($key);

        $this->bag[$key] = $this->extractValue($key, $value, $type);
    }

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
     * @param $key
     *
     * @return bool
     */
    public function keyExists($key)
    {
        return (isset($this->bag[$key]));
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
    public function json()
    {
        return json_encode($this->all());
    }

    /**
     * @param $key
     *
     * @throws ParamBagException
     */
    private function checkKey($key)
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