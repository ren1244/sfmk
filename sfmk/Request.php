<?php

namespace sfmk;

class Request
{
    public function __construct()
    {
        $this->method=$_SERVER['REQUEST_METHOD'];
        //REQUEST_URI
    }
}