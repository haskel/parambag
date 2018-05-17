<?php

namespace Haskel\ParamBag\ValueExtractor;

use Haskel\ParamBag\ValueType;

class JsonExtractor implements ValueExtractorInterface
{
    /** {@inheritdoc} */
    public function extract($value)
    {
        return json_decode($value);
    }

    /** {@inheritdoc} */
    public function isFit($key, $value, $type)
    {
        return ($type === ValueType::JSON);
    }
}