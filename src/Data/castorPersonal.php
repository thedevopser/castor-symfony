<?php

use Castor\Attribute\AsTask;
use Castor\Context;

use function Castor\io;
use function Castor\run;

#[AsTask('Greeting')]
function greet(): void
{
    $name = io()->ask('What is your name?');
    io()->write("Hello, $name!");
}