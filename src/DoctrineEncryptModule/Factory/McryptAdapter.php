<?php

namespace DoctrineEncryptModule\Factory;

use Interop\Container\ContainerInterface;
use Interop\Container\Exception\ContainerException;
use Zend\Crypt\BlockCipher;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;
use Zend\ServiceManager\Exception\ServiceNotFoundException;
use Zend\ServiceManager\Factory\FactoryInterface;

class McryptAdapter implements FactoryInterface {

    /**
     * Create an object
     *
     * @param  ContainerInterface $container
     * @param  string $requestedName
     * @param  null|array $options
     * @return object
     * @throws ServiceNotFoundException if unable to resolve the service.
     * @throws ServiceNotCreatedException if an exception is raised when
     *     creating a service.
     * @throws ContainerException if any other error occurs
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null) {
        $config = $container->get('Config');

        if (empty($config['doctrine']['encryption']['key'])) {
            throw new \InvalidArgumentException('You need to define a non-empty key in doctrine.encryption.key config');
        }
        $key = $config['doctrine']['encryption']['key'];

        $salt = null;
        if (!empty($config['doctrine']['encryption']['salt'])) {
            $salt = $config['doctrine']['encryption']['salt'];
        }

        $cipher = BlockCipher::factory('mcrypt');
        $cipher->setKey($key);
        if ($salt) {
            $cipher->setSalt($salt);
        }
        return $cipher;
    }
}
