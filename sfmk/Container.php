<?php
namespace sfmk;

class Container
{
    private $container=[];
    private $currentName=false;
    private $shareBuffer=[];
    
    public function add($name, $class=false)
    {
        if(isset($this->container[$name])) {
            throw new Exception('sfmk\\container->add: name '.$name.' repeated.');
            return $this;
        }
        $this->container[$name]=[
            'class'=>($class===false?$name:$class),
            'arguments'=>[],
            'shared'=>false
        ];
        $this->currentName=$name;
        return $this;
    }
    
    public function addArgument($argument)
    {
        if(!isset($this->container[$this->currentName])) {
            throw new Exception('sfmk\\container->addArgument: bad container element');
            return $this;
        }
        $this->container[$this->currentName]['arguments'][]=$argument;
        return $this;
    }
    
    public function setShared($shared=true)
    {
        if(!isset($this->container[$this->currentName])) {
            throw new Exception('sfmk\\container->addArgument: bad container element');
            return $this;
        }
        $this->container[$this->currentName]['shared']=$shared;
        return $this;
    }
    
    public function get($name)
    {
        if(!isset($this->container[$name])) {
            return null;
        }
        $classConfig=$this->container[$name];
        if(isset($this->shareBuffer[$name])) {
            return $this->shareBuffer[$name];
        }
        $argList=[]; //要傳入 consruct 的參數
        foreach($classConfig['arguments'] as $param) {
            if(gettype($param)==='string' && isset($this->container[$param])) {
                $argList[]=$this->get($param);
            } else {
                $argList[]=$param;
            }
        }
        $reflector=new \ReflectionClass($classConfig['class']);
        $obj=$reflector->newInstanceArgs($argList);
        if($classConfig['shared']) {
            $this->shareBuffer[$name]=$obj;
        }
        return $obj;
    }
}
