<?php

namespace Haskel\ParamBag\Restriction;

interface KeyRestrictionInterface
{
    /**
     * @param $key
     *
     * @return bool
     */
    public function isValid($key);

    /**
     * @param $key
     *
     * @return string
     */
    public function getMessage($key);
}