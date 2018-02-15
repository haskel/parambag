<?php

namespace Haskel\ParamBag\Restriction;

class ScalarKeyRestriction implements KeyRestrictionInterface
{
    /** {@inheritdoc} */
    public function isValid($key)
    {
        return is_scalar($key);
    }

    /** {@inheritdoc} */
    public function getMessage($key)
    {
        return 'Key is not scalar';
    }
}