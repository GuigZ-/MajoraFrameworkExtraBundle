<?php

namespace Majora\Bundle\FrameworkExtraBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Base trait for controllers traits.
 *
 * @property \Symfony\Component\DependencyInjection\ContainerInterface $container
 */
trait ControllerTrait
{
    /**
     * return controller security mapping as "intention" => "credentials"
     *
     * @example :
     *    return array(
     *       'list'   => array('fetch'),
     *       'get'    => array('read'),
     *       'create' => array('write'),
     *       'udpate' => array('write')
     *    );
     *
     * @see ControllerTrait::checkSecurity()
     *
     * @return array
     */
    protected function getSecurityMapping()
    {
        return array();
    }

    /**
     * checks security for given intention
     *
     * @param string $intention
     * @param mixed  $resource
     *
     * @return boolean
     */
    protected function checkSecurity($intention, $resource = null)
    {
        $securityMapping = $this->getSecurityMapping();

        return $this->container->get('security.authorization_checker')->isGranted(
            (array) (empty($securityMapping[$intention]) ?
                $intention :
                $securityMapping[$intention]
            ),
            $resource
        );
    }

    /**
     * Extract available query filter from request.
     *
     * @param Request $request
     *
     * @return array
     */
    protected function extractQueryFilter(Request $request)
    {
        return array_map(
            function ($value) {
                return array_filter(explode(',', trim($value, ',')), function ($var) {
                    return !empty($var);
                });
            },
            $request->query->all()
        );
    }

    /**
     * Retrieves entity for given id into given repository service.
     *
     * @param $entityId
     * @param $loaderId
     *
     * @return Object
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    protected function retrieveOr404($entityId, $loaderId)
    {
        if (!$this->container->has($loaderId)) {
            throw new NotFoundHttpException(sprintf('Unknow required loader : "%s"',
                $loaderId
            ));
        }

        if (!$entity = $this->container->get($loaderId)->retrieve($entityId)) {
            throw $this->create404($entityId, $loaderId);
        }

        return $entity;
    }

    /**
     * create a formatted http not found exception.
     *
     * @param string $entityId
     * @param string $loaderId
     *
     * @return NotFoundHttpException
     */
    protected function create404($entityId, $loaderId)
    {
        return new NotFoundHttpException(sprintf('Entity with id "%s" not found%s.',
            $entityId,
            $this->container->getParameter('kernel.debug') ?
                sprintf(' : (looked into "%s")', $loaderId) :
                ''
        ));
    }
}
