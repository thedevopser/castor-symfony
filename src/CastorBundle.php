<?php

namespace TheDevOpser\CastorBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use TheDevOpser\CastorBundle\DependencyInjection\CastorExtension;

class CastorBundle extends Bundle
{
    public function getContainerExtension()
    {
        if (null === $this->extension) {
            $this->extension = new CastorExtension();
        }

        return $this->extension;
    }
}