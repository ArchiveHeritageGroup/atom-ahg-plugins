<?php

class ahgMuseumPlugin implements \Stringable, ArrayAccess
{
    protected $resource;

    public function __construct($resource)
    {
        $this->resource = $resource;
    }

    public function __toString()
    {
        $string = $this->resource->__toString();

        if (0 < strlen($string)) {
            return $string;
        }

        return sfConfig::get('app_ui_label_informationobject');
    }

    public function __get($name)
    {
        return $this->resource->__get($name);
    }

    public function __set($name, $value)
    {
        return $this->resource->__set($name, $value);
    }

    public function __isset($name)
    {
        return $this->resource->__isset($name);
    }

    public function offsetExists($offset): bool
    {
        return isset($this->resource[$offset]);
    }

    public function offsetGet($offset): mixed
    {
        return $this->resource[$offset];
    }

    public function offsetSet($offset, $value): void
    {
        $this->resource[$offset] = $value;
    }

    public function offsetUnset($offset): void
    {
        unset($this->resource[$offset]);
    }
}
