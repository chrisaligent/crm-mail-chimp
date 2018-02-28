<?php

namespace Oro\Bundle\MailChimpBundle\Controller\Api\Rest;

use FOS\RestBundle\Controller\Annotations as Rest;
use FOS\RestBundle\Routing\ClassResourceInterface;
use FOS\RestBundle\Util\Codes;
use Nelmio\ApiDocBundle\Annotation\ApiDoc;
use Oro\Bundle\MailChimpBundle\Entity\StaticSegment;
use Oro\Bundle\SecurityBundle\Annotation\AclAncestor;
use Oro\Bundle\SoapBundle\Controller\Api\Rest\RestController;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Rest\RouteResource("staticsegment")
 * @Rest\NamePrefix("oro_api_")
 */
class StaticSegmentController extends RestController implements ClassResourceInterface
{
    /**
     * REST DELETE
     *
     * @param int $id
     *
     * @ApiDoc(
     *      description="Delete MailChimp Static Segment List",
     *      resource=true
     * )
     * @AclAncestor("oro_mailchimp")
     *
     * @return Response
     */
    public function deleteAction($id)
    {
        return $this->handleDeleteRequest($id);
    }

    /**
     * @Rest\Post(
     *      "/staticsegment/{id}/status",
     *      requirements={"id"="\d+"}
     * )
     * @ParamConverter("staticSegment", options={"id"="id"})
     * @Rest\QueryParam(
     *      name="id",
     *      requirements="\d+",
     *      nullable=false,
     *      description="Static Segment Id"
     * )
     * @ApiDoc(
     *      description="Update Static Segment status",
     *      resource=false
     * )
     * @AclAncestor("oro_mailchimp")
     * @param Request $request
     * @param StaticSegment $staticSegment
     * @return Response
     */
    public function updateStatusAction(Request $request, StaticSegment $staticSegment)
    {
        $status = $request->get('status');
        $staticSegment->setSyncStatus($status);

        $em = $this->getDoctrine()->getManager();
        $em->persist($staticSegment);
        $em->flush();

        return $this->handleView($this->view('', Codes::HTTP_OK));
    }

    /**
     * {@inheritdoc}
     */
    public function getManager()
    {
        return $this->get('oro_mailchimp.static_segment.manager.api');
    }

    /**
     * {@inheritdoc}
     */
    public function getForm()
    {
        throw new \BadMethodCallException('Form is not available.');
    }

    /**
     * {@inheritdoc}
     */
    public function getFormHandler()
    {
        throw new \BadMethodCallException('FormHandler is not available.');
    }
}
