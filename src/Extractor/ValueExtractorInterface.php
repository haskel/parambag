<?php

namespace Haskel\ParamBag\ValueExtractor;

interface ValueExtractorInterface
{
    /**
     * @param $value
     *
     * @return mixed
     */
    public function extract($value);

    /**
     * @param $key
     * @param $value
     * @param $type
     *
     * @return bool
     */
    public function isFit($key, $value, $type);
}