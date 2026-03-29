<?php

namespace App\EventListener;

use App\Service\ServiceException;
use App\Service\ServiceExceptionData;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;

class ExceptionListener
{
    public function __construct(private readonly LoggerInterface $logger)
    {
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        if ($exception instanceof ServiceException) {

            $exceptionData = $exception->getExceptionData();
            $this->logger->warning('HTTP exception occurred', [
                'type'    => $exceptionData->getType(),
                'status'  => $exceptionData->getStatusCode(),
                'message' => $exception->getMessage(),
            ]);
        } else {
            $statusCode  = Response::HTTP_INTERNAL_SERVER_ERROR;
            $exceptionData = new ServiceExceptionData($statusCode, $exception->getMessage());

            $this->logger->error('Unhandled exception', [
                'message' => $exception->getMessage(),
                'file'    => $exception->getFile(),
                'line'    => $exception->getLine(),
            ]);
        }

        $response = new JsonResponse($exceptionData->toArray(), $exceptionData->getStatusCode());
        $event->setResponse($response);
    }
}