<?php
namespace Haskel\ParamBag;

use Haskel\ParamBag\Restriction\SpecificKeyRestriction;

class StrictParamBag extends AbstractParamBag
{
    /**
     * @param array $hash
     * @param array $keys
     *
     * @throws ParamBagException
     */
    public function __construct(array $hash, array $keys)
    {
        $this->keyRestrictions[] = new SpecificKeyRestriction($keys);

        parent::__construct($hash);
    }
}