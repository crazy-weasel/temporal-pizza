<?php

namespace App\DataFixtures;

use App\Entity\Pizza;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $pizzas = [
            [ 'Pizza Basil and Olives', 'likemeat-CbNAuxSZTFo-unsplash.jpg', "Tasty Pizza!\n\nPhoto by <a href=\"https://unsplash.com/de/@likemeat?utm_source=unsplash&utm_medium=referral&utm_content=creditCopyText\">LikeMeat</a> on <a href=\"https://unsplash.com/de/fotos/pizza-mit-grunen-blattern-auf-braunem-holztisch-CbNAuxSZTFo?utm_source=unsplash&utm_medium=referral&utm_content=creditCopyText\">Unsplash</a>" ],
            [ 'Yet Another Pizza', 'drake-whitney-pWqMo3bhv3A-unsplash.jpg', "Eat it, it is Pizza!\n\nPhoto by <a href=\"https://unsplash.com/de/@thesupremechips?utm_source=unsplash&utm_medium=referral&utm_content=creditCopyText\">Drake Whitney</a> on <a href=\"https://unsplash.com/de/fotos/eine-pizza-die-auf-einem-blatt-papier-sitzt-pWqMo3bhv3A?utm_source=unsplash&utm_medium=referral&utm_content=creditCopyText\">Unsplash</a>" ],
            [ 'Pizza? Please!', 'narek-petrosyan--0nj85eRLPk-unsplash.jpg', "I scream, and you scream,.. but it's pizza. Yum!\n\nPhoto by <a href=\"https://unsplash.com/de/@np1991?utm_source=unsplash&utm_medium=referral&utm_content=creditCopyText\">Narek Petrosyan</a> on <a href=\"https://unsplash.com/de/fotos/ein-teller-mit-essen--0nj85eRLPk?utm_source=unsplash&utm_medium=referral&utm_content=creditCopyText\">Unsplash</a>" ],
        ];

        foreach ($pizzas as $pizza) {
            $p = new Pizza();
            $p
                ->setName($pizza[0])
                ->setImageUrl($pizza[1])
                ->setDescription($pizza[2])
            ;
            $manager->persist($p);
        }

        $manager->flush();
    }
}
