<?php

namespace Mautic\PageBundle\Controller;

use Mautic\CoreBundle\Controller\AjaxController as CommonAjaxController;
use Mautic\CoreBundle\Controller\VariantAjaxControllerTrait;
use Mautic\CoreBundle\Helper\InputHelper;
use Mautic\PageBundle\Form\Type\AbTestPropertiesType;
use Mautic\PageBundle\Model\PageModel;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Twig\Environment;

class AjaxController extends CommonAjaxController
{
    use VariantAjaxControllerTrait;

    public function getAbTestFormAction(Request $request, FormFactoryInterface $formFactory, PageModel $pageModel, Environment $twig): JsonResponse
    {
        return $this->sendJsonResponse($this->getAbTestForm(
            $request,
            $pageModel,
            fn ($formType, $formOptions) => $formFactory->create(AbTestPropertiesType::class, [], ['formType' => $formType, 'formTypeOptions' => $formOptions]),
            fn ($form)                   => $this->renderView('@MauticPage/AbTest/form.html.twig', ['form' => $this->setFormTheme($form, $twig, ['@MauticPage/AbTest/form.html.twig', 'MauticPageBundle:FormTheme\Page'])]),
            'page_abtest_settings',
            'page'
        ));
    }

    public function pageListAction(Request $request): JsonResponse
    {
        $filter    = InputHelper::clean($request->query->get('filter'));
        $pageModel = $this->getModel('page.page');
        \assert($pageModel instanceof PageModel);
        $results   = $pageModel->getLookupResults('page', $filter);
        $dataArray = [];

        foreach ($results as $r) {
            $dataArray[] = [
                'label' => $r['title']." ({$r['id']}:{$r['alias']})",
                'value' => $r['id'],
            ];
        }

        return $this->sendJsonResponse($dataArray);
    }

    public function setBuilderContentAction(Request $request): JsonResponse
    {
        $dataArray = ['success' => 0];
        $entityId  = InputHelper::clean($request->request->get('entity'));
        $session   = $request->getSession();

        if (!empty($entityId)) {
            $sessionVar = 'mautic.pagebuilder.'.$entityId.'.content';

            // Check for an array of slots
            $slots   = InputHelper::_($request->request->all()['slots'] ?? [], 'html');
            $content = $session->get($sessionVar, []);

            if (!is_array($content)) {
                $content = [];
            }

            if (!empty($slots)) {
                // Builder was closed so save each content
                foreach ($slots as $slot => $newContent) {
                    $content[$slot] = $newContent;
                }

                $session->set($sessionVar, $content);
                $dataArray['success'] = 1;
            } else {
                // Check for a single slot
                $newContent = InputHelper::html($request->request->get('content'));
                $slot       = InputHelper::clean($request->request->get('slot'));

                if (!empty($slot)) {
                    $content[$slot] = $newContent;
                    $session->set($sessionVar, $content);
                    $dataArray['success'] = 1;
                }
            }
        }

        return $this->sendJsonResponse($dataArray);
    }

    /**
     * Called by parent::getBuilderTokensAction().
     *
     * @return array
     */
    protected function getBuilderTokens($query)
    {
        /** @var PageModel $model */
        $model = $this->getModel('page');

        return $model->getBuilderComponents(null, ['tokens'], $query ?? '');
    }
}
