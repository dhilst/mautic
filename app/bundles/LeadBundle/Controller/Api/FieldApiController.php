<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Controller\Api;

use Mautic\ApiBundle\Controller\CommonApiController;
use Mautic\LeadBundle\Entity\LeadField;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;

/**
 * Class FieldApiController.
 */
class FieldApiController extends CommonApiController
{
    /**
     * Can have value of 'contact' or 'company'.
     *
     * @var string
     */
    protected $fieldObject;

    public function initialize(FilterControllerEvent $event)
    {
        $this->fieldObject     = $this->request->get('object');
        $this->model           = $this->getModel('lead.field');
        $this->entityClass     = LeadField::class;
        $this->entityNameOne   = 'field';
        $this->entityNameMulti = 'fields';
        $this->routeParams     = ['object' => $this->fieldObject];

        if ('contact' === $this->fieldObject) {
            $this->fieldObject = 'lead';
        }

        $repo                = $this->model->getRepository();
        $tableAlias          = $repo->getTableAlias();
        $this->listFilters[] = [
            'column' => $tableAlias.'.object',
            'expr'   => 'eq',
            'value'  => $this->fieldObject,
        ];

        parent::initialize($event);
    }

    /**
     * Sanitizes and returns an array of where statements from the request.
     *
     * @return array
     */
    protected function getWhereFromRequest()
    {
        $where = parent::getWhereFromRequest();

        $where[] = [
            'col'  => 'object',
            'expr' => 'eq',
            'val'  => $this->fieldObject,
        ];

        return $where;
    }

    /**
     * @param $parameters
     * @param $entity
     * @param $action
     *
     * @return mixed|void
     */
    protected function prepareParametersForBinding($parameters, $entity, $action)
    {
        $parameters['object'] = $this->fieldObject;
        // Workaround for mispelled isUniqueIdentifer.
        if (isset($parameters['isUniqueIdentifier'])) {
            $parameters['isUniqueIdentifer'] = $parameters['isUniqueIdentifier'];
        }

        return $parameters;
    }

    /**
     * {@inheritdoc}
     *
     * @param \Mautic\LeadBundle\Entity\Lead &$entity
     * @param                                $parameters
     * @param                                $form
     * @param string                         $action
     */
    protected function preSaveEntity(&$entity, $form, $parameters, $action = 'edit')
    {
        if (isset($parameters['properties'])) {
            $result = $this->model->setFieldProperties($entity, $parameters['properties']);

            if (true !== $result) {
                return $this->returnError($this->get('translator')->trans($result, [], 'validators'), Response::HTTP_BAD_REQUEST);
            }
        }
    }

    /**
     * Creates a new entity.
     *
     * @return Response
     */
    public function newEntityAction()
    {
        $parameters = $this->request->request->all();
        $parameters = $this->sanitizeProperties($parameters);
        $entity     = $this->getNewEntity($parameters);

        if (!$this->checkEntityAccess($entity, 'create')) {
            return $this->accessDenied();
        }

        return $this->processForm($entity, $parameters, 'POST');
    }

    /**
     * @param array parameters
     */
    protected function sanitizeProperties(array $parameters)
    {
        if (isset($parameters['type']) && 'boolean' === $parameters['type']) {
            $parameters['properties']        = $parameters['properties'] ?? [];
            $parameters['properties']['yes'] = $parameters['properties']['yes'] ?? $this->translator->trans('mautic.core.yes');
            $parameters['properties']['no']  = $parameters['properties']['no'] ?? $this->translator->trans('mautic.core.no');
        }

        return $parameters;
    }
}
