<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Domain\SkillSetDomain;
use App\Entity\AssetType;
use App\Entity\AvailabilityInterface;
use App\Entity\CommissionableAsset;
use App\Entity\CommissionableAssetAvailability;
use App\Entity\MissionType;
use App\Entity\Organization;
use App\Entity\User;
use App\Entity\UserAvailability;
use App\Exception\ConstraintViolationListException;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class ApplicationFixtures extends Fixture
{
    // 168 = 12 (slot per day) * 7 (days in a week) * 2 (number of week available)
    private const SLOT_NUMBER = 168;

    // A slot is 2h long
    private const SLOT_INTERVAL = 'PT2H';

    private const PERCENT_USER_LOCKED = 0.10;
    private const PERCENT_USER_AVAILABLE = 0.30;
    private const PERCENT_USER_PARTIALLY_AVAILABLE = 0.40;

    private const PERCENT_ASSET_LOCKED = 0.10;
    private const PERCENT_ASSET_AVAILABLE = 0.30;
    private const PERCENT_ASSET_PARTIALLY_AVAILABLE = 0.30;

    private const ORGANIZATIONS = [
        'DT75' => [
            'UL 01-02',
            'UL 03-10',
            'UL 04',
            'UL 05',
            'UL 06',
            'UL 07',
            'UL 08',
            'UL 09',
            'UL 11',
            'UL 12',
            'UL 13',
            'UL 14',
            'UL 15',
            'UL 16',
            'UL 17',
            'UL 18',
            'UL 19',
            'UL 20',
        ],
        'DT77' => [
            'UL DE BRIE ET CHANTEREINE',
            'UL DE BRIE SENART',
            'UL DE CENTRE BRIE',
            'UL DE CHATEAU LANDON',
            'UL DE COULOMMIERS',
            'UL DE DONNEMARIE-DONTILLY',
            'UL DE FONTAINEBLEAU',
            'UL DE L\'EST FRANCILIEN',
            'UL DE LA MARNE ET LES DEUX MORINS',
            'UL DE LAGNY SUR MARNE',
            'UL DE LIZY SUR OURCQ',
            'UL DE MEAUX',
            'UL DE MELUN',
            'UL DE MITRY-MORY - VILLEPARISIS',
            'UL DE MONTEREAU',
            'UL DE MORET LOING ET ORVANNE',
            'UL DE NANGIS',
            'UL DE PROVINS',
            'UL DES PORTES DE ROISSY CDG',
        ],
    ];

    private ValidatorInterface $validator;

    private EncoderFactoryInterface $encoders;

    /** @var Organization[] */
    private array $organizations = [];

    /** @var User[] */
    private array $users = [];

    /** @var CommissionableAsset[] */
    private array $assets = [];

    private array $assetTypes = [];

    private SkillSetDomain $skillSetDomain;
    private int $nbUsers;
    private int $nbAvailabilities;

    private int $availabilitiesId = 1;

    private SlotBookingGuesser $slotBookingGuesser;
    private SlotAvailabilityGuesser $slotAvailabilityGuesser;

    public function __construct(
        EncoderFactoryInterface $encoders,
        ValidatorInterface $validator,
        SkillSetDomain $skillSetDomain,
        SlotBookingGuesser $slotBookingGuesser,
        SlotAvailabilityGuesser $slotAvailabilityGuesser,
        int $nbUsers = null,
        int $nbAvailabilities = null
    ) {
        $this->encoders = $encoders;
        $this->validator = $validator;
        $this->skillSetDomain = $skillSetDomain;
        $this->nbUsers = $nbUsers ?: random_int(10, 20);
        $this->nbAvailabilities = $nbAvailabilities ?: random_int(2, 6);
        $this->slotBookingGuesser = $slotBookingGuesser;
        $this->slotAvailabilityGuesser = $slotAvailabilityGuesser;
    }

    /**
     * @param ObjectManager|EntityManagerInterface $manager
     */
    public function load(ObjectManager $manager): void
    {
        $this->loadOrganizations($manager);
        $this->loadMissionTypes($manager);
        $this->loadAssetTypes($manager);
        $this->loadCommissionableAssets($manager);
        $this->loadResourcesAvailabilities($manager, $this->assets, CommissionableAssetAvailability::class);
        $this->loadUsers($manager);
        $this->loadResourcesAvailabilities($manager, $this->users, UserAvailability::class);

        $manager->flush();
    }

    public function loadAssetTypes(ObjectManager $manager): void
    {
        foreach ($this->organizations as $organization) {
            if (null !== $organization->parent) {
                continue;
            }

            $vl = new AssetType();
            $vl->organization = $organization;
            $vl->name = 'VL';
            $vl->properties = [
                ['key' => 'key1', 'type' => AssetType::TYPE_BOOLEAN, 'label' => 'Présence d\'un mobile radio ?', 'help' => '', 'required' => true, 'hidden' => false],
                ['key' => 'key2', 'type' => AssetType::TYPE_BOOLEAN, 'label' => 'Présence d\'un lot de secours ?', 'help' => '', 'required' => true, 'hidden' => false],
                ['key' => 'key3', 'type' => AssetType::TYPE_SMALL_TEXT, 'label' => 'Lieu de stationnement', 'help' => '', 'required' => false, 'hidden' => false],
                ['key' => 'key4', 'type' => AssetType::TYPE_SMALL_TEXT, 'label' => 'Qui contacter ?', 'help' => '', 'required' => false, 'hidden' => false],
                ['key' => 'key5', 'type' => AssetType::TYPE_NUMBER, 'label' => 'Combien de places ?', 'help' => '', 'required' => false, 'hidden' => false],
                ['key' => 'key6', 'type' => AssetType::TYPE_SMALL_TEXT, 'label' => 'Plaque d\'immatriculation', 'help' => '', 'required' => false, 'hidden' => false],
                ['key' => 'key7', 'type' => AssetType::TYPE_TEXT, 'label' => 'Commentaires', 'help' => '', 'required' => false, 'hidden' => false],
            ];
            $manager->persist($vl);

            $vpsp = new AssetType();
            $vpsp->organization = $organization;
            $vpsp->name = 'VPSP';
            $vpsp->properties = [
                ['key' => 'key1', 'type' => AssetType::TYPE_SMALL_TEXT, 'label' => 'Lieu de stationnement', 'help' => '', 'required' => false, 'hidden' => false],
                ['key' => 'key2', 'type' => AssetType::TYPE_SMALL_TEXT, 'label' => 'Qui contacter ?', 'help' => '', 'required' => false, 'hidden' => false],
                ['key' => 'key3', 'type' => AssetType::TYPE_NUMBER, 'label' => 'Combien de places ?', 'help' => '', 'required' => false, 'hidden' => false],
                ['key' => 'key4', 'type' => AssetType::TYPE_SMALL_TEXT, 'label' => 'Plaque d\'immatriculation', 'help' => '', 'required' => false, 'hidden' => false],
                ['key' => 'key5', 'type' => AssetType::TYPE_TEXT, 'label' => 'Commentaires', 'help' => '', 'required' => false, 'hidden' => false],
            ];
            $manager->persist($vpsp);

            $drone = new AssetType();
            $drone->organization = $organization;
            $drone->name = 'DRONE';
            $drone->properties = [
                ['key' => 'key1', 'type' => AssetType::TYPE_SMALL_TEXT, 'label' => 'Lieu de stationnement', 'help' => '', 'required' => false, 'hidden' => false],
                ['key' => 'key2', 'type' => AssetType::TYPE_SMALL_TEXT, 'label' => 'Qui contacter ?', 'help' => '', 'required' => false, 'hidden' => false],
                ['key' => 'key3', 'type' => AssetType::TYPE_TEXT, 'label' => 'Commentaires', 'help' => '', 'required' => false, 'hidden' => false],
            ];
            $manager->persist($drone);

            $this->assetTypes[$organization->id] = [
                'VL' => $vl,
                'VPSP' => $vpsp,
                'DRONE' => $drone,
            ];

            $manager->flush();
        }
    }

    private function loadMissionTypes(ObjectManager $manager): void
    {
        foreach ($this->organizations as $organization) {
            if (!$organization->isParent()) {
                continue;
            }

            $alphaType = new MissionType();
            $alphaType->name = 'Alpha';
            $alphaType->organization = $organization;
            $alphaType->userSkillsRequirement = [
                ['skill' => 'ci_bspp', 'number' => 1],
                ['skill' => 'ch_vpsp', 'number' => 1],
                ['skill' => 'pse2', 'number' => 1],
            ];

            $alphaType->assetTypesRequirement = [
                ['type' => 'VPSP', 'number' => 1],
            ];

            $this->validateAndPersist($manager, $alphaType);

            $alphaType = new MissionType();
            $alphaType->name = 'Maraude';
            $alphaType->organization = $organization;
            $alphaType->userSkillsRequirement = [
                ['skill' => 'ce_maraude', 'number' => 1],
                ['skill' => 'ch_vl', 'number' => 1],
                ['skill' => 'maraudeur', 'number' => 2],
            ];

            $alphaType->assetTypesRequirement = [
                ['type' => 'VL', 'number' => 1],
            ];

            $this->validateAndPersist($manager, $alphaType);
        }

        $manager->flush();
    }

    private function loadOrganizations(ObjectManager $manager): void
    {
        // Yield same password for all organizations.
        // Password generation can be expensive and time consuming.
        $encoder = $this->encoders->getEncoder(Organization::class);
        $password = $encoder->encodePassword('covid19', null);

        foreach (self::ORGANIZATIONS as $parentName => $organizations) {
            $this->addOrganization($this->makeOrganization($parentName, $password));

            foreach ($organizations as $name) {
                $this->addOrganization($this->makeOrganization($name, $password, $this->organizations[$parentName]));
            }
        }

        // Persist all organizations
        foreach ($this->organizations as $organization) {
            $this->validateAndPersist($manager, $organization);
        }

        $manager->flush();
    }

    private function loadCommissionableAssets(ObjectManager $manager): void
    {
        $combinations = [
            ['VPSP', '2'],
            ['VPSP', '4'],
            ['VL', '6'],
            ['VL', '8'],
        ];

        $incUlId = 10;
        foreach ($this->organizations as $organization) {
            $ulId = '99';
            if (!$organization->isParent()) {
                if ('DT75' === $organization->getParentName()) {
                    $ulId = substr(str_replace('UL ', '', $organization->name), 0, 2);
                } else {
                    $ulId = $incUlId++;
                }
            }

            $nameToSearch = $organization->isParent() ? $organization->name : $organization->getParentName();
            $prefix = str_replace('DT', '', $nameToSearch ?? '');
            foreach ($combinations as [$type, $suffix]) {
                if (random_int(0, 10) >= 5) {
                    continue;
                }

                $asset = new CommissionableAsset();
                $asset->organization = $organization;
                $asset->type = $type;
                $asset->assetType = $organization->isParent() ? $this->assetTypes[$organization->id][$type] : $this->assetTypes[$organization->parent->id][$type];
                $asset->name = $prefix.$ulId.$suffix;
                $this->validateAndPersist($manager, $asset);
                $this->assets[] = $asset;
            }
        }

        $manager->flush();
    }

    private function loadUsers(ObjectManager $manager): void
    {
        $startIdNumber = 990000;
        $firstNames = ['Audrey', 'Arnaud', 'Bastien', 'Beatrice', 'Benoit', 'Camille', 'Claire', 'Hugo', 'Fabien', 'Florian', 'Francis', 'Lilia', 'Lisa', 'Marie', 'Marine', 'Mathias', 'Mathieu', 'Michel', 'Nassim', 'Nathalie', 'Olivier', 'Pierre', 'Philippe', 'Sybille', 'Thomas', 'Tristan'];
        $lastNames = ['Bryant', 'Butler', 'Curry', 'Davis', 'Doncic', 'Durant', 'Embiid', 'Fournier', 'Grant', 'Gobert', 'Harden', 'Irving', 'James', 'Johnson', 'Jordan', 'Lilliard', 'Morant', 'Noah', 'Oneal', 'Parker', 'Pippen', 'Skywalker', 'Thompson', 'Westbrook'];
        $occupations = ['Pharmacien', 'Pompier', 'Ambulancier.e', 'Logisticien', 'Infirmier.e'];

        $x = 1;
        $availableSkillSet = $this->skillSetDomain->getSkillSet();
        foreach ($this->organizations as $organization) {
            for ($i = 0; $i < $this->nbUsers; ++$i) {
                $user = new User();
                $user->id = $i + 1;
                $user->firstName = $firstNames[array_rand($firstNames)];
                $user->lastName = $lastNames[array_rand($lastNames)];
                $user->organization = $organization;

                // e.g. 990001A
                $user->setIdentificationNumber(str_pad(''.++$startIdNumber.'', 10, '0', \STR_PAD_LEFT).'A');
                $user->setEmailAddress('user'.$x.'@resop.com');
                $user->phoneNumber = '0102030405';
                $user->birthday = '1990-01-01';
                $user->occupation = $occupations[array_rand($occupations)];
                $user->organizationOccupation = 'Secouriste';
                $user->skillSet = (array) array_rand($availableSkillSet, random_int(1, 3));
                $user->vulnerable = (bool) random_int(0, 1);
                $user->fullyEquipped = (bool) random_int(0, 1);
                $user->drivingLicence = (bool) random_int(0, 1);

                $this->users[$user->getIdentificationNumber()] = $user;

                $this->validateAndPersist($manager, $user);
                ++$x;
            }
        }

        $manager->flush();
    }

    private function loadResourcesAvailabilities(ObjectManager $manager, array $resources, string $class): void
    {
        /** @var EntityManagerInterface $manager */
        $today = (new \DateTimeImmutable('today'));
        $this->availabilitiesId = 1;

        // Mixing user
        $resourcesRandom = $resources;
        shuffle($resourcesRandom);

        $resourceCount = \count($resourcesRandom);

        $index = 0;

        $percentLocked = UserAvailability::class === $class ? self::PERCENT_USER_LOCKED : self::PERCENT_ASSET_LOCKED;
        $resourceLocked = \array_slice($resourcesRandom, $index, (int) ($resourceCount * $percentLocked));
        $index += \count($resourceLocked);

        $percentAvailable = UserAvailability::class === $class ? self::PERCENT_USER_AVAILABLE : self::PERCENT_ASSET_AVAILABLE;
        $resourceAvailable = \array_slice($resourcesRandom, $index, (int) ($resourceCount * $percentAvailable));
        $index += \count($resourceAvailable);

        $percentPartiallyAvailable = UserAvailability::class === $class ? self::PERCENT_USER_PARTIALLY_AVAILABLE : self::PERCENT_ASSET_PARTIALLY_AVAILABLE;
        $resourcePartiallyAvailable = \array_slice($resourcesRandom, $index, (int) ($resourceCount * $percentPartiallyAvailable));

        // Creating slots locked
        $data = $this->createAvailabilities($resourceLocked, $today, AvailabilityInterface::STATUS_LOCKED);

        // Creating slots available
        $data = array_merge($data, $this->createAvailabilities($resourceAvailable, $today, AvailabilityInterface::STATUS_AVAILABLE));

        // Creating slots partially available
        $data = array_merge($data, $this->createAvailabilities($resourcePartiallyAvailable, $today, AvailabilityInterface::STATUS_AVAILABLE, true));

        $insert = sprintf(
            'INSERT INTO %s (id, %s, start_time, end_time, status, created_at, updated_at, planning_agent_id) VALUES %s',
            $manager->getClassMetadata($class)->getTableName(),
            UserAvailability::class === $class ? 'user_id' : 'asset_id',
            implode(', ', $data)
        );

        $manager->getConnection()->exec(sprintf($insert));
        if (CommissionableAssetAvailability::class === $class) {
            $sequence = 'commissionable_asset_availability_id_seq';
        } else {
            $sequence = 'user_availability_id_seq';
        }

        $manager->getConnection()->exec(sprintf('SELECT setval(\''.$sequence.'\', %d, true)', $this->availabilitiesId));
    }

    private function createAvailabilities(array $objects, \DateTimeInterface $thisWeek, string $globalStatus, bool $partiallyAvailable = false): array
    {
        $data = [];

        // Creating slots for locked user or asset
        foreach ($objects as $object) {
            $slot = $thisWeek;
            for ($i = 0; $i < self::SLOT_NUMBER; ++$i) {
                $status = $globalStatus;
                $organizationId = null;
                if (AvailabilityInterface::STATUS_LOCKED === $globalStatus) {
                    $status = AvailabilityInterface::STATUS_LOCKED;
                } elseif (AvailabilityInterface::STATUS_AVAILABLE === $globalStatus) {
                    if ($partiallyAvailable) {
                        // If partially available is active whe check if guesser will return an available slot otherwise we skip.
                        if ($this->slotAvailabilityGuesser->guessAvailableSlot($slot)) {
                            $status = $this->slotBookingGuesser->guessBookedSlot($slot) ? AvailabilityInterface::STATUS_BOOKED : AvailabilityInterface::STATUS_AVAILABLE;
                        } else {
                            $status = AvailabilityInterface::STATUS_UNKNOW;
                        }
                        // If not we the slot is available and we just check if it is booked
                    } else {
                        $status = $this->slotBookingGuesser->guessBookedSlot($slot) ? AvailabilityInterface::STATUS_BOOKED : AvailabilityInterface::STATUS_AVAILABLE;
                    }
                }

                // Avoid inserting unavailable data
                if (AvailabilityInterface::STATUS_UNKNOW !== $status) {
                    // Linking an random organization when slot is booked
                    if (AvailabilityInterface::STATUS_BOOKED === $status) {
                        $organizationId = $this->getRandomOrganization();
                    }

                    $data[] = '('.$this->availabilitiesId++.','.
                        $object->getId().','.
                        "'".$slot->format('Y-m-d H:i:s')."',".
                        "'".$this->closeInterval($slot)->format('Y-m-d H:i:s')."',".
                        "'".$status."',".
                        "'".date('Y-m-d H:i:s')."',".
                        "'".date('Y-m-d H:i:s')."',".
                        ($organizationId ?: 'NULL').')';
                }

                $slot = $slot->add(new \DateInterval(self::SLOT_INTERVAL));
            }

            $this->slotBookingGuesser->resetGuesser();
            $this->slotAvailabilityGuesser->resetGuesser();
        }

        return $data;
    }

    private function getRandomOrganization(): int
    {
        return $this->organizations[array_rand($this->organizations)]->getId();
    }

    private function closeInterval(\DateTimeImmutable $dateTime): \DateTimeInterface
    {
        return $dateTime->add(new \DateInterval('PT2H'));
    }

    private function makeOrganization(string $name, string $password = null, Organization $parent = null): Organization
    {
        $organization = new Organization();
        $organization->name = $name;
        $organization->parent = $parent;

        if ($password) {
            $organization->password = $password;
        }

        return $organization;
    }

    private function addOrganization(Organization $organization): void
    {
        $this->organizations[$organization->name] = $organization;
    }

    private function validateAndPersist(ObjectManager $manager, object $object): void
    {
        $violations = $this->validator->validate($object);

        if (\count($violations)) {
            throw new ConstraintViolationListException($violations);
        }

        $manager->persist($object);
    }
}
