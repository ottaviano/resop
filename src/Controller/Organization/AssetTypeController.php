<?php

declare(strict_types=1);

namespace App\Controller\Organization;

use App\Entity\AssetType;
use App\Entity\Organization;
use App\Form\Type\AssetTypeType;
use App\Repository\AssetTypeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("assetType")
 */
final class AssetTypeController extends AbstractController
{
    private AssetTypeRepository $assetTypeRepository;

    public function __construct(AssetTypeRepository $assetTypeRepository)
    {
        $this->assetTypeRepository = $assetTypeRepository;
    }

    /**
     * @Route("/", name="app_organization_assetType_list", methods={"GET"})
     */
    public function list(): Response
    {
        /** @var Organization $organization */
        $organization = $this->getUser();

        return $this->render('organization/assetType/list.html.twig', [
            'assetTypes' => $this->assetTypeRepository->findByOrganization($organization),
        ]);
    }

    /**
     * @Route("/new", name="app_organization_assetType_new", methods={"GET", "POST"})
     * @Route("/edit/{id}", name="app_organization_assetType_edit", methods={"GET", "POST"})
     */
    public function edit(Request $request, ?AssetType $assetType): Response
    {
        /** @var Organization $organization */
        $organization = $this->getUser();

        if (null === $assetType) {
            $assetType = new AssetType();
            $assetType->organization = $organization;
        }

        $form = $this->createForm(AssetTypeType::class, $assetType);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();

            $entityManager->persist($assetType);
            $entityManager->flush();

            return $this->redirectToRoute('app_organization_assetType_list');
        }

        return $this->render('organization/assetType/edit.html.twig', [
            //            'organization' => $organization,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/delete/{id}", name="app_organization_assetType_delete", methods={"GET"})
     */
    public function delete(): Response
    {
        return $this->render('organization/assetType/list.html.twig', [
            'organization' => $organization,
            'form' => $form->createView(),
        ]);
    }
}
