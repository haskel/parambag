<?php

namespace Haskel\ParamBag;

class ImmutableParamBag extends ParamBag
{
    /** {@inheritdoc} */
    final public function set($key, $value, $type = null)
    {
        if ($this->has($key)) {
            return;
        }

        parent::set($key, $value, $type);
    }
}