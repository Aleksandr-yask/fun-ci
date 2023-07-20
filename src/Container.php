<?php

namespace Adm\FunDi;

use Adm\FunDi\Attribute\NonSharedService;

class Container
{
    private array $items = [];

    public function get(string $key)
    {
        if (!isset($this->items[$key])) {
            $this->items[$key] = $this->createObject($key);
        }

        if (!$this->isSharedKey($key)) {
            return $this->createObject($key);
        }

        return $this->items[$key];
    }

    private function createObject(string $key): object
    {
        $class = new \ReflectionClass($key);
        $const = $class->getConstructor();
        $buildParams = [];
        if ($const !== null) {
            $parameters = $const->getParameters();
            foreach ($parameters as $parameter) {
                $buildParams[$parameter->getName()] = $this->get($parameter->getType()->getName());
            }
        }

        return new $key(...$buildParams);
    }

    private function isSharedKey(string $key): bool
    {
        $class = new \ReflectionClass($key);
        $isShared = count($class->getAttributes(NonSharedService::class)) === 0;

        $const = $class->getConstructor();
        if ($const !== null) {
            $parameters = $const->getParameters();
            foreach ($parameters as $parameter) {
                $reflection = new \ReflectionClass($parameter->getType()->getName());
                $classHaveNonSharedAttr = count($reflection->getAttributes(NonSharedService::class)) !== 0;
                if ($classHaveNonSharedAttr) {
                    $isShared = false;
                    break;
                }
            }
        }

        return $isShared;
    }
}
