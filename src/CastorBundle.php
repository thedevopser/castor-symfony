<?php

namespace TheDevOpser\CastorBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use TheDevOpser\CastorBundle\DependencyInjection\CastorExtension;

class CastorBundle extends Bundle
{
    public function getContainerExtension(): ?ExtensionInterface
    {
        if (null === $this->extension) {
            $this->extension = new CastorExtension();
        }

        return $this->extension;
    }
}