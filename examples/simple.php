<?php

use Haskel\ParamBag\BagAnalyzer;
use Haskel\ParamBag\ParamBag;

$params = new ParamBag();

$params->add('key', 'value');
$params->addRestriction('key', $restriction);
$params->addFilter('key', $filter);
$params->addTransformer('key', $restriction);
$params->addCleaner('key', $restriction);
$params->setStagesOrder('key', [ParamBagStage::FILTER, ParamBagStage::RESTRICTION, ParamBagStage::EXTRACT, ParamBagStage::TRANSFORM, ParamBagStage::CLEAN]);
$params->setStagesOrder('key', $restriction);

$bagAnalyzer = new BagAnalyzer();
$bagAnalyzer->showStages('key');
$bagAnalyzer->showStages();
$bagAnalyzer->showRestrictions();
$bagAnalyzer->test(['key' => 'value']);

// Dump to array
$params->dump();
// Dump to json
$params->json();

/**
 * @param ParamBag $paramBag
 */
function doSomething(ParamBag $paramBag)
{

}

/**
 * @param       $name
 * @param       $city
 * @param array $locations
 */
function doAnything($name, $city, $locations = [])
{
    $paramBag = new ParamBag($name, $city, $locations);
}

/**
 * @param       $name
 * @param       $city
 * @param array $locations
 * @ParamBaged
 */
function doThing($name, $city, $locations = [])
{
    // вытаскивает из контейнера подготовленную схему метода помеченную аннотацией и запихивает в ParamBag
    $paramBag = new FuncParamBag();
}