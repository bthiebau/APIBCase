<?php

namespace App\DataFixtures;

use App\Entity\Room;
use App\Entity\Space;
use App\Entity\User;
use App\Enum\BedEnum;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(
        private UserPasswordHasherInterface $hasher
    ) {}
    private const GENDER = ['Homme', 'Femme'];
    private const TAGS = ['Student', 'Freelance', 'Digital Nomad', 'Remote Worker'];
    private const NB_USER = 5;
    private const NB_SPACE = 20;
    private const NB_ROOM = 40;
    public function load(ObjectManager $manager): void
    {
    /*---------- Users ----------*/
        $faker = Factory::create('fr_FR');
        $users = [];
        for($i = 0; $i < self::NB_USER; $i++) {
            $user = new User();
            $user
                ->setEmail("user$i@coliving.com")
                ->setPassword($this->hasher->hashPassword($user, "Password123"))
                ->setGender($faker->randomElement(self::GENDER))
                ->setLastname($faker->lastName)
                ->setFirstname($faker->firstName)
                ->setBirthdate($faker->dateTimeThisCentury)
                ->setTag($faker->randomElement(self::TAGS))
                ->setProfilpic($faker->imageUrl)
                ->setAdress($faker->address)
                ->setPostalcode($faker->postcode)
                ->setCity($faker->city)
                ->setCountry($faker->country());

            $users[] = $user;
            $manager->persist($user);
        }

        $admin = new User();
        $admin
            ->setEmail("admin@immo.fr")
            ->setPassword($this->hasher->hashPassword($admin, "admin1234"))
            ->setRoles(["ROLE_ADMIN"])
            ->setGender("none")
            ->setFirstname("none")
            ->setLastname("admin")
            ->setBirthdate($faker->dateTimeThisCentury)
            ->setTag("ADMIN")
            ->setProfilpic($faker->imageUrl)
            ->setAdress($faker->address)
            ->setPostalcode($faker->postcode)
            ->setCity($faker->city)
            ->setCountry($faker->country());

        $manager->persist($admin);

    /*---------- Space ----------*/
        $faker = Factory::create('fr_FR');
        $spaces = [];
        for($i = 0; $i < self::NB_SPACE; $i++){
            $space = new Space();
            $space
                ->setTitle($faker->words(6, true))
                ->setDescription($faker->realText())
                ->setAverageRating($faker->randomFloat(1, 0, 5))
                ->setImage($faker->imageUrl)
                ->setAddress($faker-> address())
                ->setPostalcode($faker->postcode)
                ->setCity($faker->city)
                ->setCountry($faker->country())
                ->setCapacity($faker->numberBetween(2, 10))
                ->setOwner($faker->randomElement($users));
                //->setAmenity

                $spaces[] = $space;
                $manager->persist($space);
        }


    /*---------- Room ----------*/
        $faker = Factory::create('fr_FR');
        $rooms = [];
        for($i = 0; $i < self::NB_ROOM; $i++){
            $room = new Room();
            $room
                ->setTitle($faker->words(6, true))
                ->setDescription($faker->realText())
                ->setImage($faker->imageUrl)
                ->setPrice($faker->numberBetween(800, 1400))
                ->setSpace($faker->randomElement($spaces))
                ->setBed($faker->randomElement([BedEnum::OneSimpleBed, BedEnum::OneDoubleBed, BedEnum::TwoSimpleBed, BedEnum::TwoDoubleBed]));
            $rooms[] = $room;
            $manager->persist($room);
        }
        
        $manager->flush();
    }
}
