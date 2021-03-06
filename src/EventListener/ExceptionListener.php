<?php declare(strict_types=1);
/*
 * This file is part of the KleijnWeb\SwaggerBundle package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KleijnWeb\SwaggerBundle\EventListener;

use KleijnWeb\PhpApi\RoutingBundle\Routing\RequestMeta;
use KleijnWeb\SwaggerBundle\EventListener\Response\Error\HttpError;
use KleijnWeb\SwaggerBundle\EventListener\Response\Error\LogRefBuilderInterface;
use KleijnWeb\SwaggerBundle\EventListener\Response\ErrorResponseFactoryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * @author John Kleijn <john@kleijnweb.nl>
 */
class ExceptionListener
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ErrorResponseFactoryInterface
     */
    private $errorResponseFactory;

    /**
     * @var LogRefBuilderInterface
     */
    private $logRefBuilder;

    /**
     * @param ErrorResponseFactoryInterface $errorResponseFactory
     * @param LogRefBuilderInterface        $logRefBuilder
     * @param LoggerInterface               $logger
     */
    public function __construct(
        ErrorResponseFactoryInterface $errorResponseFactory,
        LogRefBuilderInterface $logRefBuilder,
        LoggerInterface $logger
    ) {
        $this->logger               = $logger;
        $this->errorResponseFactory = $errorResponseFactory;
        $this->logRefBuilder        = $logRefBuilder;
    }

    /**
     * @param GetResponseForExceptionEvent $event
     */
    public function onKernelException(GetResponseForExceptionEvent $event)
    {
        $request   = $event->getRequest();
        $exception = $event->getException();
        if (!(
            $request->attributes->has(RequestMeta::ATTRIBUTE_URI)
            || $exception instanceof HttpException
        )) {
            return;
        }

        $error = new HttpError($request, $exception, $this->logRefBuilder);

        $this->logger->log(
            $error->getSeverity(),
            "{$error->getMessage()} [logref {$error->getLogRef()}]: {$exception}"
        );

        $event->setResponse($this->errorResponseFactory->create($error));
    }
}
