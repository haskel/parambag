<?php

namespace Haskel\ParamBag\Restriction;

class SpecificKeyRestriction implements KeyRestrictionInterface
{
    private $keys = [];

    public function __construct(array $keys = [])
    {
        $this->keys = array_flip($keys);
    }

    /** {@inheritdoc} */
    public function isValid($key)
    {
        return isset($this->keys[$key]);
    }

    /** {@inheritdoc} */
    public function getMessage($key)
    {
        return sprintf('Key is not in list: [%s]', implode(", ", array_keys($this->keys)));
    }
}