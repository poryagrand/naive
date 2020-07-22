<?php

namespace System\Database;

class DB extends MySQL
{
    function __construct()
    {
        parent::__construct(
            "localhost",
            "root",
            "",
            "mydb"
        );
    }
}
