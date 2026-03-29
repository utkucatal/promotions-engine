<?php
namespace App\EventListener;

use App\Attribute\RateLimit;
use App\Service\ServiceException;
use App\Service\ServiceExceptionData;
use Exception;
use Symfony\Component\HttpFoundation\Request;
use ReflectionException;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\RateLimiter\Policy\SlidingWindowLimiter;
use Symfony\Component\RateLimiter\Storage\StorageInterface;

class RateLimitListener
{
    public function __construct(
        private StorageInterface $storage
    ) {}

    /**
     * @throws Exception
     */
    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) return;

        $request = $event->getRequest();
        $rateLimit = $this->getRateLimitAttribute($request);

        if ($rateLimit === null) return;

        $key = $request->getClientIp() . '_' . $request->getPathInfo();

        $limiter = new SlidingWindowLimiter(
            $key,
            $rateLimit->limit,
            new \DateInterval('PT' . $rateLimit->intervalSeconds . 'S'),
            $this->storage
        );

        if (!$limiter->consume(1)->isAccepted()) {
            throw new ServiceException(new ServiceExceptionData(429, 'Rate limit exceeded'));
        }
    }

    /**
     * @throws ReflectionException
     */
    private function getRateLimitAttribute(Request $request): ?RateLimit
    {
        $controller = $request->attributes->get('_controller');
        if (!$controller || !str_contains($controller, '::')) return null;

        [$class, $method] = explode('::', $controller);
        if (!class_exists($class)) return null;

        $reflection = new \ReflectionMethod($class, $method);
        $attributes = $reflection->getAttributes(RateLimit::class);

        return $attributes ? $attributes[0]->newInstance() : null;
    }
}