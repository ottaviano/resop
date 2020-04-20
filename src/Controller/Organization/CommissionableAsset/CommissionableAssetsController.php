<?php

declare(strict_types=1);

namespace App\Controller\Organization\CommissionableAsset;

use App\Entity\CommissionableAsset;
use App\Entity\Organization;
use App\Form\Type\CommissionableAssetType;
use App\Form\Type\PreAddAssetType;
use App\Repository\AssetTypeRepository;
use App\Repository\CommissionableAssetRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Exception\InvalidParameterException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class CommissionableAssetsController extends AbstractController
{
    private AssetTypeRepository $assetTypeRepository;
    private CommissionableAssetRepository $assetRepository;

    public function __construct(CommissionableAssetRepository $assetRepository, AssetTypeRepository $assetTypeRepository)
    {
        $this->assetRepository = $assetRepository;
        $this->assetTypeRepository = $assetTypeRepository;
    }

    /**
     * @Route("/", name="app_organization_commissionable_assets", methods={"GET"})
     */
    public function assets(): Response
    {
        $organization = $this->getUser();
        if (!$organization instanceof Organization) {
            throw new AccessDeniedException();
        }

        return $this->render('organization/commissionable_asset/list.html.twig', [
            'assets' => $this->assetRepository->findByOrganization($organization),
        ]);
    }

    /**
     * @Route("preAdd",  name="app_organization_commissionable_pre_add_asset" , methods={"GET", "POST"})
     */
    public function preAddAsset(): Response
    {
        return $this->render('organization/commissionable_asset/preAdd.html.twig', [
            'form' => $this->createForm(PreAddAssetType::class)->createView(),
        ]);
    }

    /**
     * @Route("add", name="app_organization_commissionable_add_asset", methods={"GET", "POST"})
     */
    public function addAsset(Request $request): Response
    {
        /** @var Organization $organization */
        $organization = $this->getUser();
        /** @var Organization $parentOrganization */
        $parentOrganization = $organization->isParent() ? $organization : $organization->parent;

        $assetType = null;
        if ($request->query->has('type')) {
            $assetType = $this->assetTypeRepository->findByOrganizationAndId($parentOrganization, (int) $request->query->get('type'));
        }

        if (null === $assetType) {
            throw new InvalidParameterException('Invalid type');
        }

        $asset = new CommissionableAsset();
        $asset->organization = $organization;
        $asset->type = 'VL';
        $asset->assetType = $assetType;

        $form = $this->createForm(CommissionableAssetType::class, $asset);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            dump('YEAH');
            dump($asset);
//            $entityManager = $this->getDoctrine()->getManager();
//            $entityManager->persist($asset);
//            $entityManager->flush();
//
//            $this->addFlash('success', 'Véhicule créé');
//
//            return $this->redirectToRoute('app_organization_commissionable_assets');
        }

        return $this->render('organization/commissionable_asset/form.html.twig', [
            'organization' => $organization,
            'form' => $form->createView(),
            'asset' => $asset,
        ]);
    }

    /**
     * @Route("/{id}/edit", name="app_organization_commissionable_edit_asset", methods={"GET", "POST"}, requirements={"id": "\d+"})
     */
    public function editAsset(Request $request, CommissionableAsset $asset): Response
    {
        $form = $this->createForm(CommissionableAssetType::class, $asset);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->flush();

            $this->addFlash('success', sprintf('Véhicule "%s" mis à jour avec succès', $asset));

            return $this->redirectToRoute('app_organization_commissionable_assets');
        }

        return $this->render('organization/commissionable_asset/form.html.twig', [
            'form' => $form->createView(),
            'asset' => $asset,
        ]);
    }
}
