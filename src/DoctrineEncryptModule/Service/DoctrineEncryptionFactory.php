<?php

namespace DoctrineEncryptModule\Service;

use Doctrine\Common\Annotations\Reader;
use Doctrine\Common\Proxy\Exception\InvalidArgumentException;
use DoctrineEncryptModule\Encryptors\ZendBlockCipherAdapter;
use DoctrineModule\Service\AbstractFactory;
use DoctrineEncrypt\Encryptors\EncryptorInterface;
use DoctrineEncrypt\Subscribers\DoctrineEncryptSubscriber;
use Interop\Container\ContainerInterface;
use Interop\Container\Exception\ContainerException;
use Zend\Crypt\BlockCipher;
use Zend\ServiceManager\Exception\ServiceNotCreatedException;
use Zend\ServiceManager\Exception\ServiceNotFoundException;
use Zend\ServiceManager\ServiceLocatorInterface;

/**
 * Handles configuration of the DoctrineEncryptSubscriber
 */
class DoctrineEncryptionFactory extends AbstractFactory
{
    /**
     * Create service
     *
     * @param ServiceLocatorInterface $container
     * @return DoctrineEncryptSubscriber
     */
    public function createService(ServiceLocatorInterface $container)
    {
        return $this($container, DoctrineEncryptSubscriber::class);
    }

    /**
     * Get the class name of the options associated with this factory.
     *
     * @return string
     */
    public function getOptionsClass()
    {
        return 'DoctrineEncryptModule\Options\Encryption';
    }

    /**
     * @param $reader
     * @param ContainerInterface $sl
     * @return Reader
     * @throws \Doctrine\Common\Proxy\Exception\InvalidArgumentException
     */
    private function createReader($reader, ContainerInterface $sl)
    {
        $reader = $this->hydrdateDefinition($reader, $sl);

        if (!$reader instanceof Reader) {
            throw new InvalidArgumentException(
                'Invalid reader provided. Must implement ' . Reader::class
            );
        }

        return $reader;
    }

    /**
     * @param $adapter
     * @param ContainerInterface $sl
     * @return EncryptorInterface
     * @throws \Doctrine\Common\Proxy\Exception\InvalidArgumentException
     */
    private function createAdapter($adapter, ContainerInterface $sl)
    {
        $adapter = $this->hydrdateDefinition($adapter, $sl);

        if ($adapter instanceof BlockCipher) {
            $adapter = new ZendBlockCipherAdapter($adapter);
        }

        if (!$adapter instanceof EncryptorInterface) {
            throw new InvalidArgumentException(
                'Invalid encryptor provided, must be a service name, '
                . 'class name, an instance, or method returning a DoctrineEncrypt\Encryptors\EncryptorInterface'
            );
        }

        return $adapter;
    }

    /**
     * Hydrates the value into an object
     * @param $value
     * @param ContainerInterface $sl
     * @return object
     */
    private function hydrdateDefinition($value, ContainerInterface $sl)
    {
        if (is_string($value)) {
            if ($sl->has($value)) {
                $value = $sl->get($value);
            } elseif (class_exists($value)) {
                $value = new $value();
            }
        } elseif (is_callable($value)) {
            $value = $value();
        }

        return $value;
    }

    /**
     * Create an object
     *
     * @param  ContainerInterface $container
     * @param  string $requestedName
     * @param  null|array $options
     * @return DoctrineEncryptSubscriber
     * @throws ServiceNotFoundException if unable to resolve the service.
     * @throws ServiceNotCreatedException if an exception is raised when
     *     creating a service.
     * @throws ContainerException if any other error occurs
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null) {
        /** @var \DoctrineEncryptModule\Options\Encryption $options */
        $options = $this->getOptions($container, 'encryption');

        $reader = $this->createReader($options->getReader(), $container);
        $adapter = $this->createAdapter($options->getAdapter(), $container);

        return new DoctrineEncryptSubscriber(
            $reader,
            $adapter
        );
    }
}
