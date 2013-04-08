<?php

namespace Egzakt\SystemBundle\Controller\Backend\Text;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use Egzakt\SystemBundle\Lib\Backend\BaseController;
use Egzakt\SystemBundle\Entity\Text;
use Egzakt\SystemBundle\Form\Backend\TextMainType;
use Egzakt\SystemBundle\Form\Backend\TextStaticType;

/**
 * Text controller.
 *
 * @throws NotFoundHttpException
 */
class TextController extends BaseController
{
    /**
     * Init
     */
    public function init()
    {
        parent::init();

//        $this->getCore()->addNavigationElement($this->getSectionBundle());
    }

    /**
     * Lists all Text entities.
     *
     * @return Response
     */
    public function indexAction()
    {
        $section = $this->getSection();

        $mainEntities = $this->getEm()->getRepository('EgzaktSystemBundle:Text')->findBy(array(
            'section' => $section->getId(),
            'static' => false
        ), array(
            'ordering' => 'ASC'
        ));

        $staticEntities = $this->getEm()->getRepository('EgzaktSystemBundle:Text')->findBy(array(
            'section' => $section->getId(),
            'static' => true
        ), array(
            'ordering' => 'ASC'
        ));

        return $this->render('EgzaktSystemBundle:Backend/Text/Text:list.html.twig', array(
            'mainEntities' => $mainEntities,
            'staticEntities' => $staticEntities,
            'section' => $section,
            'truncateLength' => 100
        ));
    }

    /**
     * Displays a form to edit an existing Text entity.
     * @param Request $request
     * @param integer $id The ID
     *
     * @return RedirectResponse|Response
     */
    public function editAction(Request $request, $id)
    {
        $section = $this->getSection();

        $entity = $this->getEm()->getRepository('EgzaktSystemBundle:Text')->find($id);

        if (false == $entity) {
            $entity = new Text();
            $entity->setContainer($this->container);
            $entity->setSection($section);
        }

        $this->getCore()->addNavigationElement($entity);

        if ($entity->isStatic()) {
            $formType = new TextStaticType();
        } else {
            $formType = new TextMainType();
        }

        $form = $this->createForm($formType, $entity);

        if ('POST' == $request->getMethod()) {

            $form->bindRequest($request);

            if ($form->isValid()) {

                $em = $this->getDoctrine()->getEntityManager();
                $em->persist($entity);
                $em->flush();

                $this->invalidateRoutingCache();

                if ($request->request->has('save')) {
                    return $this->redirect($this->generateUrl('egzakt_system_backend_text', array(
                        'section_id' => $section->getId()
                    )));
                }

                return $this->redirect($this->generateUrl('egzakt_system_backend_text_edit', array(
                    'id' => $entity->getId() ?: 0,
                    'section_id' => $section->getId()
                )));
            }
        }

        return $this->render('EgzaktSystemBundle:Backend/Text/Text:edit.html.twig', array(
            'entity' => $entity,
            'edit_form' => $form->createView(),
            'truncateLength' => 100
        ));
    }

    /**
     * Deletes a Text entity.
     *
     * @param integer $id The Id of the text to delete
     *
     * @throws \Symfony\Bundle\FrameworkBundle\Controller\NotFoundHttpException
     *
     * @return RedirectResponse
     */
    public function deleteAction($id)
    {
        $entity = $this->getEm()->getRepository($this->getBundleName() . ':Text')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Text entity.');
        }

        if ($this->get('request')->get('message')) {
            $template = $this->renderView('EgzaktBackendCoreBundle:Core:delete_message.html.twig', array(
                'entity' => $entity,
                'truncateLength' => $this->getSectionBundle()->getParam('breadcrumbs_truncate_length')
            ));

            return new Response(json_encode(array(
                'template' => $template,
                'isDeletable' => $entity->isDeletable()
            )));
        }

        $this->getEm()->remove($entity);
        $this->getEm()->flush();

        $this->invalidateRoutingCache();

        return $this->redirect($this->generateUrl($this->getBundleName(), array('section_id' => $this->getSection()->getId())));
    }


    /**
     * Set order on a BloxTexte entity.
     *
     * @return Response
     */
    public function orderAction()
    {
        if ($this->getRequest()->isXmlHttpRequest()) {

            $i = 0;

            $repo = $this->getEm()->getRepository($this->getBundleName() . ':Text');

            $elements = explode(';', trim($this->getRequest()->request->get('elements'), ';'));

            foreach ($elements as $element) {

                $element = explode('_', $element);
                $entity = $repo->find($element[1]);

                if ($entity) {
                    $entity->setOrdering(++$i);
                    $this->getEm()->persist($entity);
                    $this->getEm()->flush();
                }
            }

            $this->invalidateRoutingCache();
        }

        return new Response('');
    }

    /**
     * Invalidate Routing Cache
     */
    private function invalidateRoutingCache()
    {
        $finder = new Finder();
        $cacheDir = $this->container->getParameter('kernel.cache_dir');

        foreach ($finder->files()->name('/(.*)Url(Matcher|Generator)(.*)/')->in($cacheDir) as $file) {
            unlink($file);
        }
    }

}