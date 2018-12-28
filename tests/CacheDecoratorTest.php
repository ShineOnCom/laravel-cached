<?php


namespace More\Laravel\Cached\Test;


use More\Laravel\Cached\CacheDecorator;
use More\Laravel\Cached\Models\CacheStub;
use PHPUnit\Framework\TestCase;

class CacheDecoratorTest extends TestCase
{
    /** @test */
    public function it_set_and_gets_model_id_of_string_type()
    {
        $model = new CacheStub();
        $cache_decorator = new CacheDecorator($model);
        $cache_decorator->setModelId('some_string');

        $this->assertEquals('some_string', $cache_decorator->getModelId());
    }
}