<?php

namespace Drupal\Core\Controller\ArgumentResolver;

use Drupal\Core\Routing\RouteMatch;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

/**
 * Yields a RouteMatch object based on the request object passed along.
 */
final class RouteMatchValueResolver implements ValueResolverInterface {

  /**
   * {@inheritdoc}
   */
  public function resolve(Request $request, ArgumentMetadata $argument): array {
    return $argument->getType() === RouteMatchInterface::class || is_subclass_of($argument->getType(), RouteMatchInterface::class) ? [RouteMatch::createFromRequest($request)] : [];
  }

}
